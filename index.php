<?php
// index.php - Login Page
require_once 'config.php';

if (isLoggedIn()) {
  redirect(isStudent() ? 'student_dashboard.php' : 'professor_dashboard.php');
}

$error = '';
if ($_POST) {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  if (!$username || !$password) {
    $error = 'Please fill in all fields.';
  } else {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && hash_equals($user['password'], hash('sha256', $password))) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['role'] = $user['role'];
      $_SESSION['name'] = $user['name'];
      redirect($user['role'] === 'student' ? 'student_dashboard.php' : 'professor_dashboard.php');
    } else {
      $error = 'Invalid credentials.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - Project Portal</title>
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

    .login-container {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 450px;
      margin: 2rem;
    }

    .login-card {
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

    .login-card::before {
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

    .login-title {
      font-size: 2rem;
      font-weight: 700;
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 0.5rem;
    }

    .login-subtitle {
      color: #6b7280;
      font-size: 1rem;
      font-weight: 500;
    }

    .form-group {
      position: relative;
      margin-bottom: 1.5rem;
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

    .btn-login {
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
    }

    .btn-login::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.6s;
    }

    .btn-login:hover::before {
      left: 100%;
    }

    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
      background: linear-gradient(135deg, #5a67d8 0%, #667eea 100%);
    }

    .btn-login:active {
      transform: translateY(0);
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .alert-modern {
      background: linear-gradient(135deg, #fee2e2, #fecaca);
      border: 1px solid #f87171;
      color: #dc2626;
      padding: 1rem;
      border-radius: 12px;
      margin-bottom: 1.5rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      animation: shake 0.5s ease-in-out;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      75% { transform: translateX(5px); }
    }

    .register-link {
      text-align: center;
      margin-top: 2rem;
      padding-top: 2rem;
      border-top: 1px solid #e5e7eb;
    }

    .register-link a {
      color: #667eea;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .register-link a:hover {
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

    /* Mobile Responsive */
    @media (max-width: 576px) {
      .login-container {
        margin: 1rem;
      }
      
      .login-card {
        padding: 2rem;
      }
      
      .login-title {
        font-size: 1.75rem;
      }
      
      .brand-logo {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
      }
    }

    @media (max-width: 400px) {
      .login-card {
        padding: 1.5rem;
      }
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

  <div class="login-container">
    <div class="login-card">
      <!-- Brand Header -->
      <div class="brand-header">
        <div class="brand-logo">
          <i class="fas fa-graduation-cap"></i>
        </div>
        <h1 class="login-title">Project Portal</h1>
        <p class="login-subtitle">Welcome back! Please sign in to your account</p>
      </div>

      <!-- Error Alert -->
      <?php if ($error): ?>
        <div class="alert-modern">
          <i class="fas fa-exclamation-triangle"></i>
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <!-- Login Form -->
      <form method="post" novalidate>
        <div class="form-group">
          <i class="fas fa-user form-icon"></i>
          <input 
            type="text" 
            name="username" 
            class="form-control-modern" 
            id="username" 
            placeholder=" "
            required
            autocomplete="username"
          >
          <label for="username" class="form-label">Username</label>
        </div>

        <div class="form-group">
          <i class="fas fa-lock form-icon"></i>
          <input 
            type="password" 
            name="password" 
            class="form-control-modern" 
            id="password" 
            placeholder=" "
            required
            autocomplete="current-password"
          >
          <label for="password" class="form-label">Password</label>
        </div>

        <button type="submit" class="btn-login">
          <i class="fas fa-sign-in-alt" style="margin-right: 0.5rem;"></i>
          Sign In
        </button>
      </form>

      <!-- Register Link -->
      <div class="register-link">
        <p style="color: #6b7280; margin-bottom: 0.5rem;">Don't have an account?</p>
        <a href="register.php">
          <i class="fas fa-user-plus"></i>
          Create Student Account
        </a>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Add subtle interactions -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Add loading state to login button
      const loginForm = document.querySelector('form');
      const loginBtn = document.querySelector('.btn-login');
      const originalText = loginBtn.innerHTML;
      
      loginForm.addEventListener('submit', function() {
        loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 0.5rem;"></i>Signing In...';
        loginBtn.disabled = true;
      });
      
      // Reset button if form submission fails (page reloads with error)
      if (document.querySelector('.alert-modern')) {
        loginBtn.innerHTML = originalText;
        loginBtn.disabled = false;
      }
    });
  </script>
</body>

</html>
