<?php

namespace App\Http\Controllers;

use App\Models\LearningGoal;
use App\Models\GoalMilestone;
use App\Models\GoalProgressLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LearningGoalController extends Controller
{
    /**
     * Create a new learning goal.
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'goal_type' => 'required|in:skill,certification,project,career,time_based,custom',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_date' => 'nullable|date|after:today',
            'target_metric' => 'nullable|string|max:100',
            'target_value' => 'nullable|numeric|min:0',
            'related_courses' => 'nullable|array',
            'related_courses.*' => 'integer|exists:courses,id',
            'related_skills' => 'nullable|array',
            'milestones' => 'nullable|array',
            'milestones.*.title' => 'required_with:milestones|string|max:255',
            'milestones.*.description' => 'nullable|string',
            'milestones.*.sequence_order' => 'required_with:milestones|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $goal = LearningGoal::create([
                'user_id' => auth()->id(),
                'goal_type' => $validated['goal_type'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'target_date' => $validated['target_date'] ?? null,
                'target_metric' => $validated['target_metric'] ?? null,
                'current_value' => 0,
                'target_value' => $validated['target_value'] ?? null,
                'related_courses' => $validated['related_courses'] ?? null,
                'related_skills' => $validated['related_skills'] ?? null,
            ]);

            // Create milestones if provided
            if (!empty($validated['milestones'])) {
                foreach ($validated['milestones'] as $milestone) {
                    GoalMilestone::create([
                        'learning_goal_id' => $goal->id,
                        'title' => $milestone['title'],
                        'description' => $milestone['description'] ?? null,
                        'sequence_order' => $milestone['sequence_order'],
                    ]);
                }
            }

            DB::commit();

            $goal->load('milestones');

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $goal->id,
                    'title' => $goal->title,
                    'goal_type' => $goal->goal_type,
                    'status' => $goal->status,
                    'progress' => [
                        'current_value' => $goal->current_value,
                        'target_value' => $goal->target_value,
                        'percentage' => $goal->progress_percentage,
                    ],
                    'milestones' => $goal->milestones->map(function ($m) {
                        return [
                            'id' => $m->id,
                            'title' => $m->title,
                            'sequence_order' => $m->sequence_order,
                            'is_achieved' => $m->is_achieved,
                        ];
                    }),
                    'days_remaining' => $goal->days_remaining,
                    'target_date' => $goal->target_date?->format('Y-m-d'),
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create learning goal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all learning goals for the authenticated user.
     */
    public function getMyGoals(Request $request)
    {
        $status = $request->query('status', 'active');

        $goalsQuery = LearningGoal::where('user_id', auth()->id())
            ->with(['milestones' => function ($query) {
                $query->orderBy('sequence_order');
            }]);

        if ($status !== 'all') {
            $goalsQuery->where('status', $status);
        }

        $goals = $goalsQuery->orderBy('created_at', 'desc')->get();

        $summary = [
            'active_goals' => LearningGoal::where('user_id', auth()->id())->where('status', 'active')->count(),
            'achieved_goals' => LearningGoal::where('user_id', auth()->id())->where('status', 'achieved')->count(),
            'total_milestones' => GoalMilestone::whereHas('learningGoal', function ($q) {
                $q->where('user_id', auth()->id());
            })->count(),
            'achieved_milestones' => GoalMilestone::whereHas('learningGoal', function ($q) {
                $q->where('user_id', auth()->id());
            })->where('is_achieved', true)->count(),
        ];

        $goalsData = $goals->map(function ($goal) {
            $nextMilestone = $goal->milestones->firstWhere('is_achieved', false);
            
            return [
                'id' => $goal->id,
                'title' => $goal->title,
                'goal_type' => $goal->goal_type,
                'description' => $goal->description,
                'progress_percentage' => $goal->progress_percentage,
                'current_value' => $goal->current_value,
                'target_value' => $goal->target_value,
                'status' => $goal->status,
                'days_remaining' => $goal->days_remaining,
                'target_date' => $goal->target_date?->format('Y-m-d'),
                'on_track' => $goal->on_track,
                'next_milestone' => $nextMilestone ? [
                    'id' => $nextMilestone->id,
                    'title' => $nextMilestone->title,
                ] : null,
                'milestones_count' => $goal->milestones->count(),
                'achieved_milestones_count' => $goal->milestones->where('is_achieved', true)->count(),
                'created_at' => $goal->created_at->format('Y-m-d H:i:s'),
            ];
        });

        // Find overdue goals
        $overdueGoals = $goals->filter(function ($goal) {
            return $goal->days_remaining !== null && $goal->days_remaining < 0 && $goal->status === 'active';
        })->values();

        // Find almost complete goals
        $almostComplete = $goals->filter(function ($goal) {
            return $goal->progress_percentage >= 75 && $goal->progress_percentage < 100 && $goal->status === 'active';
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'goals' => $goalsData,
                'overdue_goals' => $overdueGoals->map(function ($goal) {
                    return [
                        'id' => $goal->id,
                        'title' => $goal->title,
                        'days_overdue' => abs($goal->days_remaining),
                    ];
                }),
                'almost_complete' => $almostComplete->map(function ($goal) {
                    return [
                        'id' => $goal->id,
                        'title' => $goal->title,
                        'progress_percentage' => $goal->progress_percentage,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Get a specific learning goal.
     */
    public function getGoal($id)
    {
        $goal = LearningGoal::where('user_id', auth()->id())
            ->with(['milestones', 'progressLogs'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $goal->id,
                'title' => $goal->title,
                'description' => $goal->description,
                'goal_type' => $goal->goal_type,
                'target_metric' => $goal->target_metric,
                'progress' => [
                    'current_value' => $goal->current_value,
                    'target_value' => $goal->target_value,
                    'percentage' => $goal->progress_percentage,
                ],
                'status' => $goal->status,
                'target_date' => $goal->target_date?->format('Y-m-d'),
                'days_remaining' => $goal->days_remaining,
                'on_track' => $goal->on_track,
                'related_courses' => $goal->related_courses,
                'related_skills' => $goal->related_skills,
                'milestones' => $goal->milestones->map(function ($m) {
                    return [
                        'id' => $m->id,
                        'title' => $m->title,
                        'description' => $m->description,
                        'sequence_order' => $m->sequence_order,
                        'is_achieved' => $m->is_achieved,
                        'achieved_at' => $m->achieved_at?->format('Y-m-d H:i:s'),
                    ];
                }),
                'progress_history' => $goal->progressLogs->map(function ($log) {
                    return [
                        'progress_value' => $log->progress_value,
                        'note' => $log->note,
                        'logged_at' => $log->logged_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'created_at' => $goal->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Track progress for a goal.
     */
    public function trackProgress(Request $request, $id)
    {
        $validated = $request->validate([
            'progress_value' => 'required|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        $goal = LearningGoal::where('user_id', auth()->id())->findOrFail($id);

        DB::beginTransaction();
        try {
            // Update goal progress
            $oldValue = $goal->current_value;
            $goal->current_value = $validated['progress_value'];
            
            // Check if goal is achieved
            if ($goal->target_value && $goal->current_value >= $goal->target_value) {
                $goal->status = 'achieved';
                $goal->achieved_at = now();
            }
            
            $goal->save();

            // Log the progress
            GoalProgressLog::create([
                'learning_goal_id' => $goal->id,
                'progress_value' => $validated['progress_value'],
                'note' => $validated['note'] ?? null,
                'logged_at' => now(),
            ]);

            // Check if any milestones should be marked as achieved
            $milestoneAchieved = null;
            if ($goal->target_value) {
                $progressPercentage = ($goal->current_value / $goal->target_value) * 100;
                
                $unachievedMilestones = $goal->milestones()
                    ->where('is_achieved', false)
                    ->orderBy('sequence_order')
                    ->get();

                foreach ($unachievedMilestones as $milestone) {
                    $milestoneThreshold = ($milestone->sequence_order / $goal->milestones->count()) * 100;
                    
                    if ($progressPercentage >= $milestoneThreshold) {
                        $milestone->is_achieved = true;
                        $milestone->achieved_at = now();
                        $milestone->save();
                        
                        if (!$milestoneAchieved) {
                            $milestoneAchieved = $milestone;
                        }
                    }
                }
            }

            DB::commit();

            $response = [
                'success' => true,
                'data' => [
                    'goal_id' => $goal->id,
                    'old_value' => $oldValue,
                    'current_value' => $goal->current_value,
                    'target_value' => $goal->target_value,
                    'percentage' => $goal->progress_percentage,
                    'status' => $goal->status,
                ],
            ];

            if ($milestoneAchieved) {
                $response['data']['milestone_achieved'] = [
                    'id' => $milestoneAchieved->id,
                    'title' => $milestoneAchieved->title,
                    'congratulations' => 'Great job! You achieved a milestone! 🎉',
                ];
            }

            if ($goal->status === 'achieved') {
                $response['data']['goal_achieved'] = true;
                $response['data']['message'] = 'Congratulations! You achieved your goal! 🎊';
            }

            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to track progress',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a learning goal.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'target_date' => 'nullable|date',
            'target_value' => 'nullable|numeric|min:0',
            'status' => 'sometimes|in:active,achieved,paused,abandoned',
        ]);

        $goal = LearningGoal::where('user_id', auth()->id())->findOrFail($id);
        $goal->update($validated);

        return response()->json([
            'success' => true,
            'data' => $goal,
        ]);
    }

    /**
     * Delete a learning goal.
     */
    public function delete($id)
    {
        $goal = LearningGoal::where('user_id', auth()->id())->findOrFail($id);
        $goal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Learning goal deleted successfully',
        ]);
    }
}
