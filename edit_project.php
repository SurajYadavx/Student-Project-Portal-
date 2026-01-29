<?php
// edit_project.php - NEW FILE
require_once 'config.php';
if (!isLoggedIn() || !isStudent()) redirect('index.php');

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id=? AND student_id=? AND status='pending'");
$stmt->execute([$id, $_SESSION['user_id']]);
$project = $stmt->fetch();
if (!$project) die('Project not found or cannot be edited');

$error = '';
$success = '';
if ($_POST) {
  $title = trim($_POST['title'] ?? '');
  $intro = trim($_POST['intro'] ?? '');
  $importance = trim($_POST['importance'] ?? '');
  $domain = $_POST['domain'] ?? '';
  
  if (!$title||!$intro||!$importance||!$domain) {
    $error = 'All fields required.';
  } else {
    $filePath = $project['file_path']; // Keep existing file
    
    // Handle new file upload
    if (!empty($_FILES['file']['name'])) {
      $fname = time() . '_' . basename($_FILES['file']['name']);
      $dest = 'uploads/' . $fname;
      if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        // Delete old file if exists
        if ($filePath && file_exists($filePath)) unlink($filePath);
        $filePath = $dest;
      } else {
        $error = 'Upload failed.';
      }
    }
    
    if (!$error) {
      $stmt = $pdo->prepare("UPDATE projects SET title=?, intro=?, importance=?, file_path=?, domain=? WHERE id=?");
      $stmt->execute([$title, $intro, $importance, $filePath, $domain, $id]);
      $success = 'Project updated successfully!';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Project - Project Portal</title>
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

    .main-container {
      padding: 2rem 1rem;
    }

    .page-header {
      text-align: center;
      margin-bottom: 2rem;
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

    .edit-card {
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

    .edit-card::before {
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

    @keyframes slideInDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes shimmer {
      0%, 100% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
    }

    .card-body-modern {
      padding: 3rem;
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
    }

    .file-upload-area {
      border: 2px dashed #d1d5db;
      border-radius: 16px;
      padding: 2rem;
      text-align: center;
      background: #f8fafc;
      transition: all 0.3s ease;
      position: relative;
      cursor: pointer;
    }

    .file-upload-area:hover {
      border-color: #667eea;
      background: #f0f7ff;
    }

    .file-upload-area.dragover {
      border-color: #667eea;
      background: #e0f2fe;
      transform: scale(1.02);
    }

    .file-upload-icon {
      font-size: 2rem;
      color: #9ca3af;
      margin-bottom: 1rem;
    }

    .file-upload-text {
      color: #6b7280;
      font-weight: 500;
      margin-bottom: 0.5rem;
    }

    .file-upload-hint {
      color: #9ca3af;
      font-size: 0.875rem;
    }

    .current-file {
      background: linear-gradient(135deg, #e0f2fe, #f0f9ff);
      border: 2px solid #0ea5e9;
      border-radius: 12px;
      padding: 1rem;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .current-file-icon {
      width: 40px;
      height: 40px;
      background: #0ea5e9;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
    }

    .current-file-info {
      flex: 1;
    }

    .current-file-name {
      font-weight: 600;
      color: #0f172a;
      margin-bottom: 0.25rem;
    }

    .current-file-meta {
      color: #64748b;
      font-size: 0.875rem;
    }

    .btn-group-modern {
      display: flex;
      gap: 1rem;
      margin-top: 2rem;
    }

    .btn-update {
      flex: 1;
      padding: 1rem 2rem;
      background: linear-gradient(135deg, #10b981, #059669);
      border: none;
      border-radius: 16px;
      color: white;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
      position: relative;
      overflow: hidden;
    }

    .btn-update::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.6s;
    }

    .btn-update:hover::before {
      left: 100%;
    }

    .btn-update:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
      background: linear-gradient(135deg, #059669, #047857);
    }

    .btn-update:active {
      transform: translateY(0);
      box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    }

    .btn-cancel {
      flex: 1;
      padding: 1rem 2rem;
      background: linear-gradient(135deg, #f8fafc, #e2e8f0);
      border: 2px solid #d1d5db;
      border-radius: 16px;
      color: #374151;
      font-size: 1.1rem;
      font-weight: 600;
      text-decoration: none;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      justify-content: center;
    }

    .btn-cancel:hover {
      background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
      border-color: #9ca3af;
      color: #1f2937;
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
      text-decoration: none;
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
    }

    /* Loading state */
    .btn-update.loading {
      pointer-events: none;
      opacity: 0.8;
    }

    .btn-update.loading::after {
      content: '';
      position: absolute;
      width: 16px;
      height: 16px;
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

    /* Mobile Responsive */
    @media (max-width: 768px) {
      .main-container {
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
      
      .btn-group-modern {
        flex-direction: column;
      }
      
      .file-upload-area {
        padding: 1.5rem;
      }
    }

    @media (max-width: 576px) {
      .card-body-modern {
        padding: 1.5rem;
      }
      
      .page-title {
        font-size: 1.75rem;
      }
    }

    /* Focus states for accessibility */
    .btn-update:focus,
    .btn-cancel:focus {
      outline: 2px solid #667eea;
      outline-offset: 2px;
    }

    /* Character counter for textareas */
    .char-counter {
      text-align: right;
      color: #9ca3af;
      font-size: 0.875rem;
      margin-top: 0.5rem;
    }
  </style>
</head>

<body>
  <?php include __DIR__ . '/includes/navbar.php'; ?>
  
  <div class="main-container">
    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title">
        <i class="fas fa-edit"></i>
        Edit Project
      </h1>
      <p class="page-subtitle">Update your project details and make improvements</p>
    </div>

    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
          
          <!-- Alerts -->
          <?php if ($error): ?>
            <div class="alert-modern alert-error-modern">
              <i class="fas fa-exclamation-circle"></i>
              <?php echo htmlspecialchars($error); ?>
            </div>
          <?php endif; ?>
          
          <?php if ($success): ?>
            <div class="alert-modern alert-success-modern">
              <i class="fas fa-check-circle"></i>
              <?php echo htmlspecialchars($success); ?>
            </div>
          <?php endif; ?>

          <!-- Edit Form Card -->
          <div class="edit-card">
            <div class="card-body-modern">
              <form method="post" enctype="multipart/form-data" id="editForm">
                
                <!-- Title -->
                <div class="form-group-modern">
                  <label class="form-label-modern">
                    <i class="fas fa-heading"></i>
                    Project Title
                  </label>
                  <input 
                    name="title" 
                    class="form-control-modern" 
                    value="<?= htmlspecialchars($project['title']) ?>" 
                    required
                    placeholder="Enter your project title"
                    maxlength="100"
                  >
                  <div class="char-counter">
                    <span id="titleCounter">0</span>/100 characters
                  </div>
                </div>
                
                <!-- Domain -->
                <div class="form-group-modern">
                  <label class="form-label-modern">
                    <i class="fas fa-tags"></i>
                    Project Domain
                  </label>
                  <select name="domain" class="form-select-modern" required>
                    <option value="">Select a domain</option>
                    <?php foreach(['AI','Web Development','Mobile App','IoT','Data Science','Cybersecurity','Blockchain','Other'] as $d): ?>
                      <option value="<?= $d ?>" <?= $project['domain']===$d?'selected':'' ?>><?= $d ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <!-- Introduction -->
                <div class="form-group-modern">
                  <label class="form-label-modern">
                    <i class="fas fa-align-left"></i>
                    Project Introduction
                  </label>
                  <textarea 
                    name="intro" 
                    class="form-control-modern textarea-modern" 
                    required
                    placeholder="Provide a detailed introduction to your project..."
                    maxlength="1000"
                  ><?= htmlspecialchars($project['intro']) ?></textarea>
                  <div class="char-counter">
                    <span id="introCounter">0</span>/1000 characters
                  </div>
                </div>
                
                <!-- Importance -->
                <div class="form-group-modern">
                  <label class="form-label-modern">
                    <i class="fas fa-star"></i>
                    Project Importance
                  </label>
                  <textarea 
                    name="importance" 
                    class="form-control-modern textarea-modern" 
                    required
                    placeholder="Explain why this project is important and its potential impact..."
                    maxlength="1000"
                  ><?= htmlspecialchars($project['importance']) ?></textarea>
                  <div class="char-counter">
                    <span id="importanceCounter">0</span>/1000 characters
                  </div>
                </div>
                
                <!-- File Upload -->
                <div class="form-group-modern">
                  <label class="form-label-modern">
                    <i class="fas fa-file-upload"></i>
                    Project File
                  </label>
                  
                  <!-- Current File Display -->
                  <?php if ($project['file_path']): ?>
                    <div class="current-file">
                      <div class="current-file-icon">
                        <i class="fas fa-file"></i>
                      </div>
                      <div class="current-file-info">
                        <div class="current-file-name">Current File</div>
                        <div class="current-file-meta">
                          <a href="<?= htmlspecialchars($project['file_path']) ?>" target="_blank" style="color: #0ea5e9; text-decoration: none;">
                            <i class="fas fa-download"></i> Download Current File
                          </a>
                        </div>
                      </div>
                    </div>
                  <?php endif; ?>
                  
                  <!-- File Upload Area -->
                  <div class="file-upload-area" onclick="document.getElementById('fileInput').click()">
                    <div class="file-upload-icon">
                      <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="file-upload-text">
                      Click to upload new file or drag and drop
                    </div>
                    <div class="file-upload-hint">
                      Leave blank to keep current file • PDF, DOC, DOCX up to 10MB
                    </div>
                    <input 
                      type="file" 
                      name="file" 
                      id="fileInput"
                      style="display: none;"
                      accept=".pdf,.doc,.docx,.txt"
                    >
                  </div>
                  <div id="selectedFile" style="display: none; margin-top: 1rem; padding: 1rem; background: #f0f9ff; border-radius: 8px; color: #0369a1;">
                    <i class="fas fa-file"></i> <span id="fileName"></span>
                  </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="btn-group-modern">
                  <button type="submit" class="btn-update" id="updateBtn">
                    <i class="fas fa-save"></i>
                    Update Project
                  </button>
                  <a href="student_dashboard.php" class="btn-cancel">
                    <i class="fas fa-times"></i>
                    Cancel
                  </a>
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
      // Character counters
      function setupCharCounter(textareaId, counterId, maxLength) {
        const textarea = document.querySelector(`[name="${textareaId}"]`);
        const counter = document.getElementById(counterId);
        
        function updateCounter() {
          const length = textarea.value.length;
          counter.textContent = length;
          counter.parentElement.style.color = length > maxLength * 0.9 ? '#ef4444' : '#9ca3af';
        }
        
        textarea.addEventListener('input', updateCounter);
        updateCounter(); // Initial count
      }
      
      setupCharCounter('title', 'titleCounter', 100);
      setupCharCounter('intro', 'introCounter', 1000);
      setupCharCounter('importance', 'importanceCounter', 1000);
      
      // File upload handling
      const fileInput = document.getElementById('fileInput');
      const uploadArea = document.querySelector('.file-upload-area');
      const selectedFile = document.getElementById('selectedFile');
      const fileName = document.getElementById('fileName');
      
      fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
          fileName.textContent = this.files[0].name;
          selectedFile.style.display = 'block';
        } else {
          selectedFile.style.display = 'none';
        }
      });
      
      // Drag and drop
      ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
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
      
      // Form submission with loading state
      const editForm = document.getElementById('editForm');
      const updateBtn = document.getElementById('updateBtn');
      const originalText = updateBtn.innerHTML;
      
      editForm.addEventListener('submit', function() {
        updateBtn.classList.add('loading');
        updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        updateBtn.disabled = true;
        
        // Disable cancel button
        document.querySelector('.btn-cancel').style.pointerEvents = 'none';
        document.querySelector('.btn-cancel').style.opacity = '0.6';
      });
      
      // Reset button if form submission fails (page reloads with error)
      if (document.querySelector('.alert-error-modern')) {
        updateBtn.innerHTML = originalText;
        updateBtn.disabled = false;
        updateBtn.classList.remove('loading');
        
        document.querySelector('.btn-cancel').style.pointerEvents = '';
        document.querySelector('.btn-cancel').style.opacity = '';
      }
    });
  </script>
</body>

</html>
