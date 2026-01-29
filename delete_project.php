<?php
// delete_project.php - NEW FILE
require_once 'config.php';
if (!isLoggedIn() || !isStudent()) redirect('index.php');

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id=? AND student_id=? AND status='pending'");
$stmt->execute([$id, $_SESSION['user_id']]);
$project = $stmt->fetch();
if (!$project) redirect('student_dashboard.php');

if ($_POST && $_POST['confirm'] === 'yes') {
  // Delete associated records first
  $pdo->prepare("DELETE FROM ratings WHERE project_id=?")->execute([$id]);
  $pdo->prepare("DELETE FROM comments WHERE project_id=?")->execute([$id]);
  $pdo->prepare("DELETE FROM project_reviews WHERE project_id=?")->execute([$id]);
  
  // Delete file if exists
  if ($project['file_path'] && file_exists($project['file_path'])) {
    unlink($project['file_path']);
  }
  
  // Delete project
  $pdo->prepare("DELETE FROM projects WHERE id=?")->execute([$id]);
  
  redirect('student_dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Delete Project - Project Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      padding-top: 2rem;
    }

    .main-container {
      min-height: calc(100vh - 100px);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
    }

    .delete-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      box-shadow: 
        0 25px 50px -12px rgba(0, 0, 0, 0.25),
        0 0 0 1px rgba(255, 255, 255, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.2);
      overflow: hidden;
      max-width: 500px;
      width: 100%;
      animation: slideInDown 0.6s cubic-bezier(0.16, 1, 0.3, 1);
      position: relative;
    }

    @keyframes slideInDown {
      from {
        opacity: 0;
        transform: translateY(-40px) scale(0.95);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .delete-header {
      background: linear-gradient(135deg, #ff6b6b, #ee5a24);
      padding: 2rem;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    .delete-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="warning-pattern" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M 20 0 L 0 0 0 20" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23warning-pattern)"/></svg>');
      opacity: 0.3;
    }

    .warning-icon {
      width: 80px;
      height: 80px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
      font-size: 2rem;
      color: white;
      animation: pulse 2s ease-in-out infinite;
      position: relative;
      z-index: 2;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4); }
      50% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(255, 255, 255, 0); }
    }

    .delete-title {
      color: white;
      font-size: 1.75rem;
      font-weight: 700;
      margin: 0;
      position: relative;
      z-index: 2;
    }

    .delete-body {
      padding: 2.5rem;
      text-align: center;
    }

    .confirmation-text {
      color: #374151;
      font-size: 1.1rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
    }

    .project-info {
      background: linear-gradient(135deg, #f8fafc, #e2e8f0);
      border: 2px solid #e2e8f0;
      border-radius: 16px;
      padding: 1.5rem;
      margin: 1.5rem 0;
      position: relative;
      overflow: hidden;
    }

    .project-info::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #667eea, #764ba2);
    }

    .project-title {
      font-size: 1.2rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      justify-content: center;
    }

    .project-meta {
      color: #6b7280;
      font-size: 0.9rem;
      font-weight: 500;
    }

    .warning-message {
      background: linear-gradient(135deg, #fef2f2, #fee2e2);
      border: 2px solid #fecaca;
      border-radius: 12px;
      padding: 1rem;
      margin: 1.5rem 0;
      color: #dc2626;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      justify-content: center;
      animation: shake 0.5s ease-in-out;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-3px); }
      75% { transform: translateX(3px); }
    }

    .action-buttons {
      display: flex;
      gap: 1rem;
      justify-content: center;
      flex-wrap: wrap;
      margin-top: 2rem;
    }

    .btn-delete {
      background: linear-gradient(135deg, #ff6b6b, #ee5a24);
      border: none;
      border-radius: 16px;
      color: white;
      padding: 1rem 2rem;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
      position: relative;
      overflow: hidden;
      min-width: 140px;
    }

    .btn-delete::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.6s;
    }

    .btn-delete:hover::before {
      left: 100%;
    }

    .btn-delete:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(255, 107, 107, 0.4);
      background: linear-gradient(135deg, #ee5a24, #ff6b6b);
    }

    .btn-delete:active {
      transform: translateY(0);
      box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
    }

    .btn-cancel {
      background: linear-gradient(135deg, #f8fafc, #e2e8f0);
      border: 2px solid #d1d5db;
      border-radius: 16px;
      color: #374151;
      padding: 1rem 2rem;
      font-weight: 600;
      font-size: 1rem;
      text-decoration: none;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      min-width: 140px;
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

    .btn-cancel:active {
      transform: translateY(0);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    /* Loading state */
    .btn-delete.loading {
      pointer-events: none;
      opacity: 0.8;
    }

    .btn-delete.loading::after {
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
    @media (max-width: 576px) {
      .main-container {
        padding: 1rem;
      }
      
      .delete-card {
        margin: 1rem;
      }
      
      .delete-header {
        padding: 1.5rem;
      }
      
      .delete-body {
        padding: 1.5rem;
      }
      
      .action-buttons {
        flex-direction: column;
        align-items: stretch;
      }
      
      .btn-delete,
      .btn-cancel {
        width: 100%;
        margin: 0.25rem 0;
      }
      
      .warning-icon {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
      }
      
      .delete-title {
        font-size: 1.5rem;
      }
    }

    /* Focus states for accessibility */
    .btn-delete:focus,
    .btn-cancel:focus {
      outline: 2px solid #667eea;
      outline-offset: 2px;
    }
  </style>
</head>

<body>
  <?php include __DIR__ . '/includes/navbar.php'; ?>
  
  <div class="main-container">
    <div class="delete-card">
      <!-- Header -->
      <div class="delete-header">
        <div class="warning-icon">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h1 class="delete-title">Delete Project</h1>
      </div>

      <!-- Body -->
      <div class="delete-body">
        <p class="confirmation-text">
          Are you sure you want to permanently delete this project?
        </p>

        <!-- Project Information -->
        <div class="project-info">
          <div class="project-title">
            <i class="fas fa-file-alt"></i>
            <?= htmlspecialchars($project['title']) ?>
          </div>
          <div class="project-meta">
            Submitted on <?= date('M d, Y', strtotime($project['created_at'])) ?>
          </div>
        </div>

        <!-- Warning Message -->
        <div class="warning-message">
          <i class="fas fa-exclamation-circle"></i>
          <span>This action cannot be undone!</span>
        </div>

        <!-- Action Buttons -->
        <form method="post" id="deleteForm">
          <input type="hidden" name="confirm" value="yes">
          <div class="action-buttons">
            <button type="submit" class="btn-delete" id="deleteBtn">
              <i class="fas fa-trash-alt"></i>
              Yes, Delete Project
            </button>
            <a href="student_dashboard.php" class="btn-cancel">
              <i class="fas fa-arrow-left"></i>
              Cancel
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const deleteForm = document.getElementById('deleteForm');
      const deleteBtn = document.getElementById('deleteBtn');
      const originalText = deleteBtn.innerHTML;
      
      // Add loading state on form submission
      deleteForm.addEventListener('submit', function(e) {
        // Add confirmation dialog for extra safety
        if (!confirm('Are you absolutely sure? This will permanently delete your project and cannot be undone.')) {
          e.preventDefault();
          return;
        }
        
        // Show loading state
        deleteBtn.classList.add('loading');
        deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
        deleteBtn.disabled = true;
        
        // Disable cancel button
        document.querySelector('.btn-cancel').style.pointerEvents = 'none';
        document.querySelector('.btn-cancel').style.opacity = '0.6';
      });
      
      // Keyboard navigation
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          window.location.href = 'student_dashboard.php';
        }
      });
    });
  </script>
</body>

</html>
