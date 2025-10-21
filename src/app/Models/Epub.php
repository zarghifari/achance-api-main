<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Epub extends Model
{
    protected $table = 'epubs';
    protected $primaryKey = 'id';
    protected $keytype = 'int';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'lesson_id',
        'title',
        'file_path'
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
