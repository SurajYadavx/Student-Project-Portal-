<?php
// submit_project.php
require_once 'config.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isLoggedIn() || !isStudent()) redirect('index.php');

$error = '';
$success = '';

if ($_POST) {
    $title      = trim($_POST['title'] ?? '');
    $intro      = trim($_POST['intro'] ?? '');
    $importance = trim($_POST['importance'] ?? '');
    $domain     = $_POST['domain'] ?? '';

    if (!$title || !$intro || !$importance || !$domain) {
        $error = 'All fields required.';
    } else {
        $filePath = null;
        if (!empty($_FILES['file']['name'])) {
            $fname = time() . '_' . basename($_FILES['file']['name']);
            $dest = 'uploads/' . $fname;

            // Check if uploads folder exists and is writable
            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }

            if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                $error = 'Upload failed.';
            } else {
                $filePath = $dest;
            }
        }

        if (!$error) {
            $stmt = $pdo->prepare("INSERT INTO projects
              (student_id, title, intro, importance, file_path, domain)
              VALUES (?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
              $_SESSION['user_id'], $title, $intro, $importance, $filePath, $domain
            ]);
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                $error = "Database insertion error: " . $errorInfo[2];
            } else {
                $success = 'Project submitted successfully!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Submit Project - Project Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      padding-top: 2rem;
      padding-bottom: 2rem;
    }

    .submit-container {
      padding: 2rem 1rem;
    }

    .page-header {
      text-align: center;
      margin-bottom: 3rem;
      animation: slideInDown 0.6s ease;
    }

    .page-title {
      color: white;
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 1rem;
    }

    .page-subtitle {
      color: rgba(255, 255, 255, 0.8);
      font-size: 1.1rem;
      font-weight: 500;
    }

    .submit-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      box-shadow: 
        0 25px 50px -12px rgba(0, 0, 0, 0.25),
        0 0 0 1px rgba(255, 255, 255, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.2);
      overflow: hidden;
      animation: slideInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
      position: relative;
    }

    .submit-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
      background-size: 200% 100%;
      animation: shimmer 3s ease-in-out infinite;
    }

    @keyframes slideInDown {
      from {
        opacity: 0;
        transform: translateY(-30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes slideInUp {
      from {
        opacity: 0;
        transform: translateY(40px) scale(0.95);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    @keyframes shimmer {
      0%, 100% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
    }

    .card-header-modern {
      background: linear-gradient(135deg, #f8fafc, #e2e8f0);
      padding: 2.5rem;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
      text-align: center;
    }

    .form-header-icon {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
      font-size: 2rem;
      color: white;
      animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }

    .form-header-title {
      font-size: 1.75rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 0.5rem;
    }

    .form-header-subtitle {
      color: #6b7280;
      font-size: 1rem;
    }

    .card-body-modern {
      padding: 3rem;
    }

    .form-section {
      margin-bottom: 2.5rem;
    }

    .section-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding-bottom: 0.75rem;
      border-bottom: 2px solid #f1f5f9;
    }

    .form-group-modern {
      position: relative;
      margin-bottom: 2rem;
    }

    .form-label-modern {
      color: #374151;
      font-weight: 600;
      font-size: 1rem;
      margin-bottom: 0.75rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .form-control-modern {
      width: 100%;
      padding: 1rem;
      border: 2px solid #e5e7eb;
      border-radius: 16px;
      font-size: 1rem;
      background: #f8fafc;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      color: #374151;
      resize: vertical;
    }

    .form-control-modern:focus {
      outline: none;
      border-color: #667eea;
      background: white;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
      transform: translateY(-1px);
    }

    .form-select-modern {
      width: 100%;
      padding: 1rem;
      border: 2px solid #e5e7eb;
      border-radius: 16px;
      font-size: 1rem;
      background: #f8fafc;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      color: #374151;
      cursor: pointer;
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
      background-position: right 1rem center;
      background-repeat: no-repeat;
      background-size: 1rem;
      appearance: none;
    }

    .form-select-modern:focus {
      outline: none;
      border-color: #667eea;
      background: white;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
      transform: translateY(-1px);
    }

    .textarea-modern {
      min-height: 120px;
      font-family: inherit;
      line-height: 1.6;
    }

    .char-counter {
      text-align: right;
      color: #9ca3af;
      font-size: 0.875rem;
      margin-top: 0.5rem;
      font-weight: 500;
    }

    .char-counter.warning {
      color: #f59e0b;
    }

    .char-counter.danger {
      color: #ef4444;
    }

    .file-upload-section {
      background: #f8fafc;
      border: 2px solid #e2e8f0;
      border-radius: 20px;
      padding: 2rem;
      text-align: center;
      margin-bottom: 2rem;
    }

    .file-upload-area {
      border: 2px dashed #d1d5db;
      border-radius: 16px;
      padding: 3rem 2rem;
      background: white;
      transition: all 0.3s ease;
      cursor: pointer;
      position: relative;
      overflow: hidden;
    }

    .file-upload-area:hover {
      border-color: #667eea;
      background: #f8fafc;
      transform: translateY(-2px);
    }

    .file-upload-area.dragover {
      border-color: #667eea;
      background: #e0f2fe;
      transform: scale(1.02);
      border-style: solid;
    }

    .file-upload-icon {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
      font-size: 2rem;
      color: #6b7280;
      transition: all 0.3s ease;
    }

    .file-upload-area:hover .file-upload-icon {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      transform: scale(1.1);
    }

    .file-upload-text {
      color: #374151;
      font-weight: 600;
      font-size: 1.1rem;
      margin-bottom: 0.5rem;
    }

    .file-upload-hint {
      color: #9ca3af;
      font-size: 0.95rem;
      margin-bottom: 1.5rem;
    }

    .file-upload-button {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      border: none;
      padding: 0.75rem 1.5rem;
      border-radius: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .file-upload-button:hover {
      background: linear-gradient(135deg, #5a67d8, #667eea);
      transform: translateY(-2px);
    }

    .selected-file {
      display: none;
      margin-top: 1.5rem;
      padding: 1rem;
      background: linear-gradient(135deg, #d1fae5, #a7f3d0);
      border: 2px solid #10b981;
      border-radius: 12px;
      color: #047857;
      font-weight: 600;
    }

    .file-info {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      justify-content: center;
    }

    .file-icon {
      width: 32px;
      height: 32px;
      background: #10b981;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 0.875rem;
    }

    .remove-file {
      background: #ef4444;
      color: white;
      border: none;
      width: 24px;
      height: 24px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.75rem;
      cursor: pointer;
      margin-left: auto;
    }

    .alert-modern {
      border-radius: 16px;
      padding: 1.25rem;
      margin-bottom: 2rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      animation: slideInDown 0.5s ease;
    }

    .alert-success-modern {
      background: linear-gradient(135deg, #d1fae5, #a7f3d0);
      border: 2px solid #10b981;
      color: #047857;
    }

    .alert-error-modern {
      background: linear-gradient(135deg, #fee2e2, #fecaca);
      border: 2px solid #f87171;
      color: #dc2626;
      animation: shake 0.5s ease-in-out;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      75% { transform: translateX(5px); }
    }

    .submit-section {
      padding-top: 2rem;
      border-top: 2px solid #f1f5f9;
      text-align: center;
    }

    .btn-submit {
      background: linear-gradient(135deg, #10b981, #059669);
      border: none;
      border-radius: 16px;
      color: white;
      padding: 1rem 3rem;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
      position: relative;
      overflow: hidden;
      display: inline-flex;
      align-items: center;
      gap: 0.75rem;
      min-width: 200px;
      justify-content: center;
    }

    .btn-submit::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.6s;
    }

    .btn-submit:hover::before {
      left: 100%;
    }

    .btn-submit:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
      background: linear-gradient(135deg, #059669, #047857);
    }

    .btn-submit:active {
      transform: translateY(-1px);
      box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    }

    .submit-note {
      color: #6b7280;
      font-size: 0.95rem;
      margin-top: 1.5rem;
      font-style: italic;
    }

    /* Loading state */
    .btn-submit.loading {
      pointer-events: none;
      opacity: 0.8;
    }

    .btn-submit.loading::after {
      content: '';
      position: absolute;
      width: 20px;
      height: 20px;
      margin: auto;
      border: 2px solid white;
      border-top: 2px solid transparent;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      top: 0;
      left: 0;
      bottom: 0;
      right: 0;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }

    /* Domain badges for visual enhancement */
    .domain-preview {
      margin-top: 1rem;
      display: none;
    }

    .domain-badge {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 15px;
      font-size: 0.875rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    /* Progress indicator */
    .form-progress {
      background: #f1f5f9;
      height: 6px;
      border-radius: 3px;
      margin-bottom: 2rem;
      overflow: hidden;
    }

    .progress-bar {
      height: 100%;
      background: linear-gradient(90deg, #667eea, #764ba2);
      border-radius: 3px;
      transition: width 0.3s ease;
      width: 0%;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
      .submit-container {
        padding: 1rem;
      }
      
      .card-body-modern {
        padding: 2rem;
      }
      
      .page-title {
        font-size: 2rem;
        flex-direction: column;
        gap: 0.5rem;
      }
      
      .file-upload-area {
        padding: 2rem 1rem;
      }
      
      .btn-submit {
        width: 100%;
        margin-top: 1rem;
      }
    }

    @media (max-width: 576px) {
      .card-header-modern,
      .card-body-modern {
        padding: 1.5rem;
      }
      
      .file-upload-section {
        padding: 1.5rem;
      }
    }

    /* Form validation styles */
    .form-control-modern:valid:not(:placeholder-shown) {
      border-color: #10b981;
    }

    .form-control-modern:invalid:not(:placeholder-shown) {
      border-color: #ef4444;
    }

    .validation-message {
      margin-top: 0.5rem;
      font-size: 0.875rem;
      font-weight: 500;
    }

    .validation-success {
      color: #10b981;
    }

    .validation-error {
      color: #ef4444;
    }
  </style>
</head>

<body>
  <?php include 'includes/navbar.php'; ?>
  
  <div class="submit-container">
    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title">
        <i class="fas fa-plus-circle"></i>
        Submit New Project
      </h1>
      <p class="page-subtitle">Share your innovative project with the community</p>
    </div>

    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
          
          <div class="submit-card">
            <!-- Card Header -->
            <div class="card-header-modern">
              <div class="form-header-icon">
                <i class="fas fa-rocket"></i>
              </div>
              <h2 class="form-header-title">Project Submission</h2>
              <p class="form-header-subtitle">Fill out all the details about your amazing project</p>
            </div>

            <!-- Card Body -->
            <div class="card-body-modern">
              <!-- Progress Bar -->
              <div class="form-progress">
                <div class="progress-bar" id="progressBar"></div>
              </div>

              <!-- Alerts -->
              <?php if ($error): ?>
                <div class="alert-modern alert-error-modern">
                  <i class="fas fa-exclamation-triangle"></i>
                  <?php echo htmlspecialchars($error); ?>
                </div>
              <?php endif; ?>
              
              <?php if ($success): ?>
                <div class="alert-modern alert-success-modern">
                  <i class="fas fa-check-circle"></i>
                  <?php echo htmlspecialchars($success); ?>
                </div>
              <?php endif; ?>

              <form method="post" enctype="multipart/form-data" id="submitForm" novalidate>
                
                <!-- Basic Information Section -->
                <div class="form-section">
                  <h3 class="section-title">
                    <i class="fas fa-info-circle"></i>
                    Project Information
                  </h3>
                  
                  <!-- Title -->
                  <div class="form-group-modern">
                    <label class="form-label-modern">
                      <i class="fas fa-heading"></i>
                      Project Title *
                    </label>
                    <input 
                      name="title" 
                      class="form-control-modern" 
                      required
                      placeholder="Enter a compelling project title"
                      maxlength="100"
                      value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>"
                    >
                    <div class="char-counter">
                      <span id="titleCounter">0</span>/100 characters
                    </div>
                    <div class="validation-message" id="titleValidation"></div>
                  </div>

                  <!-- Domain -->
                  <div class="form-group-modern">
                    <label class="form-label-modern">
                      <i class="fas fa-tags"></i>
                      Project Domain *
                    </label>
                    <select name="domain" class="form-select-modern" required id="domainSelect">
                      <option value="">Select your project domain</option>
                      <option value="AI" <?= (isset($_POST['domain']) && $_POST['domain'] === 'AI') ? 'selected' : '' ?>>Artificial Intelligence</option>
                      <option value="Web Development" <?= (isset($_POST['domain']) && $_POST['domain'] === 'Web Development') ? 'selected' : '' ?>>Web Development</option>
                      <option value="Mobile App" <?= (isset($_POST['domain']) && $_POST['domain'] === 'Mobile App') ? 'selected' : '' ?>>Mobile App Development</option>
                      <option value="IoT" <?= (isset($_POST['domain']) && $_POST['domain'] === 'IoT') ? 'selected' : '' ?>>Internet of Things (IoT)</option>
                      <option value="Data Science" <?= (isset($_POST['domain']) && $_POST['domain'] === 'Data Science') ? 'selected' : '' ?>>Data Science & Analytics</option>
                      <option value="Cybersecurity" <?= (isset($_POST['domain']) && $_POST['domain'] === 'Cybersecurity') ? 'selected' : '' ?>>Cybersecurity</option>
                      <option value="Blockchain" <?= (isset($_POST['domain']) && $_POST['domain'] === 'Blockchain') ? 'selected' : '' ?>>Blockchain & Cryptocurrency</option>
                      <option value="Other" <?= (isset($_POST['domain']) && $_POST['domain'] === 'Other') ? 'selected' : '' ?>>Other</option>
                    </select>
                    <div class="domain-preview" id="domainPreview">
                      <span class="domain-badge" id="domainBadge">
                        <i class="fas fa-tag"></i>
                        <span id="domainText"></span>
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Project Details Section -->
                <div class="form-section">
                  <h3 class="section-title">
                    <i class="fas fa-align-left"></i>
                    Project Details
                  </h3>

                  <!-- Introduction -->
                  <div class="form-group-modern">
                    <label class="form-label-modern">
                      <i class="fas fa-file-alt"></i>
                      Project Introduction *
                    </label>
                    <textarea 
                      name="intro" 
                      class="form-control-modern textarea-modern" 
                      required
                      placeholder="Provide a detailed introduction to your project. Explain what it does, how it works, and what makes it unique..."
                      maxlength="1000"
                    ><?= isset($_POST['intro']) ? htmlspecialchars($_POST['intro']) : '' ?></textarea>
                    <div class="char-counter">
                      <span id="introCounter">0</span>/1000 characters
                    </div>
                    <div class="validation-message" id="introValidation"></div>
                  </div>

                  <!-- Importance -->
                  <div class="form-group-modern">
                    <label class="form-label-modern">
                      <i class="fas fa-star"></i>
                      Project Importance *
                    </label>
                    <textarea 
                      name="importance" 
                      class="form-control-modern textarea-modern" 
                      required
                      placeholder="Explain why this project is important. What problem does it solve? What impact will it have? Who will benefit from it?"
                      maxlength="1000"
                    ><?= isset($_POST['importance']) ? htmlspecialchars($_POST['importance']) : '' ?></textarea>
                    <div class="char-counter">
                      <span id="importanceCounter">0</span>/1000 characters
                    </div>
                    <div class="validation-message" id="importanceValidation"></div>
                  </div>
                </div>

                <!-- File Upload Section -->
                <div class="form-section">
                  <h3 class="section-title">
                    <i class="fas fa-cloud-upload-alt"></i>
                    Project Files
                  </h3>

                  <div class="file-upload-section">
                    <div class="file-upload-area" onclick="document.getElementById('fileInput').click()">
                      <div class="file-upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                      </div>
                      <div class="file-upload-text">Drop your file here or click to browse</div>
                      <div class="file-upload-hint">
                        Optional: Upload project documentation, source code, or demo files<br>
                        Supported formats: PDF, DOC, DOCX, ZIP, RAR (Max: 10MB)
                      </div>
                      <button type="button" class="file-upload-button" onclick="document.getElementById('fileInput').click()">
                        <i class="fas fa-folder-open"></i>
                        Choose File
                      </button>
                      <input 
                        type="file" 
                        name="file" 
                        id="fileInput"
                        style="display: none;"
                        accept=".pdf,.doc,.docx,.zip,.rar,.txt"
                      >
                    </div>

                    <div class="selected-file" id="selectedFile">
                      <div class="file-info">
                        <div class="file-icon">
                          <i class="fas fa-file"></i>
                        </div>
                        <span id="fileName"></span>
                        <button type="button" class="remove-file" onclick="removeFile()">
                          <i class="fas fa-times"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Submit Section -->
                <div class="submit-section">
                  <button type="submit" class="btn-submit" id="submitBtn">
                    <i class="fas fa-paper-plane"></i>
                    Submit Project
                  </button>
                  <p class="submit-note">
                    <i class="fas fa-info-circle"></i>
                    Your project will be reviewed by professors before being published
                  </p>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('submitForm');
      const submitBtn = document.getElementById('submitBtn');
      const progressBar = document.getElementById('progressBar');
      const fileInput = document.getElementById('fileInput');
      const uploadArea = document.querySelector('.file-upload-area');
      const selectedFile = document.getElementById('selectedFile');
      const fileName = document.getElementById('fileName');
      const domainSelect = document.getElementById('domainSelect');
      const domainPreview = document.getElementById('domainPreview');
      const domainBadge = document.getElementById('domainBadge');
      const domainText = document.getElementById('domainText');
      
      // Character counters
      function setupCharCounter(inputId, counterId, maxLength) {
        const input = document.querySelector([name="${inputId}"]);
        const counter = document.getElementById(counterId);
        
        function updateCounter() {
          const length = input.value.length;
          counter.textContent = length;
          
          const counterElement = counter.parentElement;
          counterElement.className = 'char-counter';
          
          if (length > maxLength * 0.9) {
            counterElement.classList.add('danger');
          } else if (length > maxLength * 0.7) {
            counterElement.classList.add('warning');
          }
          
          updateProgress();
        }
        
        input.addEventListener('input', updateCounter);
        updateCounter();
      }
      
      setupCharCounter('title', 'titleCounter', 100);
      setupCharCounter('intro', 'introCounter', 1000);
      setupCharCounter('importance', 'importanceCounter', 1000);
      
      // Progress tracking
      function updateProgress() {
        const title = document.querySelector('[name="title"]').value;
        const domain = document.querySelector('[name="domain"]').value;
        const intro = document.querySelector('[name="intro"]').value;
        const importance = document.querySelector('[name="importance"]').value;
        
        let progress = 0;
        if (title.length >= 5) progress += 25;
        if (domain) progress += 25;
        if (intro.length >= 50) progress += 25;
        if (importance.length >= 50) progress += 25;
        
        progressBar.style.width = progress + '%';
      }
      
      // Domain preview
      domainSelect.addEventListener('change', function() {
        if (this.value) {
          domainText.textContent = this.value;
          domainPreview.style.display = 'block';
        } else {
          domainPreview.style.display = 'none';
        }
        updateProgress();
      });
      
      // File upload handling
      fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
          const file = this.files[0];
          fileName.textContent = file.name;
          selectedFile.style.display = 'block';
          uploadArea.style.borderColor = '#10b981';
          uploadArea.style.background = '#d1fae5';
        }
      });
      
      // Drag and drop functionality
      ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
      });
      
      function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
      }
      
      ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, () => uploadArea.classList.add('dragover'), false);
      });
      
      ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, () => uploadArea.classList.remove('dragover'), false);
      });
      
      uploadArea.addEventListener('drop', function(e) {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
          fileInput.files = files;
          fileName.textContent = files[0].name;
          selectedFile.style.display = 'block';
        }
      });
      
      // Remove file function
      window.removeFile = function() {
        fileInput.value = '';
        selectedFile.style.display = 'none';
        uploadArea.style.borderColor = '#d1d5db';
        uploadArea.style.background = 'white';
      };
      
      // Form validation
      function validateForm() {
        const title = document.querySelector('[name="title"]').value;
        const domain = document.querySelector('[name="domain"]').value;
        const intro = document.querySelector('[name="intro"]').value;
        const importance = document.querySelector('[name="importance"]').value;
        
        let isValid = true;
        
        // Title validation
        if (title.length < 5) {
          document.getElementById('titleValidation').innerHTML = '<i class="fas fa-exclamation-circle"></i> Title must be at least 5 characters';
          document.getElementById('titleValidation').className = 'validation-message validation-error';
          isValid = false;
        } else {
          document.getElementById('titleValidation').innerHTML = '<i class="fas fa-check-circle"></i> Good title';
          document.getElementById('titleValidation').className = 'validation-message validation-success';
        }
        
        // Intro validation
        if (intro.length < 50) {
          document.getElementById('introValidation').innerHTML = '<i class="fas fa-exclamation-circle"></i> Introduction should be at least 50 characters';
          document.getElementById('introValidation').className = 'validation-message validation-error';
          isValid = false;
        } else {
          document.getElementById('introValidation').innerHTML = '<i class="fas fa-check-circle"></i> Great introduction';
          document.getElementById('introValidation').className = 'validation-message validation-success';
        }
        
        // Importance validation
        if (importance.length < 50) {
          document.getElementById('importanceValidation').innerHTML = '<i class="fas fa-exclamation-circle"></i> Importance explanation should be at least 50 characters';
          document.getElementById('importanceValidation').className = 'validation-message validation-error';
          isValid = false;
        } else {
          document.getElementById('importanceValidation').innerHTML = '<i class="fas fa-check-circle"></i> Well explained importance';
          document.getElementById('importanceValidation').className = 'validation-message validation-success';
        }
        
        return isValid;
      }
      
      // Real-time validation
      const inputs = document.querySelectorAll('input[required], textarea[required], select[required]');
      inputs.forEach(input => {
        input.addEventListener('blur', validateForm);
        input.addEventListener('input', updateProgress);
      });
      
      // Form submission
      form.addEventListener('submit', function(e) {
        if (!validateForm()) {
          e.preventDefault();
          return;
        }
        
        // Show loading state
        submitBtn.classList.add('loading');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting Project...';
        submitBtn.disabled = true;
        
        // Disable form
        const formElements = form.querySelectorAll('input, textarea, select, button');
        formElements.forEach(element => {
          element.disabled = true;
          element.style.opacity = '0.6';
        });
      });
      
      // Reset button if there's an error
      if (document.querySelector('.alert-error-modern')) {
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Project';
        submitBtn.disabled = false;
        submitBtn.classList.remove('loading');
        
        // Re-enable form
        const formElements = form.querySelectorAll('input, textarea, select, button');
        formElements.forEach(element => {
          element.disabled = false;
          element.style.opacity = '';
        });
      }
      
      // Initial progress update
      updateProgress();
    });
  </script>
</body>

</html>
