<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module extends Model
{
    protected $table = 'modules';
    protected $primaryKey = 'id';
    protected $keytype = 'int';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'video_url',
        'cover_image',
        'position',
        'course_id',
    ];

    protected $casts = [
        'id' => 'integer',
        'course_id' => 'integer',
        'position' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
    
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position', 'asc');
    }
    
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function learningOutcomes(): BelongsToMany
    {
        return $this->belongsToMany(LearningOutcome::class, 'learning_outcome_module');
    }

    public function getActiveLearningOutcomes()
    {
        return $this->learningOutcomes()
            ->where('is_active', true)
            ->get();
    }

    // Scope for eager loading
    public function scopeWithRelations($query)
    {
        return $query->with(['course', 'lessons.epub', 'tasks']);
    }
}
