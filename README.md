# 📄 Video Interview Proctoring System

A comprehensive **AI-powered video proctoring system** built for online interviews that detects focus, unauthorized objects, and suspicious activities in real-time using computer vision and machine learning.

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColorensorFlow.js-FF6F00?style=for-the-DF1E?style=for-the-badge&logo=javascript&.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap& 🎯 Features

### ✅ Core Functionality
- **Real-time Video Monitoring** - Live candidate video feed with detection overlays
- **AI-Powered Detection** - Focus tracking, face detection, and object recognition
- **Integrity Scoring** - Dynamic scoring based on violations with real-time updates
- **Session Management** - Complete interview lifecycle management
- **Video Recording** - WebRTC-based recording with automatic upload
- **Report Generation** - Comprehensive PDF reports with detailed analytics

### 🔍 Detection Capabilities
- **Focus Detection** - Alerts when candidate looks away for >5 seconds
- **Face Monitoring** - Detects no face present for >10 seconds
- **Multiple Faces** - Identifies when multiple people are in frame
- **Object Detection** - Flags phones, books, notes, and electronic devices
- **Real-time Alerts** - Visual indicators for immediate violations
- **Event Logging** - Timestamped detection logs with confidence scores

### 📊 Advanced Features
- **Session Persistence** - Survives page refreshes using localStorage
- **Live Statistics** - Real-time violation counters and score updates
- **Responsive Design** - Works on desktop and tablet devices
- **Database Integration** - Complete audit trail in MySQL database
- **API Integration** - RESTful API for external system integration

## 🛠️ Technology Stack

| Component | Technology |
|-----------|------------|
| **Backend** | PHP 8.2+, Laravel 10+ |
| **Frontend** | HTML5, CSS3, JavaScript ES6+, jQuery |
| **Styling** | Bootstrap 5.3, Font Awesome 6.0 |
| **Database** | MySQL 8.0+ |
| **AI/ML** | TensorFlow.js 4.10+, COCO-SSD 2.2+ |
| **Computer Vision** | MediaPipe Face Mesh |
| **Video Processing** | WebRTC MediaRecorder API |
| **Report Generation** | Laravel DomPDF |

## ⚙️ Requirements

### System Requirements
- **PHP**: 8.2 or higher
- **Composer**: 2.0 or higher
- **Node.js**: 16.0 or higher (for development)
- **MySQL**: 8.0 or higher
- **Web Server**: Apache 2.4+ or Nginx 1.18+

### Browser Compatibility
- **Chrome**: 88+ (Recommended)
- **Firefox**: 85+
- **Safari**: 14+
- **Edge**: 88+

### Hardware Requirements
- **RAM**: Minimum 4GB, Recommended 8GB
- **Storage**: 2GB free space
- **Camera**: Webcam or built-in camera
- **Microphone**: For audio recording (optional)

## 🚀 Installation

### 1. Clone Repository
```bash
git clone https://github.com/your-username/video-proctoring-system.git
cd video-proctoring-system
```

### 2. Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies (if using Laravel Mix)
npm install && npm run build
```

### 3. Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Database Setup
```bash
# Configure database in .env file
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=video_proctoring
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Run migrations
php artisan migrate
```

### 5. Storage Configuration
```bash
# Create symbolic link for public storage
php artisan storage:link

# Set proper permissions
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

### 6. Start Development Server
```bash
php artisan serve
```

Visit `http://127.0.0.1:8000/interview` to access the system.

## 📁 Project Structure

```
video-proctoring-system/
├── app/
│   ├── Http/Controllers/
│   │   └── InterviewController.php
│   └── Models/
│       ├── InterviewSession.php
│       └── DetectionLog.php
├── database/
│   └── migrations/
│       ├── create_interview_sessions_table.php
│       └── create_detection_logs_table.php
├── public/
│   └── js/
│       └── interview-system.js
├── resources/
│   └── views/
│       ├── interview.blade.php
│       └── reports/
│           └── interview-report.blade.php
├── routes/
│   └── web.php
└── storage/
    └── app/public/interviews/
```

## 🎮 Usage

### Starting an Interview

1. **Navigate to Interview Page**
   ```
   http://localhost:8000/interview
   ```

2. **Initialize System**
   - Allow camera and microphone permissions
   - Wait for "System Ready" status

3. **Start Interview Session**
   - Click "Start Interview"
   - Enter candidate name and email
   - Click "Start Interview" to begin monitoring

### During Interview

- **Monitor Statistics**: View real-time detection counts
- **Check Alerts**: Watch for violation notifications
- **Record Video**: Optional video recording for evidence
- **View Logs**: Live event log with timestamps

### Ending Interview

1. Click "End Interview" button
2. System calculates final integrity score
3. PDF report generates automatically
4. Session data saved to database

## 🔧 API Documentation

### Start Interview Session
```http
POST /api/interview/start
Content-Type: application/json

{
  "candidate_name": "John Doe",
  "candidate_email": "john@example.com"
}
```

### End Interview Session
```http
POST /api/interview/end
Content-Type: application/json

{
  "session_id": "uuid-here"
}
```

### Log Detection Event
```http
POST /api/interview/log-detection
Content-Type: application/json

{
  "session_id": "uuid-here",
  "event_type": "phone_detected",
  "description": "Mobile phone detected (confidence: 0.85)",
  "duration_seconds": 0,
  "confidence_score": 0.85
}
```

### Get Session Report
```http
GET /api/interview/report/{sessionId}
```

### Generate PDF Report
```http
GET /api/interview/report/{sessionId}/pdf
```

### Upload Video Recording
```http
POST /api/interview/upload-video
Content-Type: multipart/form-data

session_id: uuid-here
video: [WebM file]
```

## ⚡ Configuration

### Detection Sensitivity
Update detection thresholds in `interview-system.js`:

```javascript
// Focus detection threshold (seconds)
const FOCUS_LOST_THRESHOLD = 5;

// Face absence threshold (seconds)  
const NO_FACE_THRESHOLD = 10;

// Object detection confidence threshold
const DETECTION_CONFIDENCE = 0.6;
```

### Scoring Algorithm
Modify scoring rules in `InterviewSession` model:

```php
public function calculateIntegrityScore()
{
    $deductions = 0;
    $deductions += $this->focus_lost_count * 2;      // 2 points per focus loss
    $deductions += $this->multiple_faces_count * 5;  // 5 points per multiple faces
    $deductions += $this->no_face_count * 3;         // 3 points per no face
    $deductions += $this->phone_detected_count * 10; // 10 points per phone
    $deductions += $this->books_detected_count * 8;  // 8 points per book/notes
    $deductions += $this->device_detected_count * 7; // 7 points per device
    
    return max(0, 100 - $deductions);
}
```

## 🔒 Security Features

- **CSRF Protection** - Laravel's built-in CSRF tokens
- **Input Validation** - Server-side validation for all inputs
- **SQL Injection Prevention** - Eloquent ORM with prepared statements
- **Session Security** - Secure session management with Laravel
- **File Upload Security** - MIME type validation and size limits
- **XSS Protection** - Blade template engine auto-escaping

## 📈 Performance Optimization

### AI Model Optimization
- **Lazy Loading** - Models load only when needed
- **Optimized Detection Frequency** - Face detection at 10 FPS, objects at 1 FPS
- **Memory Management** - Automatic cleanup of detection data

### Database Optimization
- **Indexed Columns** - session_id and timestamp columns indexed
- **Connection Pooling** - Laravel's database connection management
- **Query Optimization** - Eager loading for related models

### Frontend Optimization
- **Local Storage** - Session persistence for reliability
- **Debounced Updates** - Throttled UI updates for performance
- **Efficient DOM Manipulation** - Minimal DOM operations

## 🐛 Troubleshooting

### Common Issues

**Camera Not Working**
```javascript
// Check camera permissions
navigator.mediaDevices.getUserMedia({video: true})
  .then(stream => console.log('Camera OK'))
  .catch(err => console.error('Camera Error:', err));
```

**ML Models Not Loading**
```javascript
// Check library loading
console.log('TensorFlow:', typeof tf !== 'undefined');
console.log('COCO-SSD:', typeof cocoSsd !== 'undefined');
```

**Database Connection Issues**
```bash
# Test database connection
php artisan tinker
DB::connection()->getPdo();
```

**Storage Permissions**
```bash
# Fix storage permissions
chmod -R 775 storage/
chown -R www-data:www-data storage/
```

