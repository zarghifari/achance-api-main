<?php

namespace App\Events;

use App\Models\AttemptQuiz;
use App\Models\User;
use App\Models\Quiz;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuizAttemptCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public AttemptQuiz $attempt;
    public User $user;
    public Quiz $quiz;

    /**
     * Create a new event instance.
     */
    public function __construct(AttemptQuiz $attempt)
    {
        $this->attempt = $attempt;
        $this->user = $attempt->user;
        $this->quiz = $attempt->quiz;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('quiz.' . $this->quiz->id),
            new PrivateChannel('user.' . $this->user->id),
        ];
    }

    /**
     * Get the data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'attempt_id' => $this->attempt->id,
            'quiz_id' => $this->quiz->id,
            'user_id' => $this->user->id,
            'score' => $this->attempt->score,
            'status' => $this->attempt->status,
            'completed_at' => $this->attempt->completed_at,
        ];
    }
}
