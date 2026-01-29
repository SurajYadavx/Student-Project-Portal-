<?php
// enhanced_view_project.php - REPLACE your existing view_project.php
require_once 'config.php';
if (!isLoggedIn() || !isStudent()) redirect('index.php');

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT p.*, u.name AS student_name FROM projects p JOIN users u ON p.student_id=u.id WHERE p.id=?");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) die('Project not found');

// Handle rating submission
if ($_POST && isset($_POST['rating'])) {
  $rating = intval($_POST['rating']);
  $pdo->prepare("REPLACE INTO ratings (project_id,student_id,rating) VALUES(?,?,?)")
      ->execute([$id,$_SESSION['user_id'],$rating]);
  $avg = $pdo->query("SELECT AVG(rating) FROM ratings WHERE project_id=$id")->fetchColumn();
  $pdo->prepare("UPDATE projects SET avg_rating=? WHERE id=?")->execute([$avg,$id]);
  redirect("view_project.php?id=$id");
}

// Handle comment submission
if ($_POST && isset($_POST['comment'])) {
  $comment = trim($_POST['comment']);
  if ($comment) {
    $pdo->prepare("INSERT INTO comments (project_id,user_id,comment) VALUES(?,?,?)")
        ->execute([$id,$_SESSION['user_id'],$comment]);
  }
  redirect("view_project.php?id=$id");
}

// Get comments
$comments = $pdo->prepare("SELECT c.*, u.name FROM comments c JOIN users u ON c.user_id=u.id WHERE c.project_id=? ORDER BY c.created_at DESC");
$comments->execute([$id]);
$comments = $comments->fetchAll();

// Get professor review (if exists)
$review = null;
if ($p['status'] !== 'pending') {
  $stmt = $pdo->prepare("SELECT pr.*, u.name AS professor_name FROM project_reviews pr JOIN users u ON pr.professor_id=u.id WHERE pr.project_id=? ORDER BY pr.review_date DESC LIMIT 1");
  $stmt->execute([$id]);
  $review = $stmt->fetch();
}

// Get current user's rating
$user_rating = 0;
$stmt = $pdo->prepare("SELECT rating FROM ratings WHERE project_id=? AND student_id=?");
$stmt->execute([$id, $_SESSION['user_id']]);
$user_rating_row = $stmt->fetch();
if ($user_rating_row) {
  $user_rating = $user_rating_row['rating'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($p['title']) ?> - Project Portal</title>
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

    .project-container {
      padding: 2rem 1rem;
    }

    .project-header {
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

    .project-header::before {
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

    .project-title {
      font-size: 2.5rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 1rem;
      line-height: 1.2;
    }

    .status-badge {
      padding: 0.5rem 1.5rem;
      border-radius: 25px;
      font-weight: 600;
      font-size: 1rem;
      margin-left: 1rem;
      display: inline-block;
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

    .project-meta {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 2rem;
      margin-top: 2rem;
      padding-top: 2rem;
      border-top: 2px solid #f1f5f9;
    }

    .meta-item {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      color: #6b7280;
      font-weight: 500;
    }

    .meta-icon {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1rem;
    }

    .project-rating-header {
      text-align: center;
      margin-top: 2rem;
    }

    .rating-display {
      font-size: 2rem;
      color: #fbbf24;
      margin-bottom: 0.5rem;
    }

    .rating-text {
      color: #6b7280;
      font-size: 1rem;
      font-weight: 500;
    }

    .content-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      overflow: hidden;
      margin-bottom: 2rem;
      animation: slideInUp 0.8s ease;
      animation-fill-mode: both;
    }

    .content-card:nth-child(1) { animation-delay: 0.1s; }
    .content-card:nth-child(2) { animation-delay: 0.2s; }
    .content-card:nth-child(3) { animation-delay: 0.3s; }

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

    .card-header-modern {
      background: linear-gradient(135deg, #f8fafc, #e2e8f0);
      padding: 2rem;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .card-title-modern {
      font-size: 1.5rem;
      font-weight: 700;
      color: #1f2937;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .card-body-modern {
      padding: 2.5rem;
    }

    .project-section {
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
      padding-bottom: 0.75rem;
      border-bottom: 2px solid #f1f5f9;
    }

    .section-content {
      color: #374151;
      line-height: 1.7;
      font-size: 1rem;
    }

    .file-download {
      background: linear-gradient(135deg, #0ea5e9, #0284c7);
      color: white;
      border: none;
      padding: 1rem 2rem;
      border-radius: 12px;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.75rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);
      margin-top: 1rem;
    }

    .file-download:hover {
      background: linear-gradient(135deg, #0284c7, #0369a1);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(14, 165, 233, 0.4);
      color: white;
      text-decoration: none;
    }

    .review-card {
      border-left: 4px solid #667eea;
      background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
    }

    .review-header {
      display: flex;
      justify-content: between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;
      margin-bottom: 1rem;
    }

    .reviewer-info {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      color: #374151;
      font-weight: 600;
    }

    .reviewer-avatar {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
    }

    .grade-badge {
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-weight: 600;
      font-size: 0.9rem;
    }

    .sidebar-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      overflow: hidden;
      margin-bottom: 2rem;
      animation: slideInRight 0.8s ease;
      animation-fill-mode: both;
    }

    .sidebar-card:nth-child(1) { animation-delay: 0.3s; }
    .sidebar-card:nth-child(2) { animation-delay: 0.4s; }

    @keyframes slideInRight {
      from {
        opacity: 0;
        transform: translateX(30px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    .rating-widget {
      text-align: center;
      padding: 2rem;
    }

    .rating-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 1.5rem;
    }

    .rating-buttons {
      display: flex;
      justify-content: center;
      gap: 0.5rem;
      flex-wrap: wrap;
      margin-bottom: 1rem;
    }

    .rating-btn {
      width: 50px;
      height: 50px;
      border: 2px solid #e5e7eb;
      background: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      color: #9ca3af;
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .rating-btn:hover {
      border-color: #fbbf24;
      color: #fbbf24;
      transform: scale(1.1);
    }

    .rating-btn.active {
      background: linear-gradient(135deg, #fbbf24, #f59e0b);
      border-color: #f59e0b;
      color: white;
    }

    .rating-btn.active:hover {
      background: linear-gradient(135deg, #f59e0b, #d97706);
    }

    .user-rating-info {
      color: #6b7280;
      font-size: 0.9rem;
      margin-top: 1rem;
      font-style: italic;
    }

    .stats-list {
      padding: 2rem;
    }

    .stats-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 1.5rem;
      text-align: center;
    }

    .stat-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem;
      margin-bottom: 0.75rem;
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

    .stat-value {
      font-weight: 700;
      color: #1f2937;
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .comments-section {
      padding: 2.5rem;
    }

    .comments-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 2rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .comment-form {
      background: #f8fafc;
      border-radius: 16px;
      padding: 2rem;
      margin-bottom: 2rem;
    }

    .comment-input {
      width: 100%;
      border: 2px solid #e5e7eb;
      border-radius: 12px;
      padding: 1rem;
      font-size: 1rem;
      background: white;
      transition: all 0.3s ease;
      resize: vertical;
      min-height: 80px;
    }

    .comment-input:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .comment-btn {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      border: none;
      padding: 0.75rem 2rem;
      border-radius: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-top: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .comment-btn:hover {
      background: linear-gradient(135deg, #5a67d8, #667eea);
      transform: translateY(-2px);
    }

    .comments-list {
      margin-top: 2rem;
    }

    .comment-item {
      background: white;
      border-radius: 16px;
      padding: 2rem;
      margin-bottom: 1.5rem;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
      border: 1px solid #f1f5f9;
      transition: all 0.3s ease;
    }

    .comment-item:hover {
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
      transform: translateY(-2px);
    }

    .comment-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
    }

    .comment-author {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .author-avatar {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
      font-size: 0.9rem;
    }

    .author-name {
      font-weight: 600;
      color: #1f2937;
      font-size: 1rem;
    }

    .comment-date {
      color: #9ca3af;
      font-size: 0.875rem;
    }

    .comment-text {
      color: #374151;
      line-height: 1.6;
      font-size: 0.95rem;
    }

    .empty-comments {
      text-align: center;
      padding: 3rem 2rem;
      color: #9ca3af;
    }

    .empty-icon {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
      font-size: 2rem;
      color: #9ca3af;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
      .project-container {
        padding: 1rem;
      }
      
      .project-header {
        padding: 2rem;
      }
      
      .project-title {
        font-size: 2rem;
      }
      
      .project-meta {
        grid-template-columns: 1fr;
        gap: 1rem;
      }
      
      .card-body-modern {
        padding: 2rem;
      }
      
      .rating-buttons {
        gap: 0.25rem;
      }
      
      .rating-btn {
        width: 45px;
        height: 45px;
        font-size: 1rem;
      }
    }

    @media (max-width: 576px) {
      .project-header,
      .card-body-modern,
      .comments-section {
        padding: 1.5rem;
      }
      
      .project-title {
        font-size: 1.75rem;
        line-height: 1.3;
      }
      
      .status-badge {
        margin-left: 0;
        margin-top: 0.5rem;
        display: block;
        width: fit-content;
      }
    }

    /* Loading states */
    .rating-btn.loading {
      pointer-events: none;
      opacity: 0.6;
    }

    .comment-btn.loading {
      pointer-events: none;
      opacity: 0.8;
    }

    .comment-btn.loading::after {
      content: '';
      width: 16px;
      height: 16px;
      border: 2px solid white;
      border-top: 2px solid transparent;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin-left: 0.5rem;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  </style>
</head>

<body>
  <?php include __DIR__ . '/includes/navbar.php'; ?>
  
  <div class="project-container">
    <!-- Project Header -->
    <div class="project-header">
      <h1 class="project-title">
        <?= htmlspecialchars($p['title']) ?>
        <span class="status-badge status-<?= $p['status'] ?>">
          <?= ucfirst($p['status']) ?>
        </span>
      </h1>
      
      <div class="project-meta">
        <div class="meta-item">
          <div class="meta-icon">
            <i class="fas fa-user"></i>
          </div>
          <div>
            <div style="font-weight: 600; color: #1f2937;">Created by</div>
            <div><?= htmlspecialchars($p['student_name']) ?></div>
          </div>
        </div>
        
        <div class="meta-item">
          <div class="meta-icon">
            <i class="fas fa-tag"></i>
          </div>
          <div>
            <div style="font-weight: 600; color: #1f2937;">Domain</div>
            <div><?= htmlspecialchars($p['domain']) ?></div>
          </div>
        </div>
        
        <div class="meta-item">
          <div class="meta-icon">
            <i class="fas fa-calendar-alt"></i>
          </div>
          <div>
            <div style="font-weight: 600; color: #1f2937;">Submitted</div>
            <div><?= date('M j, Y', strtotime($p['submission_date'])) ?></div>
          </div>
        </div>
        
        <div class="meta-item">
          <div class="meta-icon">
            <i class="fas fa-star"></i>
          </div>
          <div>
            <div style="font-weight: 600; color: #1f2937;">Rating</div>
            <div><?= number_format($p['avg_rating'], 1) ?>/5.0</div>
          </div>
        </div>
      </div>
      
      <div class="project-rating-header">
        <div class="rating-display">
          <?= str_repeat('★', floor($p['avg_rating'])) ?>
          <?= str_repeat('☆', 5-floor($p['avg_rating'])) ?>
        </div>
        <div class="rating-text">
          <?= number_format($p['avg_rating'], 1) ?> out of 5 stars
        </div>
      </div>
    </div>

    <div class="container">
      <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
          
          <!-- Project Details -->
          <div class="content-card">
            <div class="card-body-modern">
              <!-- Introduction Section -->
              <div class="project-section">
                <h3 class="section-title">
                  <i class="fas fa-info-circle"></i>
                  Project Introduction
                </h3>
                <div class="section-content">
                  <?= nl2br(htmlspecialchars($p['intro'])) ?>
                </div>
              </div>

              <!-- Importance Section -->
              <div class="project-section">
                <h3 class="section-title">
                  <i class="fas fa-star"></i>
                  Project Importance
                </h3>
                <div class="section-content">
                  <?= nl2br(htmlspecialchars($p['importance'])) ?>
                </div>
              </div>

              <!-- File Download Section -->
              <?php if ($p['file_path']): ?>
                <div class="project-section">
                  <h3 class="section-title">
                    <i class="fas fa-file-download"></i>
                    Project Files
                  </h3>
                  <a href="<?= htmlspecialchars($p['file_path']) ?>" class="file-download" target="_blank">
                    <i class="fas fa-download"></i>
                    Download Project File
                  </a>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Professor Review -->
          <?php if ($review): ?>
          <div class="content-card review-card">
            <div class="card-header-modern">
              <h3 class="card-title-modern">
                <i class="fas fa-award"></i>
                Professor Review
              </h3>
            </div>
            <div class="card-body-modern">
              <div class="review-header">
                <div class="reviewer-info">
                  <div class="reviewer-avatar">
                    <?= strtoupper(substr($review['professor_name'], 0, 1)) ?>
                  </div>
                  <div>
                    <div style="font-weight: 600;">Reviewed by <?= htmlspecialchars($review['professor_name']) ?></div>
                    <div style="color: #6b7280; font-size: 0.875rem;">
                      <?= date('M j, Y', strtotime($review['review_date'])) ?>
                    </div>
                  </div>
                </div>
                
                <?php if ($review['grade']): ?>
                  <div class="grade-badge">
                    Grade: <?= $review['grade'] ?>/10
                  </div>
                <?php endif; ?>
              </div>
              
              <?php if ($review['comments']): ?>
                <div style="margin-top: 1.5rem; color: #374151; line-height: 1.6;">
                  <?= nl2br(htmlspecialchars($review['comments'])) ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- Comments Section -->
          <div class="content-card">
            <div class="comments-section">
              <h3 class="comments-title">
                <i class="fas fa-comments"></i>
                Discussion (<?= count($comments) ?>)
              </h3>
              
              <!-- Comment Form -->
              <div class="comment-form">
                <form method="post" id="commentForm">
                  <textarea 
                    name="comment" 
                    class="comment-input" 
                    placeholder="Share your thoughts about this project..."
                    required
                  ></textarea>
                  <button type="submit" class="comment-btn" id="commentBtn">
                    <i class="fas fa-paper-plane"></i>
                    Post Comment
                  </button>
                </form>
              </div>
              
              <!-- Comments List -->
              <div class="comments-list">
                <?php if (!$comments): ?>
                  <div class="empty-comments">
                    <div class="empty-icon">
                      <i class="fas fa-comments"></i>
                    </div>
                    <h4>No comments yet</h4>
                    <p>Be the first to share your thoughts about this project!</p>
                  </div>
                <?php else: ?>
                  <?php foreach($comments as $c): ?>
                  <div class="comment-item">
                    <div class="comment-header">
                      <div class="comment-author">
                        <div class="author-avatar">
                          <?= strtoupper(substr($c['name'], 0, 1)) ?>
                        </div>
                        <div class="author-name"><?= htmlspecialchars($c['name']) ?></div>
                      </div>
                      <div class="comment-date">
                        <?= date('M j, Y \a\t g:i A', strtotime($c['created_at'])) ?>
                      </div>
                    </div>
                    <div class="comment-text">
                      <?= nl2br(htmlspecialchars($c['comment'])) ?>
                    </div>
                  </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
          
          <!-- Rating Widget -->
          <div class="sidebar-card">
            <div class="rating-widget">
              <h4 class="rating-title">Rate This Project</h4>
              <form method="post" id="ratingForm">
                <div class="rating-buttons">
                  <?php for($i = 1; $i <= 5; $i++): ?>
                    <button 
                      type="submit" 
                      name="rating" 
                      value="<?= $i ?>" 
                      class="rating-btn <?= $user_rating == $i ? 'active' : '' ?>"
                      title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>"
                    >
                      <?= $i ?>★
                    </button>
                  <?php endfor; ?>
                </div>
              </form>
              <?php if ($user_rating > 0): ?>
                <div class="user-rating-info">
                  You rated this project <?= $user_rating ?> star<?= $user_rating > 1 ? 's' : '' ?>
                </div>
              <?php else: ?>
                <div class="user-rating-info">
                  Click a star to rate this project
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Project Statistics -->
          <div class="sidebar-card">
            <div class="stats-list">
              <h4 class="stats-title">Project Statistics</h4>
              
              <div class="stat-item">
                <div class="stat-label">
                  <i class="fas fa-comments"></i>
                  Comments
                </div>
                <div class="stat-value"><?= count($comments) ?></div>
              </div>
              
              <div class="stat-item">
                <div class="stat-label">
                  <i class="fas fa-flag"></i>
                  Status
                </div>
                <div class="stat-value"><?= ucfirst($p['status']) ?></div>
              </div>
              
              <div class="stat-item">
                <div class="stat-label">
                  <i class="fas fa-star"></i>
                  Average Rating
                </div>
                <div class="stat-value"><?= number_format($p['avg_rating'], 1) ?>/5</div>
              </div>
              
              <div class="stat-item">
                <div class="stat-label">
                  <i class="fas fa-calendar-plus"></i>
                  Days Since Submission
                </div>
                <div class="stat-value">
                  <?= floor((time() - strtotime($p['submission_date'])) / (60 * 60 * 24)) ?>
                </div>
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
      const commentForm = document.getElementById('commentForm');
      const commentBtn = document.getElementById('commentBtn');
      const ratingForm = document.getElementById('ratingForm');
      const ratingBtns = document.querySelectorAll('.rating-btn');
      
      // Comment form submission
      commentForm.addEventListener('submit', function() {
        commentBtn.classList.add('loading');
        commentBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Posting...';
        commentBtn.disabled = true;
      });
      
      // Rating button hover effects
      ratingBtns.forEach((btn, index) => {
        btn.addEventListener('mouseenter', function() {
          // Highlight stars up to this one
          for (let i = 0; i <= index; i++) {
            ratingBtns[i].style.borderColor = '#fbbf24';
            ratingBtns[i].style.color = '#fbbf24';
          }
          // Reset stars after this one
          for (let i = index + 1; i < ratingBtns.length; i++) {
            if (!ratingBtns[i].classList.contains('active')) {
              ratingBtns[i].style.borderColor = '#e5e7eb';
              ratingBtns[i].style.color = '#9ca3af';
            }
          }
        });
        
        btn.addEventListener('mouseleave', function() {
          // Reset all non-active buttons
          ratingBtns.forEach(b => {
            if (!b.classList.contains('active')) {
              b.style.borderColor = '#e5e7eb';
              b.style.color = '#9ca3af';
            }
          });
        });
        
        btn.addEventListener('click', function() {
          // Add loading state to all rating buttons
          ratingBtns.forEach(b => {
            b.classList.add('loading');
            b.disabled = true;
          });
        });
      });
      
      // Smooth scroll to comments when someone adds a comment
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('commented') === '1') {
        document.querySelector('.comments-section').scrollIntoView({
          behavior: 'smooth'
        });
      }
      
      // Auto-expand comment textarea
      const commentTextarea = document.querySelector('.comment-input');
      commentTextarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.max(80, this.scrollHeight) + 'px';
      });
      
      // Add animation delay to comments
      const commentItems = document.querySelectorAll('.comment-item');
      commentItems.forEach((item, index) => {
        item.style.animationDelay = (index * 0.1) + 's';
        item.style.animation = 'slideInUp 0.6s ease both';
      });
    });
  </script>
</body>

</html>
