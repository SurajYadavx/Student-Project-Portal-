<?php
// enhanced_search_projects.php - REPLACE your existing search_projects.php
require_once 'config.php';
if (!isLoggedIn() || !isStudent()) redirect('index.php');

$domain = $_GET['domain'] ?? '';
$status = $_GET['status'] ?? '';
$min_rating = floatval($_GET['min_rating'] ?? 0);
$search_term = trim($_GET['search'] ?? '');

$sql = "SELECT p.*, u.name AS student_name FROM projects p JOIN users u ON p.student_id = u.id WHERE 1=1";
$params = [];

if ($domain) {
  $sql .= " AND p.domain = ?";
  $params[] = $domain;
}
if ($status) {
  $sql .= " AND p.status = ?";
  $params[] = $status;
}
if ($min_rating > 0) {
  $sql .= " AND p.avg_rating >= ?";
  $params[] = $min_rating;
}
if ($search_term) {
  $sql .= " AND (p.title LIKE ? OR p.intro LIKE ?)";
  $params[] = "%$search_term%";
  $params[] = "%$search_term%";
}

$sql .= " ORDER BY p.avg_rating DESC, p.submission_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$projects = $stmt->fetchAll();

$domains = ['AI','Web Development','Mobile App','IoT','Data Science','Cybersecurity','Blockchain','Other'];

// Count active filters
$active_filters = 0;
if ($domain) $active_filters++;
if ($status) $active_filters++;
if ($min_rating > 0) $active_filters++;
if ($search_term) $active_filters++;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Search Projects - Project Portal</title>
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

    .search-container {
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

    .search-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      box-shadow: 
        0 25px 50px -12px rgba(0, 0, 0, 0.25),
        0 0 0 1px rgba(255, 255, 255, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.2);
      margin-bottom: 2rem;
      overflow: hidden;
      animation: slideInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
      position: relative;
    }

    .search-card::before {
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
        transform: translateY(30px);
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

    .search-header {
      background: linear-gradient(135deg, #f8fafc, #e2e8f0);
      padding: 2rem;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .search-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .search-subtitle {
      color: #6b7280;
      font-size: 1rem;
      margin: 0;
    }

    .search-body {
      padding: 2.5rem;
    }

    .search-form {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      align-items: end;
    }

    .form-group-modern {
      display: flex;
      flex-direction: column;
    }

    .form-label-modern {
      color: #374151;
      font-weight: 600;
      font-size: 0.95rem;
      margin-bottom: 0.75rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .form-control-modern,
    .form-select-modern {
      padding: 0.875rem 1rem;
      border: 2px solid #e5e7eb;
      border-radius: 12px;
      font-size: 0.95rem;
      background: #f8fafc;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      color: #374151;
    }

    .form-control-modern:focus,
    .form-select-modern:focus {
      outline: none;
      border-color: #667eea;
      background: white;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
      transform: translateY(-1px);
    }

    .form-select-modern {
      cursor: pointer;
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
      background-position: right 0.75rem center;
      background-repeat: no-repeat;
      background-size: 1rem;
      appearance: none;
    }

    .btn-search {
      background: linear-gradient(135deg, #667eea, #764ba2);
      border: none;
      border-radius: 12px;
      color: white;
      padding: 0.875rem 1.5rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      position: relative;
      overflow: hidden;
    }

    .btn-search::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.6s;
    }

    .btn-search:hover::before {
      left: 100%;
    }

    .btn-search:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
      background: linear-gradient(135deg, #5a67d8, #667eea);
    }

    .btn-clear {
      background: linear-gradient(135deg, #f8fafc, #e2e8f0);
      border: 2px solid #d1d5db;
      border-radius: 12px;
      color: #374151;
      padding: 0.875rem 1.5rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      margin-left: 0.75rem;
    }

    .btn-clear:hover {
      background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
      border-color: #9ca3af;
      color: #1f2937;
      transform: translateY(-2px);
      text-decoration: none;
    }

    .active-filters {
      display: flex;
      gap: 0.75rem;
      margin-top: 1.5rem;
      flex-wrap: wrap;
    }

    .filter-chip {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-size: 0.875rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateX(-20px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    .results-header {
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
      animation: slideInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) 0.2s both;
    }

    .results-title {
      color: #1f2937;
      font-size: 1.75rem;
      font-weight: 700;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .results-count {
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: white;
      padding: 0.5rem 1.5rem;
      border-radius: 25px;
      font-weight: 600;
      font-size: 1rem;
    }

    .project-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
      gap: 2rem;
      animation: fadeIn 1s ease 0.4s both;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .project-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(255, 255, 255, 0.2);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      height: fit-content;
      animation: slideInUp 0.6s ease;
      animation-fill-mode: both;
    }

    .project-card:nth-child(1) { animation-delay: 0.1s; }
    .project-card:nth-child(2) { animation-delay: 0.2s; }
    .project-card:nth-child(3) { animation-delay: 0.3s; }
    .project-card:nth-child(4) { animation-delay: 0.4s; }
    .project-card:nth-child(5) { animation-delay: 0.5s; }
    .project-card:nth-child(6) { animation-delay: 0.6s; }

    .project-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .project-header {
      padding: 2rem 2rem 1.5rem;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
      position: relative;
    }

    .project-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 1rem;
      line-height: 1.4;
    }

    .status-badge {
      padding: 0.25rem 0.75rem;
      border-radius: 15px;
      font-size: 0.8rem;
      font-weight: 600;
      margin-left: 0.5rem;
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

    .project-body {
      padding: 0 2rem 1.5rem;
    }

    .project-intro {
      color: #6b7280;
      line-height: 1.6;
      margin-bottom: 1.5rem;
      font-size: 0.95rem;
    }

    .project-meta {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    .meta-item {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.9rem;
      color: #6b7280;
    }

    .meta-icon {
      color: #667eea;
      font-size: 1rem;
    }

    .project-rating {
      text-align: center;
      padding: 1rem;
      background: #f8fafc;
      border-radius: 12px;
      margin-bottom: 1.5rem;
    }

    .stars {
      font-size: 1.2rem;
      color: #fbbf24;
      margin-bottom: 0.25rem;
    }

    .rating-text {
      color: #6b7280;
      font-size: 0.875rem;
      font-weight: 500;
    }

    .project-footer {
      padding: 1.5rem 2rem 2rem;
      border-top: 1px solid rgba(0, 0, 0, 0.05);
    }

    .view-btn {
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
      width: 100%;
      justify-content: center;
    }

    .view-btn:hover {
      background: linear-gradient(135deg, #0284c7, #0369a1);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(14, 165, 233, 0.4);
      color: white;
      text-decoration: none;
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

    .empty-suggestions {
      background: #f8fafc;
      border-radius: 12px;
      padding: 1.5rem;
      margin-top: 2rem;
    }

    .suggestion-title {
      font-weight: 600;
      color: #374151;
      margin-bottom: 1rem;
    }

    .suggestion-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .suggestion-list li {
      color: #6b7280;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
      .search-container {
        padding: 1rem;
      }
      
      .page-title {
        font-size: 2rem;
        flex-direction: column;
        gap: 0.5rem;
      }
      
      .search-form {
        grid-template-columns: 1fr;
      }
      
      .search-body {
        padding: 2rem;
      }
      
      .results-header {
        flex-direction: column;
        text-align: center;
      }
      
      .project-grid {
        grid-template-columns: 1fr;
      }
      
      .project-meta {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 576px) {
      .search-header,
      .search-body {
        padding: 1.5rem;
      }
      
      .project-header,
      .project-body,
      .project-footer {
        padding-left: 1.5rem;
        padding-right: 1.5rem;
      }
    }

    /* Loading state */
    .btn-search.loading {
      pointer-events: none;
      opacity: 0.8;
    }

    .btn-search.loading::after {
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
  
  <div class="search-container">
    <!-- Page Header -->
    <div class="page-header">
      <h1 class="page-title">
        <i class="fas fa-search"></i>
        Discover Projects
      </h1>
      <p class="page-subtitle">Explore and find amazing student projects from various domains</p>
    </div>

    <div class="container">
      <!-- Search Form -->
      <div class="search-card">
        <div class="search-header">
          <h2 class="search-title">
            <i class="fas fa-filter"></i>
            Advanced Search
          </h2>
          <p class="search-subtitle">Use filters to find exactly what you're looking for</p>
        </div>
        
        <div class="search-body">
          <form method="get" id="searchForm">
            <div class="search-form">
              <!-- Search Term -->
              <div class="form-group-modern">
                <label class="form-label-modern">
                  <i class="fas fa-search"></i>
                  Search Term
                </label>
                <input 
                  type="text" 
                  name="search" 
                  class="form-control-modern" 
                  value="<?= htmlspecialchars($search_term) ?>" 
                  placeholder="Project title or description..."
                >
              </div>

              <!-- Domain -->
              <div class="form-group-modern">
                <label class="form-label-modern">
                  <i class="fas fa-tags"></i>
                  Domain
                </label>
                <select name="domain" class="form-select-modern">
                  <option value="">All Domains</option>
                  <?php foreach($domains as $d): ?>
                    <option value="<?= $d ?>" <?= $domain===$d?'selected':'' ?>><?= $d ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- Status -->
              <div class="form-group-modern">
                <label class="form-label-modern">
                  <i class="fas fa-flag"></i>
                  Status
                </label>
                <select name="status" class="form-select-modern">
                  <option value="">All Status</option>
                  <option value="pending" <?= $status==='pending'?'selected':'' ?>>Pending Review</option>
                  <option value="approved" <?= $status==='approved'?'selected':'' ?>>Approved</option>
                  <option value="rejected" <?= $status==='rejected'?'selected':'' ?>>Rejected</option>
                </select>
              </div>

              <!-- Min Rating -->
              <div class="form-group-modern">
                <label class="form-label-modern">
                  <i class="fas fa-star"></i>
                  Minimum Rating
                </label>
                <select name="min_rating" class="form-select-modern">
                  <option value="0">Any Rating</option>
                  <option value="1" <?= $min_rating==1?'selected':'' ?>>1+ Stars</option>
                  <option value="2" <?= $min_rating==2?'selected':'' ?>>2+ Stars</option>
                  <option value="3" <?= $min_rating==3?'selected':'' ?>>3+ Stars</option>
                  <option value="4" <?= $min_rating==4?'selected':'' ?>>4+ Stars</option>
                  <option value="5" <?= $min_rating==5?'selected':'' ?>>5 Stars Only</option>
                </select>
              </div>

              <!-- Search Button -->
              <div class="form-group-modern">
                <button type="submit" class="btn-search" id="searchBtn">
                  <i class="fas fa-search"></i>
                  Search Projects
                </button>
                <?php if ($active_filters > 0): ?>
                  <a href="search_projects.php" class="btn-clear">
                    <i class="fas fa-times"></i>
                    Clear All
                  </a>
                <?php endif; ?>
              </div>
            </div>

            <!-- Active Filters -->
            <?php if ($active_filters > 0): ?>
              <div class="active-filters">
                <?php if ($search_term): ?>
                  <div class="filter-chip">
                    <i class="fas fa-search"></i>
                    "<?= htmlspecialchars($search_term) ?>"
                  </div>
                <?php endif; ?>
                <?php if ($domain): ?>
                  <div class="filter-chip">
                    <i class="fas fa-tag"></i>
                    <?= htmlspecialchars($domain) ?>
                  </div>
                <?php endif; ?>
                <?php if ($status): ?>
                  <div class="filter-chip">
                    <i class="fas fa-flag"></i>
                    <?= ucfirst($status) ?>
                  </div>
                <?php endif; ?>
                <?php if ($min_rating > 0): ?>
                  <div class="filter-chip">
                    <i class="fas fa-star"></i>
                    <?= $min_rating ?>+ Stars
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <!-- Results Header -->
      <div class="results-header">
        <h2 class="results-title">
          <i class="fas fa-list-alt"></i>
          Search Results
        </h2>
        <div class="results-count">
          <?= count($projects) ?> Projects Found
        </div>
      </div>

      <!-- Results -->
      <?php if (!$projects): ?>
        <div class="empty-state">
          <div class="empty-icon">
            <i class="fas fa-search"></i>
          </div>
          <h3 class="empty-title">No Projects Found</h3>
          <p class="empty-message">We couldn't find any projects matching your search criteria.</p>
          
          <div class="empty-suggestions">
            <div class="suggestion-title">Try these suggestions:</div>
            <ul class="suggestion-list">
              <li><i class="fas fa-lightbulb"></i> Try different or broader search terms</li>
              <li><i class="fas fa-filter"></i> Remove some filters to see more results</li>
              <li><i class="fas fa-tags"></i> Browse projects by domain instead</li>
              <li><i class="fas fa-star"></i> Lower the minimum rating requirement</li>
            </ul>
          </div>
        </div>
      <?php else: ?>
        <div class="project-grid">
          <?php foreach($projects as $p): ?>
          <div class="project-card">
            <!-- Project Header -->
            <div class="project-header">
              <h3 class="project-title">
                <?= htmlspecialchars($p['title']) ?>
                <span class="status-badge status-<?= $p['status'] ?>">
                  <?= ucfirst($p['status']) ?>
                </span>
              </h3>
            </div>

            <!-- Project Body -->
            <div class="project-body">
              <p class="project-intro">
                <?= htmlspecialchars(substr($p['intro'], 0, 150)) ?><?= strlen($p['intro']) > 150 ? '...' : '' ?>
              </p>

              <!-- Project Meta -->
              <div class="project-meta">
                <div class="meta-item">
                  <i class="fas fa-user meta-icon"></i>
                  <span><?= htmlspecialchars($p['student_name']) ?></span>
                </div>
                <div class="meta-item">
                  <i class="fas fa-tag meta-icon"></i>
                  <span><?= htmlspecialchars($p['domain']) ?></span>
                </div>
                <div class="meta-item">
                  <i class="fas fa-calendar-alt meta-icon"></i>
                  <span><?= date('M j, Y', strtotime($p['submission_date'])) ?></span>
                </div>
                <div class="meta-item">
                  <i class="fas fa-eye meta-icon"></i>
                  <span>View Details</span>
                </div>
              </div>

              <!-- Rating -->
              <?php if ($p['avg_rating'] > 0): ?>
                <div class="project-rating">
                  <div class="stars">
                    <?= str_repeat('★', floor($p['avg_rating'])) ?>
                    <?= str_repeat('☆', 5-floor($p['avg_rating'])) ?>
                  </div>
                  <div class="rating-text">
                    <?= number_format($p['avg_rating'],1) ?> out of 5 stars
                  </div>
                </div>
              <?php endif; ?>
            </div>

            <!-- Project Footer -->
            <div class="project-footer">
              <a href="view_project.php?id=<?= $p['id'] ?>" class="view-btn">
                <i class="fas fa-eye"></i>
                View Project Details
              </a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const searchForm = document.getElementById('searchForm');
      const searchBtn = document.getElementById('searchBtn');
      const originalBtnText = searchBtn.innerHTML;
      
      // Add loading state to search form
      searchForm.addEventListener('submit', function() {
        searchBtn.classList.add('loading');
        searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
        searchBtn.disabled = true;
      });
      
      // Real-time search functionality (optional)
      const searchInput = document.querySelector('input[name="search"]');
      let searchTimeout;
      
      // Auto-submit form on filter changes (optional enhancement)
      const filterInputs = document.querySelectorAll('select[name="domain"], select[name="status"], select[name="min_rating"]');
      filterInputs.forEach(input => {
        input.addEventListener('change', function() {
          // Optional: Auto-submit form when filters change
          // Uncomment the line below if you want instant filtering
          // searchForm.submit();
        });
      });
      
      // Keyboard shortcuts
      document.addEventListener('keydown', function(e) {
        // Focus search input when pressing '/' key
        if (e.key === '/' && !e.target.matches('input, textarea, select')) {
          e.preventDefault();
          searchInput.focus();
        }
        
        // Submit form when pressing Enter in search input
        if (e.key === 'Enter' && e.target === searchInput) {
          searchForm.submit();
        }
      });
      
      // Add hover effects to project cards
      const projectCards = document.querySelectorAll('.project-card');
      projectCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
          this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
          this.style.transform = 'translateY(0) scale(1)';
        });
      });
    });
  </script>
</body>

</html>
