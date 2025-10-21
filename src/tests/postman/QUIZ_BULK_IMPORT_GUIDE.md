# Quiz Bulk Import Guide

This guide explains how to use the quiz bulk import feature that supports both JSON and CSV formats with various question types.

## Supported Question Types

1. **Multiple Choice** (`multiple_choice`)
   - Single correct answer from 2-5 options
   - Exactly 1 correct answer required

2. **True/False** (`true_false`)
   - Boolean question with True/False answers
   - Exactly 2 answers required (True and False)
   - Exactly 1 correct answer required

3. **Multiple Correct Choice** (`multiple_correct_choice`)
   - Multiple correct answers from 2-5 options
   - At least 1 correct answer required
   - Can have multiple correct answers

4. **Short Answer** (`short_answer`)
   - Open-ended text response
   - Exactly 1 sample answer required

## API Endpoints

### Import Quiz
```
POST /api/quizzes/bulk-import
```

**Parameters:**
- `file` (required): The JSON or CSV file to import
- `format` (required): Either "json" or "csv"
- `overwrite` (optional): Boolean, whether to overwrite existing quiz with same slug

**Headers:**
- `Content-Type: multipart/form-data`
- `Authorization: Bearer {token}`

### Download Templates
```
GET /api/quizzes/bulk-import/template/json    # Download JSON template
GET /api/quizzes/bulk-import/template/csv     # Download CSV template
GET /api/quizzes/bulk-import/info             # Get format information
```

## JSON Format

### Structure
```json
{
  "title": "Quiz Title",
  "slug": "unique-quiz-slug",
  "summary": "Quiz description",
  "type": "assessment|practice|survey",
  "content": "Detailed instructions",
  "start_at": "2024-01-15T09:00:00Z",
  "ends_at": "2024-01-30T23:59:59Z",
  "questions": [
    {
      "question_number": 1,
      "question_text": "Question text here",
      "question_img": "optional_image.png",
      "question_type": "multiple_choice",
      "attachment": "optional_attachment.pdf",
      "answers": [
        {
          "answer": "Answer text",
          "is_correct": true
        }
      ]
    }
  ]
}
```

### Example: Multiple Choice Question
```json
{
  "question_number": 1,
  "question_text": "Which is a programming language?",
  "question_type": "multiple_choice",
  "answers": [
    {"answer": "Python", "is_correct": true},
    {"answer": "HTML", "is_correct": false},
    {"answer": "CSS", "is_correct": false}
  ]
}
```

### Example: True/False Question
```json
{
  "question_number": 2,
  "question_text": "The Earth is round.",
  "question_type": "true_false",
  "answers": [
    {"answer": "True", "is_correct": true},
    {"answer": "False", "is_correct": false}
  ]
}
```

### Example: Multiple Correct Choice Question
```json
{
  "question_number": 3,
  "question_text": "Which are web technologies?",
  "question_type": "multiple_correct_choice",
  "answers": [
    {"answer": "HTML", "is_correct": true},
    {"answer": "CSS", "is_correct": true},
    {"answer": "JavaScript", "is_correct": true},
    {"answer": "Python", "is_correct": false}
  ]
}
```

### Example: Short Answer Question
```json
{
  "question_number": 4,
  "question_text": "Explain object-oriented programming.",
  "question_type": "short_answer",
  "answers": [
    {
      "answer": "A programming paradigm based on objects that contain data and methods.",
      "is_correct": true
    }
  ]
}
```

## CSV Format

### Important Notes
- **Delimiter**: Semicolon (`;`) instead of comma
- **Encoding**: UTF-8
- **Empty cells**: Use empty strings for optional fields
- **Multiple questions**: Repeat quiz info or leave empty (except first row)

### CSV Headers
```
title;slug;summary;type;content;start_at;ends_at;question_number;question_text;question_img;question_type;attachment;answer_1;is_correct_1;answer_2;is_correct_2;answer_3;is_correct_3;answer_4;is_correct_4;answer_5;is_correct_5
```

### CSV Example
```csv
title;slug;summary;type;content;start_at;ends_at;question_number;question_text;question_img;question_type;attachment;answer_1;is_correct_1;answer_2;is_correct_2;answer_3;is_correct_3;answer_4;is_correct_4;answer_5;is_correct_5
Sample Quiz;sample-quiz;Quiz description;assessment;Instructions;2024-01-15T09:00:00Z;2024-01-30T23:59:59Z;1;What is 2+2?;;multiple_choice;;3;false;4;true;5;false;;;
;;;;;;;;2;Earth is round;;true_false;;True;true;False;false;;;;;;
;;;;;;;;3;Select programming languages;;multiple_correct_choice;;Python;true;Java;true;HTML;false;CSS;false;
;;;;;;;;4;Define OOP;;short_answer;;Object-oriented programming is...;true;;;;;;
```

## Validation Rules

### Quiz Level
- `title`: Required
- `slug`: Required, must be unique
- `type`: Required, must be "assessment", "practice", or "survey"
- `start_at`/`ends_at`: Optional, ISO 8601 format if provided

### Question Level
- `question_number`: Required, should be sequential
- `question_text`: Required
- `question_type`: Required, valid types only
- Questions must have appropriate number of answers

### Answer Level
- Each question type has specific answer requirements
- `answer`: Required, cannot be empty
- `is_correct`: Boolean value

## Usage Examples

### Using cURL

#### Import JSON file
```bash
curl -X POST \
  -H "Authorization: Bearer your-token" \
  -F "file=@quiz.json" \
  -F "format=json" \
  -F "overwrite=false" \
  http://your-domain.com/api/quizzes/bulk-import
```

#### Import CSV file
```bash
curl -X POST \
  -H "Authorization: Bearer your-token" \
  -F "file=@quiz.csv" \
  -F "format=csv" \
  -F "overwrite=true" \
  http://your-domain.com/api/quizzes/bulk-import
```

### Response Format

#### Success Response
```json
{
  "success": true,
  "message": "Quiz imported successfully",
  "quiz_id": 123,
  "total_questions_imported": 5,
  "total_answers_imported": 18,
  "import_summary": {
    "quiz_title": "Programming Quiz",
    "quiz_slug": "programming-quiz",
    "questions_count": 5,
    "question_types": ["multiple_choice", "true_false", "short_answer"],
    "imported_at": "2024-01-15T10:30:00.000000Z"
  },
  "errors": [],
  "warnings": ["Existing quiz was replaced"]
}
```

#### Error Response
```json
{
  "message": "Validation failed",
  "errors": [
    "Question 1: question_text is required",
    "Question 2: Invalid question_type",
    "Quiz slug already exists"
  ]
}
```

## Best Practices

1. **Test with small files first** before importing large datasets
2. **Use templates** as starting points for your imports
3. **Validate data** before importing (check question numbering, answer counts)
4. **Backup existing data** before using overwrite option
5. **Use descriptive slugs** that won't conflict with existing quizzes
6. **Check file encoding** - ensure UTF-8 for special characters
7. **Review validation errors** carefully before re-attempting import

## Common Issues

### CSV Issues
- **Wrong delimiter**: Ensure you're using semicolons, not commas
- **Encoding problems**: Save CSV as UTF-8
- **Empty answers**: Fill unused answer columns with empty strings
- **Quote issues**: Escape quotes properly in text fields

### JSON Issues
- **Invalid JSON**: Validate JSON syntax before importing
- **Boolean values**: Use `true`/`false`, not strings
- **Date format**: Use ISO 8601 format (YYYY-MM-DDTHH:MM:SSZ)

### General Issues
- **Duplicate slugs**: Ensure quiz slug is unique or use overwrite option
- **Missing required fields**: All required fields must be provided
- **Invalid question types**: Use only supported question types
- **Answer count mismatch**: Each question type has specific answer requirements

## File Size Limits

- Maximum file size: 10MB
- Recommended: Keep imports under 1000 questions for optimal performance
- Large imports may be processed in batches in future versions
