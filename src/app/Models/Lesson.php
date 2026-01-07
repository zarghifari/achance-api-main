<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lesson extends Model
{
    protected $table = 'lessons';
    protected $primaryKey = 'id';
    protected $keytype = 'int';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'id',
        'module_id',
        'title',
        'slug',
        'cover_image',
        'video_url',
        'attachment',
        'position',
        'description',
        'is_active',
        'duration',
        'content_type',
    ];

    protected $casts = [
        'id' => 'integer',
        'module_id' => 'integer',
        'position' => 'integer',
        'is_active' => 'boolean',
        'duration' => 'integer',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function content(): HasOne
    {
        return $this->hasOne(Content::class);
    }

    public function course()
    {
        return $this->hasOneThrough(Course::class, Module::class, 'id', 'id', 'module_id', 'course_id');
    }

    // Scope for eager loading
    public function scopeWithFullData($query)
    {
        return $query->with(['module.course', 'content']);
    }


}
