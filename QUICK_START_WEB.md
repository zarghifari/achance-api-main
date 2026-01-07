# Quick Start Guide - aChance Learning Web Interface

## 🚀 Starting the Application

### Option 1: Using Docker (Recommended)
```powershell
.\docker-start.ps1
```

### Option 2: Using PHP Built-in Server
```powershell
cd src
php artisan serve
```

### Option 3: Using Laravel Homestead/Valet
Follow Laravel documentation for your preferred local development environment.

## 📱 Accessing the Application

### On Desktop/Laptop
Open your browser and navigate to:
```
http://localhost:8000
```

### On Mobile Device (same network)
1. Find your computer's local IP address:
   ```powershell
   ipconfig
   ```
   Look for "IPv4 Address" (e.g., 192.168.1.100)

2. On your mobile device, open the browser and go to:
   ```
   http://YOUR_IP_ADDRESS:8000
   ```
   Example: http://192.168.1.100:8000

## 👤 First Time Setup

### 1. Register an Account
- Click "Sign Up" on the login page
- Fill in your details:
  - Full Name
  - Email Address
  - Password (minimum 8 characters)
- Accept terms and click "Create Account"

### 2. Explore the Dashboard
After login, you'll see:
- **Welcome Card** with your name
- **Quick Stats** showing your progress
- **Featured Courses** to browse
- **Learning Goals** section
- **Quick Actions** for bookmarks and progress

### 3. Navigate the App
Use the **bottom navigation bar**:
- 🏠 **Home**: Dashboard and overview
- 📚 **Courses**: Browse and access courses
- 📝 **Quizzes**: Take quizzes and track scores
- 🎯 **Goals**: Set and manage learning goals
- 👤 **Profile**: View stats and settings

## 📚 Using Key Features

### Browse Courses
1. Tap "Courses" in bottom navigation
2. Use search to find specific courses
3. Tap a course to see details
4. View modules and lessons within

### Take a Quiz
1. Tap "Quizzes" in bottom navigation
2. Select a quiz
3. Review quiz details (questions, time limit, pass score)
4. Tap "Start Quiz"
5. Answer questions
6. Use navigator to jump between questions
7. Submit when complete
8. Review your results

### Set Learning Goals
1. Tap "Goals" in bottom navigation
2. Tap "Create New Goal"
3. Fill in goal details:
   - Title
   - Description
   - Target date
   - Priority level
4. Track progress on your goals
5. Update progress as you learn

### Bookmark Content
While viewing courses, modules, or lessons:
1. Tap the star (☆) icon in the top right
2. Content is saved to your bookmarks
3. Access all bookmarks from Profile → Bookmarks

### Update Profile
1. Tap "Profile" in bottom navigation
2. Tap "Edit Profile"
3. Update your information
4. Change password if needed
5. Save changes

## 🎨 Mobile-Like Features

### App Bar (Top)
- Shows current page title
- Back button for navigation
- Action menu for quick options

### Bottom Navigation
- Always accessible
- Highlights current section
- Quick switching between main areas

### Card-Based Layout
- Material Design inspired
- Easy to read and interact
- Optimized for touch

### Progress Tracking
- Visual progress bars
- Completion indicators
- Score displays

## 🔧 Troubleshooting

### Can't Access on Mobile?
- Ensure both devices are on the same WiFi network
- Check if firewall is blocking the connection
- Try disabling firewall temporarily
- Verify the IP address is correct

### Login Issues?
- Clear browser cache and cookies
- Ensure email and password are correct
- Check if caps lock is on
- Try password reset (if implemented)

### Page Not Loading?
- Refresh the page
- Check internet connection
- Clear browser cache
- Try a different browser

### Styles Not Showing?
- Hard refresh the page (Ctrl+F5 or Cmd+Shift+R)
- Clear browser cache
- Ensure JavaScript is enabled

## 📊 Data and Progress

### Your Data
All your learning data is stored locally:
- Course progress
- Quiz attempts and scores
- Learning goals
- Bookmarks
- Profile information

### Syncing
- Data syncs automatically
- No manual sync needed
- Works across devices (when logged in)

## 🔒 Security Tips

- Use a strong password
- Don't share your account
- Log out on shared devices
- Keep your email secure

## 📖 Getting Help

### Documentation
- `WEB_INTERFACE_README.md` - Complete documentation
- `API_DOCUMENTATION.md` - API reference
- `README.md` - Main project documentation

### Common Questions

**Q: Do I need an internet connection?**
A: Yes, currently the app requires an internet connection.

**Q: Can I use it offline?**
A: Not yet, but it's planned for future updates.

**Q: Is my data secure?**
A: Yes, passwords are hashed and data is protected.

**Q: Can I delete my account?**
A: Contact support or use the admin panel.

**Q: How do I report a bug?**
A: Check the logs in `storage/logs/laravel.log`

## 🎯 Best Practices

### For Learning
- Set realistic goals
- Complete courses in order
- Review quiz results
- Bookmark important lessons
- Track your progress regularly

### For Performance
- Close unused tabs
- Clear cache periodically
- Use a modern browser
- Ensure stable internet connection

## 🔄 Updates

The application is regularly updated with:
- New features
- Bug fixes
- Performance improvements
- Security patches

Check the main README for changelog.

## 📞 Support

For technical issues:
1. Check Laravel logs
2. Review error messages
3. Consult documentation
4. Check GitHub issues

---

**Enjoy your learning journey with aChance! 🎓**
