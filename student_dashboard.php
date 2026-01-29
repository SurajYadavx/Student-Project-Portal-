<?php
// enhanced_student_dashboard.php - REPLACE your existing student_dashboard.php
require_once 'config.php';
if (!isLoggedIn() || !isStudent()) redirect('index.php');

// Get student's projects
$stmt = $pdo->prepare("SELECT * FROM projects WHERE student_id=? ORDER BY submission_date DESC");
$stmt->execute([$_SESSION['user_id']]);
$myProjects = $stmt->fetchAll();

// Get project statistics
$stats = [
  'total' => count($myProjects),
  'approved' => 0,
  'pending' => 0,
  'rejected' => 0
];

foreach ($myProjects as $project) {
  $stats[$project['status']]++;
}

// Get unread notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();

// Mark notifications as read
if (!empty($notifications)) {
    $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$_SESSION['user_id']]);
}

// Get recent activity (last 3 projects)
$recentProjects = array_slice($myProjects, 0, 3);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Student Dashboard - Project Portal</title>
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

    .dashboard-container {
      padding: 2rem 1rem;
    }

    .welcome-section {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      padding: 3rem;
      margin-bottom: 2rem;
      box-shadow: 
        0 25px 50px -12px rgba(0, 0, 0, 0.25),
        0 0 0 1px rgba(255, 255, 255, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.2);
      animation: slideInDown 0.6s ease;
      position: relative;
      overflow: hidden;
    }

    .welcome-section::before {
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

    @keyframes shimmer {
      0%, 100% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
    }

    .welcome-content {
      display: flex;
      align-items: center;
      gap: 2rem;
      flex-wrap: wrap;
    }

    .welcome-avatar {
      width: 100px;
      height: 100px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.5rem;
      color: white;
      font-weight: bold;
      animation: pulse 2s ease-in-out infinite;
      flex-shrink: 0;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }

    .welcome-text {
      flex: 1;
      min-width: 300px;
    }

    .welcome-title {
      font-size: 2rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 0.5rem;
    }

    .welcome-subtitle {
      color: #6b7280;
      font-size: 1.1rem;
      margin-bottom: 1rem;
    }

    .welcome-stats {
      display: flex;
      gap: 2rem;
      flex-wrap: wrap;
    }

    .welcome-stat {
      text-align: center;
    }

    .stat-number {
      font-size: 1.5rem;
      font-weight: 700;
      color: #667eea;
    }

    .stat-label {
      font-size: 0.875rem;
      color: #6b7280;
      font-weight: 500;
    }

    .notifications-section {
      margin-bottom: 2rem;
      animation: slideInUp 0.8s ease 0.2s both;
    }

    .notification-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      overflow: hidden;
      position: relative;
    }

    .notification-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, #10b981, #059669);
    }

    .notification-header {
      background: linear-gradient(135deg, #f0fdf4, #dcfce7);
      padding: 1.5rem;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .notification-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: #1f2937;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .notification-body {
      padding: 1.5rem;
    }

    .notification-item {
      padding: 1rem;
      margin-bottom: 1rem;
      background: white;
      border-radius: 12px;
      border-left: 4px solid #10b981;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
    }

    .notification-item:hover {
      transform: translateX(5px);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .notification-item:last-child {
      margin-bottom: 0;
    }

    .notif-title {
      font-weight: 600;
      color: #1f2937;
      margin-bottom: 0.5rem;
    }

    .notif-message {
      color: #6b7280;
      font-size: 0.9rem;
      margin-bottom: 0.5rem;
    }

    .notif-time {
      color: #9ca3af;
      font-size: 0.8rem;
      font-style: italic;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
      animation: slideInUp 0.8s ease 0.4s both;
    }

    .stat-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      padding: 2rem;
      text-align: center;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: var(--gradient);
    }

    .stat-card.total::before { --gradient: linear-gradient(90deg, #667eea, #764ba2); }
    .stat-card.approved::before { --gradient: linear-gradient(90deg, #10b981, #059669); }
    .stat-card.pending::before { --gradient: linear-gradient(90deg, #f59e0b, #d97706); }
    .stat-card.rejected::before { --gradient: linear-gradient(90deg, #ef4444, #dc2626); }

    .stat-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .stat-icon {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
      font-size: 1.5rem;
      color: white;
    }

    .stat-icon.total { background: linear-gradient(135deg, #667eea, #764ba2); }
    .stat-icon.approved { background: linear-gradient(135deg, #10b981, #059669); }
    .stat-icon.pending { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .stat-icon.rejected { background: linear-gradient(135deg, #ef4444, #dc2626); }

    .stat-number-large {
      font-size: 2.5rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 0.5rem;
    }

    .stat-label-large {
      color: #6b7280;
      font-weight: 600;
      font-size: 1rem;
    }

    .action-section {
      margin-bottom: 2rem;
      animation: slideInUp 0.8s ease 0.6s both;
    }

    .action-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
    }

    .action-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      padding: 2rem;
      text-align: center;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      text-decoration: none;
      color: inherit;
      position: relative;
      overflow: hidden;
    }

    .action-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: var(--action-gradient);
    }

    .action-card.primary::before { --action-gradient: linear-gradient(90deg, #667eea, #764ba2); }
    .action-card.secondary::before { --action-gradient: linear-gradient(90deg, #0ea5e9, #0284c7); }
    .action-card.tertiary::before { --action-gradient: linear-gradient(90deg, #8b5cf6, #7c3aed); }

    .action-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
      text-decoration: none;
      color: inherit;
    }

    .action-icon {
      width: 80px;
      height: 80px;
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
      font-size: 2rem;
      color: white;
    }

    .action-icon.primary { background: linear-gradient(135deg, #667eea, #764ba2); }
    .action-icon.secondary { background: linear-gradient(135deg, #0ea5e9, #0284c7); }
    .action-icon.tertiary { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }

    .action-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 0.75rem;
    }

    .action-desc {
      color: #6b7280;
      font-size: 0.95rem;
      line-height: 1.5;
    }

    .projects-section {
      animation: slideInUp 0.8s ease 0.8s both;
    }

    .section-header {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      padding: 2rem;
      margin-bottom: 2rem;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .section-title {
      color: #1f2937;
      font-size: 1.75rem;
      font-weight: 700;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .project-count {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      padding: 0.5rem 1.5rem;
      border-radius: 25px;
      font-weight: 600;
      font-size: 1rem;
    }

    .projects-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
      gap: 2rem;
    }

    .project-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      animation: slideInUp 0.6s ease both;
    }

    .project-card:nth-child(1) { animation-delay: 0.1s; }
    .project-card:nth-child(2) { animation-delay: 0.2s; }
    .project-card:nth-child(3) { animation-delay: 0.3s; }
    .project-card:nth-child(4) { animation-delay: 0.4s; }

    .project-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .project-header {
      padding: 2rem;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
      position: relative;
    }

    .project-status-indicator {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: var(--status-color);
    }

    .project-card.approved .project-status-indicator { --status-color: linear-gradient(90deg, #10b981, #059669); }
    .project-card.rejected .project-status-indicator { --status-color: linear-gradient(90deg, #ef4444, #dc2626); }
    .project-card.pending .project-status-indicator { --status-color: linear-gradient(90deg, #f59e0b, #d97706); }

    .project-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 1rem;
      line-height: 1.4;
    }

    .project-intro {
      color: #6b7280;
      line-height: 1.6;
      margin-bottom: 1.5rem;
    }

    .project-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .project-status {
      padding: 0.25rem 1rem;
      border-radius: 15px;
      font-size: 0.875rem;
      font-weight: 600;
    }

    .status-approved {
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
    }

    .status-rejected {
      background: linear-gradient(135deg, #ef4444, #dc2626);
      color: white;
    }

    .status-pending {
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: white;
    }

    .project-date {
      color: #9ca3af;
      font-size: 0.875rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .project-actions {
      background: #f8fafc;
      padding: 1.5rem 2rem;
      display: flex;
      gap: 0.75rem;
      flex-wrap: wrap;
    }

    .btn-view {
      background: linear-gradient(135deg, #0ea5e9, #0284c7);
      color: white;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
      font-size: 0.875rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-view:hover {
      background: linear-gradient(135deg, #0284c7, #0369a1);
      color: white;
      text-decoration: none;
      transform: translateY(-1px);
    }

    .btn-edit {
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: white;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
      font-size: 0.875rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-edit:hover {
      background: linear-gradient(135deg, #d97706, #b45309);
      color: white;
      text-decoration: none;
      transform: translateY(-1px);
    }

    .btn-delete {
      background: linear-gradient(135deg, #ef4444, #dc2626);
      color: white;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: 8px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
      font-size: 0.875rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-delete:hover {
      background: linear-gradient(135deg, #dc2626, #b91c1c);
      color: white;
      text-decoration: none;
      transform: translateY(-1px);
    }

    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .empty-icon {
      width: 120px;
      height: 120px;
      background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 2rem;
      font-size: 3rem;
      color: #9ca3af;
      animation: bounce 2s ease-in-out infinite;
    }

    @keyframes bounce {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }

    .empty-title {
      font-size: 1.75rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 1rem;
    }

    .empty-message {
      color: #6b7280;
      font-size: 1.1rem;
      font-weight: 500;
      margin-bottom: 2rem;
    }

    .empty-action {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      border: none;
      padding: 1rem 2rem;
      border-radius: 12px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 0.75rem;
      font-size: 1rem;
    }

    .empty-action:hover {
      background: linear-gradient(135deg, #5a67d8, #667eea);
      color: white;
      text-decoration: none;
      transform: translateY(-2px);
    }

    @keyframes slideInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
      .dashboard-container {
        padding: 1rem;
      }
      
      .welcome-section {
        padding: 2rem;
      }
      
      .welcome-content {
        flex-direction: column;
        text-align: center;
        gap: 1.5rem;
      }
      
      .welcome-title {
        font-size: 1.75rem;
      }
      
      .welcome-avatar {
        width: 80px;
        height: 80px;
        font-size: 2rem;
      }
      
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
      }
      
      .action-grid {
        grid-template-columns: 1fr;
      }
      
      .projects-grid {
        grid-template-columns: 1fr;
      }
      
      .section-header {
        flex-direction: column;
        text-align: center;
      }
    }

    @media (max-width: 576px) {
      .stats-grid {
        grid-template-columns: 1fr;
      }
      
      .stat-card {
        padding: 1.5rem;
      }
      
      .project-header,
      .project-actions {
        padding: 1.5rem;
      }
      
      .project-actions {
        flex-direction: column;
      }
    }

    /* Dismiss button for notifications */
    .btn-dismiss {
      position: absolute;
      top: 1rem;
      right: 1rem;
      background: none;
      border: none;
      font-size: 1.5rem;
      color: #9ca3af;
      cursor: pointer;
      transition: color 0.3s ease;
    }

    .btn-dismiss:hover {
      color: #6b7280;
    }
  </style>
</head>

<body>
  <?php include __DIR__ . '/includes/navbar.php'; ?>
  
  <div class="dashboard-container">
    <div class="container">
      
      <!-- Welcome Section -->
      <div class="welcome-section">
        <div class="welcome-content">
          <div class="welcome-avatar">
            <?= strtoupper(substr($_SESSION['name'], 0, 1)) ?>
          </div>
          <div class="welcome-text">
            <h1 class="welcome-title">Welcome back, <?= htmlspecialchars($_SESSION['name']) ?>!</h1>
            <p class="welcome-subtitle">Ready to showcase your amazing projects? Here's your dashboard overview.</p>
            <div class="welcome-stats">
              <div class="welcome-stat">
                <div class="stat-number"><?= $stats['total'] ?></div>
                <div class="stat-label">Total Projects</div>
              </div>
              <div class="welcome-stat">
                <div class="stat-number"><?= $stats['approved'] ?></div>
                <div class="stat-label">Approved</div>
              </div>
              <div class="welcome-stat">
                <div class="stat-number"><?= $stats['pending'] ?></div>
                <div class="stat-label">Under Review</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Notifications Section -->
      <?php if (!empty($notifications)): ?>
      <div class="notifications-section">
        <div class="notification-card">
          <button class="btn-dismiss" onclick="this.parentElement.parentElement.style.display='none'">
            <i class="fas fa-times"></i>
          </button>
          <div class="notification-header">
            <h3 class="notification-title">
              <i class="fas fa-bell"></i>
              Recent Notifications
            </h3>
          </div>
          <div class="notification-body">
            <?php foreach($notifications as $notif): ?>
              <div class="notification-item">
                <div class="notif-title"><?= htmlspecialchars($notif['title']) ?></div>
                <div class="notif-message"><?= htmlspecialchars($notif['message']) ?></div>
                <div class="notif-time"><?= date('M j, Y \a\t g:i A', strtotime($notif['created_at'])) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Statistics Grid -->
      <div class="stats-grid">
        <div class="stat-card total">
          <div class="stat-icon total">
            <i class="fas fa-project-diagram"></i>
          </div>
          <div class="stat-number-large"><?= $stats['total'] ?></div>
          <div class="stat-label-large">Total Projects</div>
        </div>
        
        <div class="stat-card approved">
          <div class="stat-icon approved">
            <i class="fas fa-check-circle"></i>
          </div>
          <div class="stat-number-large"><?= $stats['approved'] ?></div>
          <div class="stat-label-large">Approved</div>
        </div>
        
        <div class="stat-card pending">
          <div class="stat-icon pending">
            <i class="fas fa-clock"></i>
          </div>
          <div class="stat-number-large"><?= $stats['pending'] ?></div>
          <div class="stat-label-large">Under Review</div>
        </div>
        
        <div class="stat-card rejected">
          <div class="stat-icon rejected">
            <i class="fas fa-times-circle"></i>
          </div>
          <div class="stat-number-large"><?= $stats['rejected'] ?></div>
          <div class="stat-label-large">Rejected</div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="action-section">
        <div class="action-grid">
          <a href="submit_project.php" class="action-card primary">
            <div class="action-icon primary">
              <i class="fas fa-plus-circle"></i>
            </div>
            <h3 class="action-title">Submit New Project</h3>
            <p class="action-desc">Ready to showcase your latest creation? Submit your project for review and get it published.</p>
          </a>
          
          <a href="search_projects.php" class="action-card secondary">
            <div class="action-icon secondary">
              <i class="fas fa-search"></i>
            </div>
            <h3 class="action-title">Explore Projects</h3>
            <p class="action-desc">Discover amazing projects from other students. Get inspired and learn from their innovations.</p>
          </a>
          
          <a href="profile.php" class="action-card tertiary">
            <div class="action-icon tertiary">
              <i class="fas fa-user-cog"></i>
            </div>
            <h3 class="action-title">Manage Profile</h3>
            <p class="action-desc">Update your profile information, change your password, and view your account statistics.</p>
          </a>
        </div>
      </div>

      <!-- Projects Section -->
      <div class="projects-section">
        <div class="section-header">
          <h2 class="section-title">
            <i class="fas fa-folder-open"></i>
            My Projects
          </h2>
          <div class="project-count"><?= count($myProjects) ?> Projects</div>
        </div>

        <?php if (!$myProjects): ?>
          <div class="empty-state">
            <div class="empty-icon">
              <i class="fas fa-folder-plus"></i>
            </div>
            <h3 class="empty-title">No Projects Yet</h3>
            <p class="empty-message">You haven't submitted any projects yet. Start by creating your first project!</p>
            <a href="submit_project.php" class="empty-action">
              <i class="fas fa-rocket"></i>
              Submit Your First Project
            </a>
          </div>
        <?php else: ?>
          <div class="projects-grid">
            <?php foreach($myProjects as $p): ?>
            <div class="project-card <?= $p['status'] ?>">
              <div class="project-status-indicator"></div>
              
              <div class="project-header">
                <h3 class="project-title"><?= htmlspecialchars($p['title']) ?></h3>
                <p class="project-intro">
                  <?= htmlspecialchars(substr($p['intro'], 0, 150)) ?><?= strlen($p['intro']) > 150 ? '...' : '' ?>
                </p>
                
                <div class="project-meta">
                  <span class="project-status status-<?= $p['status'] ?>">
                    <?= ucfirst($p['status']) ?>
                  </span>
                  <div class="project-date">
                    <i class="fas fa-calendar-alt"></i>
                    <?= date('M j, Y', strtotime($p['submission_date'])) ?>
                  </div>
                </div>
              </div>

              <div class="project-actions">
                <a href="view_project.php?id=<?= $p['id'] ?>" class="btn-view">
                  <i class="fas fa-eye"></i>
                  View
                </a>
                <?php if ($p['status'] === 'pending'): ?>
                  <a href="edit_project.php?id=<?= $p['id'] ?>" class="btn-edit">
                    <i class="fas fa-edit"></i>
                    Edit
                  </a>
                  <a 
                    href="delete_project.php?id=<?= $p['id'] ?>" 
                    class="btn-delete"
                    onclick="return confirm('Are you sure you want to delete this project? This action cannot be undone.')"
                  >
                    <i class="fas fa-trash"></i>
                    Delete
                  </a>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Add click animation to stat cards
      const statCards = document.querySelectorAll('.stat-card');
      statCards.forEach(card => {
        card.addEventListener('click', function() {
          this.style.transform = 'translateY(-8px) scale(1.02)';
          setTimeout(() => {
            this.style.transform = 'translateY(-8px)';
          }, 150);
        });
      });
      
      // Add hover effects to action cards
      const actionCards = document.querySelectorAll('.action-card');
      actionCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
          this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
          this.style.transform = 'translateY(0) scale(1)';
        });
      });
      
      // Smooth scroll for internal links
      const internalLinks = document.querySelectorAll('a[href^="#"]');
      internalLinks.forEach(link => {
        link.addEventListener('click', function(e) {
          e.preventDefault();
          const target = document.querySelector(this.getAttribute('href'));
          if (target) {
            target.scrollIntoView({
              behavior: 'smooth',
              block: 'start'
            });
          }
        });
      });
      
      // Auto-hide notifications after 10 seconds
      const notificationCard = document.querySelector('.notification-card');
      if (notificationCard) {
        setTimeout(() => {
          notificationCard.style.opacity = '0.7';
        }, 10000);
      }
      
      // Add delete confirmation with better UX
      const deleteButtons = document.querySelectorAll('.btn-delete');
      deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          
          const projectTitle = this.closest('.project-card').querySelector('.project-title').textContent;
          const isConfirmed = confirm(`Are you sure you want to delete "${projectTitle}"?\n\nThis action cannot be undone and will permanently remove your project.`);
          
          if (isConfirmed) {
            // Add loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
            this.style.pointerEvents = 'none';
            
            // Navigate to delete page
            window.location.href = this.href;
          }
        });
      });
      
      // Welcome animation enhancement
      const welcomeAvatar = document.querySelector('.welcome-avatar');
      if (welcomeAvatar) {
        welcomeAvatar.addEventListener('click', function() {
          this.style.transform = 'scale(1.1) rotate(10deg)';
          setTimeout(() => {
            this.style.transform = 'scale(1) rotate(0deg)';
          }, 300);
        });
      }
    });
  </script>
</body>

</html>
