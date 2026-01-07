<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivity extends Model
{
    use HasFactory;
    
    protected $table = 'user_activities';
    protected $primaryKey = 'id';
    protected $keytype = 'int';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'activity_type',
        'activity_id',
        'last_seen_url',
        'last_seen_at',
        'metadata',
        'duration_seconds',
        'progress_percentage',
        'action',
        'started_at',
        'completed_at',
        'device_type',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
        'progress_percentage' => 'decimal:2',
        'last_seen_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Activity types
    const TYPE_LESSON = 'lesson';
    const TYPE_EPUB = 'epub';
    const TYPE_QUIZ = 'quiz';
    const TYPE_TASK = 'task';

    // Actions
    const ACTION_START = 'start';
    const ACTION_PROGRESS = 'progress';
    const ACTION_COMPLETE = 'complete';
    const ACTION_DOWNLOAD = 'download';
    const ACTION_VIEW = 'view';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'activity_id');
    }

    public function epub(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'activity_id');
    }
    
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'activity_id');
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class, 'activity_id');
    }

    // Scopes for analytics
    public function scopeEpubActivities($query)
    {
        return $query->where('activity_type', self::TYPE_EPUB);
    }

    public function scopeQuizActivities($query)
    {
        return $query->where('activity_type', self::TYPE_QUIZ);
    }

    public function scopeCompleted($query)
    {
        return $query->where('action', self::ACTION_COMPLETE);
    }

    public function scopeInProgress($query)
    {
        return $query->where('action', self::ACTION_PROGRESS);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
