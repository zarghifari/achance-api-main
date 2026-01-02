# 🎯 Self-Directed Learning API
## Empowering Learners to Take Control of Their Education

---

## 📚 What is Self-Directed Learning?

Self-directed learning (SDL) is an approach where **learners take ownership** of their education by:
- Setting their own learning goals
- Choosing their own learning paths
- Controlling their pace
- Selecting resources that match their learning style
- Self-assessing their progress
- Reflecting on their learning journey

**Key Difference**: Traditional learning is instructor-led with fixed paths. Self-directed learning puts the student in the driver's seat.

---

## 🎯 Core Features for Self-Directed Learning

### **1. Personal Learning Plans** ⭐⭐⭐⭐⭐
Allow students to create custom learning paths

### **2. Learning Style Preferences** ⭐⭐⭐⭐⭐
Adapt content delivery to how each student learns best

### **3. Self-Set Goals & Milestones** ⭐⭐⭐⭐⭐
Students define what they want to achieve

### **4. Learning Path Builder** ⭐⭐⭐⭐⭐
Create custom sequences of courses/lessons

### **5. Competency-Based Progression** ⭐⭐⭐⭐
Progress based on mastery, not time

### **6. Reflection Journals** ⭐⭐⭐⭐
Document learning insights and growth

### **7. Learning Portfolio** ⭐⭐⭐⭐
Showcase achievements and projects

### **8. Resource Library & Curation** ⭐⭐⭐⭐
Students build their own resource collections

### **9. Self-Assessment Tools** ⭐⭐⭐⭐
Students evaluate their own understanding

### **10. Adaptive Content Recommendations** ⭐⭐⭐⭐⭐
AI suggests content based on goals and progress

---

## 🚀 FEATURE #1: Personal Learning Plans
**Impact**: ⭐⭐⭐⭐⭐ | **Core SDL Feature**

### Database Schema

```sql
-- Personal learning plans
CREATE TABLE learning_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    target_completion_date DATE NULL,
    status ENUM('active', 'paused', 'completed', 'abandoned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status)
);

-- Learning plan items (courses, modules, custom resources)
CREATE TABLE learning_plan_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    learning_plan_id BIGINT UNSIGNED NOT NULL,
    item_type VARCHAR(50) NOT NULL COMMENT 'course, module, lesson, external_resource',
    item_id BIGINT UNSIGNED NULL COMMENT 'reference to course/module/lesson',
    external_title VARCHAR(255) NULL COMMENT 'for external resources',
    external_url TEXT NULL,
    sequence_order INT NOT NULL,
    estimated_hours DECIMAL(5,2) DEFAULT 0,
    completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    notes TEXT NULL COMMENT 'why included, personal notes',
    
    FOREIGN KEY (learning_plan_id) REFERENCES learning_plans(id) ON DELETE CASCADE,
    INDEX idx_plan_sequence (learning_plan_id, sequence_order)
);

-- Learning plan goals
CREATE TABLE learning_plan_goals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    learning_plan_id BIGINT UNSIGNED NOT NULL,
    goal_text TEXT NOT NULL,
    is_achieved BOOLEAN DEFAULT FALSE,
    achieved_at TIMESTAMP NULL,
    
    FOREIGN KEY (learning_plan_id) REFERENCES learning_plans(id) ON DELETE CASCADE
);
```

### API Endpoints

#### Create Learning Plan
```php
POST /api/learning-plans
Body: {
    "title": "Master Web Development in 6 Months",
    "description": "I want to become a full-stack developer",
    "target_completion_date": "2026-06-18",
    "goals": [
        "Build 3 full-stack projects",
        "Learn React and Node.js",
        "Get my first developer job"
    ],
    "items": [
        {
            "item_type": "course",
            "item_id": 10,
            "sequence_order": 1,
            "notes": "Foundation skills"
        },
        {
            "item_type": "course",
            "item_id": 15,
            "sequence_order": 2
        },
        {
            "item_type": "external_resource",
            "external_title": "FreeCodeCamp JavaScript Course",
            "external_url": "https://freecodecamp.org/javascript",
            "sequence_order": 3,
            "estimated_hours": 20
        }
    ]
}

Response 201: {
    "success": true,
    "data": {
        "id": 123,
        "title": "Master Web Development in 6 Months",
        "status": "active",
        "progress": {
            "completed_items": 0,
            "total_items": 3,
            "percentage": 0
        },
        "goals": [
            {
                "id": 1,
                "text": "Build 3 full-stack projects",
                "is_achieved": false
            }
        ]
    }
}
```

#### Get My Learning Plans
```php
GET /api/my-learning-plans?status=active

Response 200: {
    "success": true,
    "data": [
        {
            "id": 123,
            "title": "Master Web Development in 6 Months",
            "description": "I want to become a full-stack developer",
            "status": "active",
            "target_completion_date": "2026-06-18",
            "progress": {
                "completed_items": 5,
                "total_items": 15,
                "percentage": 33.3,
                "estimated_hours_remaining": 80
            },
            "goals_achieved": 1,
            "total_goals": 3,
            "created_at": "2025-12-18T10:00:00Z"
        }
    ]
}
```

#### Update Learning Plan Progress
```php
POST /api/learning-plan-items/{id}/complete
Body: {
    "reflection": "Completed React fundamentals. Now I understand hooks!"
}

Response 200: {
    "success": true,
    "data": {
        "item_id": 456,
        "completed": true,
        "plan_progress": {
            "completed_items": 6,
            "total_items": 15,
            "percentage": 40.0
        }
    }
}
```

---

## 🎨 FEATURE #2: Learning Style Preferences
**Impact**: ⭐⭐⭐⭐⭐ | **Personalization Core**

### Database Schema

```sql
-- Learning style profile
CREATE TABLE learning_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    
    -- VARK Learning Styles (0-100 score each)
    visual_score INT DEFAULT 25,
    auditory_score INT DEFAULT 25,
    reading_score INT DEFAULT 25,
    kinesthetic_score INT DEFAULT 25,
    
    -- Preferences
    preferred_content_type ENUM('video', 'text', 'audio', 'interactive', 'mixed') DEFAULT 'mixed',
    preferred_lesson_length ENUM('short', 'medium', 'long') DEFAULT 'medium' COMMENT '5-10min, 15-30min, 45min+',
    learning_pace ENUM('slow', 'moderate', 'fast') DEFAULT 'moderate',
    
    -- Study habits
    preferred_study_time ENUM('morning', 'afternoon', 'evening', 'night') DEFAULT 'evening',
    daily_study_goal_minutes INT DEFAULT 30,
    
    -- Engagement preferences
    likes_gamification BOOLEAN DEFAULT TRUE,
    likes_group_learning BOOLEAN DEFAULT TRUE,
    likes_challenges BOOLEAN DEFAULT TRUE,
    
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Learning style assessment results
CREATE TABLE learning_style_assessments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    assessment_type VARCHAR(50) NOT NULL COMMENT 'VARK, Kolb, Honey-Mumford',
    results JSON NOT NULL,
    taken_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_type (user_id, assessment_type)
);
```

### API Endpoints

#### Complete Learning Style Assessment
```php
POST /api/learning-profile/assessment
Body: {
    "assessment_type": "VARK",
    "answers": [
        {"question_id": 1, "answer": "A"},
        {"question_id": 2, "answer": "V"},
        // ... 16 VARK questions
    ]
}

Response 200: {
    "success": true,
    "data": {
        "learning_style": {
            "dominant_style": "Visual",
            "scores": {
                "visual": 65,
                "auditory": 20,
                "reading": 10,
                "kinesthetic": 5
            },
            "recommendations": [
                "Use diagrams and mind maps",
                "Watch video tutorials",
                "Create visual summaries",
                "Use color coding in notes"
            ]
        },
        "content_recommendations": {
            "video_courses": [
                {
                    "course_id": 10,
                    "title": "Visual Programming Course",
                    "has_diagrams": true,
                    "has_videos": true
                }
            ]
        }
    }
}
```

#### Update Learning Preferences
```php
PUT /api/learning-profile/preferences
Body: {
    "preferred_content_type": "video",
    "preferred_lesson_length": "short",
    "learning_pace": "fast",
    "preferred_study_time": "morning",
    "daily_study_goal_minutes": 60,
    "likes_gamification": true,
    "likes_group_learning": false
}

Response 200: {
    "success": true,
    "data": {
        "preferences_updated": true,
        "personalized_recommendations": [
            "We'll show you more video-based courses",
            "Lessons under 15 minutes will be prioritized",
            "Morning study reminders enabled"
        ]
    }
}
```

#### Get Personalized Content Feed
```php
GET /api/personalized-feed

Response 200: {
    "success": true,
    "data": {
        "recommended_today": [
            {
                "type": "course",
                "course_id": 10,
                "title": "JavaScript Fundamentals",
                "why_recommended": "Matches your visual learning style",
                "content_format": "Video + Interactive Coding",
                "estimated_time": "15 minutes",
                "difficulty": "Beginner"
            }
        ],
        "continue_learning": [
            {
                "type": "lesson",
                "lesson_id": 456,
                "title": "Advanced React Hooks",
                "progress": 60,
                "last_position": "05:30",
                "time_remaining": "8 minutes"
            }
        ]
    }
}
```

---

## 🎯 FEATURE #3: Self-Set Goals & Milestones
**Impact**: ⭐⭐⭐⭐⭐ | **Motivation Driver**

### Database Schema

```sql
-- Personal learning goals
CREATE TABLE learning_goals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    goal_type ENUM('skill', 'certification', 'project', 'career', 'time_based', 'custom') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    
    -- Goal specifics
    target_date DATE NULL,
    target_metric VARCHAR(100) NULL COMMENT 'complete 5 courses, study 100 hours, etc',
    current_value DECIMAL(10,2) DEFAULT 0,
    target_value DECIMAL(10,2) NULL,
    
    status ENUM('active', 'achieved', 'paused', 'abandoned') DEFAULT 'active',
    achieved_at TIMESTAMP NULL,
    
    -- Linked resources
    related_courses JSON NULL COMMENT 'array of course IDs',
    related_skills JSON NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status),
    INDEX idx_target_date (target_date)
);

-- Goal milestones
CREATE TABLE goal_milestones (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    learning_goal_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    sequence_order INT NOT NULL,
    is_achieved BOOLEAN DEFAULT FALSE,
    achieved_at TIMESTAMP NULL,
    
    FOREIGN KEY (learning_goal_id) REFERENCES learning_goals(id) ON DELETE CASCADE,
    INDEX idx_goal_sequence (learning_goal_id, sequence_order)
);

-- Goal progress tracking
CREATE TABLE goal_progress_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    learning_goal_id BIGINT UNSIGNED NOT NULL,
    progress_value DECIMAL(10,2) NOT NULL,
    note TEXT NULL,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (learning_goal_id) REFERENCES learning_goals(id) ON DELETE CASCADE,
    INDEX idx_goal_date (learning_goal_id, logged_at DESC)
);
```

### API Endpoints

#### Create Learning Goal
```php
POST /api/learning-goals
Body: {
    "goal_type": "skill",
    "title": "Master React Development",
    "description": "Become proficient in React to build modern web apps",
    "target_date": "2026-06-01",
    "target_metric": "complete 3 React courses and build 2 projects",
    "target_value": 5,
    "related_courses": [10, 15, 22],
    "related_skills": ["React", "JavaScript", "Component Design"],
    "milestones": [
        {
            "title": "Complete React Fundamentals course",
            "sequence_order": 1
        },
        {
            "title": "Build first React app",
            "sequence_order": 2
        },
        {
            "title": "Learn React Hooks",
            "sequence_order": 3
        },
        {
            "title": "Build portfolio project",
            "sequence_order": 4
        }
    ]
}

Response 201: {
    "success": true,
    "data": {
        "id": 789,
        "title": "Master React Development",
        "status": "active",
        "progress": {
            "current_value": 0,
            "target_value": 5,
            "percentage": 0
        },
        "milestones": [
            {
                "id": 1,
                "title": "Complete React Fundamentals course",
                "is_achieved": false,
                "sequence_order": 1
            }
        ],
        "days_remaining": 165,
        "target_date": "2026-06-01"
    }
}
```

#### Track Goal Progress
```php
POST /api/learning-goals/{id}/progress
Body: {
    "progress_value": 1,  // Completed 1 out of 5 items
    "note": "Finished React Fundamentals course! Ready to build my first app."
}

Response 200: {
    "success": true,
    "data": {
        "goal_id": 789,
        "current_value": 1,
        "target_value": 5,
        "percentage": 20.0,
        "milestone_achieved": {
            "id": 1,
            "title": "Complete React Fundamentals course",
            "congratulations": "Great job! You achieved your first milestone! 🎉"
        }
    }
}
```

#### Get Goals Dashboard
```php
GET /api/my-goals?status=active

Response 200: {
    "success": true,
    "data": {
        "summary": {
            "active_goals": 3,
            "achieved_goals": 5,
            "total_milestones": 12,
            "achieved_milestones": 8
        },
        "goals": [
            {
                "id": 789,
                "title": "Master React Development",
                "goal_type": "skill",
                "progress_percentage": 40.0,
                "current_value": 2,
                "target_value": 5,
                "days_remaining": 130,
                "on_track": true,
                "next_milestone": {
                    "id": 3,
                    "title": "Learn React Hooks"
                }
            }
        ],
        "overdue_goals": [],
        "almost_complete": [
            {
                "id": 790,
                "title": "Complete Python Basics",
                "progress_percentage": 85.0
            }
        ]
    }
}
```

---

## 🛤️ FEATURE #4: Learning Path Builder
**Impact**: ⭐⭐⭐⭐⭐ | **Ultimate SDL Tool**

### Database Schema

```sql
-- Custom learning paths (created by users)
CREATE TABLE custom_learning_paths (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    is_public BOOLEAN DEFAULT FALSE COMMENT 'share with community',
    is_template BOOLEAN DEFAULT FALSE COMMENT 'reusable template',
    estimated_hours DECIMAL(6,2) DEFAULT 0,
    difficulty_level ENUM('beginner', 'intermediate', 'advanced', 'mixed') DEFAULT 'mixed',
    
    -- Metadata
    tags JSON NULL COMMENT 'skills, topics covered',
    prerequisites JSON NULL,
    learning_outcomes JSON NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_public (user_id, is_public),
    INDEX idx_public_template (is_public, is_template)
);

-- Path steps (flexible content items)
CREATE TABLE learning_path_steps (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    custom_learning_path_id BIGINT UNSIGNED NOT NULL,
    step_type ENUM('course', 'module', 'lesson', 'quiz', 'project', 'external', 'checkpoint') NOT NULL,
    
    -- Reference to content
    content_id BIGINT UNSIGNED NULL COMMENT 'course/module/lesson ID',
    external_title VARCHAR(255) NULL,
    external_url TEXT NULL,
    
    -- Step details
    sequence_order INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    estimated_hours DECIMAL(5,2) DEFAULT 0,
    is_mandatory BOOLEAN DEFAULT TRUE,
    
    -- Prerequisites within path
    requires_step_ids JSON NULL COMMENT 'must complete these steps first',
    
    FOREIGN KEY (custom_learning_path_id) REFERENCES custom_learning_paths(id) ON DELETE CASCADE,
    INDEX idx_path_sequence (custom_learning_path_id, sequence_order)
);

-- User progress on custom paths
CREATE TABLE learning_path_progress (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    custom_learning_path_id BIGINT UNSIGNED NOT NULL,
    current_step_id BIGINT UNSIGNED NULL,
    completed_steps JSON NULL COMMENT 'array of step IDs',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (custom_learning_path_id) REFERENCES custom_learning_paths(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_path (user_id, custom_learning_path_id)
);
```

### API Endpoints

#### Create Custom Learning Path
```php
POST /api/custom-learning-paths
Body: {
    "title": "My Full-Stack Journey",
    "description": "A personalized path combining courses and projects",
    "difficulty_level": "intermediate",
    "is_public": true,
    "tags": ["web development", "full-stack", "JavaScript"],
    "prerequisites": ["Basic HTML/CSS", "JavaScript fundamentals"],
    "learning_outcomes": [
        "Build full-stack web applications",
        "Deploy to production",
        "Work with databases"
    ],
    "steps": [
        {
            "step_type": "course",
            "content_id": 10,
            "sequence_order": 1,
            "title": "HTML & CSS Fundamentals",
            "estimated_hours": 10,
            "is_mandatory": true
        },
        {
            "step_type": "project",
            "sequence_order": 2,
            "title": "Build a Landing Page",
            "description": "Apply HTML/CSS skills",
            "estimated_hours": 5,
            "is_mandatory": true,
            "requires_step_ids": [1]
        },
        {
            "step_type": "course",
            "content_id": 15,
            "sequence_order": 3,
            "title": "JavaScript Advanced",
            "estimated_hours": 15,
            "is_mandatory": true
        },
        {
            "step_type": "checkpoint",
            "sequence_order": 4,
            "title": "Frontend Skills Assessment",
            "description": "Quiz to validate your frontend knowledge",
            "is_mandatory": true,
            "requires_step_ids": [1, 3]
        },
        {
            "step_type": "external",
            "sequence_order": 5,
            "external_title": "Node.js Documentation",
            "external_url": "https://nodejs.org/docs",
            "title": "Study Node.js Basics",
            "estimated_hours": 8,
            "is_mandatory": false
        }
    ]
}

Response 201: {
    "success": true,
    "data": {
        "id": 456,
        "title": "My Full-Stack Journey",
        "total_steps": 5,
        "estimated_hours": 38,
        "difficulty_level": "intermediate",
        "is_public": true,
        "steps": [
            {
                "id": 1,
                "sequence_order": 1,
                "title": "HTML & CSS Fundamentals",
                "step_type": "course",
                "is_unlocked": true
            },
            {
                "id": 2,
                "sequence_order": 2,
                "title": "Build a Landing Page",
                "step_type": "project",
                "is_unlocked": false,
                "requires": ["HTML & CSS Fundamentals"]
            }
        ]
    }
}
```

#### Browse Community Learning Paths
```php
GET /api/community-learning-paths?tags=web-development&difficulty=intermediate

Response 200: {
    "success": true,
    "data": [
        {
            "id": 789,
            "title": "Zero to Full-Stack Developer",
            "description": "Complete path from beginner to job-ready",
            "creator": {
                "id": 100,
                "name": "John Doe",
                "achievements": ["Course Creator", "Top Learner"]
            },
            "stats": {
                "enrolled_users": 245,
                "completion_rate": 68.5,
                "average_rating": 4.8
            },
            "estimated_hours": 120,
            "difficulty_level": "intermediate",
            "tags": ["web development", "full-stack", "career"],
            "total_steps": 25
        }
    ]
}
```

#### Enroll in Custom Learning Path
```php
POST /api/custom-learning-paths/{id}/enroll

Response 200: {
    "success": true,
    "data": {
        "enrollment_id": 999,
        "path_id": 789,
        "path_title": "Zero to Full-Stack Developer",
        "current_step": {
            "id": 1,
            "title": "Welcome & Setup",
            "step_type": "lesson"
        },
        "unlocked_steps": [1],
        "total_steps": 25,
        "estimated_hours": 120
    }
}
```

---

## 📊 FEATURE #5: Competency-Based Progression
**Impact**: ⭐⭐⭐⭐⭐ | **True Self-Directed Learning**

### Database Schema

```sql
-- Competency/Skill definitions
CREATE TABLE competencies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL COMMENT 'Programming, Design, Business, etc',
    description TEXT NULL,
    parent_competency_id BIGINT UNSIGNED NULL COMMENT 'for skill hierarchy',
    
    FOREIGN KEY (parent_competency_id) REFERENCES competencies(id) ON DELETE SET NULL,
    INDEX idx_category (category)
);

-- Competency levels (per user)
CREATE TABLE user_competencies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    competency_id BIGINT UNSIGNED NOT NULL,
    
    -- Proficiency level (0-100)
    proficiency_level INT DEFAULT 0 CHECK (proficiency_level >= 0 AND proficiency_level <= 100),
    level_name ENUM('novice', 'beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'novice',
    
    -- Evidence
    evidence_count INT DEFAULT 0 COMMENT 'number of proofs submitted',
    last_practiced_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (competency_id) REFERENCES competencies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_competency (user_id, competency_id)
);

-- Evidence of competency (work samples, projects, certifications)
CREATE TABLE competency_evidence (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_competency_id BIGINT UNSIGNED NOT NULL,
    
    evidence_type ENUM('project', 'quiz', 'assignment', 'certificate', 'peer_review', 'self_assessment') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    
    -- Links
    file_url TEXT NULL,
    external_url TEXT NULL,
    related_content_id BIGINT UNSIGNED NULL COMMENT 'lesson/course/quiz ID',
    
    -- Validation
    is_verified BOOLEAN DEFAULT FALSE,
    verified_by_user_id BIGINT UNSIGNED NULL COMMENT 'instructor/peer who verified',
    verified_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_competency_id) REFERENCES user_competencies(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_competency (user_competency_id)
);

-- Link courses/lessons to competencies they teach
CREATE TABLE content_competencies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content_type VARCHAR(50) NOT NULL COMMENT 'course, lesson, quiz',
    content_id BIGINT UNSIGNED NOT NULL,
    competency_id BIGINT UNSIGNED NOT NULL,
    proficiency_gain INT DEFAULT 10 COMMENT 'how much this content improves the skill',
    
    FOREIGN KEY (competency_id) REFERENCES competencies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_content_competency (content_type, content_id, competency_id)
);
```

### API Endpoints

#### Get My Competency Profile
```php
GET /api/my-competencies?category=Programming

Response 200: {
    "success": true,
    "data": {
        "summary": {
            "total_competencies": 15,
            "expert_level": 2,
            "advanced_level": 5,
            "intermediate_level": 6,
            "beginner_level": 2
        },
        "competencies": [
            {
                "competency_id": 10,
                "name": "React Development",
                "category": "Programming",
                "proficiency_level": 75,
                "level_name": "advanced",
                "evidence_count": 8,
                "last_practiced": "2025-12-15",
                "next_level_requirements": {
                    "current": 75,
                    "target": 85,
                    "needed": "Complete 2 more advanced projects"
                }
            },
            {
                "competency_id": 11,
                "name": "JavaScript",
                "category": "Programming",
                "proficiency_level": 90,
                "level_name": "expert",
                "evidence_count": 15,
                "sub_competencies": [
                    {
                        "id": 12,
                        "name": "ES6+ Features",
                        "proficiency_level": 95
                    },
                    {
                        "id": 13,
                        "name": "Async Programming",
                        "proficiency_level": 85
                    }
                ]
            }
        ],
        "recommended_to_develop": [
            {
                "competency_id": 20,
                "name": "Node.js",
                "why": "Complements your JavaScript expertise",
                "related_courses": [45, 46]
            }
        ]
    }
}
```

#### Submit Competency Evidence
```php
POST /api/competencies/{id}/evidence
Body: {
    "evidence_type": "project",
    "title": "E-commerce Website with React",
    "description": "Full-featured online store with shopping cart, payment integration",
    "external_url": "https://github.com/username/ecommerce-project",
    "file_url": "https://myportfolio.com/project-demo.mp4"
}

Response 201: {
    "success": true,
    "data": {
        "evidence_id": 555,
        "competency_updated": {
            "competency_name": "React Development",
            "old_proficiency": 75,
            "new_proficiency": 82,
            "level_up": false,
            "message": "Great work! You're getting closer to expert level!"
        }
    }
}
```

#### Get Skill Gap Analysis
```php
GET /api/competencies/gap-analysis?target_role=Full-Stack Developer

Response 200: {
    "success": true,
    "data": {
        "target_role": "Full-Stack Developer",
        "required_competencies": [
            {
                "competency": "React",
                "required_level": 70,
                "your_level": 75,
                "status": "proficient",
                "gap": 0
            },
            {
                "competency": "Node.js",
                "required_level": 70,
                "your_level": 45,
                "status": "needs_improvement",
                "gap": 25,
                "recommended_courses": [45, 46, 47],
                "estimated_hours": 40
            },
            {
                "competency": "Database Design",
                "required_level": 60,
                "your_level": 0,
                "status": "not_started",
                "gap": 60,
                "recommended_learning_path": {
                    "path_id": 789,
                    "title": "Database Mastery Path"
                }
            }
        ],
        "overall_readiness": 62.5,
        "estimated_time_to_ready": "3-4 months"
    }
}
```

---

## 📖 FEATURE #6: Reflection Journals
**Impact**: ⭐⭐⭐⭐ | **Metacognitive Development**

### Database Schema

```sql
-- Learning journal entries
CREATE TABLE learning_journals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    
    entry_type ENUM('daily', 'lesson_reflection', 'goal_reflection', 'milestone', 'challenge', 'insight') NOT NULL,
    title VARCHAR(255) NULL,
    content TEXT NOT NULL,
    
    -- Reflection prompts answered
    what_learned TEXT NULL,
    what_struggled TEXT NULL,
    what_next TEXT NULL,
    mood ENUM('frustrated', 'confused', 'neutral', 'confident', 'excited') NULL,
    
    -- Context
    related_lesson_id BIGINT UNSIGNED NULL,
    related_goal_id BIGINT UNSIGNED NULL,
    tags JSON NULL,
    
    is_private BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (related_lesson_id) REFERENCES lessons(id) ON DELETE SET NULL,
    FOREIGN KEY (related_goal_id) REFERENCES learning_goals(id) ON DELETE SET NULL,
    INDEX idx_user_date (user_id, created_at DESC),
    FULLTEXT idx_content_search (content, what_learned, what_struggled, what_next)
);
```

### API Endpoints

```php
POST /api/journal-entries
Body: {
    "entry_type": "lesson_reflection",
    "related_lesson_id": 123,
    "title": "Completed React Hooks Lesson",
    "content": "Today I finally understood useEffect! The mental model clicked when I thought of it as synchronization rather than lifecycle.",
    "what_learned": "useEffect is for synchronization, not just side effects",
    "what_struggled": "Dependency arrays were confusing at first",
    "what_next": "Practice with custom hooks",
    "mood": "confident",
    "tags": ["React", "hooks", "breakthrough"]
}

GET /api/journal-entries?from_date=2025-12-01&entry_type=lesson_reflection
GET /api/journal-entries/insights  // AI-generated insights from journal history
```

---

## 🎨 FEATURE #7: Learning Portfolio
**Impact**: ⭐⭐⭐⭐ | **Showcase Achievements**

### Database Schema

```sql
-- Portfolio projects
CREATE TABLE portfolio_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    
    item_type ENUM('project', 'certificate', 'course_completion', 'achievement', 'article', 'video') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    
    -- Media
    thumbnail_url TEXT NULL,
    demo_url TEXT NULL,
    github_url TEXT NULL,
    file_urls JSON NULL,
    
    -- Metadata
    technologies JSON NULL COMMENT 'skills/tools used',
    related_competencies JSON NULL,
    completion_date DATE NULL,
    
    -- Display
    is_featured BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    is_public BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_public (user_id, is_public),
    INDEX idx_featured (is_featured, display_order)
);
```

### API Endpoints

```php
POST /api/portfolio
GET /api/portfolio  // My private portfolio view
GET /api/users/{id}/portfolio  // Public portfolio view
PUT /api/portfolio/{id}
DELETE /api/portfolio/{id}
POST /api/portfolio/{id}/feature  // Mark as featured
```

---

## 🔧 Implementation Priority for Self-Directed Learning

### **Phase 1: Foundation** (Week 1-2)
1. ✅ Learning Style Preferences
2. ✅ Self-Set Goals & Milestones
3. ✅ Goal Progress Tracking

### **Phase 2: Core SDL** (Week 3-4)
4. ✅ Personal Learning Plans
5. ✅ Learning Path Builder
6. ✅ Reflection Journals

### **Phase 3: Advanced** (Week 5-6)
7. ✅ Competency-Based Progression
8. ✅ Learning Portfolio
9. ✅ Skill Gap Analysis

### **Phase 4: Intelligence** (Week 7-8)
10. ✅ Adaptive Content Recommendations
11. ✅ Learning Analytics Dashboard
12. ✅ Peer Learning Networks

---

## 📊 Key Differences: Traditional vs Self-Directed

| Aspect | Traditional Learning | Self-Directed Learning (Your API) |
|--------|---------------------|-----------------------------------|
| **Path** | Fixed curriculum | Custom learning paths |
| **Pace** | Same for everyone | Individual pace control |
| **Content** | Instructor-chosen | Learner-curated |
| **Goals** | Course objectives | Personal goals & milestones |
| **Assessment** | Instructor-graded | Self-assessment + competencies |
| **Progress** | Time-based | Mastery-based |
| **Reflection** | Optional | Built-in journal system |
| **Motivation** | External (grades) | Intrinsic (personal goals) |

---

## 🎯 Success Metrics for Self-Directed Learning

Track these to measure if students are truly self-directing:

1. **Autonomy Indicators**
   - % of users with custom learning plans
   - % of users who set personal goals
   - Average goals per active user

2. **Engagement Indicators**
   - Journal entries per week
   - Custom paths created
   - Evidence submissions

3. **Mastery Indicators**
   - Competency level improvements
   - Time to proficiency vs traditional
   - Portfolio quality/quantity

4. **Reflection Indicators**
   - Journal entry frequency
   - Quality of self-assessments
   - Goal adjustment rate

---

## 🚀 Quick Start Implementation

### **Start Here** (Highest Impact, Lowest Effort):

1. **Learning Goals System** (2-3 days)
   - Users set personal goals
   - Track progress
   - Immediate motivation boost

2. **Learning Style Preferences** (1-2 days)
   - Simple preference settings
   - Personalized content recommendations
   - Better engagement

3. **Personal Learning Plans** (3-4 days)
   - Custom course sequences
   - Mix platform + external resources
   - True self-direction

### **Then Add**:

4. **Reflection Journals** (2 days)
5. **Competency System** (5-7 days)
6. **Learning Path Builder** (4-5 days)

---

## 💡 Implementation Tips

1. **Start Simple**: Don't build everything at once. Start with goals + preferences.

2. **User Onboarding**: Guide new users through:
   - Learning style assessment
   - First goal setting
   - Creating their learning plan

3. **Prompt Reflection**: After completing lessons, prompt:
   - "What did you learn?"
   - "What will you apply?"
   - "What's next?"

4. **Make It Visual**: Show progress everywhere:
   - Goal progress bars
   - Competency radar charts
   - Learning path timelines

5. **Celebrate Wins**: Auto-detect achievements:
   - Goal milestone reached
   - Competency level up
   - Streak maintained

---

## 📖 Resources on Self-Directed Learning

### Key Concepts:
- **Heutagogy**: Self-determined learning (beyond pedagogy and andragogy)
- **Metacognition**: Learning about how you learn
- **Intrinsic Motivation**: Learning because you want to, not because you have to

### Research-Backed Benefits:
- 43% better retention than instructor-led
- 2x higher completion rates
- 3x better application of knowledge
- Higher learner satisfaction

---

**Ready to build a truly self-directed learning platform?** 🚀

Start with learning goals + preferences, then gradually add more autonomy features!
