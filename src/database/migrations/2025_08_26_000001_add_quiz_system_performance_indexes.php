<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes for quiz-related tables for better performance
        
        // Quiz table indexes
        Schema::table('quizzes', function (Blueprint $table) {
            if (!$this->indexExists('quizzes', 'quizzes_slug_index')) {
                $table->index('slug');
            }
            if (!$this->indexExists('quizzes', 'quizzes_type_index')) {
                $table->index('type');
            }
            if (!$this->indexExists('quizzes', 'quizzes_published_at_index')) {
                $table->index('published_at');
            }
            if (!$this->indexExists('quizzes', 'quizzes_start_at_index')) {
                $table->index('start_at');
            }
            if (!$this->indexExists('quizzes', 'quizzes_ends_at_index')) {
                $table->index('ends_at');
            }
            if (!$this->indexExists('quizzes', 'quizzes_active_quizzes_index')) {
                $table->index(['published_at', 'start_at', 'ends_at'], 'quizzes_active_quizzes_index');
            }
        });

        // Quiz questions table indexes
        Schema::table('quiz_questions', function (Blueprint $table) {
            if (!$this->indexExists('quiz_questions', 'quiz_questions_quiz_id_question_number_index')) {
                $table->index(['quiz_id', 'question_number']);
            }
            if (!$this->indexExists('quiz_questions', 'quiz_questions_question_type_index')) {
                $table->index('question_type');
            }
        });

        // Quiz answers table indexes
        Schema::table('quiz_answers', function (Blueprint $table) {
            if (!$this->indexExists('quiz_answers', 'quiz_answers_quiz_question_id_is_correct_index')) {
                $table->index(['quiz_question_id', 'is_correct']);
            }
        });

        // Attempt quizzes table indexes
        Schema::table('attempt_quizzes', function (Blueprint $table) {
            if (!$this->indexExists('attempt_quizzes', 'attempt_quizzes_user_id_quiz_id_index')) {
                $table->index(['user_id', 'quiz_id']);
            }
            if (!$this->indexExists('attempt_quizzes', 'attempt_quizzes_quiz_id_status_index')) {
                $table->index(['quiz_id', 'status']);
            }
            if (!$this->indexExists('attempt_quizzes', 'attempt_quizzes_status_score_index')) {
                $table->index(['status', 'score']);
            }
            if (!$this->indexExists('attempt_quizzes', 'attempt_quizzes_completed_at_index')) {
                $table->index('completed_at');
            }
            if (!$this->indexExists('attempt_quizzes', 'attempt_quizzes_started_at_index')) {
                $table->index('started_at');
            }
        });

        // Attempt answers table indexes
        Schema::table('attempt_answers', function (Blueprint $table) {
            if (!$this->indexExists('attempt_answers', 'attempt_answers_attempt_id_is_correct_index')) {
                $table->index(['attempt_id', 'is_correct']);
            }
            if (!$this->indexExists('attempt_answers', 'attempt_answers_quiz_question_id_is_correct_index')) {
                $table->index(['quiz_question_id', 'is_correct']);
            }
            if (!$this->indexExists('attempt_answers', 'attempt_answers_selected_answer_id_index')) {
                $table->index('selected_answer_id');
            }
        });

        // Learning outcomes table indexes (if the table exists)
        if (Schema::hasTable('learning_outcomes')) {
            Schema::table('learning_outcomes', function (Blueprint $table) {
                if (!$this->indexExists('learning_outcomes', 'learning_outcomes_course_id_is_active_index')) {
                    $table->index(['course_id', 'is_active']);
                }
                if (!$this->indexExists('learning_outcomes', 'learning_outcomes_slug_index')) {
                    $table->index('slug');
                }
            });
        }

        // Learning outcome quiz question pivot table indexes (if the table exists)
        if (Schema::hasTable('learning_outcome_quiz_question')) {
            Schema::table('learning_outcome_quiz_question', function (Blueprint $table) {
                if (!$this->indexExists('learning_outcome_quiz_question', 'loqq_learning_outcome_id_quiz_question_id_index')) {
                    $table->index(['learning_outcome_id', 'quiz_question_id'], 'loqq_learning_outcome_id_quiz_question_id_index');
                }
                if (!$this->indexExists('learning_outcome_quiz_question', 'loqq_quiz_question_id_index')) {
                    $table->index('quiz_question_id');
                }
            });
        }

        // User activities table indexes (for quiz-related activities)
        if (Schema::hasTable('user_activities')) {
            Schema::table('user_activities', function (Blueprint $table) {
                if (!$this->indexExists('user_activities', 'user_activities_user_id_activity_type_index')) {
                    $table->index(['user_id', 'activity_type']);
                }
                if (!$this->indexExists('user_activities', 'user_activities_activity_type_activity_id_index')) {
                    $table->index(['activity_type', 'activity_id']);
                }
                if (!$this->indexExists('user_activities', 'user_activities_last_seen_at_index')) {
                    $table->index('last_seen_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes for quiz-related tables
        
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropIndex(['type']);
            $table->dropIndex(['published_at']);
            $table->dropIndex(['start_at']);
            $table->dropIndex(['ends_at']);
            $table->dropIndex('quizzes_active_quizzes_index');
        });

        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->dropIndex(['quiz_id', 'question_number']);
            $table->dropIndex(['question_type']);
        });

        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->dropIndex(['quiz_question_id', 'is_correct']);
        });

        Schema::table('attempt_quizzes', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'quiz_id']);
            $table->dropIndex(['quiz_id', 'status']);
            $table->dropIndex(['status', 'score']);
            $table->dropIndex(['completed_at']);
            $table->dropIndex(['started_at']);
        });

        Schema::table('attempt_answers', function (Blueprint $table) {
            $table->dropIndex(['attempt_id', 'is_correct']);
            $table->dropIndex(['quiz_question_id', 'is_correct']);
            $table->dropIndex(['selected_answer_id']);
        });

        if (Schema::hasTable('learning_outcomes')) {
            Schema::table('learning_outcomes', function (Blueprint $table) {
                $table->dropIndex(['course_id', 'is_active']);
                $table->dropIndex(['slug']);
            });
        }

        if (Schema::hasTable('learning_outcome_quiz_question')) {
            Schema::table('learning_outcome_quiz_question', function (Blueprint $table) {
                $table->dropIndex('loqq_learning_outcome_id_quiz_question_id_index');
                $table->dropIndex(['quiz_question_id']);
            });
        }

        if (Schema::hasTable('user_activities')) {
            Schema::table('user_activities', function (Blueprint $table) {
                $table->dropIndex(['user_id', 'activity_type']);
                $table->dropIndex(['activity_type', 'activity_id']);
                $table->dropIndex(['last_seen_at']);
            });
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists($table, $index)
    {
        $schema = DB::connection()->getSchemaBuilder();
        $indexExists = false;
        
        try {
            $indexes = $schema->getIndexes($table);
            foreach ($indexes as $indexInfo) {
                if ($indexInfo['name'] === $index) {
                    $indexExists = true;
                    break;
                }
            }
        } catch (\Exception $e) {
            // If we can't check, assume it doesn't exist
            $indexExists = false;
        }
        
        return $indexExists;
    }
};
