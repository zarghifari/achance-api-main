<?php

namespace Database\Seeders;

use App\Models\Content;
use App\Models\Lesson;
use App\Services\DocumentConverterService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Content Seeder...');
        $this->command->info('📌 GPT-5 enabled for all clients');

        // Get all lessons
        $lessons = Lesson::all();

        if ($lessons->isEmpty()) {
            $this->command->warn('⚠️ No lessons found. Please run CourseSeeder first.');
            return;
        }

        // Create sample HTML content for each lesson
        $contentData = $this->getSampleContentData();

        foreach ($lessons as $index => $lesson) {
            // Check if content already exists for this lesson
            if (Content::where('lesson_id', $lesson->id)->exists()) {
                $this->command->info("⏭️ Content already exists for lesson: {$lesson->title}");
                continue;
            }

            // Use different sample content for each lesson (cycle through available samples)
            $sampleIndex = $index % count($contentData);
            $sample = $contentData[$sampleIndex];

            try {
                $content = Content::create([
                    'lesson_id' => $lesson->id,
                    'title' => $sample['title'],
                    'type' => 'html',
                    'description' => $sample['description'],
                    'original_filename' => $sample['filename'],
                    'html_content' => $sample['html'],
                    'json_content' => $sample['json_content'],
                    'images' => $sample['images'],
                    'assets' => $sample['assets'],
                    'total_pages' => $sample['total_pages'],
                    'words_per_page' => 500,
                    'metadata' => $sample['metadata'],
                    'is_processed' => true,
                    'processed_at' => now(),
                    'is_active' => true,
                    'position' => 0
                ]);

                $this->command->info("✅ Created content for lesson: {$lesson->title} (ID: {$content->id})");
            } catch (\Exception $e) {
                $this->command->error("❌ Failed to create content for lesson {$lesson->id}: {$e->getMessage()}");
                Log::error('Content seeder error', [
                    'lesson_id' => $lesson->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->command->info('✨ Content Seeder completed!');
    }

    /**
     * Get sample content data
     */
    private function getSampleContentData(): array
    {
        // Try to load HTML files from seeders directory
        $seedersPath = database_path('seeders');
        $htmlFiles = [
            '1_computer_grafis/1_computer_grafis.htm'
        ];
        
        $loadedContent = [];
        
        foreach ($htmlFiles as $htmlFile) {
            $filePath = $seedersPath . '/' . $htmlFile;
            if (file_exists($filePath)) {
                $htmlContent = file_get_contents($filePath);
                $filename = basename($htmlFile);
                
                // Convert from Windows-1252 to UTF-8 (common for Word exports)
                $htmlContent = mb_convert_encoding($htmlContent, 'UTF-8', 'Windows-1252');
                $this->command->info("🔄 Converted {$filename} encoding to UTF-8");
                
                // Clean and validate HTML
                $htmlContent = $this->cleanHtml($htmlContent);
                
                // Check for associated image files
                $imagesDir = dirname($filePath) . '/' . pathinfo($filename, PATHINFO_FILENAME) . '_files';
                $images = [];
                
                // Process images and update HTML paths
                if (is_dir($imagesDir)) {
                    $result = $this->processImagesAndUpdateHtml($htmlContent, $imagesDir, pathinfo($filename, PATHINFO_FILENAME));
                    $htmlContent = $result['html'];
                    $images = $result['images'];
                    
                    if (!empty($images)) {
                        $imageCount = count($images);
                        $this->command->info("🖼️  Found and processed {$imageCount} image(s)");
                    }
                }
                
                $loadedContent[] = [
                    'title' => str_replace('_', ' ', pathinfo($filename, PATHINFO_FILENAME)),
                    'filename' => $filename,
                    'description' => 'Content loaded from ' . $filename,
                    'html' => $htmlContent,
                    'json_content' => ['pages' => [['page' => 1, 'content' => $htmlContent]]],
                    'images' => $images,
                    'assets' => [],
                    'total_pages' => 1,
                    'metadata' => [
                        'source' => 'file',
                        'file_path' => $htmlFile,
                        'word_count' => str_word_count(strip_tags($htmlContent)),
                        'character_count' => strlen(strip_tags($htmlContent))
                    ]
                ];
                
                $this->command->info("📄 Loaded HTML file: {$filename}");
            }
        }
        
        // If no files found, use hardcoded samples as fallback
        if (empty($loadedContent)) {
            $this->command->warn('⚠️ No HTML files found, using hardcoded samples');
            return $this->getHardcodedSampleData();
        }
        
        return $loadedContent;
    }
    
    /**
     * Clean HTML content
     */
    private function cleanHtml(string $html): string
    {
        // Remove null bytes
        $html = str_replace("\0", '', $html);
        
        // Ensure UTF-8 encoding
        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');
        
        return $html;
    }
    
    /**
     * Load images from a directory
     */
    /**
     * Process images from directory, copy to storage, and update HTML paths
     * Enhanced with GPT-5 compatibility and improved path handling
     */
    private function processImagesAndUpdateHtml(string $html, string $imagesDir, string $baseFilename): array
    {
        $images = [];
        $imageFolderName = $baseFilename . '_files';
        
        if (!is_dir($imagesDir)) {
            $this->command->warn("   ⚠️ Image directory not found: {$imagesDir}");
            return ['html' => $html, 'images' => $images];
        }
        
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico'];
        $uploadPath = 'uploads/contents/images/';
        $files = scandir($imagesDir);
        
        $this->command->info("   📂 Processing images from: {$imageFolderName}");
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $sourcePath = $imagesDir . '/' . $file;
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            
            if (is_file($sourcePath) && in_array($extension, $imageExtensions)) {
                // Generate unique filename
                $newFilename = 'image_' . uniqid() . '_' . time() . '.' . $extension;
                $relativePath = $uploadPath . $newFilename;
                
                // Ensure directory exists
                $fullDir = storage_path('app/public/' . $uploadPath);
                if (!is_dir($fullDir)) {
                    mkdir($fullDir, 0755, true);
                    $this->command->info("   📁 Created directory: {$uploadPath}");
                }
                
                // Copy image to storage
                $destinationPath = storage_path('app/public/' . $relativePath);
                if (copy($sourcePath, $destinationPath)) {
                    // Update HTML paths - support multiple formats
                    $oldPath1 = $imageFolderName . '/' . $file;
                    $oldPath2 = '/' . $imageFolderName . '/' . $file;
                    $oldPath3 = '/storage/' . $imageFolderName . '/' . $file;
                    $oldPath4 = './' . $imageFolderName . '/' . $file;
                    $newStoragePath = '/storage/' . $relativePath;
                    
                    // Replace all possible path variations
                    $html = str_replace($oldPath1, $newStoragePath, $html);
                    $html = str_replace($oldPath2, $newStoragePath, $html);
                    $html = str_replace($oldPath3, $newStoragePath, $html);
                    $html = str_replace($oldPath4, $newStoragePath, $html);
                    
                    $images[] = [
                        'filename' => $newFilename,
                        'original_filename' => $file,
                        'path' => $relativePath,
                        'storage_path' => $newStoragePath,
                        'size' => filesize($sourcePath),
                        'mime_type' => mime_content_type($sourcePath),
                        'accessible_via_html' => true
                    ];
                    
                    $this->command->info("   ✓ Processed image: {$file} → {$newFilename}");
                } else {
                    $this->command->error("   ✗ Failed to copy: {$file}");
                }
            }
        }
        
        $this->command->info("   📊 Total images processed: " . count($images));
        
        return ['html' => $html, 'images' => $images];
    }
    
    private function loadImagesFromDirectory(string $directory): array
    {
        $images = [];
        
        if (!is_dir($directory)) {
            return $images;
        }
        
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $files = scandir($directory);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $filePath = $directory . '/' . $file;
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            
            if (is_file($filePath) && in_array($extension, $imageExtensions)) {
                $images[] = [
                    'filename' => $file,
                    'absolute_path' => $filePath,
                    'size' => filesize($filePath),
                    'mime_type' => mime_content_type($filePath)
                ];
            }
        }
        
        return $images;
    }
    
    /**
     * Get hardcoded sample data (fallback)
     */
    private function getHardcodedSampleData(): array
    {
        return [
            // Sample 1: Introduction to Web Development
            [
                'title' => 'Introduction to Web Development',
                'filename' => 'intro-web-dev.html',
                'description' => 'A comprehensive guide to modern web development fundamentals',
                'html' => $this->getWebDevHtml(),
                'json_content' => $this->getWebDevJsonContent(),
                'images' => [],
                'assets' => [],
                'total_pages' => 3,
                'metadata' => [
                    'word_count' => 850,
                    'character_count' => 5234,
                    'image_count' => 0,
                    'asset_count' => 0,
                    'processing_date' => now()->toDateTimeString(),
                    'original_filename' => 'intro-web-dev.html'
                ]
            ],

            // Sample 2: JavaScript Basics
            [
                'title' => 'JavaScript Programming Fundamentals',
                'filename' => 'javascript-basics.html',
                'description' => 'Learn the core concepts of JavaScript programming',
                'html' => $this->getJavaScriptHtml(),
                'json_content' => $this->getJavaScriptJsonContent(),
                'images' => [],
                'assets' => [],
                'total_pages' => 4,
                'metadata' => [
                    'word_count' => 1200,
                    'character_count' => 7890,
                    'image_count' => 0,
                    'asset_count' => 0,
                    'processing_date' => now()->toDateTimeString(),
                    'original_filename' => 'javascript-basics.html'
                ]
            ],

            // Sample 3: React Framework
            [
                'title' => 'React Framework Essential Guide',
                'filename' => 'react-essentials.html',
                'description' => 'Master React components, hooks, and state management',
                'html' => $this->getReactHtml(),
                'json_content' => $this->getReactJsonContent(),
                'images' => [],
                'assets' => [],
                'total_pages' => 5,
                'metadata' => [
                    'word_count' => 1500,
                    'character_count' => 9876,
                    'image_count' => 0,
                    'asset_count' => 0,
                    'processing_date' => now()->toDateTimeString(),
                    'original_filename' => 'react-essentials.html'
                ]
            ],

            // Sample 4: Database Design
            [
                'title' => 'Database Design Principles',
                'filename' => 'database-design.html',
                'description' => 'Understanding relational databases and SQL',
                'html' => $this->getDatabaseHtml(),
                'json_content' => $this->getDatabaseJsonContent(),
                'images' => [],
                'assets' => [],
                'total_pages' => 4,
                'metadata' => [
                    'word_count' => 1100,
                    'character_count' => 7234,
                    'image_count' => 0,
                    'asset_count' => 0,
                    'processing_date' => now()->toDateTimeString(),
                    'original_filename' => 'database-design.html'
                ]
            ]
        ];
    }

    private function getWebDevHtml(): string
    {
        return <<<'HTML'
<div class="content-wrapper">
    <h1>Introduction to Web Development</h1>
    
    <h2>What is Web Development?</h2>
    <p>Web development refers to the work involved in developing websites for the Internet (World Wide Web) or an intranet (a private network). It can range from developing a simple single static page of plain text to complex web applications, electronic businesses, and social network services.</p>
    
    <h3>Front-End Development</h3>
    <p>Front-end development focuses on what users see and interact with. It involves three core technologies:</p>
    <ul>
        <li><strong>HTML (HyperText Markup Language):</strong> The backbone of web content, defining structure and semantics</li>
        <li><strong>CSS (Cascading Style Sheets):</strong> Controls the visual presentation and layout</li>
        <li><strong>JavaScript:</strong> Adds interactivity and dynamic behavior to web pages</li>
    </ul>
    
    <h3>Back-End Development</h3>
    <p>Back-end development handles server-side logic, database interactions, and application functionality. Common technologies include:</p>
    <ul>
        <li>PHP, Python, Ruby, Node.js for server-side programming</li>
        <li>MySQL, PostgreSQL, MongoDB for database management</li>
        <li>RESTful APIs for communication between front-end and back-end</li>
    </ul>
    
    <h2>Modern Web Development</h2>
    <p>Today's web development ecosystem includes frameworks, build tools, and best practices that enhance productivity and code quality. Popular frameworks include React, Vue, Angular for front-end, and Laravel, Django, Express for back-end development.</p>
    
    <h3>Best Practices</h3>
    <ol>
        <li>Write clean, maintainable code</li>
        <li>Follow responsive design principles</li>
        <li>Optimize for performance</li>
        <li>Ensure accessibility for all users</li>
        <li>Implement security best practices</li>
    </ol>
</div>
HTML;
    }

    private function getWebDevJsonContent(): array
    {
        return [
            'pages' => [
                '<h1>Introduction to Web Development</h1><h2>What is Web Development?</h2><p>Web development refers to the work involved in developing websites for the Internet (World Wide Web) or an intranet (a private network). It can range from developing a simple single static page of plain text to complex web applications, electronic businesses, and social network services.</p><h3>Front-End Development</h3><p>Front-end development focuses on what users see and interact with. It involves three core technologies:</p><ul><li><strong>HTML (HyperText Markup Language):</strong> The backbone of web content, defining structure and semantics</li><li><strong>CSS (Cascading Style Sheets):</strong> Controls the visual presentation and layout</li><li><strong>JavaScript:</strong> Adds interactivity and dynamic behavior to web pages</li></ul>',
                '<h3>Back-End Development</h3><p>Back-end development handles server-side logic, database interactions, and application functionality. Common technologies include:</p><ul><li>PHP, Python, Ruby, Node.js for server-side programming</li><li>MySQL, PostgreSQL, MongoDB for database management</li><li>RESTful APIs for communication between front-end and back-end</li></ul>',
                '<h2>Modern Web Development</h2><p>Today\'s web development ecosystem includes frameworks, build tools, and best practices that enhance productivity and code quality. Popular frameworks include React, Vue, Angular for front-end, and Laravel, Django, Express for back-end development.</p><h3>Best Practices</h3><ol><li>Write clean, maintainable code</li><li>Follow responsive design principles</li><li>Optimize for performance</li><li>Ensure accessibility for all users</li><li>Implement security best practices</li></ol>'
            ]
        ];
    }

    private function getJavaScriptHtml(): string
    {
        return <<<'HTML'
<div class="content-wrapper">
    <h1>JavaScript Programming Fundamentals</h1>
    
    <h2>Introduction</h2>
    <p>JavaScript is a versatile, high-level programming language that enables interactive web pages. It's an essential part of web applications, allowing developers to implement complex features on web pages.</p>
    
    <h3>Variables and Data Types</h3>
    <p>JavaScript supports various data types:</p>
    <ul>
        <li><strong>String:</strong> Text data enclosed in quotes</li>
        <li><strong>Number:</strong> Numeric values including integers and floats</li>
        <li><strong>Boolean:</strong> True or false values</li>
        <li><strong>Object:</strong> Collections of key-value pairs</li>
        <li><strong>Array:</strong> Ordered lists of values</li>
    </ul>
    
    <h3>Functions</h3>
    <p>Functions are reusable blocks of code that perform specific tasks. Modern JavaScript supports both traditional function declarations and arrow functions.</p>
    <pre><code>
// Traditional function
function greet(name) {
    return `Hello, ${name}!`;
}

// Arrow function
const greet = (name) => `Hello, ${name}!`;
    </code></pre>
    
    <h2>Control Structures</h2>
    <p>JavaScript provides various control structures for decision-making and looping:</p>
    <ul>
        <li>if/else statements for conditional execution</li>
        <li>switch statements for multiple conditions</li>
        <li>for loops for iteration</li>
        <li>while loops for conditional repetition</li>
    </ul>
    
    <h3>ES6+ Features</h3>
    <p>Modern JavaScript (ES6 and beyond) includes powerful features like destructuring, spread operators, template literals, and async/await for handling asynchronous operations.</p>
</div>
HTML;
    }

    private function getJavaScriptJsonContent(): array
    {
        return [
            'pages' => [
                '<h1>JavaScript Programming Fundamentals</h1><h2>Introduction</h2><p>JavaScript is a versatile, high-level programming language that enables interactive web pages. It\'s an essential part of web applications, allowing developers to implement complex features on web pages.</p><h3>Variables and Data Types</h3><p>JavaScript supports various data types:</p><ul><li><strong>String:</strong> Text data enclosed in quotes</li><li><strong>Number:</strong> Numeric values including integers and floats</li><li><strong>Boolean:</strong> True or false values</li><li><strong>Object:</strong> Collections of key-value pairs</li><li><strong>Array:</strong> Ordered lists of values</li></ul>',
                '<h3>Functions</h3><p>Functions are reusable blocks of code that perform specific tasks. Modern JavaScript supports both traditional function declarations and arrow functions.</p><pre><code>// Traditional function\nfunction greet(name) {\n    return `Hello, ${name}!`;\n}\n\n// Arrow function\nconst greet = (name) => `Hello, ${name}!`;</code></pre>',
                '<h2>Control Structures</h2><p>JavaScript provides various control structures for decision-making and looping:</p><ul><li>if/else statements for conditional execution</li><li>switch statements for multiple conditions</li><li>for loops for iteration</li><li>while loops for conditional repetition</li></ul>',
                '<h3>ES6+ Features</h3><p>Modern JavaScript (ES6 and beyond) includes powerful features like destructuring, spread operators, template literals, and async/await for handling asynchronous operations.</p>'
            ]
        ];
    }

    private function getReactHtml(): string
    {
        return <<<'HTML'
<div class="content-wrapper">
    <h1>React Framework Essential Guide</h1>
    
    <h2>What is React?</h2>
    <p>React is a JavaScript library for building user interfaces, particularly single-page applications. Developed by Facebook, it allows developers to create reusable UI components.</p>
    
    <h3>Core Concepts</h3>
    <ul>
        <li><strong>Components:</strong> Building blocks of React applications</li>
        <li><strong>JSX:</strong> JavaScript XML syntax extension</li>
        <li><strong>Props:</strong> Data passed from parent to child components</li>
        <li><strong>State:</strong> Internal data management within components</li>
    </ul>
    
    <h2>React Hooks</h2>
    <p>Hooks are functions that let you use state and other React features in functional components:</p>
    <ul>
        <li><strong>useState:</strong> Manage component state</li>
        <li><strong>useEffect:</strong> Handle side effects</li>
        <li><strong>useContext:</strong> Access context values</li>
        <li><strong>useRef:</strong> Create mutable references</li>
    </ul>
    
    <h3>Component Lifecycle</h3>
    <p>Understanding the component lifecycle is crucial for effective React development. Components go through mounting, updating, and unmounting phases.</p>
    
    <h2>State Management</h2>
    <p>For complex applications, consider using state management libraries like Redux, MobX, or the built-in Context API for sharing state across components.</p>
    
    <h3>Best Practices</h3>
    <ol>
        <li>Keep components small and focused</li>
        <li>Use functional components with hooks</li>
        <li>Implement proper error boundaries</li>
        <li>Optimize re-renders with React.memo</li>
        <li>Follow the single responsibility principle</li>
    </ol>
</div>
HTML;
    }

    private function getReactJsonContent(): array
    {
        return [
            'pages' => [
                '<h1>React Framework Essential Guide</h1><h2>What is React?</h2><p>React is a JavaScript library for building user interfaces, particularly single-page applications. Developed by Facebook, it allows developers to create reusable UI components.</p><h3>Core Concepts</h3><ul><li><strong>Components:</strong> Building blocks of React applications</li><li><strong>JSX:</strong> JavaScript XML syntax extension</li><li><strong>Props:</strong> Data passed from parent to child components</li><li><strong>State:</strong> Internal data management within components</li></ul>',
                '<h2>React Hooks</h2><p>Hooks are functions that let you use state and other React features in functional components:</p><ul><li><strong>useState:</strong> Manage component state</li><li><strong>useEffect:</strong> Handle side effects</li><li><strong>useContext:</strong> Access context values</li><li><strong>useRef:</strong> Create mutable references</li></ul><h3>Component Lifecycle</h3><p>Understanding the component lifecycle is crucial for effective React development. Components go through mounting, updating, and unmounting phases.</p>',
                '<h2>State Management</h2><p>For complex applications, consider using state management libraries like Redux, MobX, or the built-in Context API for sharing state across components.</p>',
                '<h3>Best Practices</h3><ol><li>Keep components small and focused</li><li>Use functional components with hooks</li><li>Implement proper error boundaries</li><li>Optimize re-renders with React.memo</li><li>Follow the single responsibility principle</li></ol>'
            ]
        ];
    }

    private function getDatabaseHtml(): string
    {
        return <<<'HTML'
<div class="content-wrapper">
    <h1>Database Design Principles</h1>
    
    <h2>Introduction to Databases</h2>
    <p>A database is an organized collection of structured information, typically stored electronically in a computer system. Databases are managed by Database Management Systems (DBMS).</p>
    
    <h3>Relational Databases</h3>
    <p>Relational databases organize data into tables with rows and columns. Each table represents an entity, and relationships connect related data across tables.</p>
    
    <h2>SQL Basics</h2>
    <p>SQL (Structured Query Language) is used to communicate with databases. Key operations include:</p>
    <ul>
        <li><strong>SELECT:</strong> Retrieve data from tables</li>
        <li><strong>INSERT:</strong> Add new records</li>
        <li><strong>UPDATE:</strong> Modify existing records</li>
        <li><strong>DELETE:</strong> Remove records</li>
    </ul>
    
    <h3>Database Normalization</h3>
    <p>Normalization is the process of organizing data to reduce redundancy and improve data integrity. Common normal forms include 1NF, 2NF, 3NF, and BCNF.</p>
    
    <h2>Indexing and Performance</h2>
    <p>Indexes improve query performance by providing faster data retrieval. However, they also consume storage space and can slow down write operations.</p>
    
    <h3>Best Practices</h3>
    <ol>
        <li>Design tables with clear relationships</li>
        <li>Use appropriate data types</li>
        <li>Create indexes on frequently queried columns</li>
        <li>Normalize data to reduce redundancy</li>
        <li>Implement proper constraints and validations</li>
    </ol>
</div>
HTML;
    }

    private function getDatabaseJsonContent(): array
    {
        return [
            'pages' => [
                '<h1>Database Design Principles</h1><h2>Introduction to Databases</h2><p>A database is an organized collection of structured information, typically stored electronically in a computer system. Databases are managed by Database Management Systems (DBMS).</p><h3>Relational Databases</h3><p>Relational databases organize data into tables with rows and columns. Each table represents an entity, and relationships connect related data across tables.</p>',
                '<h2>SQL Basics</h2><p>SQL (Structured Query Language) is used to communicate with databases. Key operations include:</p><ul><li><strong>SELECT:</strong> Retrieve data from tables</li><li><strong>INSERT:</strong> Add new records</li><li><strong>UPDATE:</strong> Modify existing records</li><li><strong>DELETE:</strong> Remove records</li></ul><h3>Database Normalization</h3><p>Normalization is the process of organizing data to reduce redundancy and improve data integrity. Common normal forms include 1NF, 2NF, 3NF, and BCNF.</p>',
                '<h2>Indexing and Performance</h2><p>Indexes improve query performance by providing faster data retrieval. However, they also consume storage space and can slow down write operations.</p>',
                '<h3>Best Practices</h3><ol><li>Design tables with clear relationships</li><li>Use appropriate data types</li><li>Create indexes on frequently queried columns</li><li>Normalize data to reduce redundancy</li><li>Implement proper constraints and validations</li></ol>'
            ]
        ];
    }

}
