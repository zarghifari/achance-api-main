<?php

namespace App\Policies;

use App\Models\TaskUserAnswer;
use App\Models\User;

class TaskUserAnswerPolicy
{
    /**
     * Determine if the user can view the task user answer.
     */
    public function view(User $user, TaskUserAnswer $taskUserAnswer): bool
    {
        // User can view their own task answers or if they have permission to view all tasks
        return $user->can('view all tasks') || 
               ($user->can('doing own tasks') && $taskUserAnswer->user_id === $user->id);
    }

    /**
     * Determine if the user can create task user answers.
     */
    public function create(User $user): bool
    {
        return $user->can('doing own tasks');
    }

    /**
     * Determine if the user can update the task user answer.
     */
    public function update(User $user, TaskUserAnswer $taskUserAnswer): bool
    {
        // User can only update their own task answers
        return $user->can('doing own tasks') && $taskUserAnswer->user_id === $user->id;
    }

    /**
     * Determine if the user can delete the task user answer.
     */
    public function delete(User $user, TaskUserAnswer $taskUserAnswer): bool
    {
        // Teachers can delete any task answer, students can delete their own
        return $user->can('delete courses') || 
               ($user->can('doing own tasks') && $taskUserAnswer->user_id === $user->id);
    }
}
