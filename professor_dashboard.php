<?php
// enhanced_professor_dashboard.php - REPLACE your existing professor_dashboard.php
require_once 'config.php';
if (!isLoggedIn() || !isProfessor()) redirect('index.php');

// Get pending projects
$pending = $pdo->query(
  "SELECT p.id, p.title, p.intro, u.name, p.submission_date, p.domain, p.avg_rating
   FROM projects p JOIN users u ON p.student_id=u.id
   WHERE p.status='pending' ORDER BY p.submission_date DESC"
)->fetchAll();

// Get recently reviewed projects
$recentlyReviewed = $pdo->prepare(
  "SELECT p.id, p.title, p.status, u.name, pr.review_date, pr.grade, p.domain
   FROM projects p 
   JOIN users u ON p.student_id=u.id 
   JOIN project_reviews pr ON p.id=pr.project_id
   WHERE pr.professor_id=? AND p.status IN ('approved', 'rejected')
   ORDER BY pr.review_date DESC LIMIT 5"
);
$recentlyReviewed->execute([$_SESSION['user_id']]);
$recentlyReviewed = $recentlyReviewed->fetchAll();

// Get statistics
$stats = [
  'total_projects' => $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn(),
  'approved' => $pdo->query("SELECT COUNT(*) FROM projects WHERE status='approved'")->fetchColumn(),
  'rejected' => $pdo->query("SELECT COUNT(*) FROM projects WHERE status='rejected'")->fetchColumn(),
  'pending' => count($pending),
  'my_reviews' => $pdo->prepare("SELECT COUNT(*) FROM project_reviews WHERE professor_id=?")->execute([$_SESSION['user_id']]) ? $pdo->prepare("SELECT COUNT(*) FROM project_reviews WHERE professor_id=?")->execute([$_SESSION['user_id']]) : 0
];

// Get my review count properly
$stmt = $pdo->prepare("SELECT COUNT(*) FROM project_reviews WHERE professor_id=?");
$stmt->execute([$_SESSION['user_id']]);
$stats['my_reviews'] = $stmt->fetchColumn();

// Get domain distribution
$domainStats = $pdo->query(
  "SELECT domain, COUNT(*) as count, 
   SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved_count
   FROM projects GROUP BY domain ORDER BY count DESC"
)->fetchAll();

// Handle bulk actions
if ($_POST && isset($_POST['bulk_action'])) {
  $projectIds = $_POST['project_ids'] ?? [];
  $bulkAction = $_POST['bulk_action'];
  $bulkGrade = floatval($_POST['bulk_grade'] ?? 0);
  $bulkComments = trim($_POST['bulk_comments'] ?? '');
  
  if (!empty($projectIds) && in_array($bulkAction, ['approve', 'reject'])) {
    $status = $bulkAction === 'approve' ? 'approved' : 'rejected';
    
    foreach ($projectIds as $pid) {
      $pid = intval($pid);
      // Update project status
      $pdo->prepare("UPDATE projects SET status=? WHERE id=?")->execute([$status, $pid]);
      
      // Insert review record
      $stmt = $pdo->prepare("INSERT INTO project_reviews (project_id, professor_id, status, grade, comments) VALUES (?,?,?,?,?)");
      $stmt->execute([$pid, $_SESSION['user_id'], $status, $bulkGrade ?: null, $bulkComments ?: null]);
      
      // Send notification to student
      $student_id = $pdo->query("SELECT student_id FROM projects WHERE id=$pid")->fetchColumn();
      $project_title = $pdo->query("SELECT title FROM projects WHERE id=$pid")->fetchColumn();
      $notif_msg = $status === 'approved' 
        ? "Your project '$project_title' has been approved!" . ($bulkGrade ? " Grade: $bulkGrade/10" : "")
        : "Your project '$project_title' has been rejected. " . ($bulkComments ? "Reason: $bulkComments" : "");
      
      $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?,?,?)")
          ->execute([$student_id, "Project " . ucfirst($status), $notif_msg]);
    }
    
    redirect('professor_dashboard.php');
  }
}

// Handle individual approval/rejection
if ($_POST && isset($_POST['action']) && !isset($_POST['bulk_action'])) {
  $pid = intval($_POST['project_id']);
  $action = $_POST['action'];
  $grade = floatval($_POST['grade'] ?? 0);
  $comments = trim($_POST['comments'] ?? '');
  
  if ($action === 'approve' || $action === 'reject') {
    $status = $action === 'approve' ? 'approved' : 'rejected';
    $pdo->prepare("UPDATE projects SET status=? WHERE id=?")->execute([$status, $pid]);
    
    // Insert review record
    $stmt = $pdo->prepare("INSERT INTO project_reviews (project_id, professor_id, status, grade, comments) VALUES (?,?,?,?,?)");
    $stmt->execute([$pid, $_SESSION['user_id'], $status, $grade ?: null, $comments ?: null]);
    
    // Send notification to student
    $student_id = $pdo->query("SELECT student_id FROM projects WHERE id=$pid")->fetchColumn();
    $project_title = $pdo->query("SELECT title FROM projects WHERE id=$pid")->fetchColumn();
    $notif_msg = $status === 'approved' 
      ? "Your project '$project_title' has been approved!" . ($grade ? " Grade: $grade/10" : "")
      : "Your project '$project_title' has been rejected. " . ($comments ? "Reason: $comments" : "");
    
    $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?,?,?)")
        ->execute([$student_id, "Project " . ucfirst($status), $notif_msg]);
    
    redirect('professor_dashboard.php');
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Professor Dashboard - Project Portal</title>
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

    .dashboard-header {
      text-align: center;
      margin-bottom: 3rem;
      animation: slideInDown 0.6s ease;
    }

    .dashboard-title {
      color: white;
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 1rem;
    }

    .dashboard-subtitle {
      color: rgba(255, 255, 255, 0.8);
      font-size: 1.1rem;
      font-weight: 500;
    }

    .quick-actions {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
      margin-bottom: 3rem;
      animation: slideInUp 0.8s ease 0.1s both;
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

    .action-card.search::before { --action-gradient: linear-gradient(90deg, #0ea5e9, #0284c7); }
    .action-card.analytics::before { --action-gradient: linear-gradient(90deg, #8b5cf6, #7c3aed); }
    .action-card.export::before { --action-gradient: linear-gradient(90deg, #10b981, #059669); }
    .action-card.students::before { --action-gradient: linear-gradient(90deg, #f59e0b, #d97706); }

    .action-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
      text-decoration: none;
      color: inherit;
    }

    .action-icon {
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

    .action-icon.search { background: linear-gradient(135deg, #0ea5e9, #0284c7); }
    .action-icon.analytics { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
    .action-icon.export { background: linear-gradient(135deg, #10b981, #059669); }
    .action-icon.students { background: linear-gradient(135deg, #f59e0b, #d97706); }

    .action-title {
      font-size: 1.1rem;
      font-weight: 600;
      color: #1f2937;
      margin-bottom: 0.5rem;
    }

    .action-desc {
      color: #6b7280;
      font-size: 0.875rem;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-bottom: 3rem;
      animation: slideInUp 0.8s ease 0.2s both;
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
      background-size: 200% 100%;
      animation: shimmer 3s ease-in-out infinite;
    }

    .stat-card.total::before { --gradient: linear-gradient(90deg, #667eea, #764ba2); }
    .stat-card.approved::before { --gradient: linear-gradient(90deg, #10b981, #059669); }
    .stat-card.rejected::before { --gradient: linear-gradient(90deg, #ef4444, #dc2626); }
    .stat-card.pending::before { --gradient: linear-gradient(90deg, #f59e0b, #d97706); }
    .stat-card.reviews::before { --gradient: linear-gradient(90deg, #3b82f6, #2563eb); }

    .stat-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .stat-icon {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
      font-size: 1.25rem;
      color: white;
    }

    .stat-icon.total { background: linear-gradient(135deg, #667eea, #764ba2); }
    .stat-icon.approved { background: linear-gradient(135deg, #10b981, #059669); }
    .stat-icon.rejected { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .stat-icon.pending { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .stat-icon.reviews { background: linear-gradient(135deg, #3b82f6, #2563eb); }

    .stat-number {
      font-size: 2rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 0.5rem;
    }

    .stat-label {
      color: #6b7280;
      font-weight: 600;
      font-size: 0.9rem;
    }

    .dashboard-tabs {
      display: flex;
      gap: 1rem;
      margin-bottom: 2rem;
      animation: slideInUp 0.8s ease 0.3s both;
    }

    .tab-button {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border: none;
      border-radius: 15px;
      padding: 1rem 2rem;
      font-weight: 600;
      color: #6b7280;
      transition: all 0.3s ease;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .tab-button.active {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
    }

    .tab-button:hover:not(.active) {
      background: rgba(255, 255, 255, 1);
      color: #374151;
    }

    .content-section {
      display: none;
      animation: fadeIn 0.5s ease;
    }

    .content-section.active {
      display: block;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .bulk-actions {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      padding: 1.5rem;
      margin-bottom: 2rem;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      display: flex;
      gap: 1rem;
      align-items: center;
      flex-wrap: wrap;
    }

    .select-all {
      margin-right: 1rem;
    }

    .bulk-controls {
      display: flex;
      gap: 1rem;
      align-items: center;
      flex-wrap: wrap;
    }

    .bulk-input {
      padding: 0.5rem;
      border: 2px solid #e5e7eb;
      border-radius: 8px;
      font-size: 0.875rem;
    }

    .bulk-btn {
      padding: 0.5rem 1rem;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 0.875rem;
    }

    .bulk-approve {
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
    }

    .bulk-reject {
      background: linear-gradient(135deg, #ef4444, #dc2626);
      color: white;
    }

    .recent-activity {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      overflow: hidden;
      margin-bottom: 2rem;
    }

    .activity-header {
      background: linear-gradient(135deg, #f8fafc, #e2e8f0);
      padding: 1.5rem;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .activity-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: #1f2937;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .activity-body {
      padding: 1.5rem;
    }

    .activity-item {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1rem;
      margin-bottom: 1rem;
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
    }

    .activity-item:hover {
      transform: translateX(5px);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .activity-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 0.875rem;
    }

    .activity-icon.approved { background: linear-gradient(135deg, #10b981, #059669); }
    .activity-icon.rejected { background: linear-gradient(135deg, #ef4444, #dc2626); }

    .activity-content {
      flex: 1;
    }

    .activity-project {
      font-weight: 600;
      color: #1f2937;
      margin-bottom: 0.25rem;
    }

    .activity-details {
      color: #6b7280;
      font-size: 0.875rem;
    }

    .analytics-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 2rem;
      margin-bottom: 2rem;
    }

    .chart-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      padding: 2rem;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .chart-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .domain-bar {
      margin-bottom: 1rem;
    }

    .domain-label {
      display: flex;
      justify-content: between;
      align-items: center;
      margin-bottom: 0.5rem;
      font-size: 0.875rem;
      font-weight: 500;
    }

    .domain-name {
      color: #374151;
    }

    .domain-count {
      color: #6b7280;
    }

    .progress-bar-container {
      width: 100%;
      height: 8px;
      background: #e5e7eb;
      border-radius: 4px;
      overflow: hidden;
    }

    .progress-bar {
      height: 100%;
      background: linear-gradient(90deg, #667eea, #764ba2);
      border-radius: 4px;
      transition: width 0.3s ease;
    }

    /* All existing styles from the previous version remain the same */
    .projects-section {
      animation: fadeIn 1s ease 0.4s both;
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

    .pending-badge {
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: white;
      padding: 0.5rem 1.5rem;
      border-radius: 25px;
      font-weight: 600;
      font-size: 1rem;
      box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
      animation: pulse 2s ease-in-out infinite;
    }

    .project-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      margin-bottom: 2rem;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      overflow: hidden;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      animation: slideInLeft 0.6s ease;
      animation-fill-mode: both;
    }

    .project-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .project-header {
      background: linear-gradient(135deg, #f8fafc, #e2e8f0);
      padding: 2rem;
      border-bottom: 1px solid rgba(0, 0, 0, 0.1);
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
      background: linear-gradient(90deg, #667eea, #764ba2);
    }

    .project-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      flex-wrap: wrap;
    }

    .project-checkbox {
      margin-right: 1rem;
      transform: scale(1.2);
    }

    .domain-badge {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      padding: 0.25rem 1rem;
      border-radius: 15px;
      font-size: 0.875rem;
      font-weight: 500;
    }

    .project-meta {
      color: #6b7280;
      font-size: 0.95rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      flex-wrap: wrap;
    }

    .meta-item {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .project-body {
      padding: 2rem;
    }

    .project-intro {
      color: #374151;
      line-height: 1.6;
      margin-bottom: 1.5rem;
      font-size: 1rem;
    }

    .view-details-btn {
      background: linear-gradient(135deg, #0ea5e9, #0284c7);
      color: white;
      border: none;
      padding: 0.75rem 1.5rem;
      border-radius: 12px;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);
    }

    .view-details-btn:hover {
      background: linear-gradient(135deg, #0284c7, #0369a1);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(14, 165, 233, 0.4);
      color: white;
      text-decoration: none;
    }

    .project-footer {
      background: #f8fafc;
      padding: 2rem;
      border-top: 1px solid rgba(0, 0, 0, 0.05);
    }

    .review-form {
      display: grid;
      grid-template-columns: 1fr 2fr 1fr;
      gap: 1.5rem;
      align-items: end;
    }

    .form-group-compact {
      display: flex;
      flex-direction: column;
    }

    .form-label-compact {
      color: #374151;
      font-weight: 600;
      font-size: 0.9rem;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .form-control-compact {
      padding: 0.75rem;
      border: 2px solid #e5e7eb;
      border-radius: 12px;
      font-size: 0.9rem;
      background: white;
      transition: all 0.3s ease;
    }

    .form-control-compact:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .action-buttons {
      display: flex;
      gap: 0.75rem;
    }

    .btn-approve {
      background: linear-gradient(135deg, #10b981, #059669);
      border: none;
      color: white;
      padding: 0.75rem 1.5rem;
      border-radius: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    }

    .btn-approve:hover {
      background: linear-gradient(135deg, #059669, #047857);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
    }

    .btn-reject {
      background: linear-gradient(135deg, #ef4444, #dc2626);
      border: none;
      color: white;
      padding: 0.75rem 1.5rem;
      border-radius: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
    }

    .btn-reject:hover {
      background: linear-gradient(135deg, #dc2626, #b91c1c);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
    }

    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      animation: fadeIn 0.8s ease;
    }

    .empty-icon {
      width: 120px;
      height: 120px;
      background: linear-gradient(135deg, #10b981, #059669);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 2rem;
      font-size: 3rem;
      color: white;
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
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
      .dashboard-container {
        padding: 1rem;
      }
      
      .dashboard-title {
        font-size: 2rem;
        flex-direction: column;
        gap: 0.5rem;
      }
      
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
      }
      
      .quick-actions {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .section-header {
        flex-direction: column;
        text-align: center;
      }
      
      .review-form {
        grid-template-columns: 1fr;
        gap: 1rem;
      }
      
      .bulk-actions {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
      }
      
      .dashboard-tabs {
        flex-wrap: wrap;
      }
      
      .tab-button {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
      }
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
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes slideInLeft {
      from {
        opacity: 0;
        transform: translateX(-30px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    @keyframes shimmer {
      0%, 100% { background-position: 0% 50%; }
      50% { background-position: 100% 50%; }
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }

    /* Loading states */
    .btn-approve.loading,
    .btn-reject.loading {
      pointer-events: none;
      opacity: 0.8;
    }

    .btn-approve.loading::after,
    .btn-reject.loading::after {
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
  </style>
</head>

<body>
  <?php include __DIR__ . '/includes/navbar.php'; ?>
  
  <div class="dashboard-container">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
      <h1 class="dashboard-title">
        <i class="fas fa-chalkboard-teacher"></i>
        Professor Dashboard
      </h1>
      <p class="dashboard-subtitle">Comprehensive project management and analytics</p>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
      <a href="search_projects.php" class="action-card search">
        <div class="action-icon search">
          <i class="fas fa-search"></i>
        </div>
        <h3 class="action-title">Search Projects</h3>
        <p class="action-desc">Find and explore all student projects</p>
      </a>
      
      <a href="#analytics" class="action-card analytics" onclick="showTab('analytics')">
        <div class="action-icon analytics">
          <i class="fas fa-chart-line"></i>
        </div>
        <h3 class="action-title">View Analytics</h3>
        <p class="action-desc">Detailed statistics and insights</p>
      </a>
      
      <a href="#export" class="action-card export" onclick="exportData()">
        <div class="action-icon export">
          <i class="fas fa-download"></i>
        </div>
        <h3 class="action-title">Export Data</h3>
        <p class="action-desc">Download project reports</p>
      </a>
      
      <a href="profile.php" class="action-card students">
        <div class="action-icon students">
          <i class="fas fa-user-graduate"></i>
        </div>
        <h3 class="action-title">My Profile</h3>
        <p class="action-desc">Manage account settings</p>
      </a>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
      <div class="stat-card total">
        <div class="stat-icon total">
          <i class="fas fa-project-diagram"></i>
        </div>
        <div class="stat-number"><?= $stats['total_projects'] ?></div>
        <div class="stat-label">Total Projects</div>
      </div>
      
      <div class="stat-card approved">
        <div class="stat-icon approved">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-number"><?= $stats['approved'] ?></div>
        <div class="stat-label">Approved</div>
      </div>
      
      <div class="stat-card rejected">
        <div class="stat-icon rejected">
          <i class="fas fa-times-circle"></i>
        </div>
        <div class="stat-number"><?= $stats['rejected'] ?></div>
        <div class="stat-label">Rejected</div>
      </div>
      
      <div class="stat-card pending">
        <div class="stat-icon pending">
          <i class="fas fa-clock"></i>
        </div>
        <div class="stat-number"><?= $stats['pending'] ?></div>
        <div class="stat-label">Pending Review</div>
      </div>
      
      <div class="stat-card reviews">
        <div class="stat-icon reviews">
          <i class="fas fa-clipboard-check"></i>
        </div>
        <div class="stat-number"><?= $stats['my_reviews'] ?></div>
        <div class="stat-label">My Reviews</div>
      </div>
    </div>

    <!-- Dashboard Tabs -->
    <div class="dashboard-tabs">
      <button class="tab-button active" onclick="showTab('pending')" id="tab-pending">
        <i class="fas fa-clock"></i>
        Pending Reviews (<?= count($pending) ?>)
      </button>
      <button class="tab-button" onclick="showTab('recent')" id="tab-recent">
        <i class="fas fa-history"></i>
        Recent Activity
      </button>
      <button class="tab-button" onclick="showTab('analytics')" id="tab-analytics">
        <i class="fas fa-chart-bar"></i>
        Analytics
      </button>
    </div>

    <!-- Pending Projects Section -->
    <div id="content-pending" class="content-section active">
      <?php if (!empty($pending)): ?>
        <!-- Bulk Actions -->
        <div class="bulk-actions">
          <div class="select-all">
            <input type="checkbox" id="selectAll" onchange="toggleAllCheckboxes()">
            <label for="selectAll">Select All</label>
          </div>
          <form method="post" id="bulkForm">
            <div class="bulk-controls">
              <input type="number" name="bulk_grade" class="bulk-input" placeholder="Grade (1-10)" min="1" max="10" step="0.1">
              <input type="text" name="bulk_comments" class="bulk-input" placeholder="Comments" maxlength="500">
              <button type="submit" name="bulk_action" value="approve" class="bulk-btn bulk-approve" onclick="return confirmBulkAction('approve')">
                <i class="fas fa-check"></i> Bulk Approve
              </button>
              <button type="submit" name="bulk_action" value="reject" class="bulk-btn bulk-reject" onclick="return confirmBulkAction('reject')">
                <i class="fas fa-times"></i> Bulk Reject
              </button>
            </div>
          </form>
        </div>
      <?php endif; ?>

      <!-- Section Header -->
      <div class="section-header">
        <h2 class="section-title">
          <i class="fas fa-list-alt"></i>
          Pending Submissions
        </h2>
        <?php if ($pending): ?>
          <div class="pending-badge">
            <?= count($pending) ?> Awaiting Review
          </div>
        <?php endif; ?>
      </div>

      <!-- Projects List -->
      <?php if (!$pending): ?>
        <div class="empty-state">
          <div class="empty-icon">
            <i class="fas fa-check-double"></i>
          </div>
          <h3 class="empty-title">All Caught Up!</h3>
          <p class="empty-message">No pending submissions at this time. Great work!</p>
        </div>
      <?php else: ?>
        <?php foreach($pending as $p): ?>
        <div class="project-card">
          <!-- Project Header -->
          <div class="project-header">
            <div class="project-title">
              <input type="checkbox" name="project_ids[]" value="<?= $p['id'] ?>" class="project-checkbox" form="bulkForm">
              <span><?= htmlspecialchars($p['title']) ?></span>
              <span class="domain-badge"><?= htmlspecialchars($p['domain']) ?></span>
              <?php if ($p['avg_rating'] > 0): ?>
                <span style="color: #fbbf24; font-size: 0.875rem;">
                  ★ <?= number_format($p['avg_rating'], 1) ?>
                </span>
              <?php endif; ?>
            </div>
            <div class="project-meta">
              <div class="meta-item">
                <i class="fas fa-user"></i>
                <span><?= htmlspecialchars($p['name']) ?></span>
              </div>
              <div class="meta-item">
                <i class="fas fa-calendar-alt"></i>
                <span><?= date('M j, Y', strtotime($p['submission_date'])) ?></span>
              </div>
              <div class="meta-item">
                <i class="fas fa-clock"></i>
                <span><?= date('g:i A', strtotime($p['submission_date'])) ?></span>
              </div>
            </div>
          </div>

          <!-- Project Body -->
          <div class="project-body">
            <p class="project-intro">
              <?= htmlspecialchars(substr($p['intro'], 0, 300)) ?><?= strlen($p['intro']) > 300 ? '...' : '' ?>
            </p>
            <a href="view_project.php?id=<?= $p['id'] ?>" class="view-details-btn" target="_blank">
              <i class="fas fa-eye"></i>
              View Full Details
            </a>
          </div>

          <!-- Project Footer -->
          <div class="project-footer">
            <form method="post" class="review-form" id="reviewForm<?= $p['id'] ?>">
              <input type="hidden" name="project_id" value="<?= $p['id'] ?>">
              
              <div class="form-group-compact">
                <label class="form-label-compact">
                  <i class="fas fa-star"></i>
                  Grade (1-10)
                </label>
                <input 
                  type="number" 
                  name="grade" 
                  class="form-control-compact" 
                  min="1" 
                  max="10" 
                  step="0.1"
                  placeholder="Optional"
                >
              </div>
              
              <div class="form-group-compact">
                <label class="form-label-compact">
                  <i class="fas fa-comment"></i>
                  Comments & Feedback
                </label>
                <input 
                  type="text" 
                  name="comments" 
                  class="form-control-compact" 
                  placeholder="Optional feedback for the student..."
                  maxlength="500"
                >
              </div>
              
              <div class="form-group-compact">
                <div class="action-buttons">
                  <button 
                    type="submit" 
                    name="action" 
                    value="approve" 
                    class="btn-approve"
                    onclick="return confirm('Are you sure you want to approve this project?')"
                  >
                    <i class="fas fa-check"></i>
                    Approve
                  </button>
                  <button 
                    type="submit" 
                    name="action" 
                    value="reject" 
                    class="btn-reject"
                    onclick="return confirm('Are you sure you want to reject this project?')"
                  >
                    <i class="fas fa-times"></i>
                    Reject
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Recent Activity Section -->
    <div id="content-recent" class="content-section">
      <div class="recent-activity">
        <div class="activity-header">
          <h3 class="activity-title">
            <i class="fas fa-history"></i>
            Recently Reviewed Projects
          </h3>
        </div>
        <div class="activity-body">
          <?php if (!empty($recentlyReviewed)): ?>
            <?php foreach($recentlyReviewed as $review): ?>
            <div class="activity-item">
              <div class="activity-icon <?= $review['status'] ?>">
                <i class="fas fa-<?= $review['status'] === 'approved' ? 'check' : 'times' ?>"></i>
              </div>
              <div class="activity-content">
                <div class="activity-project"><?= htmlspecialchars($review['title']) ?></div>
                <div class="activity-details">
                  <?= ucfirst($review['status']) ?> project by <?= htmlspecialchars($review['name']) ?> 
                  <?php if ($review['grade']): ?>
                    • Grade: <?= $review['grade'] ?>/10
                  <?php endif; ?>
                  • <?= date('M j, Y \a\t g:i A', strtotime($review['review_date'])) ?>
                </div>
              </div>
              <a href="view_project.php?id=<?= $review['id'] ?>" class="view-details-btn" target="_blank" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                View
              </a>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="empty-state" style="padding: 2rem;">
              <div class="empty-icon" style="width: 80px; height: 80px; font-size: 2rem; margin-bottom: 1rem;">
                <i class="fas fa-history"></i>
              </div>
              <h4 class="empty-title" style="font-size: 1.25rem;">No Recent Activity</h4>
              <p class="empty-message" style="font-size: 1rem;">Start reviewing projects to see your activity here.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Analytics Section -->
    <div id="content-analytics" class="content-section">
      <div class="analytics-grid">
        <div class="chart-card">
          <h3 class="chart-title">
            <i class="fas fa-chart-pie"></i>
            Projects by Domain
          </h3>
          <?php if (!empty($domainStats)): ?>
            <?php $maxCount = max(array_column($domainStats, 'count')); ?>
            <?php foreach($domainStats as $domain): ?>
            <div class="domain-bar">
              <div class="domain-label">
                <span class="domain-name"><?= htmlspecialchars($domain['domain']) ?></span>
                <span class="domain-count"><?= $domain['count'] ?> projects</span>
              </div>
              <div class="progress-bar-container">
                <div class="progress-bar" style="width: <?= ($domain['count'] / $maxCount) * 100 ?>%"></div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="text-align: center; color: #6b7280; padding: 2rem;">No data available yet.</p>
          <?php endif; ?>
        </div>
        
        <div class="chart-card">
          <h3 class="chart-title">
            <i class="fas fa-chart-bar"></i>
            Review Performance
          </h3>
          <div class="stat-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: 12px;">
              <div style="font-size: 1.5rem; font-weight: 700; color: #10b981;">
                <?= round(($stats['approved'] / max($stats['total_projects'], 1)) * 100) ?>%
              </div>
              <div style="color: #6b7280; font-size: 0.875rem;">Approval Rate</div>
            </div>
            <div style="text-align: center; padding: 1rem; background: #f8fafc; border-radius: 12px;">
              <div style="font-size: 1.5rem; font-weight: 700; color: #667eea;">
                <?= $stats['my_reviews'] ?>
              </div>
              <div style="color: #6b7280; font-size: 0.875rem;">My Reviews</div>
            </div>
          </div>
          
          <div style="margin-top: 2rem;">
            <h4 style="font-size: 1rem; font-weight: 600; color: #374151; margin-bottom: 1rem;">Quick Stats</h4>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
              <div style="display: flex; justify-content: space-between; padding: 0.5rem; background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <span style="color: #6b7280;">Avg. Reviews per Day</span>
                <span style="color: #1f2937; font-weight: 600;">
                  <?= round($stats['my_reviews'] / max(1, (time() - strtotime('-30 days')) / (60*60*24))) ?>
                </span>
              </div>
              <div style="display: flex; justify-content: space-between; padding: 0.5rem; background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <span style="color: #6b7280;">Pending Workload</span>
                <span style="color: #1f2937; font-weight: 600;"><?= $stats['pending'] ?> projects</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    function showTab(tabName) {
      // Hide all content sections
      document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
      });
      
      // Remove active class from all tab buttons
      document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active');
      });
      
      // Show selected content section
      document.getElementById('content-' + tabName).classList.add('active');
      
      // Add active class to selected tab button
      document.getElementById('tab-' + tabName).classList.add('active');
    }
    
    function toggleAllCheckboxes() {
      const selectAll = document.getElementById('selectAll');
      const checkboxes = document.querySelectorAll('.project-checkbox');
      
      checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
      });
    }
    
    function confirmBulkAction(action) {
      const checkedBoxes = document.querySelectorAll('.project-checkbox:checked');
      if (checkedBoxes.length === 0) {
        alert('Please select at least one project.');
        return false;
      }
      
      const actionText = action === 'approve' ? 'approve' : 'reject';
      return confirm(`Are you sure you want to ${actionText} ${checkedBoxes.length} selected project(s)?`);
    }
    
    function exportData() {
      // Create a simple CSV export
      const csvContent = "data:text/csv;charset=utf-8,";
      const headers = ["Project Title", "Student", "Domain", "Status", "Submission Date"];
      
      // This would be populated with actual project data in a real implementation
      alert('Export functionality would be implemented here. This would generate a CSV/PDF report of all projects.');
    }
    
    document.addEventListener('DOMContentLoaded', function() {
      // Add loading states to review forms
      const forms = document.querySelectorAll('[id^="reviewForm"]');
      
      forms.forEach(form => {
        form.addEventListener('submit', function(e) {
          const submitBtn = e.submitter;
          if (submitBtn) {
            const originalText = submitBtn.innerHTML;
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = submitBtn.classList.contains('btn-approve') 
              ? '<i class="fas fa-spinner fa-spin"></i> Approving...'
              : '<i class="fas fa-spinner fa-spin"></i> Rejecting...';
            submitBtn.disabled = true;
            
            // Disable other buttons in the form
            const otherButtons = form.querySelectorAll('button:not([disabled])');
            otherButtons.forEach(btn => {
              if (btn !== submitBtn) {
                btn.disabled = true;
                btn.style.opacity = '0.6';
              }
            });
          }
        });
      });
      
      // Auto-resize textareas
      const textareas = document.querySelectorAll('input[name="comments"]');
      textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
          if (this.value.length > 400) {
            this.style.borderColor = '#f59e0b';
          } else {
            this.style.borderColor = '#e5e7eb';
          }
        });
      });
      
      // Add keyboard shortcuts
      document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + R to refresh
        if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
          e.preventDefault();
          window.location.reload();
        }
        
        // Number keys to switch tabs
        if (e.key >= '1' && e.key <= '3') {
          const tabs = ['pending', 'recent', 'analytics'];
          showTab(tabs[parseInt(e.key) - 1]);
        }
      });
      
      // Animate progress bars in analytics
      setTimeout(() => {
        document.querySelectorAll('.progress-bar').forEach(bar => {
          const width = bar.style.width;
          bar.style.width = '0%';
          setTimeout(() => {
            bar.style.width = width;
          }, 100);
        });
      }, 500);
    });
  </script>
</body>

</html>
