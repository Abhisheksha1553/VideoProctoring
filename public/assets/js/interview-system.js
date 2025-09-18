class VideoProctoring {
    constructor() {
        this.sessionId = null;
        this.isRecording = false;
        this.startTime = null;
        this.timerInterval = null;
        this.mediaRecorder = null;
        this.recordedChunks = [];

        // Detection variables
        this.faceDetector = null;
        this.objectModel = null;
        this.canvas = document.getElementById('hiddenCanvas');
        this.ctx = this.canvas.getContext('2d');

        // Timing variables
        this.lastFaceTime = Date.now();
        this.lastFocusTime = Date.now();
        this.focusLostStart = null;
        this.noFaceStart = null;

        // Detection counts
        this.detectionCounts = {
            focusLost: 0,
            noFace: 0,
            multipleFaces: 0,
            phone: 0,
            books: 0,
            devices: 0
        };

        // Loading states
        this.isInitialized = false;
        this.modelsLoaded = false;

        this.initializeSystem();
    }

    async initializeSystem() {
        try {
            console.log('Starting system initialization...');

            // Check if required libraries are loaded
            await this.waitForLibraries();

            await this.setupCamera();
            await this.initializeML();
            this.setupEventListeners();

            this.isInitialized = true;
            console.log('Video proctoring system initialized successfully');

            // Update UI to show system is ready
            this.updateConnectionStatus('ready', 'System Ready');

        } catch (error) {
            console.error('Failed to initialize system:', error);
            this.updateConnectionStatus('error', 'Initialization Failed');
            alert(`Failed to initialize system: ${error.message}\n\nPlease refresh and try again.`);
        }
    }

    async waitForLibraries() {
        return new Promise((resolve, reject) => {
            let attempts = 0;
            const maxAttempts = 50; // 10 seconds max wait

            const checkLibraries = () => {
                attempts++;
                console.log(`Checking libraries... Attempt ${attempts}`);

                // Check if TensorFlow.js is loaded
                if (typeof tf === 'undefined') {
                    if (attempts >= maxAttempts) {
                        reject(new Error('TensorFlow.js failed to load'));
                        return;
                    }
                    setTimeout(checkLibraries, 200);
                    return;
                }

                // Check if COCO-SSD is loaded
                if (typeof cocoSsd === 'undefined') {
                    if (attempts >= maxAttempts) {
                        reject(new Error('COCO-SSD model failed to load'));
                        return;
                    }
                    setTimeout(checkLibraries, 200);
                    return;
                }

                console.log('Required libraries loaded successfully');
                resolve();
            };

            checkLibraries();
        });
    }

    async setupCamera() {
        const video = document.getElementById('candidateVideo');

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                    facingMode: 'user'
                },
                audio: true
            });

            video.srcObject = stream;
            this.stream = stream;

            // Setup canvas dimensions
            this.canvas.width = 640;
            this.canvas.height = 480;

            console.log('Camera setup completed');

        } catch (error) {
            throw new Error(`Camera access failed: ${error.message}`);
        }
    }

    async initializeML() {
        try {
            console.log('Loading ML models...');

            // Initialize Face Detection (optional - fallback if not available)
            if (typeof faceLandmarksDetection !== 'undefined') {
                const model = faceLandmarksDetection.SupportedModels.MediaPipeFaceMesh;
                const detectorConfig = {
                    runtime: 'tfjs',
                    maxFaces: 3,
                    refineLandmarks: true
                };

                this.faceDetector = await faceLandmarksDetection.createDetector(model, detectorConfig);
                console.log('Face landmarks detector loaded successfully');
            }

            // Initialize TensorFlow.js COCO-SSD model
            this.objectModel = await cocoSsd.load({
                base: 'mobilenet_v2'
            });
            console.log('COCO-SSD model loaded successfully');

            this.modelsLoaded = true;

        } catch (error) {
            console.error('ML model initialization error:', error);

            // Try to load at least object detection
            try {
                this.objectModel = await cocoSsd.load();
                console.log('COCO-SSD loaded with fallback');
                this.modelsLoaded = true;
            } catch (fallbackError) {
                console.warn('ML models failed to load, using basic monitoring');
                this.modelsLoaded = true; // Continue without ML
            }
        }
    }

    updateConnectionStatus(status, message) {
        const statusElement = document.getElementById('connectionStatus');
        const indicator = document.querySelector('.status-indicator');

        if (statusElement && indicator) {
            statusElement.textContent = message;

            // Reset classes
            indicator.classList.remove('status-active', 'status-warning', 'status-danger');

            switch (status) {
                case 'ready':
                    indicator.classList.add('status-active');
                    break;
                case 'warning':
                    indicator.classList.add('status-warning');
                    break;
                case 'error':
                    indicator.classList.add('status-danger');
                    break;
            }
        }
    }

    setupEventListeners() {
        document.getElementById('startBtn').addEventListener('click', () => {
            if (!this.isInitialized) {
                alert('System is still initializing. Please wait...');
                return;
            }
            $('#sessionModal').modal('show');
        });

        document.getElementById('endBtn').addEventListener('click', this.endInterview.bind(this));
        document.getElementById('recordBtn').addEventListener('click', this.toggleRecording.bind(this));
    }

    async startInterview() {
        const candidateName = document.getElementById('candidateNameInput').value.trim();
        const candidateEmail = document.getElementById('candidateEmailInput').value.trim();

        if (!candidateName || !candidateEmail) {
            alert('Please fill in all fields');
            return;
        }

        if (!this.isInitialized) {
            alert('System is not ready yet. Please wait for initialization to complete.');
            return;
        }

        try {
            const response = await fetch(`${window.apiUrl}/api/interview/start`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    candidate_name: candidateName,
                    candidate_email: candidateEmail
                })
            });

            const data = await response.json();

            if (data.success) {
                this.sessionId = data.session_id;
                this.startTime = new Date();

                // Update UI
                document.getElementById('candidateName').textContent = candidateName;
                document.getElementById('sessionId').textContent = this.sessionId;
                document.getElementById('startTime').textContent = this.startTime.toLocaleString();

                document.getElementById('startBtn').disabled = true;
                document.getElementById('endBtn').disabled = false;
                document.getElementById('recordBtn').disabled = false;

                $('#sessionModal').modal('hide');

                // Start monitoring
                this.startMonitoring();
                this.startTimer();

                this.addLogEntry('info', 'Interview session started', 'success');
                this.updateConnectionStatus('ready', 'Monitoring Active');

            } else {
                alert('Failed to start session: ' + data.message);
            }
        } catch (error) {
            console.error('Error starting interview:', error);
            alert('Failed to start interview session. Please check your connection.');
        }
    }

    async endInterview() {
        if (!this.sessionId) {
            console.error('No session ID available for ending interview');
            alert('No active session to end');
            return;
        }

        console.log('Ending interview with session ID:', this.sessionId);

        try {
            if (this.isRecording) {
                this.stopRecording();
            }

            this.stopMonitoring();
            this.stopTimer();

            const requestData = {
                session_id: this.sessionId
            };

            console.log('Sending end interview request:', requestData);

            const response = await fetch(`${window.apiUrl}/api/interview/end`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(requestData)
            });

            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);

            const data = await response.json();
            console.log('Response data:', data);

            if (data.success) {
                document.getElementById('integrityScore').textContent = data.integrity_score;
                this.addLogEntry('info', `Interview ended. Final integrity score: ${data.integrity_score}%`, 'primary');
                this.updateConnectionStatus('ready', 'Interview Completed');

                // Generate report
                setTimeout(() => {
                    window.open(`${window.apiUrl}/api/interview/report/${this.sessionId}/pdf`, '_blank');
                }, 1000);

            } else {
                console.error('End session failed:', data);
                alert('Failed to end session: ' + (data.message || 'Unknown error'));

                // Show validation errors if available
                if (data.errors) {
                    console.error('Validation errors:', data.errors);
                    Object.keys(data.errors).forEach(field => {
                        data.errors[field].forEach(error => {
                            console.error(`${field}: ${error}`);
                        });
                    });
                }
            }
        } catch (error) {
            console.error('Error ending interview:', error);
            alert('Failed to end interview session: ' + error.message);
        }
    }

    startMonitoring() {
        const video = document.getElementById('candidateVideo');

        // Face detection loop
        const detectFaces = async () => {
            if (!this.sessionId) return;

            try {
                if (this.faceDetector) {
                    const faces = await this.faceDetector.estimateFaces(video);
                    this.processFaceDetections(faces);
                }
            } catch (error) {
                console.warn('Face detection error:', error);
            }

            setTimeout(detectFaces, 100); // 10 FPS
        };

        // Object detection loop
        const detectObjects = async () => {
            if (!this.sessionId || !this.objectModel) return;

            try {
                const predictions = await this.objectModel.detect(video);
                this.processObjectDetections(predictions);
            } catch (error) {
                console.warn('Object detection error:', error);
            }

            setTimeout(detectObjects, 1000); // 1 FPS
        };

        if (this.faceDetector) {
            detectFaces();
        }

        if (this.objectModel) {
            detectObjects();
        }

        // Basic monitoring fallback
        this.startBasicMonitoring();
    }

    startBasicMonitoring() {
        // Basic timer-based monitoring when ML models aren't available
        const basicCheck = () => {
            if (!this.sessionId) return;

            // Simulate some basic checks
            const currentTime = Date.now();

            // Random detection simulation for demo (remove in production)
            if (Math.random() < 0.1) { // 10% chance every second
                const events = ['focus_lost', 'multiple_faces'];
                const randomEvent = events[Math.floor(Math.random() * events.length)];

                if (randomEvent === 'focus_lost') {
                    this.detectionCounts.focusLost++;
                    this.addLogEntry('focus_lost', 'Basic monitoring: Focus check', 'warning');
                }
            }

            this.updateDetectionCounts();
            setTimeout(basicCheck, 5000); // Every 5 seconds
        };

        setTimeout(basicCheck, 5000);
    }

    stopMonitoring() {
        this.sessionId = null;
        this.updateConnectionStatus('ready', 'Monitoring Stopped');
    }

    processFaceDetections(faces) {
        if (!this.sessionId) return;

        const currentTime = Date.now();

        // Check face count
        if (faces.length === 0) {
            if (!this.noFaceStart) {
                this.noFaceStart = currentTime;
            } else if (currentTime - this.noFaceStart > 10000) {
                this.logDetection('no_face', 'No face detected for more than 10 seconds',
                    Math.floor((currentTime - this.noFaceStart) / 1000));
                this.detectionCounts.noFace++;
                this.updateDetectionCounts();
                this.showAlert('faceAlert');
                this.noFaceStart = null;
            }
        } else {
            this.noFaceStart = null;
            this.hideAlert('faceAlert');
            this.lastFaceTime = currentTime;

            if (faces.length > 1) {
                this.logDetection('multiple_faces', `${faces.length} faces detected simultaneously`);
                this.detectionCounts.multipleFaces++;
                this.updateDetectionCounts();
                this.showAlert('multipleFacesAlert');
            } else {
                this.hideAlert('multipleFacesAlert');
            }
        }
    }

    processObjectDetections(predictions) {
        if (!this.sessionId || !predictions) return;

        let phoneDetected = false;
        let bookDetected = false;
        let deviceDetected = false;

        predictions.forEach(prediction => {
            const { class: className, score } = prediction;

            if (score > 0.6) {
                switch (className) {
                    case 'cell phone':
                        phoneDetected = true;
                        this.logDetection('phone_detected', `Mobile phone detected (confidence: ${score.toFixed(2)})`, 0, score);
                        this.detectionCounts.phone++;
                        break;
                    case 'book':
                        bookDetected = true;
                        this.logDetection('books_detected', `Book/notes detected (confidence: ${score.toFixed(2)})`, 0, score);
                        this.detectionCounts.books++;
                        break;
                    case 'laptop':
                    case 'keyboard':
                    case 'mouse':
                        deviceDetected = true;
                        this.logDetection('device_detected', `Electronic device detected: ${className} (confidence: ${score.toFixed(2)})`, 0, score);
                        this.detectionCounts.devices++;
                        break;
                }
            }
        });

        // Update alerts
        if (phoneDetected) this.showAlert('phoneAlert');
        else this.hideAlert('phoneAlert');

        if (bookDetected) this.showAlert('bookAlert');
        else this.hideAlert('bookAlert');

        if (phoneDetected || bookDetected || deviceDetected) {
            this.updateDetectionCounts();
        }
    }

    async logDetection(eventType, description, durationSeconds = 0, confidenceScore = null) {
        if (!this.sessionId) return;

        try {
            await fetch(`${window.apiUrl}/api/interview/log-detection`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    session_id: this.sessionId,
                    event_type: eventType,
                    description: description,
                    duration_seconds: durationSeconds,
                    confidence_score: confidenceScore
                })
            });

            this.addLogEntry(eventType, description, this.getEventTypeClass(eventType));

        } catch (error) {
            console.error('Error logging detection:', error);
        }
    }

    getEventTypeClass(eventType) {
        switch (eventType) {
            case 'focus_lost': return 'warning';
            case 'no_face': return 'danger';
            case 'multiple_faces': return 'danger';
            case 'phone_detected': return 'danger';
            case 'books_detected': return 'warning';
            case 'device_detected': return 'info';
            default: return 'secondary';
        }
    }

    showAlert(alertId) {
        const alertElement = document.getElementById(alertId);
        if (alertElement) {
            alertElement.classList.remove('d-none');
        }
    }

    hideAlert(alertId) {
        const alertElement = document.getElementById(alertId);
        if (alertElement) {
            alertElement.classList.add('d-none');
        }
    }

    updateDetectionCounts() {
        document.getElementById('focusLostCount').textContent = this.detectionCounts.focusLost;
        document.getElementById('noFaceCount').textContent = this.detectionCounts.noFace;
        document.getElementById('multipleFacesCount').textContent = this.detectionCounts.multipleFaces;
        document.getElementById('phoneCount').textContent = this.detectionCounts.phone;
        document.getElementById('booksCount').textContent = this.detectionCounts.books;
        document.getElementById('devicesCount').textContent = this.detectionCounts.devices;

        // Calculate and update integrity score
        const totalDeductions =
            this.detectionCounts.focusLost * 2 +
            this.detectionCounts.noFace * 3 +
            this.detectionCounts.multipleFaces * 5 +
            this.detectionCounts.phone * 10 +
            this.detectionCounts.books * 8 +
            this.detectionCounts.devices * 7;

        const integrityScore = Math.max(0, 100 - totalDeductions);
        document.getElementById('integrityScore').textContent = integrityScore;

        // Update score color
        const scoreElement = document.getElementById('integrityScore');
        scoreElement.className = 'integrity-score';
        if (integrityScore >= 80) {
            scoreElement.classList.add('text-success');
        } else if (integrityScore >= 60) {
            scoreElement.classList.add('text-warning');
        } else {
            scoreElement.classList.add('text-danger');
        }
    }

    addLogEntry(type, message, alertClass = 'secondary') {
        const log = document.getElementById('detectionLog');
        const timestamp = new Date().toLocaleTimeString();

        const entry = document.createElement('div');
        entry.className = `alert alert-${alertClass} py-2 px-3 mb-2`;
        entry.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small class="text-muted">${timestamp}</small><br>
                    <span>${message}</span>
                </div>
                <i class="fas fa-${this.getIconForType(type)}"></i>
            </div>
        `;

        log.appendChild(entry);
        log.scrollTop = log.scrollHeight;

        // Keep only last 20 entries
        while (log.children.length > 20) {
            log.removeChild(log.firstChild);
        }
    }

    getIconForType(type) {
        switch (type) {
            case 'focus_lost': return 'eye-slash';
            case 'no_face': return 'user-slash';
            case 'multiple_faces': return 'users';
            case 'phone_detected': return 'mobile-alt';
            case 'books_detected': return 'book';
            case 'device_detected': return 'laptop';
            case 'info': return 'info-circle';
            default: return 'exclamation-triangle';
        }
    }

    startTimer() {
        this.timerInterval = setInterval(() => {
            if (this.startTime) {
                const elapsed = new Date() - this.startTime;
                const hours = Math.floor(elapsed / 3600000);
                const minutes = Math.floor((elapsed % 3600000) / 60000);
                const seconds = Math.floor((elapsed % 60000) / 1000);

                document.getElementById('timer').textContent =
                    `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
        }, 1000);
    }

    stopTimer() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
            this.timerInterval = null;
        }
    }

    toggleRecording() {
        if (this.isRecording) {
            this.stopRecording();
        } else {
            this.startRecording();
        }
    }

    startRecording() {
        if (!this.stream) return;

        this.recordedChunks = [];

        try {
            this.mediaRecorder = new MediaRecorder(this.stream, {
                mimeType: 'video/webm'
            });

            this.mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    this.recordedChunks.push(event.data);
                }
            };

            this.mediaRecorder.onstop = () => {
                this.uploadRecording();
            };

            this.mediaRecorder.start();
            this.isRecording = true;

            const recordBtn = document.getElementById('recordBtn');
            recordBtn.innerHTML = '<i class="fas fa-stop"></i> Stop Recording';
            recordBtn.classList.replace('btn-primary', 'btn-danger');

            this.addLogEntry('info', 'Recording started', 'primary');
        } catch (error) {
            console.error('Recording start failed:', error);
            alert('Failed to start recording');
        }
    }

    stopRecording() {
        if (this.mediaRecorder && this.isRecording) {
            this.mediaRecorder.stop();
            this.isRecording = false;

            const recordBtn = document.getElementById('recordBtn');
            recordBtn.innerHTML = '<i class="fas fa-record-vinyl"></i> Record';
            recordBtn.classList.replace('btn-danger', 'btn-primary');

            this.addLogEntry('info', 'Recording stopped', 'primary');
        }
    }

    async uploadRecording() {
        if (!this.sessionId || this.recordedChunks.length === 0) {
            console.warn('No session or recording data available');
            return;
        }

        try {
            console.log('Starting video upload...', {
                sessionId: this.sessionId,
                chunksCount: this.recordedChunks.length,
                totalSize: this.recordedChunks.reduce((total, chunk) => total + chunk.size, 0)
            });

            const blob = new Blob(this.recordedChunks, { type: 'video/webm' });
            const formData = new FormData();
            formData.append('session_id', this.sessionId);
            formData.append('video', blob, `interview-recording-${this.sessionId}.webm`);

            // Log form data for debugging
            console.log('FormData contents:', {
                sessionId: formData.get('session_id'),
                videoSize: formData.get('video').size,
                videoType: formData.get('video').type
            });

            const response = await fetch(`${window.apiUrl}/api/interview/upload-video`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            console.log('Upload response status:', response.status);

            const data = await response.json();
            console.log('Upload response data:', data);

            if (data.success) {
                this.addLogEntry('info', 'Recording uploaded successfully', 'success');
            } else {
                console.error('Upload failed:', data);
                this.addLogEntry('error', `Upload failed: ${data.message || 'Unknown error'}`, 'danger');

                // Show detailed error if available
                if (data.errors) {
                    console.error('Validation errors:', data.errors);
                    Object.keys(data.errors).forEach(field => {
                        data.errors[field].forEach(error => {
                            this.addLogEntry('error', `${field}: ${error}`, 'danger');
                        });
                    });
                }
            }
        } catch (error) {
            console.error('Upload exception:', error);
            this.addLogEntry('error', `Upload failed: ${error.message}`, 'danger');
        }
    }

}

// Global functions
function clearLog() {
    document.getElementById('detectionLog').innerHTML = `
        <div class="text-muted text-center py-3">
            <i class="fas fa-eye"></i><br>
            Log cleared
        </div>
    `;
}

function startInterview() {
    if (window.proctoring) {
        window.proctoring.startInterview();
    }
}

// Initialize system when page loads
document.addEventListener('DOMContentLoaded', () => {
    console.log('Initializing Video Proctoring System...');
    window.proctoring = new VideoProctoring();
});
