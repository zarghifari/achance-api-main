<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use DOMDocument;
use DOMXPath;
use PhpOffice\PhpWord\IOFactory;

class DocumentConverterService
{
    private $wordsPerPage = 500;
    private $uploadPath = 'uploads/contents/';
    private $tempPath = 'temp/';

    /**
     * Convert .doc/.docx file to HTML and JSON
     */
    public function convertDocument(string $filePath, string $originalFilename): array
    {
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        
        try {
            if (in_array($extension, ['doc', 'docx'])) {
                return $this->convertWordDocument($filePath, $originalFilename);
            } elseif ($extension === 'html' || $extension === 'htm') {
                return $this->processHtmlDocument($filePath, $originalFilename);
            } else {
                throw new \Exception("Unsupported file format: {$extension}");
            }
        } catch (\Exception $e) {
            Log::error('Document conversion failed', [
                'file' => $originalFilename,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Convert Word document to HTML using PhpWord
     */
    private function convertWordDocument(string $filePath, string $originalFilename): array
    {
        // Check if PhpWord is available
        if (!class_exists('\PhpOffice\PhpWord\IOFactory')) {
            throw new \Exception('PhpWord library not installed. Run: composer require phpoffice/phpword');
        }

        $phpWord = IOFactory::load($filePath);
        $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
        
        // Create unique temp directory for this conversion
        $tempDir = storage_path('app/temp/' . uniqid());
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $tempHtmlPath = $tempDir . '/document.html';
        $htmlWriter->save($tempHtmlPath);
        $htmlContent = file_get_contents($tempHtmlPath);
        
        // Extract images from PhpWord's image folder
        $baseFilename = pathinfo($originalFilename, PATHINFO_FILENAME);
        $imageFolder = $tempDir . '/' . $baseFilename . '_files';
        
        // Move images to permanent storage and update HTML paths
        if (is_dir($imageFolder)) {
            $htmlContent = $this->movePhpWordImages($htmlContent, $imageFolder, $baseFilename);
        }
        
        // Clean up temp directory
        $this->deleteDirectory($tempDir);

        // Process the HTML content
        return $this->processHtmlContent($htmlContent, $originalFilename);
    }

    /**
     * Process HTML document
     */
    private function processHtmlDocument(string $filePath, string $originalFilename): array
    {
        $htmlContent = file_get_contents($filePath);
        return $this->processHtmlContent($htmlContent, $originalFilename);
    }

    /**
     * Process HTML content: extract images, paginate, optimize
     */
    private function processHtmlContent(string $htmlContent, string $originalFilename): array
    {
        // Clean and optimize HTML
        $htmlContent = $this->cleanHtml($htmlContent);
        
        // Extract images and assets
        $images = $this->extractImages($htmlContent);
        $assets = $this->extractAssets($htmlContent);
        
        // Process images (optimize, extract base64, etc.)
        $processedImages = $this->processImages($images);
        
        // Process assets (download and save locally)
        $processedAssets = $this->processAssets($assets);
        
        // Update HTML with processed image paths
        $htmlContent = $this->updateImagePaths($htmlContent, $processedImages);
        
        // Update HTML with processed asset paths
        $htmlContent = $this->updateAssetPaths($htmlContent, $processedAssets);
        
        // Paginate content
        $pages = $this->paginateHtml($htmlContent, $this->wordsPerPage);
        
        // Calculate metadata
        $wordCount = str_word_count(strip_tags($htmlContent));
        $videoEmbeds = array_filter($processedAssets, function($asset) {
            return in_array($asset['type'], ['youtube', 'vimeo', 'dailymotion']) && $asset['status'] === 'embed';
        });
        
        $metadata = [
            'word_count' => $wordCount,
            'character_count' => strlen(strip_tags($htmlContent)),
            'image_count' => count($processedImages),
            'asset_count' => count($processedAssets),
            'video_embed_count' => count($videoEmbeds),
            'processing_date' => now()->toDateTimeString(),
            'original_filename' => $originalFilename
        ];

        return [
            'html_content' => $htmlContent,
            'json_content' => [
                'pages' => $pages,
                'metadata' => $metadata
            ],
            'images' => array_values($processedImages),
            'assets' => array_values($processedAssets),
            'total_pages' => count($pages),
            'metadata' => $metadata
        ];
    }

    /**
     * Clean and optimize HTML
     */
    private function cleanHtml(string $html): string
    {
        // Remove unnecessary whitespace
        $html = preg_replace('/\s+/', ' ', $html);
        
        // Remove script tags for security
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        
        // Remove potentially dangerous attributes
        $html = preg_replace('/\s(on\w+)="[^"]*"/i', '', $html);
        
        // Ensure UTF-8 encoding
        $html = mb_convert_encoding($html, 'UTF-8', 'auto');
        
        return trim($html);
    }

    /**
     * Extract all images from HTML
     */
    private function extractImages(string $html): array
    {
        $images = [];
        preg_match_all('/<img[^>]+src="([^"]+)"/i', $html, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $index => $src) {
                $images[] = [
                    'original_src' => $src,
                    'index' => $index,
                    'type' => $this->detectImageType($src)
                ];
            }
        }
        
        return $images;
    }

    /**
     * Extract other assets (videos, audio, PDFs, documents, etc.)
     */
    private function extractAssets(string $html): array
    {
        $assets = [];
        
        // Extract video tags
        preg_match_all('/<video[^>]+src="([^"]+)"/i', $html, $videoMatches);
        if (!empty($videoMatches[1])) {
            foreach ($videoMatches[1] as $src) {
                $assets[] = ['type' => 'video', 'src' => $src, 'tag' => 'video'];
            }
        }
        
        // Extract source tags (video/audio sources)
        preg_match_all('/<source[^>]+src="([^"]+)"/i', $html, $sourceMatches);
        if (!empty($sourceMatches[1])) {
            foreach ($sourceMatches[1] as $src) {
                $assets[] = ['type' => 'media', 'src' => $src, 'tag' => 'source'];
            }
        }
        
        // Extract audio tags
        preg_match_all('/<audio[^>]+src="([^"]+)"/i', $html, $audioMatches);
        if (!empty($audioMatches[1])) {
            foreach ($audioMatches[1] as $src) {
                $assets[] = ['type' => 'audio', 'src' => $src, 'tag' => 'audio'];
            }
        }
        
        // Extract anchor/link tags for downloadable files (PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP, etc.)
        preg_match_all('/<a[^>]+href="([^"]+\.(pdf|doc|docx|xls|xlsx|ppt|pptx|zip|rar|7z|txt|csv|json))"/i', $html, $linkMatches);
        if (!empty($linkMatches[1])) {
            foreach ($linkMatches[1] as $href) {
                $extension = strtolower(pathinfo($href, PATHINFO_EXTENSION));
                $assets[] = ['type' => 'document', 'src' => $href, 'tag' => 'a', 'extension' => $extension];
            }
        }
        
        // Extract embed tags (PDFs, videos, etc.)
        preg_match_all('/<embed[^>]+src="([^"]+)"/i', $html, $embedMatches);
        if (!empty($embedMatches[1])) {
            foreach ($embedMatches[1] as $src) {
                $assets[] = ['type' => 'embed', 'src' => $src, 'tag' => 'embed'];
            }
        }
        
        // Extract iframe sources (embedded content, including YouTube, Vimeo, etc.)
        preg_match_all('/<iframe[^>]+src="([^"]+)"/i', $html, $iframeMatches);
        if (!empty($iframeMatches[1])) {
            foreach ($iframeMatches[1] as $src) {
                // Detect video platform embeds
                if (preg_match('/(youtube\.com|youtu\.be)/i', $src)) {
                    $assets[] = ['type' => 'youtube', 'src' => $src, 'tag' => 'iframe', 'embed' => true];
                } elseif (preg_match('/vimeo\.com/i', $src)) {
                    $assets[] = ['type' => 'vimeo', 'src' => $src, 'tag' => 'iframe', 'embed' => true];
                } elseif (preg_match('/dailymotion\.com/i', $src)) {
                    $assets[] = ['type' => 'dailymotion', 'src' => $src, 'tag' => 'iframe', 'embed' => true];
                } elseif (preg_match('/google\.com\/maps/i', $src)) {
                    $assets[] = ['type' => 'google_maps', 'src' => $src, 'tag' => 'iframe', 'embed' => true];
                } else {
                    // Local or other iframe content
                    $assets[] = ['type' => 'iframe', 'src' => $src, 'tag' => 'iframe', 'embed' => false];
                }
            }
        }
        
        // Extract YouTube links from anchor tags (will convert to embeds)
        preg_match_all('/<a[^>]+href="(https?:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})(?:[^"]*))"/i', $html, $ytLinkMatches);
        if (!empty($ytLinkMatches[1])) {
            foreach ($ytLinkMatches[2] as $index => $videoId) {
                $originalUrl = $ytLinkMatches[1][$index];
                $embedUrl = "https://www.youtube.com/embed/{$videoId}";
                $assets[] = [
                    'type' => 'youtube', 
                    'src' => $embedUrl, 
                    'tag' => 'a', 
                    'embed' => true,
                    'original_url' => $originalUrl,
                    'video_id' => $videoId
                ];
            }
        }
        
        // Extract object tags (Flash, PDFs, etc.)
        preg_match_all('/<object[^>]+data="([^"]+)"/i', $html, $objectMatches);
        if (!empty($objectMatches[1])) {
            foreach ($objectMatches[1] as $src) {
                $assets[] = ['type' => 'object', 'src' => $src, 'tag' => 'object'];
            }
        }
        
        return $assets;
    }

    /**
     * Detect image type (URL, base64, local path)
     */
    private function detectImageType(string $src): string
    {
        if (strpos($src, 'data:image') === 0) {
            return 'base64';
        } elseif (preg_match('/^https?:\/\//i', $src)) {
            return 'url';
        } else {
            return 'local';
        }
    }

    /**
     * Process and optimize images
     */
    private function processImages(array $images): array
    {
        $processed = [];
        
        foreach ($images as $image) {
            $type = $image['type'];
            $src = $image['original_src'];
            
            if ($type === 'base64') {
                // Extract and save base64 images
                $processed[] = $this->saveBase64Image($src, $image['index']);
            } elseif ($type === 'url') {
                // Keep external URLs as is
                $processed[] = [
                    'original_src' => $src,
                    'processed_path' => $src,
                    'type' => 'external'
                ];
            } else {
                // Handle local file paths - check if file exists and copy to storage
                $localPath = null;
                
                // Try to resolve relative paths
                if (strpos($src, '/') === 0) {
                    // Absolute path from public
                    $localPath = public_path(ltrim($src, '/'));
                } elseif (strpos($src, 'storage/') === 0) {
                    $localPath = storage_path('app/public/' . substr($src, 8));
                } elseif (strpos($src, '/storage/') === 0) {
                    $localPath = storage_path('app/public/' . substr($src, 9));
                }
                
                // If local file exists, copy it to uploads folder
                if ($localPath && file_exists($localPath)) {
                    $extension = pathinfo($localPath, PATHINFO_EXTENSION);
                    $filename = 'image_' . uniqid() . '_' . $image['index'] . '.' . $extension;
                    $newPath = $this->uploadPath . 'images/' . $filename;
                    
                    // Ensure directory exists
                    $fullDir = storage_path('app/public/' . dirname($newPath));
                    if (!is_dir($fullDir)) {
                        mkdir($fullDir, 0755, true);
                    }
                    
                    // Copy file
                    $fullNewPath = storage_path('app/public/' . $newPath);
                    copy($localPath, $fullNewPath);
                    
                    $processed[] = [
                        'original_src' => $src,
                        'processed_path' => '/storage/' . $newPath,
                        'type' => 'uploaded',
                        'size' => filesize($fullNewPath)
                    ];
                } else {
                    // File not found, keep original path
                    $processed[] = [
                        'original_src' => $src,
                        'processed_path' => $src,
                        'type' => 'local'
                    ];
                }
            }
        }
        
        return $processed;
    }

    /**
     * Save base64 encoded image
     */
    private function saveBase64Image(string $base64Data, int $index): array
    {
        // Extract image data
        if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $base64Data, $matches)) {
            $imageType = $matches[1];
            $imageData = base64_decode($matches[2]);
            
            $filename = 'image_' . uniqid() . '_' . $index . '.' . $imageType;
            $path = $this->uploadPath . 'images/' . $filename;
            
            Storage::disk('public')->put($path, $imageData);
            
            return [
                'original_src' => 'data:image/' . $imageType . ';base64,...',
                'processed_path' => 'storage/' . $path,
                'type' => 'uploaded',
                'size' => strlen($imageData)
            ];
        }
        
        return [
            'original_src' => $base64Data,
            'processed_path' => $base64Data,
            'type' => 'base64_failed'
        ];
    }

    /**
     * Update image paths in HTML
     */
    private function updateImagePaths(string $html, array $processedImages): string
    {
        foreach ($processedImages as $image) {
            if (isset($image['original_src']) && isset($image['processed_path'])) {
                $html = str_replace($image['original_src'], $image['processed_path'], $html);
            }
        }
        
        return $html;
    }



    /**
     * Process and download assets (videos, documents, etc.)
     */
    private function processAssets(array $assets): array
    {
        $processed = [];
        
        foreach ($assets as $index => $asset) {
            $src = $asset['src'];
            $type = $asset['type'];
            
            // Handle video platform embeds (YouTube, Vimeo, etc.) - don't download, just preserve
            if (isset($asset['embed']) && $asset['embed'] === true) {
                $processed[] = [
                    'original_src' => $src,
                    'processed_path' => $src,
                    'type' => $type,
                    'tag' => $asset['tag'],
                    'status' => 'embed',
                    'video_id' => $asset['video_id'] ?? null,
                    'original_url' => $asset['original_url'] ?? null
                ];
                continue;
            }
            
            // Check if it's a remote URL that needs downloading
            if (preg_match('/^https?:\/\//i', $src)) {
                // Download remote file
                $downloaded = $this->downloadAsset($src, $type, $index);
                if ($downloaded) {
                    $processed[] = $downloaded;
                } else {
                    // Keep original if download fails
                    $processed[] = [
                        'original_src' => $src,
                        'processed_path' => $src,
                        'type' => $type,
                        'status' => 'external'
                    ];
                }
            } elseif (file_exists(public_path($src))) {
                // Local file exists
                $processed[] = [
                    'original_src' => $src,
                    'local_path' => public_path($src),
                    'processed_path' => $src,
                    'type' => $type,
                    'status' => 'local'
                ];
            } else {
                // File not found or external embed (YouTube, etc.)
                $processed[] = [
                    'original_src' => $src,
                    'processed_path' => $src,
                    'type' => $type,
                    'status' => 'unavailable'
                ];
            }
        }
        
        return $processed;
    }

    /**
     * Download remote asset and save to storage
     */
    private function downloadAsset(string $url, string $type, int $index): ?array
    {
        try {
            // Set reasonable timeout and size limits
            $maxFileSize = 100 * 1024 * 1024; // 100MB max
            $timeout = 30; // 30 seconds timeout
            
            // Get file extension from URL
            $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (empty($extension)) {
                $extension = $this->guessExtensionFromType($type);
            }
            
            // Download file
            $context = stream_context_create([
                'http' => [
                    'timeout' => $timeout,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ]);
            
            $fileContent = @file_get_contents($url, false, $context);
            
            if ($fileContent === false || strlen($fileContent) === 0) {
                Log::warning('Failed to download asset', ['url' => $url]);
                return null;
            }
            
            // Check file size
            if (strlen($fileContent) > $maxFileSize) {
                Log::warning('Asset too large to download', ['url' => $url, 'size' => strlen($fileContent)]);
                return null;
            }
            
            // Save to storage
            $filename = $type . '_' . uniqid() . '_' . $index . '.' . $extension;
            $path = $this->uploadPath . 'assets/' . $filename;
            
            Storage::disk('public')->put($path, $fileContent);
            
            Log::info('Asset downloaded successfully', [
                'url' => $url,
                'saved_to' => $path,
                'size' => strlen($fileContent)
            ]);
            
            return [
                'original_src' => $url,
                'processed_path' => 'storage/' . $path,
                'local_path' => storage_path('app/public/' . $path),
                'type' => $type,
                'extension' => $extension,
                'size' => strlen($fileContent),
                'status' => 'downloaded'
            ];
            
        } catch (\Exception $e) {
            Log::error('Asset download failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Guess file extension from asset type
     */
    private function guessExtensionFromType(string $type): string
    {
        $extensionMap = [
            'video' => 'mp4',
            'audio' => 'mp3',
            'document' => 'pdf',
            'media' => 'mp4',
            'embed' => 'pdf',
            'object' => 'pdf'
        ];
        
        return $extensionMap[$type] ?? 'bin';
    }

    /**
     * Update asset paths in HTML (including converting YouTube links to embeds)
     */
    private function updateAssetPaths(string $html, array $processedAssets): string
    {
        foreach ($processedAssets as $asset) {
            // Handle YouTube link to embed conversion
            if ($asset['type'] === 'youtube' && $asset['tag'] === 'a' && isset($asset['original_url'])) {
                // Convert YouTube links to responsive embeds
                $videoId = $asset['video_id'];
                $embedHtml = '<div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;max-width:100%;margin:20px 0;">' .
                             '<iframe src="https://www.youtube.com/embed/' . $videoId . '" ' .
                             'style="position:absolute;top:0;left:0;width:100%;height:100%;" ' .
                             'frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" ' .
                             'allowfullscreen></iframe></div>';
                
                // Replace the anchor tag containing the YouTube URL
                $html = preg_replace(
                    '/<a[^>]*href="' . preg_quote($asset['original_url'], '/') . '"[^>]*>.*?<\/a>/i',
                    $embedHtml,
                    $html
                );
                
                Log::info('Converted YouTube link to embed', ['video_id' => $videoId]);
            }
            // Handle existing YouTube iframes (ensure they're responsive)
            elseif (in_array($asset['type'], ['youtube', 'vimeo', 'dailymotion']) && $asset['tag'] === 'iframe') {
                // Wrap bare iframes in responsive container if not already wrapped
                $escapedSrc = preg_quote($asset['src'], '/');
                $html = preg_replace(
                    '/<iframe([^>]*)src="' . $escapedSrc . '"([^>]*)><\/iframe>/i',
                    '<div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;max-width:100%;margin:20px 0;">' .
                    '<iframe$1src="' . $asset['src'] . '"$2 style="position:absolute;top:0;left:0;width:100%;height:100%;" ' .
                    'frameborder="0" allowfullscreen></iframe></div>',
                    $html,
                    1
                );
            }
            // Handle downloaded assets
            elseif (isset($asset['original_src']) && isset($asset['processed_path']) && $asset['status'] === 'downloaded') {
                $html = str_replace($asset['original_src'], $asset['processed_path'], $html);
            }
        }
        
        return $html;
    }

    /**
     * Paginate HTML content
     */
    private function paginateHtml(string $html, int $wordsPerPage): array
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) {
            // No body tag, treat entire content as one page
            return [$html];
        }

        $pages = [];
        $currentPage = '';
        $wordCount = 0;

        foreach ($body->childNodes as $node) {
            $nodeHtml = $dom->saveHTML($node);
            $nodeWords = str_word_count(strip_tags($nodeHtml));
            
            if ($wordCount + $nodeWords > $wordsPerPage && $wordCount > 0) {
                // Start new page
                $pages[] = $currentPage;
                $currentPage = $nodeHtml;
                $wordCount = $nodeWords;
            } else {
                $currentPage .= $nodeHtml;
                $wordCount += $nodeWords;
            }
        }

        // Add last page
        if (!empty($currentPage)) {
            $pages[] = $currentPage;
        }

        return !empty($pages) ? $pages : [$html];
    }

    /**
     * Set words per page for pagination
     */
    public function setWordsPerPage(int $words): void
    {
        $this->wordsPerPage = $words;
    }

    /**
     * Move PhpWord-generated images to permanent storage
     */
    private function movePhpWordImages(string $html, string $imageFolder, string $baseFilename): string
    {
        $imageFolderName = $baseFilename . '_files';
        
        if (!is_dir($imageFolder)) {
            return $html;
        }
        
        // Get all image files from the folder
        $imageFiles = glob($imageFolder . '/*');
        
        foreach ($imageFiles as $imageFile) {
            if (!is_file($imageFile)) {
                continue;
            }
            
            $filename = basename($imageFile);
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $newFilename = 'image_' . uniqid() . '.' . $extension;
            $newPath = $this->uploadPath . 'images/' . $newFilename;
            
            // Ensure directory exists
            $fullDir = storage_path('app/public/' . dirname($newPath));
            if (!is_dir($fullDir)) {
                mkdir($fullDir, 0755, true);
            }
            
            // Copy image to permanent storage
            $fullNewPath = storage_path('app/public/' . $newPath);
            copy($imageFile, $fullNewPath);
            
            // Update HTML to replace old path with new path
            $oldPath1 = $imageFolderName . '/' . $filename;
            $oldPath2 = '/' . $imageFolderName . '/' . $filename;
            $oldPath3 = '/storage/' . $imageFolderName . '/' . $filename;
            $newStoragePath = '/storage/' . $newPath;
            
            $html = str_replace($oldPath1, $newStoragePath, $html);
            $html = str_replace($oldPath2, $newStoragePath, $html);
            $html = str_replace($oldPath3, $newStoragePath, $html);
            
            Log::info('Moved PhpWord image', [
                'from' => $filename,
                'to' => $newPath
            ]);
        }
        
        return $html;
    }

    /**
     * Recursively delete a directory
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
}
