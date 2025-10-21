# Enhanced Quiz System Documentation

## Overview

The quiz system has been significantly enhanced with enterprise-level features including advanced caching, comprehensive analytics, event-driven architecture, and robust security measures.

## Key Improvements

### 1. Performance & Scalability
- **Advanced Caching**: Multi-layer caching with automatic invalidation
- **Database Optimization**: Strategic indexes for faster queries
- **Bulk Operations**: Efficient batch processing for quiz attempts
- **N+1 Query Elimination**: Optimized database queries

### 2. Security & Authorization
- **Policy-Based Authorization**: Comprehensive access control
- **Rate Limiting**: Protection against spam attempts
- **Input Validation**: Enhanced request validation with business rules
- **Audit Logging**: Comprehensive operation logging

### 3. Event-Driven Architecture
- **Quiz Events**: Automatic event firing for quiz lifecycle
- **Event Listeners**: Extensible system for handling quiz completion
- **Background Processing**: Queue-based processing for heavy operations

### 4. Analytics & Reporting
- **Quiz Statistics**: Comprehensive quiz performance metrics
- **Leaderboards**: Real-time quiz rankings
- **User Analytics**: Individual performance tracking
- **Learning Outcomes Integration**: Assessment mapping

## New Features

### Enhanced Models

#### Quiz Model
```php
// New status checking methods
$quiz->isActive();        // Check if quiz is currently active
$quiz->isUpcoming();      // Check if quiz is upcoming
$quiz->hasEnded();        // Check if quiz has ended
$quiz->getTimeRemainingAttribute(); // Get time remaining

// New scopes
Quiz::active()->get();    // Get active quizzes
Quiz::upcoming()->get();  // Get upcoming quizzes
Quiz::ended()->get();     // Get ended quizzes
```

#### QuizQuestion Model
```php
// Question type constants
QuizQuestion::TYPE_MULTIPLE_CHOICE
QuizQuestion::TYPE_TRUE_FALSE
QuizQuestion::TYPE_SHORT_ANSWER
QuizQuestion::TYPE_MULTIPLE_CORRECT_CHOICE

// New methods
$question->isMultipleChoice();
$question->getCorrectAnswers();
$question->validateAnswer($answerId);
$question->getStatistics();
```

### New Services

#### QuizCacheService
```php
// Cache management
QuizCacheService::getQuiz($quizId);
QuizCacheService::getQuizWithQuestionsAndAnswers($quizId);
QuizCacheService::getQuizStatistics($quizId);
QuizCacheService::invalidateQuizCache($quizId);
QuizCacheService::warmUpQuizCache($quizId);
```

#### QuizService
```php
// Business logic
$quizService->createAttemptWithAnswers($user, $quizId, $data);
$quizService->canUserTakeQuiz($user, $quiz);
$quizService->getQuizLeaderboard($quizId);
$quizService->getQuizStatistics($quizId);
```

### New API Endpoints

```http
# Enhanced quiz attempt endpoints
POST   /api/quizzes/{quiz_id}/attempts              # Create attempt with answers
POST   /api/quizzes/{quiz_id}/simple-attempts       # Create simple attempt
GET    /api/quizzes/{quiz_id}/attempts               # List user attempts
GET    /api/quizzes/{quiz_id}/attempts/{id}          # Get attempt details
PUT    /api/quizzes/{quiz_id}/attempts/{id}          # Update attempt
DELETE /api/quizzes/{quiz_id}/attempts/{id}          # Delete attempt (admin only)

# New analytics endpoints
GET    /api/quizzes/{quiz_id}/leaderboard            # Quiz leaderboard
GET    /api/quizzes/{quiz_id}/statistics             # Quiz statistics
GET    /api/quizzes/{quiz_id}/access-check           # Check quiz access
```

### Events & Listeners

#### Events
- `QuizAttemptStarted`: Fired when a quiz attempt begins
- `QuizAttemptCompleted`: Fired when a quiz attempt is completed/failed

#### Listeners
- `HandleQuizAttemptCompleted`: Processes quiz completion (notifications, certificates, etc.)

## Installation & Setup

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed the Database
```bash
# Basic seeding
php artisan quiz:seed

# Fresh installation with demo data
php artisan quiz:seed --fresh --demo

# Basic seeding only (no attempts)
php artisan quiz:seed --basic
```

### 3. Warm Up Caches
```bash
php artisan quiz:cache-warmup
```

## Usage Examples

### Creating a Quiz Attempt
```php
// Through the controller (with validation and events)
POST /api/quizzes/1/attempts
{
  "started_at": "2025-08-26T10:00:00Z",
  "attempt_answers": [
    {
      "selected_answer_id": 1,
      "answer_text": null
    },
    {
      "selected_answer_id": 5,
      "answer_text": null
    }
  ]
}
```

### Checking Quiz Access
```php
GET /api/quizzes/1/access-check

Response:
{
  "data": {
    "can_access": true,
    "reasons": [],
    "existing_attempt": null
  }
}
```

### Getting Quiz Statistics
```php
GET /api/quizzes/1/statistics

Response:
{
  "data": {
    "total_attempts": 45,
    "completed_attempts": 38,
    "failed_attempts": 7,
    "in_progress_attempts": 0,
    "average_score": 73.5,
    "highest_score": 100,
    "lowest_score": 20
  }
}
```

### Getting Leaderboard
```php
GET /api/quizzes/1/leaderboard

Response:
{
  "data": [
    {
      "id": 15,
      "user": {
        "id": 3,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "score": 100,
      "completed_at": "2025-08-26T14:30:00Z"
    }
  ]
}
```

## Configuration

### Cache Configuration
The system uses Redis for caching by default. Configure in `.env`:
```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Queue Configuration
For background processing, configure queues:
```env
QUEUE_CONNECTION=redis
```

Run queue workers:
```bash
php artisan queue:work
```

### Rate Limiting
Quiz attempt rate limiting is configured in the middleware:
- 5 attempts per minute per user per quiz
- Configurable in `RateLimitQuizAttempts` middleware

## Performance Monitoring

### Cache Statistics
```bash
php artisan quiz:cache-warmup --stats
```

### Database Indexes
The system includes optimized indexes for:
- Quiz lookups by status and timing
- Attempt queries by user and quiz
- Answer validation queries
- Learning outcome relationships

## Security Features

### Authorization Policies
- `AttemptQuizPolicy`: Controls quiz attempt access
- Checks quiz timing, user permissions, existing attempts
- Prevents unauthorized access to quiz data

### Input Validation
- Comprehensive request validation
- Business rule validation (no duplicate answers, valid quiz timing)
- XSS and injection protection

### Rate Limiting
- Per-user, per-quiz rate limiting
- Configurable limits and time windows
- Automatic cleanup of rate limit data

## Extensibility

### Adding New Question Types
1. Add type constant to `QuizQuestion` model
2. Update validation in `AttemptQuizWithAnswerRequest`
3. Add handling in `QuizService`
4. Update frontend components

### Adding New Events
1. Create event class extending base event
2. Add listener for the event
3. Register in `EventServiceProvider`
4. Fire event in appropriate service methods

### Custom Analytics
1. Extend `QuizService` with new statistics methods
2. Add caching for complex calculations
3. Create new API endpoints for custom metrics

## Troubleshooting

### Common Issues

1. **Cache not updating**: Run `php artisan cache:clear` and `php artisan quiz:cache-warmup`
2. **Events not firing**: Check queue worker is running: `php artisan queue:work`
3. **Permission errors**: Verify user has proper roles and permissions
4. **Rate limiting**: Check if user is hitting rate limits, adjust middleware settings

### Debug Commands
```bash
# Check quiz cache status
php artisan quiz:cache-warmup --stats

# Clear all caches
php artisan cache:clear

# View failed jobs
php artisan queue:failed

# Monitor queue status
php artisan queue:monitor
```

## Demo Data

The system includes comprehensive demo data:

### Demo Users
- `demo.student.high@example.com` - High performer
- `demo.student.avg@example.com` - Average performer  
- `demo.student.low@example.com` - Struggling student
- `demo.admin@example.com` - Admin user

Password for all demo users: `demo123`

### Demo Quizzes
- **Live Quiz**: Currently active and available
- **Upcoming Quiz**: Will start tomorrow
- **Completed Quiz**: Historical data with statistics
- **Analytics Quiz**: Rich data for testing analytics

## API Documentation

For complete API documentation, refer to the generated API docs or use tools like Postman with the provided collection.

### Authentication
All endpoints require authentication via Laravel Sanctum:
```http
Authorization: Bearer {your-auth-token}
```

### Response Format
All responses follow the JSON API format:
```json
{
  "message": "Success message",
  "data": { ... },
  "errors": { ... }
}
```

## Support

For issues or questions about the enhanced quiz system:
1. Check the troubleshooting section
2. Review the Laravel logs in `storage/logs/`
3. Use the debug commands for diagnostics
4. Refer to the comprehensive test data for examples
