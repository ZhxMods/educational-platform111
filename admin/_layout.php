<?php
/**
 * admin/_layout.php — Admin Layout Template
 * MODERNIZED: Professional blue theme with hover effects
 * Usage: Include at top of admin pages, content goes in main section
 */

if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config.php';
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require admin access
requireRole(['admin', 'super_admin']);

$currentLang = getCurrentLang();
$dir         = getDirection();
$isRtl       = $dir === 'rtl';
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Get stats for dashboard
$totalUsers = (int) db_value('SELECT COUNT(*) FROM users WHERE role = "student"');
$totalLessons = (int) db_value('SELECT COUNT(*) FROM lessons WHERE is_published = 1');
$totalQuizzes = (int) db_value('SELECT COUNT(*) FROM quizzes WHERE is_active = 1');

$csrfToken = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>" dir="<?php echo $dir; ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $pageTitle ?? 'Admin Panel'; ?> — <?php echo t('app_name'); ?></title>
  <meta name="csrf-token" content="<?php echo $csrfToken; ?>">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

  <style>
    :root {
      --blue-950: #0f1f5c;
      --blue-900: #1e3a8a;
      --blue-700: #1d4ed8;
      --blue-500: #3b82f6;
      --blue-100: #dbeafe;
      --blue-50:  #eff6ff;
      --white:    #ffffff;
      --gray-50:  #f8fafc;
      --gray-100: #f1f5f9;
      --gray-200: #e2e8f0;
      --gray-500: #64748b;
      --gray-700: #334155;
      --gray-900: #0f172a;
      --sidebar-w: 260px;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--gray-100);
      color: var(--gray-700);
      min-height: 100vh;
    }

    /* Top Bar */
    .topbar {
      position: sticky;
      top: 0;
      z-index: 100;
      background: linear-gradient(90deg, var(--blue-950), var(--blue-700));
      height: 64px;
      display: flex;
      align-items: center;
      padding: 0 1.5rem;
      gap: 1rem;
      box-shadow: 0 2px 20px rgba(0,0,0,.3);
    }

    .topbar-brand {
      display: flex;
      align-items: center;
      gap: .6rem;
      color: #fff;
      text-decoration: none;
      font-weight: 700;
      font-size: 1.05rem;
    }

    .brand-icon {
      width: 36px;
      height: 36px;
      background: rgba(255,255,255,.15);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .hamburger {
      display: none;
      background: none;
      border: none;
      color: #fff;
      cursor: pointer;
      padding: .3rem;
    }

    .topbar-spacer { flex: 1; }

    .topbar-stats {
      display: flex;
      align-items: center;
      gap: 1.5rem;
    }

    .stat-pill {
      display: flex;
      align-items: center;
      gap: .4rem;
      padding: .35rem .75rem;
      background: rgba(255,255,255,.12);
      border: 1px solid rgba(255,255,255,.2);
      border-radius: 50px;
      color: #fff;
      font-size: .8rem;
      font-weight: 600;
    }

    .stat-num {
      color: #fde68a;
      font-weight: 700;
    }

    .admin-avatar {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--blue-500), #818cf8);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-weight: 700;
      font-size: .9rem;
      border: 2px solid rgba(255,255,255,.3);
    }

    /* Layout */
    .admin-layout {
      display: flex;
      min-height: calc(100vh - 64px);
    }

    /* Sidebar */
    .sidebar {
      width: var(--sidebar-w);
      background: var(--white);
      border-<?php echo $isRtl ? 'left' : 'right'; ?>: 1px solid var(--gray-200);
      flex-shrink: 0;
      display: flex;
      flex-direction: column;
      padding: 1.5rem 0;
      position: sticky;
      top: 64px;
      height: calc(100vh - 64px);
      overflow-y: auto;
      transition: transform .3s;
    }

    .sidebar-section-label {
      font-size: .7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .08em;
      color: var(--gray-400);
      padding: 0 1.25rem .5rem;
      margin-top: 1rem;
    }

    .sidebar-item {
      display: flex;
      align-items: center;
      gap: .75rem;
      padding: .7rem 1.25rem;
      color: var(--gray-600);
      text-decoration: none;
      font-size: .875rem;
      font-weight: 500;
      border-<?php echo $isRtl ? 'right' : 'left'; ?>: 3px solid transparent;
      transition: all .2s;
    }

    .sidebar-item:hover {
      background: var(--blue-50);
      color: var(--blue-700);
    }

    .sidebar-item.active {
      background: var(--blue-50);
      color: var(--blue-700);
      font-weight: 600;
      border-<?php echo $isRtl ? 'right' : 'left'; ?>-color: var(--blue-600);
    }

    .sidebar-badge {
      margin-<?php echo $isRtl ? 'right' : 'left'; ?>: auto;
      background: var(--blue-100);
      color: var(--blue-700);
      border-radius: 50px;
      padding: .1rem .5rem;
      font-size: .72rem;
      font-weight: 700;
    }

    /* Main Content */
    .admin-main {
      flex: 1;
      padding: 1.75rem;
      min-width: 0;
    }

    /* Responsive */
    @media (max-width: 968px) {
      .sidebar {
        position: fixed;
        top: 64px;
        <?php echo $isRtl ? 'right' : 'left'; ?>: 0;
        height: calc(100vh - 64px);
        z-index: 90;
        transform: translateX(<?php echo $isRtl ? '100%' : '-100%'; ?>);
        box-shadow: 4px 0 20px rgba(0,0,0,.15);
      }

      .sidebar.open {
        transform: translateX(0);
      }

      .hamburger {
        display: flex;
      }

      .topbar-stats {
        display: none;
      }

      .admin-main {
        padding: 1rem;
      }
    }
  </style>

  <?php if (isset($additionalStyles)): ?>
    <?php echo $additionalStyles; ?>
  <?php endif; ?>
</head>
<body>

<!-- Top Bar -->
<nav class="topbar">
  <button class="hamburger" id="hamburger" aria-label="Menu">
    <i data-lucide="menu" width="22" height="22"></i>
  </button>

  <a href="dashboard.php" class="topbar-brand">
    <span class="brand-icon">
      <i data-lucide="graduation-cap" width="20" height="20" color="white"></i>
    </span>
    <?php echo t('app_name'); ?> Admin
  </a>

  <div class="topbar-spacer"></div>

  <div class="topbar-stats">
    <div class="stat-pill">
      <i data-lucide="users" width="14" height="14"></i>
      <span class="stat-num"><?php echo number_format($totalUsers); ?></span>
      Users
    </div>
    <div class="stat-pill">
      <i data-lucide="book-open" width="14" height="14"></i>
      <span class="stat-num"><?php echo number_format($totalLessons); ?></span>
      Lessons
    </div>
  </div>

  <div class="admin-avatar">
    <?php echo mb_strtoupper(mb_substr($currentUser['full_name'], 0, 1, 'UTF-8'), 'UTF-8'); ?>
  </div>
</nav>

<!-- Layout -->
<div class="admin-layout">

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <span class="sidebar-section-label">Main</span>

    <a href="dashboard.php" class="sidebar-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
      <i data-lucide="layout-dashboard" width="18" height="18"></i>
      Dashboard
    </a>

    <a href="manage_lessons.php" class="sidebar-item <?php echo $currentPage === 'manage_lessons' ? 'active' : ''; ?>">
      <i data-lucide="book-open" width="18" height="18"></i>
      Manage Lessons
      <span class="sidebar-badge"><?php echo $totalLessons; ?></span>
    </a>

    <a href="manage_quizzes.php" class="sidebar-item <?php echo $currentPage === 'manage_quizzes' ? 'active' : ''; ?>">
      <i data-lucide="clipboard-list" width="18" height="18"></i>
      Manage Quizzes
      <span class="sidebar-badge"><?php echo $totalQuizzes; ?></span>
    </a>

    <a href="manage_users.php" class="sidebar-item <?php echo $currentPage === 'manage_users' ? 'active' : ''; ?>">
      <i data-lucide="users" width="18" height="18"></i>
      Manage Users
      <span class="sidebar-badge"><?php echo $totalUsers; ?></span>
    </a>

    <span class="sidebar-section-label">Content</span>

    <a href="manage_levels.php" class="sidebar-item <?php echo $currentPage === 'manage_levels' ? 'active' : ''; ?>">
      <i data-lucide="graduation-cap" width="18" height="18"></i>
      Grade Levels
    </a>

    <a href="manage_subjects.php" class="sidebar-item <?php echo $currentPage === 'manage_subjects' ? 'active' : ''; ?>">
      <i data-lucide="folder" width="18" height="18"></i>
      Subjects
    </a>

    <a href="manage_achievements.php" class="sidebar-item <?php echo $currentPage === 'manage_achievements' ? 'active' : ''; ?>">
      <i data-lucide="award" width="18" height="18"></i>
      Achievements
    </a>

    <span class="sidebar-section-label">System</span>

    <a href="settings.php" class="sidebar-item <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
      <i data-lucide="settings" width="18" height="18"></i>
      Settings
    </a>

    <a href="reports.php" class="sidebar-item <?php echo $currentPage === 'reports' ? 'active' : ''; ?>">
      <i data-lucide="bar-chart-3" width="18" height="18"></i>
      Reports
    </a>

    <span class="sidebar-section-label" style="margin-top:auto;">Account</span>

    <a href="../dashboard.php" class="sidebar-item">
      <i data-lucide="arrow-left" width="18" height="18"></i>
      Student View
    </a>

    <a href="../logout.php" class="sidebar-item" style="color:#ef4444;">
      <i data-lucide="log-out" width="18" height="18"></i>
      Logout
    </a>
  </aside>

  <!-- Main Content Area -->
  <main class="admin-main">
    <?php
    // Content will be inserted here by individual pages
    // Pages should NOT include <html>, <head>, or <body> tags
    ?>
