<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    protected $table = 'tasks';
    protected $primaryKey = 'id';
    protected $keytype = 'int';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'task_img',
        'attachment',
        'position',
        'module_id'
    ];

    protected $casts = [
        'id' => 'integer',
        'module_id' => 'integer',
        'position' => 'integer',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id', 'id');
    }

    public function taskUserAnswer()
    {
        return $this->hasMany(TaskUserAnswer::class, 'task_id', 'id');
    }
}
