<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'aChance Learning')</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --dark-color: #1f2937;
            --light-color: #f3f4f6;
            --text-color: #374151;
            --border-color: #e5e7eb;
            --app-bar-height: 56px;
            --bottom-nav-height: 64px;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: var(--light-color);
            color: var(--text-color);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            max-width: 600px;
            margin: 0 auto;
            position: relative;
        }

        /* App Bar */
        .app-bar {
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            max-width: 600px;
            width: 100%;
            height: var(--app-bar-height);
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            display: flex;
            align-items: center;
            padding: 0 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 100;
        }

        .app-bar-title {
            flex: 1;
            font-size: 20px;
            font-weight: 600;
        }

        .app-bar-action {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin-left: 8px;
        }

        .app-bar-action:active {
            background: rgba(255,255,255,0.3);
        }

        /* Main Content */
        .main-content {
            padding: calc(var(--app-bar-height) + 16px) 16px calc(var(--bottom-nav-height) + 16px);
            min-height: 100vh;
        }

        /* Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            max-width: 600px;
            width: 100%;
            height: var(--bottom-nav-height);
            background: white;
            display: flex;
            justify-content: space-around;
            align-items: center;
            box-shadow: 0 -2px 8px rgba(0,0,0,0.1);
            z-index: 100;
        }

        .nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #9ca3af;
            font-size: 12px;
            padding: 8px;
            transition: all 0.3s;
        }

        .nav-item.active {
            color: var(--primary-color);
        }

        .nav-icon {
            font-size: 24px;
            margin-bottom: 4px;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .card-header {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--dark-color);
        }

        /* Buttons */
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-block;
            text-align: center;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:active {
            background: #4f46e5;
        }

        .btn-secondary {
            background: var(--secondary-color);
            color: white;
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-full {
            width: 100%;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        /* List Items */
        .list-item {
            background: white;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--text-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .list-item-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            margin-right: 12px;
        }

        .list-item-content {
            flex: 1;
        }

        .list-item-title {
            font-weight: 600;
            margin-bottom: 4px;
        }

        .list-item-subtitle {
            font-size: 14px;
            color: #6b7280;
        }

        .list-item-arrow {
            color: #d1d5db;
            font-size: 20px;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-primary {
            background: #dbeafe;
            color: var(--primary-color);
        }

        .badge-success {
            background: #d1fae5;
            color: var(--success-color);
        }

        .badge-warning {
            background: #fef3c7;
            color: var(--warning-color);
        }

        .badge-danger {
            background: #fee2e2;
            color: var(--danger-color);
        }

        /* Progress Bar */
        .progress-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin: 8px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            transition: width 0.3s;
        }

        /* Alert */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
        }

        /* Loading Spinner */
        .spinner {
            border: 3px solid #f3f4f6;
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Utility Classes */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .mt-1 { margin-top: 8px; }
        .mt-2 { margin-top: 16px; }
        .mt-3 { margin-top: 24px; }
        .mb-1 { margin-bottom: 8px; }
        .mb-2 { margin-bottom: 16px; }
        .mb-3 { margin-bottom: 24px; }
        .flex { display: flex; }
        .justify-between { justify-content: space-between; }
        .items-center { align-items: center; }
        .gap-2 { gap: 16px; }
        .hidden { display: none; }

        /* Responsive */
        @media (max-width: 640px) {
            .main-content {
                padding: calc(var(--app-bar-height) + 12px) 12px calc(var(--bottom-nav-height) + 12px);
            }
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- App Bar -->
    <div class="app-bar">
        @if(isset($backUrl))
            <a href="{{ $backUrl }}" class="app-bar-action">‹</a>
        @endif
        <div class="app-bar-title">@yield('app-title', 'aChance')</div>
        @auth
            <button class="app-bar-action" onclick="toggleMenu()">⋮</button>
        @endauth
    </div>

    <!-- Main Content -->
    <div class="main-content">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-error">
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>

    <!-- Bottom Navigation -->
    @auth
    <div class="bottom-nav">
        <a href="{{ route('home') }}" class="nav-item {{ request()->routeIs('home') ? 'active' : '' }}">
            <div class="nav-icon">🏠</div>
            <div>Home</div>
        </a>
        <a href="{{ route('courses.index') }}" class="nav-item {{ request()->routeIs('courses.*') ? 'active' : '' }}">
            <div class="nav-icon">📚</div>
            <div>Courses</div>
        </a>
        <a href="{{ route('quizzes.index') }}" class="nav-item {{ request()->routeIs('quizzes.*') ? 'active' : '' }}">
            <div class="nav-icon">📝</div>
            <div>Quizzes</div>
        </a>
        <a href="{{ route('goals.index') }}" class="nav-item {{ request()->routeIs('goals.*') ? 'active' : '' }}">
            <div class="nav-icon">🎯</div>
            <div>Goals</div>
        </a>
        <a href="{{ route('profile.index') }}" class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
            <div class="nav-icon">👤</div>
            <div>Profile</div>
        </a>
    </div>
    @endauth

    <script>
        // CSRF Token for AJAX requests
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Toggle Menu
        function toggleMenu() {
            alert('Menu functionality - implement as needed');
        }

        // Show loading spinner
        function showLoading() {
            const spinner = document.createElement('div');
            spinner.id = 'global-spinner';
            spinner.className = 'spinner';
            spinner.style.position = 'fixed';
            spinner.style.top = '50%';
            spinner.style.left = '50%';
            spinner.style.transform = 'translate(-50%, -50%)';
            spinner.style.zIndex = '1000';
            document.body.appendChild(spinner);
        }

        function hideLoading() {
            const spinner = document.getElementById('global-spinner');
            if (spinner) spinner.remove();
        }

        // Toast notification
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type}`;
            toast.style.position = 'fixed';
            toast.style.top = '80px';
            toast.style.left = '50%';
            toast.style.transform = 'translateX(-50%)';
            toast.style.zIndex = '1000';
            toast.style.maxWidth = '90%';
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => toast.remove(), 3000);
        }
    </script>

    @stack('scripts')
</body>
</html>
