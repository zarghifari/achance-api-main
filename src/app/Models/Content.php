<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    use HasFactory;

    protected $table = 'contents';
    protected $primaryKey = 'id';
    protected $keytype = 'int';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'lesson_id',
        'title',
        'type',
        'description',
        'original_filename',
        'source_file_path',
        'source_file_size',
        'html_content',
        'json_content',
        'metadata',
        'images',
        'assets',
        'total_pages',
        'words_per_page',
        'position',
        'is_active',
        'is_processed',
        'processed_at'
    ];

    protected $casts = [
        'source_file_size' => 'integer',
        'json_content' => 'array',
        'metadata' => 'array',
        'images' => 'array',
        'assets' => 'array',
        'total_pages' => 'integer',
        'words_per_page' => 'integer',
        'position' => 'integer',
        'is_active' => 'boolean',
        'is_processed' => 'boolean',
        'processed_at' => 'datetime'
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get paginated content by page number
     */
    public function getPageContent(int $page = 1): ?array
    {
        if (!$this->is_processed || !$this->json_content) {
            return null;
        }

        $pages = $this->json_content['pages'] ?? [];
        
        if ($page < 1 || $page > count($pages)) {
            return null;
        }

        return [
            'page' => $page,
            'total_pages' => $this->total_pages,
            'content' => $pages[$page - 1] ?? null,
            'has_next' => $page < $this->total_pages,
            'has_previous' => $page > 1
        ];
    }

    /**
     * Get all images from content
     */
    public function getImages(): array
    {
        return $this->images ?? [];
    }

    /**
     * Get all assets from content
     */
    public function getAssets(): array
    {
        return $this->assets ?? [];
    }
}
