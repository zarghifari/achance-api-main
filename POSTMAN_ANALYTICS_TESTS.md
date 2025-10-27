# User Activity Analytics - Postman Test Documentation

## Overview
The Course System API Tests Postman collection has been enhanced with comprehensive tests for the user activity analytics functionality. These tests verify both automatic and manual activity tracking for EPUB reading and quiz completion analytics.

## New Test Section: "User Activity Analytics Tests"

### Test Flow
The analytics tests are designed to run after the main course/module/lesson/epub creation tests, ensuring there's data to track activities against.

### Individual Test Cases

#### 1. Track EPUB Reading Progress (Student)
- **Endpoint**: `POST /courses/{id}/modules/{id}/lessons/{id}/epub-reading-progress`
- **Purpose**: Tests manual progress tracking for EPUB reading
- **Test Data**: 25% progress, page 5 of 20, 300 seconds reading time
- **Verifies**: 
  - Successful progress tracking (200 status)
  - Correct progress percentage recorded
  - Action type set to "progress"

#### 2. Complete EPUB Reading (Student)
- **Endpoint**: `POST /courses/{id}/modules/{id}/lessons/{id}/epub-reading-progress`
- **Purpose**: Tests completion tracking for EPUB reading
- **Test Data**: 100% progress, final page, total reading time
- **Verifies**:
  - Completion tracking (200 status)
  - Progress marked as 100%
  - Action type set to "complete"

#### 3. Create Manual User Activity (Student)
- **Endpoint**: `POST /useractivities`
- **Purpose**: Tests manual activity creation with custom metadata
- **Test Data**: Lesson view activity with custom metadata
- **Verifies**:
  - Activity creation (201 status)
  - Correct activity type and ID
  - Metadata preservation including session info

#### 4. Get User Activities (Student)
- **Endpoint**: `GET /useractivities`
- **Purpose**: Retrieves recent user activities
- **Verifies**:
  - Activity retrieval (200 status)
  - Response contains activity array
  - Activities have required fields (type, timestamp, etc.)

#### 5. Get User Analytics (Student)
- **Endpoint**: `GET /useractivities/analytics?period=30`
- **Purpose**: Tests comprehensive analytics data retrieval
- **Verifies**:
  - Analytics data structure (200 status)
  - EPUB analytics with interaction stats
  - Quiz analytics with completion rates
  - Overall statistics including device usage

#### 6. Test EPUB Download Tracking (Student)
- **Endpoint**: `GET /courses/{id}/modules/{id}/lessons/{id}/epubs/{id}/download`
- **Purpose**: Verifies automatic download activity tracking
- **Verifies**:
  - Download attempt handling (200 or 404 acceptable)
  - Automatic activity tracking server-side
  - File type verification for successful downloads

#### 7. Verify Download Activity Tracked
- **Endpoint**: `GET /useractivities`
- **Purpose**: Confirms download activity was automatically recorded
- **Verifies**:
  - Download activity appears in recent activities
  - Correct action type ("download")
  - Proper activity metadata

#### 8. Test Quiz Analytics Integration
- **Endpoint**: `GET /quizzes`
- **Purpose**: Demonstrates quiz analytics integration point
- **Notes**: Full quiz testing requires quiz creation flow
- **Future Enhancement**: Complete quiz attempt flow with analytics

## Automatic Activity Tracking Tests

### Enhanced Existing Tests
Several existing tests have been enhanced to note automatic activity tracking:

#### Get Epub for Lesson
- Now includes note about automatic EPUB view tracking
- Verifies that viewing EPUB details triggers analytics

#### Quiz Attempt Tests (Future)
- Quiz start/completion automatically tracked
- Performance metrics recorded (score, duration, etc.)

## Test Data Requirements

### Prerequisites
Tests require the following environment variables set by earlier tests:
- `courseId` - Created course ID
- `moduleId` - Created module ID  
- `lessonId` - Created lesson ID
- `epubId` - Created EPUB ID
- `studentToken` - Student authentication token
- `teacherToken` - Teacher authentication token

### Test Environment Variables
Tests automatically set:
- `authToken` - Switches between student/teacher tokens
- Progress tracking data
- Activity metadata

## Expected Analytics Data Structure

### EPUB Analytics Response
```json
{
  "epub_analytics": {
    "stats": {
      "total_interactions": number,
      "unique_epubs": number,
      "downloads": number,
      "completed_reads": number,
      "avg_progress": number,
      "avg_reading_time": number
    },
    "top_epubs": [...],
    "reading_patterns": [...]
  }
}
```

### Quiz Analytics Response
```json
{
  "quiz_analytics": {
    "stats": {
      "total_attempts": number,
      "unique_quizzes": number,
      "completed_quizzes": number,
      "avg_completion_time": number
    },
    "completion_rate": number
  }
}
```

### Overall Statistics
```json
{
  "overall_stats": {
    "total_activities": number,
    "active_days": number,
    "device_usage": [
      {"device_type": "mobile", "usage_count": number},
      {"device_type": "desktop", "usage_count": number}
    ]
  }
}
```

## Test Execution Guidelines

### Running the Tests
1. **Full Suite**: Run the entire collection to ensure proper data setup
2. **Analytics Only**: Can run just the "User Activity Analytics Tests" folder after manual setup
3. **Individual Tests**: Some tests depend on previous test data

### Test Environment Setup
1. Ensure database is accessible and migrated
2. Have teacher@example.com and student@example.com users
3. Run the full course creation flow before analytics tests
4. Verify environment variables are properly set

### Expected Test Results
- **All Green**: Full analytics functionality working
- **Partial Success**: Some features may depend on file system setup
- **Analytics Data**: May start empty but will accumulate with test runs

## Integration with Main Test Flow

### Test Sequence
1. Authentication tests set up user tokens
2. Course/Module/Lesson/EPUB creation provides test data
3. **Analytics tests verify tracking functionality**
4. Cleanup operations remove test data

### Data Persistence
- Activity data persists beyond test cleanup
- Analytics accumulate across test runs
- Real usage patterns can be analyzed

## Troubleshooting

### Common Issues
1. **Missing IDs**: Ensure course creation tests passed
2. **Authentication**: Verify student/teacher tokens are valid
3. **Database**: Check that migrations have been applied
4. **File System**: EPUB downloads may fail if files don't exist (expected in test environment)

### Debug Information
Tests include extensive console logging:
- Activity tracking status
- Analytics data structure
- Environment variable values
- Error messages for failed operations

## Future Enhancements

### Quiz Analytics Tests
Complete quiz workflow testing:
1. Create quiz with questions
2. Start quiz attempt (triggers start activity)
3. Submit answers (triggers progress activity)
4. Complete quiz (triggers completion activity)
5. Verify comprehensive quiz analytics

### Advanced Analytics Tests
- Reading pattern analysis
- Device usage trending
- Completion rate calculations
- Performance comparisons

### Real-time Tracking Tests
- WebSocket integration for live progress
- Batch activity submission
- Offline activity synchronization