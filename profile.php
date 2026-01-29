<?php
// profile.php - NEW FILE
require_once 'config.php';
if (!isLoggedIn()) redirect('index.php');

$error = '';
$success = '';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if ($_POST) {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $new_password = $_POST['new_password'] ?? '';
  $confirm_password = $_POST['confirm_password'] ?? '';
  
  if (!$name || !$email) {
    $error = 'Name and email are required.';
  } elseif ($new_password && $new_password !== $confirm_password) {
    $error = 'Passwords do not match.';
  } else {
    // Update profile
    if ($new_password) {
      $hash = hash('sha256', $new_password);
      $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, password=? WHERE id=?");
      $stmt->execute([$name, $email, $hash, $_SESSION['user_id']]);
    } else {
      $stmt = $pdo->prepare("UPDATE users SET name=?, email=? WHERE id=?");
      $stmt->execute([$name, $email, $_SESSION['user_id']]);
    }
    
    $_SESSION['name'] = $name;
    $success = 'Profile updated successfully!';
    
    // Refresh user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
  }
}

// Get user stats
if (isStudent()) {
  $stats = $pdo->prepare("SELECT COUNT(*) as total, 
    SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending
    FROM projects WHERE student_id=?");
  $stats->execute([$_SESSION['user_id']]);
  $stats = $stats->fetch();
} else {
  $stats = $pdo->prepare("SELECT COUNT(*) as reviewed FROM project_reviews WHERE professor_id=?");
  $stats->execute([$_SESSION['user_id']]);
  $stats = $stats->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My Profile - Project Portal</title>
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

    .profile-container {
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

    .profile-card {
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
      margin-bottom: 2rem;
    }

    .profile-card::before {
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

    .stats-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      box-shadow: 
        0 25px 50px -12px rgba(0, 0, 0, 0.25),
        0 0 0 1px rgba(255, 255, 255, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.2);
      overflow: hidden;
      animation: slideInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.2s both;
      position: relative;
    }

    .stats-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #10b981, #059669, #047857);
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

    .profile-header {
      text-align: center;
      margin-bottom: 3rem;
      padding-bottom: 2rem;
      border-bottom: 2px solid #f1f5f9;
      position: relative;
    }

    .profile-avatar {
      width: 120px;
      height: 120px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
      font-size: 3rem;
      color: white;
      font-weight: bold;
      box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
      animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3); }
      50% { transform: scale(1.05); box-shadow: 0 12px 40px rgba(102, 126, 234, 0.4); }
    }

    .profile-name {
      font-size: 1.75rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 0.5rem;
    }

    .profile-role {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      padding: 0.5rem 1.5rem;
      border-radius: 25px;
      font-weight: 600;
      display: inline-block;
      margin-bottom: 0.5rem;
    }

    .profile-meta {
      color: #6b7280;
      font-size: 0.9rem;
    }

    .form-section {
      margin-bottom: 3rem;
    }

    .section-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
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
    }

    .form-control-modern:focus {
      outline: none;
      border-color: #667eea;
      background: white;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
      transform: translateY(-1px);
    }

    .form-control-modern:disabled {
      background: #f1f5f9;
      color: #64748b;
      cursor: not-allowed;
    }

    .form-hint {
      font-size: 0.875rem;
      color: #6b7280;
      margin-top: 0.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .password-section {
      background: #f8fafc;
      border: 2px solid #e2e8f0;
      border-radius: 20px;
      padding: 2rem;
      margin-top: 2rem;
    }

    .password-strength {
      margin-top: 0.5rem;
    }

    .strength-bar {
      width: 100%;
      height: 6px;
      background: #e2e8f0;
      border-radius: 3px;
      overflow: hidden;
      margin: 0.5rem 0;
    }

    .strength-fill {
      height: 100%;
      transition: all 0.3s ease;
      border-radius: 3px;
    }

    .strength-weak .strength-fill { width: 25%; background: #ef4444; }
    .strength-fair .strength-fill { width: 50%; background: #f59e0b; }
    .strength-good .strength-fill { width: 75%; background: #3b82f6; }
    .strength-strong .strength-fill { width: 100%; background: #10b981; }

    .strength-text {
      font-size: 0.875rem;
      font-weight: 500;
      margin-top: 0.25rem;
    }

    .btn-update {
      background: linear-gradient(135deg, #667eea, #764ba2);
      border: none;
      border-radius: 16px;
      color: white;
      padding: 1rem 2rem;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
      position: relative;
      overflow: hidden;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      justify-content: center;
      min-width: 180px;
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
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
      background: linear-gradient(135deg, #5a67d8, #667eea);
    }

    .btn-update:active {
      transform: translateY(0);
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
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

    .stats-header {
      text-align: center;
      margin-bottom: 2rem;
      padding-bottom: 1.5rem;
      border-bottom: 2px solid #f1f5f9;
    }

    .stats-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
    }

    .stats-subtitle {
      color: #6b7280;
      font-size: 0.95rem;
    }

    .stats-grid {
      display: grid;
      gap: 1.5rem;
    }

    .stat-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem;
      background: #f8fafc;
      border-radius: 12px;
      transition: all 0.3s ease;
    }

    .stat-item:hover {
      background: #f1f5f9;
      transform: translateX(5px);
    }

    .stat-label {
      color: #374151;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .stat-badge {
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-weight: 600;
      font-size: 0.9rem;
      min-width: 40px;
      text-align: center;
    }

    .badge-total { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
    .badge-approved { background: linear-gradient(135deg, #10b981, #059669); color: white; }
    .badge-pending { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
    .badge-rejected { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
    .badge-reviewed { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }

    .member-since {
      text-align: center;
      margin-top: 2rem;
      padding-top: 1.5rem;
      border-top: 2px solid #f1f5f9;
      color: #6b7280;
      font-size: 0.9rem;
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
      .profile-container {
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
      
      .profile-avatar {
        width: 80px;
        height: 80px;
        font-size: 2rem;
      }
      
      .profile-name {
        font-size: 1.5rem;
      }
    }

    @media (max-width: 576px) {
      .card-body-modern {
        padding: 1.5rem;
      }
      
      .password-section {
        padding: 1.5rem;
      }
      
      .profile-header {
        margin-bottom: 2rem;
      }
    }
  </style>
</head>

<body>
  <?php include __DIR__ . '/includes/navbar.php'; ?>
  
  <div class="profile-container">
    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title">
        <i class="fas fa-user-circle"></i>
        My Profile
      </h1>
      <p class="page-subtitle">Manage your account settings and view your statistics</p>
    </div>

    <div class="container">
      <div class="row">
        <!-- Profile Form Column -->
        <div class="col-lg-8">
          <div class="profile-card">
            <div class="card-body-modern">
              <!-- Profile Header -->
              <div class="profile-header">
                <div class="profile-avatar">
                  <?= strtoupper(substr($user['name'], 0, 1)) ?>
                </div>
                <h2 class="profile-name"><?= htmlspecialchars($user['name']) ?></h2>
                <div class="profile-role"><?= ucfirst($user['role']) ?></div>
                <div class="profile-meta">
                  <i class="fas fa-at"></i> <?= htmlspecialchars($user['username']) ?>
                </div>
              </div>

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

              <form method="post" id="profileForm">
                <!-- Basic Information -->
                <div class="form-section">
                  <h3 class="section-title">
                    <i class="fas fa-user"></i>
                    Basic Information
                  </h3>
                  
                  <div class="form-group-modern">
                    <label class="form-label-modern">
                      <i class="fas fa-signature"></i>
                      Full Name
                    </label>
                    <input 
                      name="name" 
                      class="form-control-modern" 
                      value="<?= htmlspecialchars($user['name']) ?>" 
                      required
                      placeholder="Enter your full name"
                    >
                  </div>
                  
                  <div class="form-group-modern">
                    <label class="form-label-modern">
                      <i class="fas fa-envelope"></i>
                      Email Address
                    </label>
                    <input 
                      name="email" 
                      type="email" 
                      class="form-control-modern" 
                      value="<?= htmlspecialchars($user['email']) ?>" 
                      required
                      placeholder="Enter your email address"
                    >
                  </div>
                  
                  <div class="form-group-modern">
                    <label class="form-label-modern">
                      <i class="fas fa-user-tag"></i>
                      Username
                    </label>
                    <input 
                      class="form-control-modern" 
                      value="<?= htmlspecialchars($user['username']) ?>" 
                      disabled
                    >
                    <div class="form-hint">
                      <i class="fas fa-info-circle"></i>
                      Username cannot be changed for security reasons
                    </div>
                  </div>
                  
                  <div class="form-group-modern">
                    <label class="form-label-modern">
                      <i class="fas fa-shield-alt"></i>
                      Account Type
                    </label>
                    <input 
                      class="form-control-modern" 
                      value="<?= ucfirst($user['role']) ?>" 
                      disabled
                    >
                  </div>
                </div>

                <!-- Password Change Section -->
                <div class="password-section">
                  <h3 class="section-title">
                    <i class="fas fa-key"></i>
                    Change Password
                  </h3>
                  <p style="color: #6b7280; margin-bottom: 1.5rem;">
                    Leave blank to keep your current password
                  </p>
                  
                  <div class="form-group-modern">
                    <label class="form-label-modern">
                      <i class="fas fa-lock"></i>
                      New Password
                    </label>
                    <input 
                      type="password" 
                      name="new_password" 
                      class="form-control-modern" 
                      id="newPassword"
                      placeholder="Enter new password (optional)"
                    >
                    <div class="password-strength" id="passwordStrength" style="display: none;">
                      <div class="strength-bar">
                        <div class="strength-fill"></div>
                      </div>
                      <div class="strength-text"></div>
                    </div>
                  </div>
                  
                  <div class="form-group-modern">
                    <label class="form-label-modern">
                      <i class="fas fa-lock"></i>
                      Confirm New Password
                    </label>
                    <input 
                      type="password" 
                      name="confirm_password" 
                      class="form-control-modern" 
                      id="confirmPassword"
                      placeholder="Confirm new password"
                    >
                    <div id="passwordMatch" style="display: none; margin-top: 0.5rem; font-size: 0.875rem;"></div>
                  </div>
                </div>

                <button type="submit" class="btn-update" id="updateBtn">
                  <i class="fas fa-save"></i>
                  Update Profile
                </button>
              </form>
            </div>
          </div>
        </div>
        
        <!-- Statistics Column -->
        <div class="col-lg-4">
          <div class="stats-card">
            <div class="card-body-modern">
              <div class="stats-header">
                <h3 class="stats-title">
                  <i class="fas fa-chart-bar"></i>
                  Account Statistics
                </h3>
                <p class="stats-subtitle">Your activity overview</p>
              </div>
              
              <div class="stats-grid">
                <?php if (isStudent()): ?>
                  <div class="stat-item">
                    <div class="stat-label">
                      <i class="fas fa-project-diagram"></i>
                      Total Projects
                    </div>
                    <div class="stat-badge badge-total"><?= $stats['total'] ?></div>
                  </div>
                  
                  <div class="stat-item">
                    <div class="stat-label">
                      <i class="fas fa-check-circle"></i>
                      Approved
                    </div>
                    <div class="stat-badge badge-approved"><?= $stats['approved'] ?></div>
                  </div>
                  
                  <div class="stat-item">
                    <div class="stat-label">
                      <i class="fas fa-clock"></i>
                      Pending Review
                    </div>
                    <div class="stat-badge badge-pending"><?= $stats['pending'] ?></div>
                  </div>
                  
                  <div class="stat-item">
                    <div class="stat-label">
                      <i class="fas fa-times-circle"></i>
                      Rejected
                    </div>
                    <div class="stat-badge badge-rejected"><?= $stats['rejected'] ?></div>
                  </div>
                <?php else: ?>
                  <div class="stat-item">
                    <div class="stat-label">
                      <i class="fas fa-clipboard-check"></i>
                      Projects Reviewed
                    </div>
                    <div class="stat-badge badge-reviewed"><?= $stats['reviewed'] ?></div>
                  </div>
                <?php endif; ?>
              </div>
              
              <div class="member-since">
                <i class="fas fa-calendar-alt"></i>
                Member since <?= date('F Y', strtotime($user['created_at'])) ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const newPassword = document.getElementById('newPassword');
      const confirmPassword = document.getElementById('confirmPassword');
      const passwordStrength = document.getElementById('passwordStrength');
      const passwordMatch = document.getElementById('passwordMatch');
      const profileForm = document.getElementById('profileForm');
      const updateBtn = document.getElementById('updateBtn');
      
      // Password strength checker
      newPassword.addEventListener('input', function() {
        const password = this.value;
        
        if (password.length === 0) {
          passwordStrength.style.display = 'none';
          return;
        }
        
        passwordStrength.style.display = 'block';
        const strength = calculatePasswordStrength(password);
        updatePasswordStrengthUI(strength);
      });
      
      // Password match checker
      function checkPasswordMatch() {
        const newPass = newPassword.value;
        const confirmPass = confirmPassword.value;
        
        if (confirmPass.length === 0) {
          passwordMatch.style.display = 'none';
          return;
        }
        
        passwordMatch.style.display = 'block';
        
        if (newPass === confirmPass) {
          passwordMatch.innerHTML = '<i class="fas fa-check" style="color: #10b981;"></i> Passwords match';
          passwordMatch.style.color = '#10b981';
        } else {
          passwordMatch.innerHTML = '<i class="fas fa-times" style="color: #ef4444;"></i> Passwords do not match';
          passwordMatch.style.color = '#ef4444';
        }
      }
      
      confirmPassword.addEventListener('input', checkPasswordMatch);
      newPassword.addEventListener('input', checkPasswordMatch);
      
      function calculatePasswordStrength(password) {
        let score = 0;
        let feedback = '';
        
        if (password.length >= 8) score += 1;
        if (/[a-z]/.test(password)) score += 1;
        if (/[A-Z]/.test(password)) score += 1;
        if (/[0-9]/.test(password)) score += 1;
        if (/[^A-Za-z0-9]/.test(password)) score += 1;
        
        if (score < 2) {
          return { level: 'weak', text: 'Weak password' };
        } else if (score < 3) {
          return { level: 'fair', text: 'Fair password' };
        } else if (score < 4) {
          return { level: 'good', text: 'Good password' };
        } else {
          return { level: 'strong', text: 'Strong password' };
        }
      }
      
      function updatePasswordStrengthUI(strength) {
        const strengthBar = passwordStrength.querySelector('.strength-bar');
        const strengthText = passwordStrength.querySelector('.strength-text');
        
        strengthBar.className = `strength-bar strength-${strength.level}`;
        strengthText.textContent = strength.text;
        strengthText.className = `strength-text text-${strength.level}`;
      }
      
      // Form submission with loading state
      profileForm.addEventListener('submit', function() {
        updateBtn.classList.add('loading');
        updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating Profile...';
        updateBtn.disabled = true;
      });
      
      // Reset button if form submission fails (page reloads with error)
      if (document.querySelector('.alert-error-modern')) {
        updateBtn.innerHTML = '<i class="fas fa-save"></i> Update Profile';
        updateBtn.disabled = false;
        updateBtn.classList.remove('loading');
      }
      
      // Auto-save indication
      const inputs = document.querySelectorAll('input[name="name"], input[name="email"]');
      inputs.forEach(input => {
        let timeout;
        input.addEventListener('input', function() {
          clearTimeout(timeout);
          this.style.borderColor = '#f59e0b';
          
          timeout = setTimeout(() => {
            this.style.borderColor = '#e5e7eb';
          }, 1000);
        });
      });
    });
  </script>
</body>

</html>
