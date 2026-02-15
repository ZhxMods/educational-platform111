<?php
/**
 * index.php — Homepage
 * REDESIGNED: Professional blue theme with micro-animations
 * Theme: Blue gradient navigation, white content areas, modern hero section
 */

declare(strict_types=1);

require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$currentLang = getCurrentLang();
$dir         = getDirection();
$isRtl       = $dir === 'rtl';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = $_SESSION['user_role'] ?? 'student';
    if (in_array($role, ['admin', 'super_admin'], true)) {
        redirect(SITE_URL . '/admin/dashboard.php');
    } else {
        redirect(SITE_URL . '/dashboard.php');
    }
}

$pageTitle = t('app_name') . ' — ' . (t('tagline') ?: 'Excellence Education Platform');
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>" dir="<?php echo $dir; ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <meta name="description" content="Modern educational platform for students">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  
  <!-- Animate.css -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

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
      --radius:   12px;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: <?php echo $isRtl ? "'Cairo'" : "'Plus Jakarta Sans'"; ?>, sans-serif;
      background: var(--white);
      color: var(--gray-700);
      line-height: 1.6;
      overflow-x: hidden;
    }

    /* ── Navigation (Blue Gradient) ── */
    .navbar {
      position: sticky; top: 0; z-index: 100;
      background: linear-gradient(90deg, var(--blue-950) 0%, var(--blue-700) 100%);
      height: 72px;
      display: flex; align-items: center;
      padding: 0 2rem;
      box-shadow: 0 2px 20px rgba(0,0,0,.3);
    }
    .nav-container {
      max-width: 1200px; width: 100%; margin: 0 auto;
      display: flex; align-items: center; gap: 2rem;
    }
    .nav-brand {
      display: flex; align-items: center; gap: .7rem;
      color: #fff; text-decoration: none; font-weight: 800;
      font-size: 1.2rem;
    }
    .brand-icon {
      width: 42px; height: 42px;
      background: rgba(255,255,255,.15);
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
    }
    .nav-spacer { flex: 1; }
    .nav-links {
      display: flex; align-items: center; gap: 1.5rem;
    }
    .nav-link {
      color: rgba(255,255,255,.85);
      text-decoration: none;
      font-size: .9rem;
      font-weight: 500;
      transition: color .2s;
    }
    .nav-link:hover { color: #fff; }
    .btn-login, .btn-signup {
      padding: .65rem 1.5rem;
      border-radius: 50px;
      font-weight: 600;
      font-size: .875rem;
      text-decoration: none;
      transition: all .2s;
      display: inline-flex;
      align-items: center;
      gap: .4rem;
    }
    .btn-login {
      background: rgba(255,255,255,.12);
      border: 1px solid rgba(255,255,255,.2);
      color: #fff;
    }
    .btn-login:hover {
      background: rgba(255,255,255,.2);
    }
    .btn-signup {
      background: #fff;
      color: var(--blue-700);
      box-shadow: 0 4px 16px rgba(0,0,0,.15);
    }
    .btn-signup:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0,0,0,.2);
    }

    /* ── Hero Section ── */
    .hero {
      background: linear-gradient(135deg, var(--blue-900) 0%, var(--blue-600) 100%);
      padding: 5rem 2rem;
      text-align: center;
      color: #fff;
      position: relative;
      overflow: hidden;
    }
    .hero::before {
      content: ''; position: absolute;
      top: -50%; right: -20%;
      width: 600px; height: 600px;
      border-radius: 50%;
      background: rgba(255,255,255,.05);
    }
    .hero::after {
      content: ''; position: absolute;
      bottom: -30%; left: -10%;
      width: 400px; height: 400px;
      border-radius: 50%;
      background: rgba(255,255,255,.03);
    }
    .hero-content {
      max-width: 800px;
      margin: 0 auto;
      position: relative;
      z-index: 1;
    }
    .hero h1 {
      font-size: 3rem;
      font-weight: 900;
      margin-bottom: 1.25rem;
      line-height: 1.15;
      background: linear-gradient(135deg, #fff, #dbeafe);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .hero p {
      font-size: 1.2rem;
      color: rgba(255,255,255,.85);
      margin-bottom: 2.5rem;
      line-height: 1.7;
    }
    .hero-cta {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 1rem;
      flex-wrap: wrap;
    }
    .btn-hero-primary {
      padding: 1rem 2.5rem;
      background: #fff;
      color: var(--blue-700);
      border-radius: 50px;
      font-weight: 700;
      font-size: 1rem;
      text-decoration: none;
      box-shadow: 0 8px 24px rgba(0,0,0,.2);
      transition: all .2s;
      display: inline-flex;
      align-items: center;
      gap: .6rem;
    }
    .btn-hero-primary:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 32px rgba(0,0,0,.25);
    }
    .btn-hero-secondary {
      padding: 1rem 2.5rem;
      background: rgba(255,255,255,.15);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255,255,255,.3);
      color: #fff;
      border-radius: 50px;
      font-weight: 700;
      font-size: 1rem;
      text-decoration: none;
      transition: all .2s;
    }
    .btn-hero-secondary:hover {
      background: rgba(255,255,255,.25);
    }

    /* ── Features Section ── */
    .features {
      padding: 5rem 2rem;
      background: var(--gray-50);
    }
    .features-container {
      max-width: 1200px;
      margin: 0 auto;
    }
    .section-header {
      text-align: center;
      margin-bottom: 3.5rem;
    }
    .section-header h2 {
      font-size: 2.25rem;
      font-weight: 800;
      color: var(--gray-900);
      margin-bottom: .75rem;
    }
    .section-header p {
      font-size: 1.1rem;
      color: var(--gray-500);
    }

    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 2rem;
    }
    .feature-card {
      background: var(--white);
      padding: 2rem;
      border-radius: 16px;
      box-shadow: 0 2px 8px rgba(0,0,0,.05);
      transition: all .3s;
      text-align: center;
    }
    .feature-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 32px rgba(0,0,0,.12);
    }
    .feature-icon {
      width: 72px;
      height: 72px;
      background: var(--blue-50);
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
    }
    .feature-card h3 {
      font-size: 1.3rem;
      font-weight: 700;
      color: var(--gray-900);
      margin-bottom: .75rem;
    }
    .feature-card p {
      color: var(--gray-500);
      line-height: 1.7;
    }

    /* ── Stats Section ── */
    .stats {
      padding: 4rem 2rem;
      background: linear-gradient(135deg, var(--blue-900), var(--blue-700));
      color: #fff;
    }
    .stats-container {
      max-width: 1200px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 3rem;
      text-align: center;
    }
    .stat-item h3 {
      font-size: 3rem;
      font-weight: 900;
      color: #fde68a;
      margin-bottom: .5rem;
    }
    .stat-item p {
      font-size: 1.1rem;
      color: rgba(255,255,255,.8);
    }

    /* ── CTA Section ── */
    .cta {
      padding: 5rem 2rem;
      background: var(--white);
      text-align: center;
    }
    .cta-container {
      max-width: 700px;
      margin: 0 auto;
    }
    .cta h2 {
      font-size: 2.5rem;
      font-weight: 800;
      color: var(--gray-900);
      margin-bottom: 1.25rem;
    }
    .cta p {
      font-size: 1.15rem;
      color: var(--gray-500);
      margin-bottom: 2.5rem;
    }

    /* ── Footer ── */
    .footer {
      padding: 2.5rem 2rem;
      background: var(--gray-900);
      color: rgba(255,255,255,.7);
      text-align: center;
    }
    .footer-container {
      max-width: 1200px;
      margin: 0 auto;
    }
    .footer p {
      font-size: .9rem;
    }
    .footer-links {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 1.5rem;
      margin-top: 1.25rem;
    }
    .footer-link {
      color: rgba(255,255,255,.6);
      text-decoration: none;
      font-size: .85rem;
      transition: color .2s;
    }
    .footer-link:hover { color: #fff; }

    /* ── Responsive ── */
    @media (max-width: 768px) {
      .navbar { padding: 0 1rem; }
      .nav-container { gap: 1rem; }
      .nav-links { display: none; }
      
      .hero { padding: 3rem 1.5rem; }
      .hero h1 { font-size: 2rem; }
      .hero p { font-size: 1rem; }
      
      .features { padding: 3rem 1.5rem; }
      .section-header h2 { font-size: 1.75rem; }
      
      .stats { padding: 3rem 1.5rem; }
      .stat-item h3 { font-size: 2.25rem; }
      
      .cta { padding: 3rem 1.5rem; }
      .cta h2 { font-size: 1.75rem; }
    }

    /* ── Animations ── */
    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-15px); }
    }
    .float-animation {
      animation: float 3s ease-in-out infinite;
    }
  </style>
</head>
<body>

<!-- ── Navigation ── -->
<nav class="navbar">
  <div class="nav-container">
    <a href="index.php" class="nav-brand">
      <span class="brand-icon">
        <i data-lucide="graduation-cap" width="24" height="24" color="white"></i>
      </span>
      <?php echo t('app_name'); ?>
    </a>
    
    <div class="nav-spacer"></div>
    
    <div class="nav-links">
      <a href="#features" class="nav-link"><?php echo t('features') ?: 'Features'; ?></a>
      <a href="#about" class="nav-link"><?php echo t('about') ?: 'About'; ?></a>
      <a href="login.php" class="btn-login">
        <i data-lucide="log-in" width="16" height="16"></i>
        <?php echo t('login'); ?>
      </a>
      <a href="register.php" class="btn-signup">
        <?php echo t('register'); ?>
        <i data-lucide="arrow-right" width="16" height="16"></i>
      </a>
    </div>
  </div>
</nav>

<!-- ── Hero Section ── -->
<section class="hero">
  <div class="hero-content">
    <h1 class="animate__animated animate__fadeInDown">
      <?php echo t('hero_title') ?: 'Excellence in Education'; ?>
    </h1>
    <p class="animate__animated animate__fadeInUp animate__delay-1s">
      <?php echo t('hero_subtitle') ?: 'Modern learning platform designed for Algerian primary students. Interactive lessons, gamified experience, and comprehensive curriculum.'; ?>
    </p>
    <div class="hero-cta animate__animated animate__fadeInUp animate__delay-2s">
      <a href="register.php" class="btn-hero-primary">
        <i data-lucide="rocket" width="20" height="20"></i>
        <?php echo t('get_started') ?: 'Get Started Free'; ?>
      </a>
      <a href="login.php" class="btn-hero-secondary">
        <?php echo t('login') ?: 'Login'; ?>
      </a>
    </div>
  </div>
</section>

<!-- ── Features Section ── -->
<section class="features" id="features">
  <div class="features-container">
    <div class="section-header">
      <h2 class="animate__animated animate__fadeIn"><?php echo t('why_choose_us') ?: 'Why Choose Excellence?'; ?></h2>
      <p class="animate__animated animate__fadeIn"><?php echo t('features_subtitle') ?: 'Everything you need for effective learning'; ?></p>
    </div>

    <div class="features-grid">
      <div class="feature-card animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
        <div class="feature-icon">
          <i data-lucide="video" width="32" height="32" color="#1d4ed8"></i>
        </div>
        <h3><?php echo t('interactive_lessons') ?: 'Interactive Lessons'; ?></h3>
        <p><?php echo t('interactive_lessons_desc') ?: 'Engaging video lessons with interactive quizzes and exercises tailored to your grade level.'; ?></p>
      </div>

      <div class="feature-card animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
        <div class="feature-icon">
          <i data-lucide="trophy" width="32" height="32" color="#1d4ed8"></i>
        </div>
        <h3><?php echo t('gamification') ?: 'Gamification'; ?></h3>
        <p><?php echo t('gamification_desc') ?: 'Earn XP points, unlock achievements, and compete with classmates on the leaderboard.'; ?></p>
      </div>

      <div class="feature-card animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
        <div class="feature-icon">
          <i data-lucide="globe" width="32" height="32" color="#1d4ed8"></i>
        </div>
        <h3><?php echo t('multilingual') ?: 'Multilingual'; ?></h3>
        <p><?php echo t('multilingual_desc') ?: 'Full support for Arabic, French, and English — learn in your preferred language.'; ?></p>
      </div>

      <div class="feature-card animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
        <div class="feature-icon">
          <i data-lucide="bar-chart-3" width="32" height="32" color="#1d4ed8"></i>
        </div>
        <h3><?php echo t('progress_tracking') ?: 'Progress Tracking'; ?></h3>
        <p><?php echo t('progress_tracking_desc') ?: 'Track your learning journey with detailed analytics and performance reports.'; ?></p>
      </div>

      <div class="feature-card animate__animated animate__fadeInUp" style="animation-delay: 0.5s;">
        <div class="feature-icon">
          <i data-lucide="shield-check" width="32" height="32" color="#1d4ed8"></i>
        </div>
        <h3><?php echo t('safe_secure') ?: 'Safe & Secure'; ?></h3>
        <p><?php echo t('safe_secure_desc') ?: 'Privacy-focused platform with secure authentication and data protection.'; ?></p>
      </div>

      <div class="feature-card animate__animated animate__fadeInUp" style="animation-delay: 0.6s;">
        <div class="feature-icon">
          <i data-lucide="smartphone" width="32" height="32" color="#1d4ed8"></i>
        </div>
        <h3><?php echo t('mobile_friendly') ?: 'Mobile Friendly'; ?></h3>
        <p><?php echo t('mobile_friendly_desc') ?: 'Access your lessons anytime, anywhere — on any device.'; ?></p>
      </div>
    </div>
  </div>
</section>

<!-- ── Stats Section ── -->
<section class="stats">
  <div class="stats-container">
    <div class="stat-item animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
      <h3>500+</h3>
      <p><?php echo t('stat_lessons') ?: 'Interactive Lessons'; ?></p>
    </div>
    <div class="stat-item animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
      <h3>1000+</h3>
      <p><?php echo t('stat_students') ?: 'Active Students'; ?></p>
    </div>
    <div class="stat-item animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
      <h3>95%</h3>
      <p><?php echo t('stat_satisfaction') ?: 'Satisfaction Rate'; ?></p>
    </div>
    <div class="stat-item animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
      <h3>6</h3>
      <p><?php echo t('stat_grades') ?: 'Grade Levels'; ?></p>
    </div>
  </div>
</section>

<!-- ── CTA Section ── -->
<section class="cta">
  <div class="cta-container animate__animated animate__fadeIn">
    <h2><?php echo t('cta_title') ?: 'Ready to Start Learning?'; ?></h2>
    <p><?php echo t('cta_subtitle') ?: 'Join thousands of students already excelling with our platform. Sign up today and unlock your potential.'; ?></p>
    <a href="register.php" class="btn-hero-primary float-animation">
      <i data-lucide="user-plus" width="20" height="20"></i>
      <?php echo t('create_account') ?: 'Create Free Account'; ?>
    </a>
  </div>
</section>

<!-- ── Footer ── -->
<footer class="footer">
  <div class="footer-container">
    <p>&copy; <?php echo date('Y'); ?> <?php echo t('app_name'); ?>. <?php echo t('all_rights_reserved') ?: 'All rights reserved.'; ?></p>
    <div class="footer-links">
      <a href="#" class="footer-link"><?php echo t('privacy_policy') ?: 'Privacy Policy'; ?></a>
      <a href="#" class="footer-link"><?php echo t('terms') ?: 'Terms of Service'; ?></a>
      <a href="#" class="footer-link"><?php echo t('contact') ?: 'Contact Us'; ?></a>
    </div>
  </div>
</footer>

<script>
  lucide.createIcons();

  // Smooth scroll for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });
</script>

</body>
</html>
