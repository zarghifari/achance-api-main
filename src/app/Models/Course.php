<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Course extends Model
{
    protected $table = 'courses';
    protected $primaryKey = 'id';
    protected $keytype = 'int';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'cover_image',
        'video_url',
        'isOpen',
        'total_hours',
    ];

    protected $casts = [
        'isOpen' => 'boolean',
        'total_hours' => 'integer',
    ];
    
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }

    public function lessons()
    {
        return $this->hasManyThrough(Lesson::class, Module::class);
    }

    public function tasks()
    {
        return $this->hasManyThrough(Task::class, Module::class);
    }

    public function learningOutcomes(): HasMany
    {
        return $this->hasMany(LearningOutcome::class);
    }

    public function getModuleLearningOutcomes()
    {
        return $this->learningOutcomes()
            ->with('modules')
            ->where('is_active', true)
            ->get();
    }

    // Optimized scopes for better performance
    public function scopeWithFullData($query)
    {
        return $query->with([
            'modules' => function ($query) {
                $query->orderBy('position')->select(['id', 'course_id', 'title', 'position']);
            },
            'modules.lessons' => function ($query) {
                $query->orderBy('position')->select(['id', 'module_id', 'title', 'position']);
            },
            'modules.lessons.epub' => function ($query) {
                $query->select(['id', 'lesson_id', 'epub_path']);
            },
            'modules.tasks' => function ($query) {
                $query->select(['id', 'module_id', 'title', 'type']);
            }
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('isOpen', true);
    }

    public function scopeWithBasicInfo($query)
    {
        return $query->select(['id', 'title', 'slug', 'description', 'cover_image', 'isOpen', 'total_hours']);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function (Builder $query) use ($search) {
            $query->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // Helper method to get course with minimal data for lists
    public static function getListData()
    {
        return static::withBasicInfo()
            ->active()
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
