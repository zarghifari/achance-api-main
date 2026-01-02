# 🌱 Self-Directed Learning Seeders

## Overview

Three new seeders have been created for the SDL features:

1. **LearningProfileSeeder** - Creates learning style profiles (VARK assessment results)
2. **LearningGoalSeeder** - Creates learning goals with milestones and progress logs
3. **BookmarkSeeder** - Creates bookmarks for lessons, courses, and modules

---

## Running Seeders

### Individual Seeders

```bash
# Run each seeder separately
docker-compose exec app1 php artisan db:seed --class=LearningProfileSeeder
docker-compose exec app1 php artisan db:seed --class=LearningGoalSeeder
docker-compose exec app1 php artisan db:seed --class=BookmarkSeeder
```

### All Seeders at Once

```bash
# Run all seeders (including SDL seeders)
docker-compose exec app1 php artisan db:seed
```

### Fresh Database with Seeders

```bash
# Reset database and seed everything
docker-compose exec app1 php artisan migrate:fresh --seed
```

---

## What Gets Seeded

### 📊 Learning Profiles (4 profiles)

Creates 4 different learning style profiles:

1. **Visual Learner** (65% Visual)
   - Prefers: Video content, medium lessons
   - Study time: Evening
   - Daily goal: 60 minutes
   - Likes gamification and challenges

2. **Auditory Learner** (60% Auditory)
   - Prefers: Audio content, short lessons
   - Study time: Morning
   - Daily goal: 45 minutes
   - Likes group learning

3. **Reading Learner** (60% Reading)
   - Prefers: Text content, long lessons
   - Study time: Afternoon
   - Daily goal: 90 minutes
   - Likes gamification and challenges

4. **Kinesthetic Learner** (60% Kinesthetic)
   - Prefers: Interactive content, short lessons
   - Study time: Night
   - Daily goal: 30 minutes
   - Likes everything (gamification, group learning, challenges)

---

### 🎯 Learning Goals (6 goals with milestones)

Creates 6 diverse learning goals:

#### 1. Master React Development (40% complete)
- **Type**: Skill
- **Status**: Active, on track
- **Target**: 6 months
- **Milestones**: 4 milestones (2 achieved)
  - ✅ Complete React Fundamentals course
  - ✅ Build first React app
  - ⏳ Learn React Hooks in depth
  - ⏳ Build portfolio project
- **Progress logs**: 2 entries

#### 2. Become a Full-Stack Developer (10% complete)
- **Type**: Career
- **Status**: Active, slightly behind schedule
- **Target**: 1 year
- **Milestones**: 3 milestones (1 achieved)
  - ✅ Learn Node.js fundamentals
  - ⏳ Master Express.js
  - ⏳ Database design and management
- **Progress logs**: 1 entry

#### 3. Build Personal Portfolio Website (75% complete)
- **Type**: Project
- **Status**: Active, ahead of schedule
- **Target**: 2 months
- **Milestones**: 4 milestones (3 achieved)
  - ✅ Design mockups
  - ✅ Develop homepage
  - ✅ Add projects section
  - ⏳ Deploy to production
- **Progress logs**: 1 entry

#### 4. AWS Cloud Practitioner Certification (20% complete)
- **Type**: Certification
- **Status**: Paused
- **Target**: 8 months
- **Milestones**: 2 milestones (2 achieved)
  - ✅ Complete AWS fundamentals course
  - ✅ Practice with AWS console

#### 5. Study 30 Hours This Month (ACHIEVED! 🎉)
- **Type**: Time-based
- **Status**: Achieved (completed 32/30 hours)
- **Target**: Past date (completed 5 days ago)
- **Milestones**: 3 milestones (all achieved)
  - ✅ Reach 10 hours
  - ✅ Reach 20 hours
  - ✅ Reach 30 hours

#### 6. Learn Machine Learning Basics (0% complete)
- **Type**: Custom
- **Status**: Active, just started
- **Target**: 10 months
- **Milestones**: 2 milestones (none achieved)
  - ⏳ Learn Python for ML
  - ⏳ Understand ML algorithms

---

### 🔖 Bookmarks (9 bookmarks)

Creates bookmarks for existing content:

- **5 Lesson bookmarks** with notes like:
  - "Important lesson to review before the final exam"
  - "Great explanation of complex concepts"
  - "Key points for the practical assignment"

- **3 Course bookmarks** with notes like:
  - "Essential course for career advancement"
  - "Recommended by colleague - must complete"
  - "Prerequisite for advanced certification"

- **2 Module bookmarks** with notes like:
  - "Core concepts module - review regularly"
  - "Advanced techniques worth revisiting"

---

## Data Relationships

### Learning Goals include:
- ✅ Multiple milestones (3-4 per goal)
- ✅ Progress logs with notes
- ✅ Related courses (where applicable)
- ✅ Related skills
- ✅ Different statuses (active, paused, achieved)
- ✅ Various goal types (skill, career, project, certification, time-based, custom)
- ✅ Different progress levels (0%, 10%, 20%, 40%, 75%, 100%)

### Features Demonstrated:
- ✅ On-track progress calculation
- ✅ Milestone auto-achievement
- ✅ Progress percentage
- ✅ Days remaining
- ✅ Goal completion
- ✅ Multiple goal statuses

---

## Testing with Seeded Data

After seeding, you can test these APIs:

### Get Learning Goals
```bash
GET /api/learning-goals?status=active
GET /api/learning-goals/1  # Specific goal details
```

**Expected**: 
- 4 active goals (React, Full-Stack, Portfolio, ML)
- 1 paused goal (AWS)
- 1 achieved goal (Study Hours)

### Get Learning Profile
```bash
GET /api/learning-profile
```

**Expected**: Visual learner profile (65% visual)

### Get Bookmarks
```bash
GET /api/bookmarks
GET /api/bookmarks?type=lesson
GET /api/bookmarks/check/lesson/1
```

**Expected**: 9 bookmarks (5 lessons, 3 courses, 2 modules)

### Track Progress
```bash
POST /api/learning-goals/1/progress
{
    "progress_value": 3,
    "note": "Completed React Hooks course!"
}
```

**Expected**: Milestone 3 auto-achieved, progress updated to 60%

---

## Seeder Order

The seeders run in this order (defined in `DatabaseSeeder.php`):

1. RolesAndPermissionsSeeder
2. CourseSeeder
3. QuizSeeder
4. LearningOutcomeSeeder
5. QuizAttemptSeeder
6. **LearningProfileSeeder** ← New
7. **LearningGoalSeeder** ← New (requires CourseSeeder)
8. **BookmarkSeeder** ← New (requires CourseSeeder)

**Note**: `BookmarkSeeder` requires courses/modules/lessons to exist, so run `CourseSeeder` first.

---

## Customization

### Change User

All seeders use the first user by default. To seed for a different user, modify:

```php
$user = User::where('email', 'specific@example.com')->first();
```

### Adjust Data

You can modify the seeders to:
- Change number of goals created
- Adjust progress percentages
- Modify learning styles
- Add more/fewer bookmarks
- Change milestone counts

---

## Verification

Check seeded data:

```bash
# Via tinker
docker-compose exec app1 php artisan tinker --execute="
echo 'Goals: ' . App\Models\LearningGoal::count() . PHP_EOL;
echo 'Profiles: ' . App\Models\LearningProfile::count() . PHP_EOL;
echo 'Bookmarks: ' . App\Models\Bookmark::count() . PHP_EOL;
"

# Via database
docker-compose exec app1 php artisan db:show
```

**Expected output**:
```
Goals: 6
Profiles: 4
Bookmarks: 9
```

---

## Notes

✅ All seeders are **idempotent** for learning profiles (use `updateOrCreate`)
✅ LearningGoalSeeder creates **realistic data** with various statuses
✅ BookmarkSeeder includes **meaningful notes** for each bookmark
✅ Data demonstrates **all SDL features** (progress tracking, milestones, achievements)
✅ Seeders work with **existing users** or create test users if needed

---

## Quick Test Workflow

```bash
# 1. Fresh database
docker-compose exec app1 php artisan migrate:fresh

# 2. Seed all data
docker-compose exec app1 php artisan db:seed

# 3. Test API
curl http://localhost:8080/api/learning-goals \
  -H "Authorization: Bearer YOUR_TOKEN"

# 4. Verify in Postman
# Import: tests/postman/SDL_API_Tests.postman_collection.json
# Test all endpoints with seeded data
```

---

**Ready to use!** 🚀 The seeders create comprehensive test data for all SDL features.
