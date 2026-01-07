# Test Content-Lesson Relationships

Write-Host "🔍 Testing HTML Content Integration..." -ForegroundColor Cyan
Write-Host ""

Write-Host "1️⃣ Testing Lesson → Content relationship..." -ForegroundColor Yellow
docker-compose exec app1 php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

\$lesson = App\Models\Lesson::with('content')->first();
echo 'Lesson: ' . \$lesson->title . PHP_EOL;
echo 'Has Content: ' . (\$lesson->content ? 'YES' : 'NO') . PHP_EOL;
if (\$lesson->content) {
    echo 'Content Title: ' . \$lesson->content->title . PHP_EOL;
    echo 'Total Pages: ' . \$lesson->content->total_pages . PHP_EOL;
    echo 'Is Processed: ' . (\$lesson->content->is_processed ? 'YES' : 'NO') . PHP_EOL;
}
"

Write-Host ""
Write-Host "2️⃣ Testing Content → Lesson relationship..." -ForegroundColor Yellow
docker-compose exec app1 php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

\$content = App\Models\Content::with('lesson')->first();
echo 'Content: ' . \$content->title . PHP_EOL;
echo 'Belongs to Lesson: ' . \$content->lesson->title . PHP_EOL;
echo 'Lesson ID: ' . \$content->lesson_id . PHP_EOL;
"

Write-Host ""
Write-Host "3️⃣ Testing Course with nested Content..." -ForegroundColor Yellow
docker-compose exec app1 php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

\$course = App\Models\Course::with('modules.lessons.content')->first();
\$contentCount = 0;
foreach (\$course->modules as \$module) {
    foreach (\$module->lessons as \$lesson) {
        if (\$lesson->content) {
            \$contentCount++;
        }
    }
}
echo 'Course: ' . \$course->title . PHP_EOL;
echo 'Total Modules: ' . \$course->modules->count() . PHP_EOL;
echo 'Lessons with Content: ' . \$contentCount . PHP_EOL;
"

Write-Host ""
Write-Host "4️⃣ Testing Foreign Key Constraint..." -ForegroundColor Yellow
docker-compose exec app1 php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

\$content = App\Models\Content::first();
\$fk = \$content->lesson_id;
\$lesson = App\Models\Lesson::find(\$fk);
echo 'Content lesson_id: ' . \$fk . PHP_EOL;
echo 'Lesson exists: ' . (\$lesson ? 'YES' : 'NO') . PHP_EOL;
echo 'Foreign key valid: ' . (\$lesson ? 'VALID ✅' : 'INVALID ❌') . PHP_EOL;
"

Write-Host ""
Write-Host "5️⃣ Checking database structure..." -ForegroundColor Yellow
docker-compose exec app1 php artisan db:table contents --columns

Write-Host ""
Write-Host "=" * 60 -ForegroundColor Cyan
Write-Host "✅ Relationship Test Complete!" -ForegroundColor Green
Write-Host "=" * 60 -ForegroundColor Cyan
