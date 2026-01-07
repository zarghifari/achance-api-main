# aChance Learning Platform - Mobile Web Interface

## Overview
This Laravel application now includes a full-stack mobile-like web interface in addition to the REST API. The web interface is designed to look and feel like a mobile application with bottom navigation and an app bar.

## Features

### 🎨 Mobile-First Design
- **App Bar**: Fixed top navigation with page titles and actions
- **Bottom Navigation**: Easy access to main sections (Home, Courses, Quizzes, Goals, Profile)
- **Responsive Cards**: Material-design inspired cards and components
- **Mobile Optimized**: Maximum width of 600px, optimized for mobile devices

### 🔐 Authentication
- User registration and login
- Session-based authentication
- Password reset functionality

### 📚 Course Management
- Browse all available courses
- View course details with modules
- Track course progress
- Bookmark courses for quick access

### 📖 Learning Experience
- Navigate through modules and lessons
- View different content types (HTML, video, PDF)
- Paginated content viewing
- Lesson completion tracking
- Previous/Next lesson navigation

### 📝 Quiz System
- Browse available quizzes
- Take quizzes with timer support
- Question navigation
- Automatic answer saving
- Detailed results with score breakdown
- Answer review with explanations
- Quiz leaderboards

### 🎯 Learning Goals
- Create and manage learning goals
- Track progress towards goals
- Set priorities and deadlines
- View completed goals

### 🔖 Bookmarks
- Bookmark courses, modules, and lessons
- Filter bookmarks by type
- Quick access to saved content

### 👤 User Profile
- View learning statistics
- Edit profile information
- Change password
- Manage learning preferences

## Directory Structure

```
src/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Web/            # Web controllers
│   │           ├── AuthController.php
│   │           ├── HomeController.php
│   │           ├── CourseWebController.php
│   │           ├── ModuleWebController.php
│   │           ├── LessonWebController.php
│   │           ├── QuizWebController.php
│   │           ├── ProfileWebController.php
│   │           ├── GoalWebController.php
│   │           └── BookmarkWebController.php
│   └── Models/
├── resources/
│   └── views/                  # Blade templates
│       ├── layouts/
│       │   └── app.blade.php   # Main layout with bottom nav
│       ├── auth/
│       │   ├── login.blade.php
│       │   └── register.blade.php
│       ├── home.blade.php
│       ├── courses/
│       │   ├── index.blade.php
│       │   └── show.blade.php
│       ├── modules/
│       │   └── show.blade.php
│       ├── lessons/
│       │   └── show.blade.php
│       ├── quizzes/
│       │   ├── index.blade.php
│       │   ├── show.blade.php
│       │   ├── take.blade.php
│       │   └── results.blade.php
│       ├── profile/
│       │   ├── index.blade.php
│       │   └── edit.blade.php
│       ├── goals/
│       │   └── index.blade.php
│       └── bookmarks/
│           └── index.blade.php
└── routes/
    ├── api.php                 # API routes (unchanged)
    └── web.php                 # Web routes (updated)
```

## Getting Started

### 1. Start the Application

Using Docker:
```powershell
.\docker-start.ps1
```

Or manually:
```powershell
cd src
php artisan serve
```

### 2. Access the Application

Open your browser and navigate to:
- Web Interface: http://localhost:8000
- API Endpoint: http://localhost:8000/api

### 3. Login or Register

- Default login page: http://localhost:8000/login
- Register new account: http://localhost:8000/register

## Routes

### Authentication Routes
- `GET /` → Login page (redirects to /home if authenticated)
- `GET /login` → Login page
- `POST /login` → Process login
- `GET /register` → Registration page
- `POST /register` → Process registration
- `POST /logout` → Logout

### Main Application Routes (requires authentication)
- `GET /home` → Dashboard
- `GET /courses` → Browse courses
- `GET /courses/{id}` → Course details
- `GET /courses/{course}/modules/{module}` → Module details
- `GET /courses/{course}/modules/{module}/lessons/{lesson}` → Lesson view
- `GET /quizzes` → Browse quizzes
- `GET /quizzes/{id}` → Quiz details
- `POST /quiz-attempts/{quiz}/start` → Start quiz attempt
- `GET /quiz-attempts/{attempt}/question/{number}` → Quiz question
- `POST /quiz-attempts/{attempt}/submit` → Submit quiz
- `GET /quiz-attempts/{attempt}/results` → Quiz results
- `GET /profile` → User profile
- `GET /goals` → Learning goals
- `GET /bookmarks` → Saved bookmarks

## Mobile-Like Features

### Bottom Navigation
The bottom navigation bar provides quick access to:
- 🏠 **Home**: Dashboard with overview
- 📚 **Courses**: Browse and access courses
- 📝 **Quizzes**: Take quizzes and view results
- 🎯 **Goals**: Manage learning goals
- 👤 **Profile**: User settings and statistics

### App Bar
- Displays current page title
- Back button (when applicable)
- Action menu (⋮)

### Visual Design
- **Primary Color**: #6366f1 (Indigo)
- **Secondary Color**: #8b5cf6 (Purple)
- **Success Color**: #10b981 (Green)
- **Danger Color**: #ef4444 (Red)
- **Warning Color**: #f59e0b (Amber)

### Components
- Cards with shadow effects
- List items with icons and arrows
- Progress bars for tracking
- Badges for status indicators
- Buttons with active states
- Form inputs with focus states

## API Integration

The web interface can also integrate with the existing API endpoints for:
- Real-time updates
- Background sync
- Advanced features

Store API token in localStorage:
```javascript
localStorage.setItem('token', 'your-api-token');
```

## Customization

### Styling
All styles are embedded in the main layout file: `resources/views/layouts/app.blade.php`

To customize colors, edit the CSS variables:
```css
:root {
    --primary-color: #6366f1;
    --secondary-color: #8b5cf6;
    /* ... */
}
```

### Adding New Pages
1. Create a new Blade view in `resources/views/`
2. Add a route in `routes/web.php`
3. Create or update controller in `app/Http/Controllers/Web/`
4. Update bottom navigation if needed

## Error Handling

The application includes:
- Validation error display
- Success/error flash messages
- Toast notifications for AJAX requests
- 404 error handling

## Session Management

- Sessions persist across page reloads
- Remember me functionality on login
- Automatic session timeout after inactivity
- CSRF protection on all forms

## Performance

- Lazy loading for images
- Minimal JavaScript
- Optimized CSS (no external frameworks)
- Database query optimization
- Caching support

## Browser Support

Optimized for modern mobile browsers:
- Chrome for Android
- Safari for iOS
- Firefox Mobile
- Samsung Internet

## Development Tips

### Testing on Mobile
1. Start the development server
2. Find your computer's local IP address
3. Access from mobile: `http://YOUR_IP:8000`

### Debugging
- Use browser DevTools mobile view
- Check Laravel logs: `storage/logs/laravel.log`
- Enable debug mode in `.env`: `APP_DEBUG=true`

## Security

- CSRF protection on all forms
- XSS prevention with Blade escaping
- SQL injection protection via Eloquent ORM
- Password hashing with bcrypt
- Session security best practices

## Next Steps

1. **Implement Progress Tracking**: Add actual lesson completion and course progress
2. **Add Notifications**: Push notifications for new content
3. **Offline Support**: Service worker for offline access
4. **Performance Analytics**: Track learning analytics
5. **Social Features**: Discussion forums, peer reviews
6. **Gamification**: Points, badges, achievements

## Support

For issues or questions:
1. Check the API documentation: `API_DOCUMENTATION.md`
2. Review Laravel documentation: https://laravel.com/docs
3. Check application logs

## License

Same as the main application.
