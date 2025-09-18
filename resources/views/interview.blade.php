<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Interview - Proctoring System</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .video-container {
            position: relative;
            background: #000;
            border-radius: 10px;
            overflow: hidden;
        }

        .detection-overlay {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
        }

        .alert-mini {
            padding: 5px 10px;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .integrity-score {
            font-size: 24px;
            font-weight: bold;
        }

        .detection-log {
            max-height: 400px;
            overflow-y: auto;
        }

        #candidateVideo {
            width: 100%;
            height: auto;
            min-height: 400px;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }

        .status-active {
            background: #28a745;
            animation: pulse 1s infinite;
        }

        .status-warning {
            background: #ffc107;
        }

        .status-danger {
            background: #dc3545;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-8">
                <!-- Video Feed Section -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-video me-2"></i>
                            Candidate Video Feed
                        </h5>
                        <div>
                            <span class="status-indicator status-warning"></span>
                            <span id="connectionStatus">Initializing...</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="video-container">
                            <video id="candidateVideo" autoplay muted></video>
                            <canvas id="hiddenCanvas" style="display: none;"></canvas>

                            <!-- Detection Overlay -->
                            <div class="detection-overlay">
                                <div id="focusAlert" class="alert alert-warning alert-mini d-none">
                                    <i class="fas fa-eye-slash"></i> Looking Away
                                </div>
                                <div id="faceAlert" class="alert alert-danger alert-mini d-none">
                                    <i class="fas fa-user-slash"></i> No Face Detected
                                </div>
                                <div id="multipleFacesAlert" class="alert alert-danger alert-mini d-none">
                                    <i class="fas fa-users"></i> Multiple Faces
                                </div>
                                <div id="phoneAlert" class="alert alert-danger alert-mini d-none">
                                    <i class="fas fa-mobile-alt"></i> Phone Detected
                                </div>
                                <div id="bookAlert" class="alert alert-warning alert-mini d-none">
                                    <i class="fas fa-book"></i> Notes/Books Detected
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Controls -->
                <div class="card mt-3">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <button id="startBtn" class="btn btn-success me-2">
                                        <i class="fas fa-play"></i> Start Interview
                                    </button>
                                    <button id="endBtn" class="btn btn-danger me-2" disabled>
                                        <i class="fas fa-stop"></i> End Interview
                                    </button>
                                    <button id="recordBtn" class="btn btn-primary me-2" disabled>
                                        <i class="fas fa-record-vinyl"></i> Record
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <div class="d-flex align-items-center justify-content-end">
                                    <span class="me-3">
                                        <i class="fas fa-clock"></i>
                                        <span id="timer">00:00:00</span>
                                    </span>
                                    <span class="integrity-score text-success" id="integrityScore">100</span>
                                    <small class="text-muted ms-1">%</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Session Info -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Session Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong>Candidate:</strong>
                            <span id="candidateName">-</span>
                        </div>
                        <div class="mb-2">
                            <strong>Session ID:</strong>
                            <span id="sessionId" class="font-monospace">-</span>
                        </div>
                        <div class="mb-2">
                            <strong>Started At:</strong>
                            <span id="startTime">-</span>
                        </div>
                    </div>
                </div>

                <!-- Detection Statistics -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>
                            Detection Statistics
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-2">
                                <div class="border rounded p-2">
                                    <div class="h4 text-warning mb-1" id="focusLostCount">0</div>
                                    <small>Focus Lost</small>
                                </div>
                            </div>
                            <div class="col-6 mb-2">
                                <div class="border rounded p-2">
                                    <div class="h4 text-danger mb-1" id="noFaceCount">0</div>
                                    <small>No Face</small>
                                </div>
                            </div>
                            <div class="col-6 mb-2">
                                <div class="border rounded p-2">
                                    <div class="h4 text-danger mb-1" id="multipleFacesCount">0</div>
                                    <small>Multi Faces</small>
                                </div>
                            </div>
                            <div class="col-6 mb-2">
                                <div class="border rounded p-2">
                                    <div class="h4 text-danger mb-1" id="phoneCount">0</div>
                                    <small>Phone</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="h4 text-warning mb-1" id="booksCount">0</div>
                                    <small>Books/Notes</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="h4 text-info mb-1" id="devicesCount">0</div>
                                    <small>Devices</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Detection Log -->
                <div class="card mt-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Detection Log
                        </h6>
                        <button class="btn btn-sm btn-outline-secondary" onclick="clearLog()">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div id="detectionLog" class="detection-log p-3">
                            <div class="text-muted text-center py-3">
                                <i class="fas fa-eye"></i><br>
                                Monitoring will start when interview begins
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Session Start Modal -->
    <div class="modal fade" id="sessionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Start Interview Session</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="sessionForm">
                        <div class="mb-3">
                            <label class="form-label">Candidate Name</label>
                            <input type="text" class="form-control" id="candidateNameInput" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Candidate Email</label>
                            <input type="email" class="form-control" id="candidateEmailInput" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="startInterview()">Start
                        Interview</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/control_utils/control_utils.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/face_mesh/face_mesh.js"></script>
    {{-- <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@latest/dist/tf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/coco-ssd@latest/dist/tf-coco-ssd.min.js"></script> --}}
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/coco-ssd"></script>
    <script>
        // Set CSRF token for all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Base URL for API calls
        window.apiUrl = '{{ url('/') }}';
    </script>
    <script src="{{ asset('assets/js/interview-system.js') }}"></script>
</body>

</html>
