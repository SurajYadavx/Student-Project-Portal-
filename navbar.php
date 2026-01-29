<?php
// includes/navbar.php
require_once __DIR__ . '/../config.php';
?>

<style>
/* Professional Navbar Styling */
.modern-navbar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 2px 20px rgba(0,0,0,0.1);
    padding: 1rem 0;
    position: sticky;
    top: 0;
    z-index: 1000;
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.navbar-brand-modern {
    font-size: 1.5rem;
    font-weight: 700;
    color: white !important;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.navbar-brand-modern:hover {
    color: #ffd700 !important;
    transform: translateY(-1px);
}

.brand-icon {
    width: 32px;
    height: 32px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.nav-links-container {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.nav-link-modern {
    color: rgba(255,255,255,0.9) !important;
    font-weight: 500;
    padding: 0.5rem 1rem !important;
    border-radius: 25px;
    transition: all 0.3s ease;
    text-decoration: none;
    position: relative;
    overflow: hidden;
}

.nav-link-modern:hover {
    color: white !important;
    background: rgba(255,255,255,0.2);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.nav-link-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.6s;
}

.nav-link-modern:hover::before {
    left: 100%;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: white;
    background: rgba(255,255,255,0.15);
    padding: 0.5rem 1rem;
    border-radius: 25px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    transition: all 0.3s ease;
}

.user-profile:hover {
    background: rgba(255,255,255,0.25);
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.user-avatar {
    width: 24px;
    height: 24px;
    background: #ffd700;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: #333;
    font-size: 0.8rem;
}

.logout-btn {
    background: linear-gradient(45deg, #ff6b6b, #ee5a24);
    color: white !important;
    border: none;
    padding: 0.5rem 1.5rem;
    border-radius: 25px;
    font-weight: 500;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.logout-btn:hover {
    background: linear-gradient(45deg, #ee5a24, #ff6b6b);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(238, 90, 36, 0.4);
    color: white !important;
}

.navbar-toggler-modern {
    border: none;
    padding: 0.25rem 0.5rem;
    background: rgba(255,255,255,0.1);
    border-radius: 8px;
    transition: all 0.3s ease;
}

.navbar-toggler-modern:hover {
    background: rgba(255,255,255,0.2);
}

.navbar-toggler-icon-modern {
    background-image: none;
    width: 24px;
    height: 18px;
    position: relative;
}

.navbar-toggler-icon-modern::before,
.navbar-toggler-icon-modern::after,
.navbar-toggler-icon-modern {
    background: white;
    height: 2px;
    border-radius: 1px;
    transition: all 0.3s ease;
}

.navbar-toggler-icon-modern::before,
.navbar-toggler-icon-modern::after {
    content: '';
    position: absolute;
    width: 100%;
    left: 0;
}

.navbar-toggler-icon-modern::before {
    top: -6px;
}

.navbar-toggler-icon-modern::after {
    bottom: -6px;
}

/* Mobile Responsive */
@media (max-width: 991.98px) {
    .navbar-collapse {
        background: rgba(255,255,255,0.95);
        backdrop-filter: blur(10px);
        margin-top: 1rem;
        padding: 1rem;
        border-radius: 15px;
        border: 1px solid rgba(255,255,255,0.2);
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    }
    
    .nav-link-modern {
        color: #333 !important;
        margin: 0.25rem 0;
    }
    
    .nav-link-modern:hover {
        background: rgba(102, 126, 234, 0.1);
        color: #667eea !important;
    }
    
    .user-profile {
        background: rgba(102, 126, 234, 0.1);
        color: #333;
        justify-content: flex-start;
        margin: 0.5rem 0;
    }
    
    .logout-btn {
        margin-top: 0.5rem;
        width: fit-content;
    }
}

/* Animation for navbar items */
@keyframes slideInDown {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.navbar-nav .nav-item {
    animation: slideInDown 0.5s ease;
    animation-fill-mode: both;
}

.navbar-nav .nav-item:nth-child(1) { animation-delay: 0.1s; }
.navbar-nav .nav-item:nth-child(2) { animation-delay: 0.2s; }
.navbar-nav .nav-item:nth-child(3) { animation-delay: 0.3s; }
.navbar-nav .nav-item:nth-child(4) { animation-delay: 0.4s; }
</style>

<nav class="navbar navbar-expand-lg modern-navbar">
    <div class="container-fluid">
        <!-- Enhanced Brand -->
        <a class="navbar-brand-modern" href="<?php echo isStudent() ? 'student_dashboard.php' : 'professor_dashboard.php'; ?>">
            <div class="brand-icon">
                📚
            </div>
            Project Portal
        </a>
        
        <!-- Enhanced Toggle Button -->
        <button class="navbar-toggler navbar-toggler-modern" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon navbar-toggler-icon-modern"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Navigation Links -->
            <ul class="navbar-nav me-auto">
                <?php if (isStudent()): ?>
                    <li class="nav-item">
                        <a class="nav-link nav-link-modern" href="student_dashboard.php">
                            🏠 Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-modern" href="submit_project.php">
                            📤 Submit Project
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-modern" href="search_projects.php">
                            🔍 Search Projects
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link nav-link-modern" href="professor_dashboard.php">
                            🏠 Dashboard
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <!-- User Profile and Actions -->
            <ul class="navbar-nav nav-links-container">
                <li class="nav-item">
                    <a class="nav-link" href="profile.php" style="text-decoration: none;">
                        <div class="user-profile">
                            <div class="user-avatar">
                                <?= strtoupper(substr(htmlspecialchars($_SESSION['name']), 0, 1)) ?>
                            </div>
                            <span><?= htmlspecialchars($_SESSION['name']) ?></span>
                        </div>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="logout-btn" href="logout.php">
                        🚪 Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
