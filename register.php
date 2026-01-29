<?php
// register.php - Student Signup
require_once 'config.php';

$error = '';
$success = '';
if ($_POST) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    if (!$username||!$password||!$name||!$email) {
        $error = 'All fields are required.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username=?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Username already taken.';
        } else {
            $hash = hash('sha256',$password);
            $stmt = $pdo->prepare("INSERT INTO users (username,password,role,name,email) VALUES(?,?,?,?,?)");
            $stmt->execute([$username,$hash,'student',$name,$email]);
            $success = 'Registration successful. You may <a href="index.php" style="color: #667eea; text-decoration: none; font-weight: 600;">login</a>.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register - Project Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow-x: hidden;
    }

    /* Animated Background Elements */
    body::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.03)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
      animation: float 20s ease-in-out infinite;
      z-index: 1;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0px) rotate(0deg); }
      50% { transform: translateY(-20px) rotate(1deg); }
    }

    .register-container {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 500px;
      margin: 2rem;
    }

    .register-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      padding: 3rem;
      box-shadow: 
        0 25px 50px -12px rgba(0, 0, 0, 0.25),
        0 0 0 1px rgba(255, 255, 255, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.2);
      animation: slideInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
      position: relative;
      overflow: hidden;
    }

    .register-card::before {
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

    @keyframes shimmer {
      0%, 100% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
    }

    .brand-header {
      text-align: center;
      margin-bottom: 2.5rem;
    }

    .brand-logo {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      border-radius: 20px;
      margin: 0 auto 1rem;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      color: white;
      box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
      animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }

    .register-title {
      font-size: 2rem;
      font-weight: 700;
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 0.5rem;
    }

    .register-subtitle {
      color: #6b7280;
      font-size: 1rem;
      font-weight: 500;
    }

    .form-group {
      position: relative;
      margin-bottom: 2rem;
    }

    .form-control-modern {
      width: 100%;
      padding: 1rem 1rem 1rem 3rem;
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

    .form-control-modern:valid {
      border-color: #10b981;
    }

    .form-control-modern:invalid:not(:placeholder-shown) {
      border-color: #ef4444;
    }

    .form-icon {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: #9ca3af;
      font-size: 1.1rem;
      transition: color 0.3s ease;
    }

    .form-group:focus-within .form-icon {
      color: #667eea;
    }

    .form-label {
      position: absolute;
      left: 3rem;
      top: 1rem;
      color: #9ca3af;
      font-size: 1rem;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      pointer-events: none;
      background: transparent;
    }

    .form-control-modern:focus + .form-label,
    .form-control-modern:not(:placeholder-shown) + .form-label {
      top: -0.5rem;
      left: 1rem;
      font-size: 0.875rem;
      color: #667eea;
      background: white;
      padding: 0 0.5rem;
      border-radius: 4px;
    }

    .form-control-modern:valid + .form-label {
      color: #10b981;
    }

    .form-control-modern:invalid:not(:placeholder-shown) + .form-label {
      color: #ef4444;
    }

    .password-strength {
      margin-top: 0.75rem;
      display: none;
    }

    .strength-bar {
      width: 100%;
      height: 6px;
      background: #e2e8f0;
      border-radius: 3px;
      overflow: hidden;
      margin-bottom: 0.5rem;
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
    }

    .btn-register {
      width: 100%;
      padding: 1rem;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      border-radius: 16px;
      color: white;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      text-transform: none;
      letter-spacing: 0.025em;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
      position: relative;
      overflow: hidden;
      margin-top: 1rem;
    }

    .btn-register::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.6s;
    }

    .btn-register:hover::before {
      left: 100%;
    }

    .btn-register:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
      background: linear-gradient(135deg, #5a67d8 0%, #667eea 100%);
    }

    .btn-register:active {
      transform: translateY(0);
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .btn-register:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
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

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      75% { transform: translateX(5px); }
    }

    .login-link {
      text-align: center;
      margin-top: 2rem;
      padding-top: 2rem;
      border-top: 1px solid #e5e7eb;
    }

    .login-link a {
      color: #667eea;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .login-link a:hover {
      color: #764ba2;
      transform: translateY(-1px);
    }

    .floating-elements {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      overflow: hidden;
    }

    .floating-element {
      position: absolute;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      animation: floatAnimation 6s ease-in-out infinite;
    }

    .floating-element:nth-child(1) {
      width: 60px;
      height: 60px;
      top: 10%;
      left: 10%;
      animation-delay: 0s;
    }

    .floating-element:nth-child(2) {
      width: 80px;
      height: 80px;
      top: 20%;
      right: 10%;
      animation-delay: 2s;
    }

    .floating-element:nth-child(3) {
      width: 40px;
      height: 40px;
      bottom: 20%;
      left: 20%;
      animation-delay: 4s;
    }

    @keyframes floatAnimation {
      0%, 100% { transform: translateY(0px) rotate(0deg); }
      50% { transform: translateY(-20px) rotate(180deg); }
    }

    /* Loading state */
    .btn-register.loading {
      pointer-events: none;
      opacity: 0.8;
    }

    .btn-register.loading::after {
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

    /* Form validation feedback */
    .validation-feedback {
      margin-top: 0.5rem;
      font-size: 0.875rem;
      display: none;
    }

    .validation-feedback.valid {
      color: #10b981;
      display: flex;
      align-items: center;
      gap: 0.25rem;
    }

    .validation-feedback.invalid {
      color: #ef4444;
      display: flex;
      align-items: center;
      gap: 0.25rem;
    }

    /* Mobile Responsive */
    @media (max-width: 576px) {
      .register-container {
        margin: 1rem;
      }
      
      .register-card {
        padding: 2rem;
      }
      
      .register-title {
        font-size: 1.75rem;
      }
      
      .brand-logo {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
      }
    }

    @media (max-width: 400px) {
      .register-card {
        padding: 1.5rem;
      }
    }

    /* Username availability indicator */
    .username-check {
      position: absolute;
      right: 1rem;
      top: 50%;
      transform: translateY(-50%);
      font-size: 1rem;
      display: none;
    }

    .username-available {
      color: #10b981;
    }

    .username-taken {
      color: #ef4444;
    }

    .username-checking {
      color: #f59e0b;
    }
  </style>
</head>

<body>
  <!-- Floating Background Elements -->
  <div class="floating-elements">
    <div class="floating-element"></div>
    <div class="floating-element"></div>
    <div class="floating-element"></div>
  </div>

  <div class="register-container">
    <div class="register-card">
      <!-- Brand Header -->
      <div class="brand-header">
        <div class="brand-logo">
          <i class="fas fa-user-plus"></i>
        </div>
        <h1 class="register-title">Join Project Portal</h1>
        <p class="register-subtitle">Create your student account to get started</p>
      </div>

      <!-- Error/Success Alerts -->
      <?php if ($error): ?>
        <div class="alert-modern alert-error-modern">
          <i class="fas fa-exclamation-triangle"></i>
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>
      
      <?php if ($success): ?>
        <div class="alert-modern alert-success-modern">
          <i class="fas fa-check-circle"></i>
          <?php echo $success; ?>
        </div>
      <?php endif; ?>

      <!-- Registration Form -->
      <form method="post" id="registerForm" novalidate>
        <!-- Name Field -->
        <div class="form-group">
          <i class="fas fa-user form-icon"></i>
          <input 
            type="text" 
            name="name" 
            class="form-control-modern" 
            id="name" 
            placeholder=" "
            required
            autocomplete="name"
          >
          <label for="name" class="form-label">Full Name</label>
          <div class="validation-feedback" id="nameValidation"></div>
        </div>

        <!-- Email Field -->
        <div class="form-group">
          <i class="fas fa-envelope form-icon"></i>
          <input 
            type="email" 
            name="email" 
            class="form-control-modern" 
            id="email" 
            placeholder=" "
            required
            autocomplete="email"
          >
          <label for="email" class="form-label">Email Address</label>
          <div class="validation-feedback" id="emailValidation"></div>
        </div>

        <!-- Username Field -->
        <div class="form-group">
          <i class="fas fa-at form-icon"></i>
          <input 
            type="text" 
            name="username" 
            class="form-control-modern" 
            id="username" 
            placeholder=" "
            required
            autocomplete="username"
            pattern="[a-zA-Z0-9_]{3,20}"
          >
          <label for="username" class="form-label">Username</label>
          <div class="username-check" id="usernameCheck"></div>
          <div class="validation-feedback" id="usernameValidation"></div>
        </div>

        <!-- Password Field -->
        <div class="form-group">
          <i class="fas fa-lock form-icon"></i>
          <input 
            type="password" 
            name="password" 
            class="form-control-modern" 
            id="password" 
            placeholder=" "
            required
            autocomplete="new-password"
            minlength="6"
          >
          <label for="password" class="form-label">Password</label>
          <div class="password-strength" id="passwordStrength">
            <div class="strength-bar">
              <div class="strength-fill"></div>
            </div>
            <div class="strength-text"></div>
          </div>
          <div class="validation-feedback" id="passwordValidation"></div>
        </div>

        <button type="submit" class="btn-register" id="registerBtn">
          <i class="fas fa-user-plus" style="margin-right: 0.5rem;"></i>
          Create Account
        </button>
      </form>

      <!-- Login Link -->
      <div class="login-link">
        <p style="color: #6b7280; margin-bottom: 0.5rem;">Already have an account?</p>
        <a href="index.php">
          <i class="fas fa-sign-in-alt"></i>
          Sign In
        </a>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('registerForm');
      const submitBtn = document.getElementById('registerBtn');
      const passwordInput = document.getElementById('password');
      const usernameInput = document.getElementById('username');
      const nameInput = document.getElementById('name');
      const emailInput = document.getElementById('email');
      const originalBtnText = submitBtn.innerHTML;
      
      // Password strength checker
      passwordInput.addEventListener('input', function() {
        const password = this.value;
        const strengthContainer = document.getElementById('passwordStrength');
        
        if (password.length === 0) {
          strengthContainer.style.display = 'none';
          return;
        }
        
        strengthContainer.style.display = 'block';
        const strength = calculatePasswordStrength(password);
        updatePasswordStrengthUI(strength);
        validatePassword();
      });
      
      // Form validation
      function validateName() {
        const name = nameInput.value.trim();
        const feedback = document.getElementById('nameValidation');
        
        if (name.length === 0) {
          feedback.style.display = 'none';
          return false;
        }
        
        if (name.length < 2) {
          feedback.className = 'validation-feedback invalid';
          feedback.innerHTML = '<i class="fas fa-times"></i> Name must be at least 2 characters';
          return false;
        }
        
        feedback.className = 'validation-feedback valid';
        feedback.innerHTML = '<i class="fas fa-check"></i> Valid name';
        return true;
      }
      
      function validateEmail() {
        const email = emailInput.value.trim();
        const feedback = document.getElementById('emailValidation');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email.length === 0) {
          feedback.style.display = 'none';
          return false;
        }
        
        if (!emailRegex.test(email)) {
          feedback.className = 'validation-feedback invalid';
          feedback.innerHTML = '<i class="fas fa-times"></i> Invalid email format';
          return false;
        }
        
        feedback.className = 'validation-feedback valid';
        feedback.innerHTML = '<i class="fas fa-check"></i> Valid email';
        return true;
      }
      
      function validateUsername() {
        const username = usernameInput.value.trim();
        const feedback = document.getElementById('usernameValidation');
        const usernameRegex = /^[a-zA-Z0-9_]{3,20}$/;
        
        if (username.length === 0) {
          feedback.style.display = 'none';
          return false;
        }
        
        if (!usernameRegex.test(username)) {
          feedback.className = 'validation-feedback invalid';
          feedback.innerHTML = '<i class="fas fa-times"></i> Username must be 3-20 characters, letters, numbers, and underscores only';
          return false;
        }
        
        feedback.className = 'validation-feedback valid';
        feedback.innerHTML = '<i class="fas fa-check"></i> Valid username format';
        return true;
      }
      
      function validatePassword() {
        const password = passwordInput.value;
        const feedback = document.getElementById('passwordValidation');
        
        if (password.length === 0) {
          feedback.style.display = 'none';
          return false;
        }
        
        if (password.length < 6) {
          feedback.className = 'validation-feedback invalid';
          feedback.innerHTML = '<i class="fas fa-times"></i> Password must be at least 6 characters';
          return false;
        }
        
        feedback.className = 'validation-feedback valid';
        feedback.innerHTML = '<i class="fas fa-check"></i> Password meets requirements';
        return true;
      }
      
      function calculatePasswordStrength(password) {
        let score = 0;
        
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
        const strengthBar = document.querySelector('.strength-bar');
        const strengthText = document.querySelector('.strength-text');
        
        strengthBar.className = `strength-bar strength-${strength.level}`;
        strengthText.textContent = strength.text;
        strengthText.className = `strength-text`;
      }
      
      // Add event listeners for real-time validation
      nameInput.addEventListener('input', validateName);
      emailInput.addEventListener('input', validateEmail);
      usernameInput.addEventListener('input', validateUsername);
      
      // Form submission
      form.addEventListener('submit', function(e) {
        const isValid = validateName() && validateEmail() && validateUsername() && validatePassword();
        
        if (!isValid) {
          e.preventDefault();
          return;
        }
        
        // Show loading state
        submitBtn.classList.add('loading');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 0.5rem;"></i>Creating Account...';
        submitBtn.disabled = true;
      });
      
      // Reset button if form submission fails (page reloads with error)
      if (document.querySelector('.alert-error-modern')) {
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
        submitBtn.classList.remove('loading');
      }
      
      // Focus enhancement
      const inputs = document.querySelectorAll('.form-control-modern');
      inputs.forEach(input => {
        input.addEventListener('focus', function() {
          this.parentElement.style.transform = 'scale(1.02)';
          this.parentElement.style.transition = 'transform 0.2s ease';
        });
        
        input.addEventListener('blur', function() {
          this.parentElement.style.transform = 'scale(1)';
        });
      });
    });
  </script>
</body>

</html>
