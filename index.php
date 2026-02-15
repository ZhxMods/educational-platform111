<?php
/**
 * index.php - Homepage
 * Public landing page for the educational platform
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Start session for language handling
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle language switching
if (isset($_GET['lang']) && in_array($_GET['lang'], ['ar', 'fr', 'en'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
    header('Location: index.php');
    exit;
}

$currentLang = getCurrentLang();
$dir = getDirection();
$isRtl = ($dir === 'rtl');

// Get statistics
$totalStudents = (int) db_value("SELECT COUNT(*) FROM users WHERE role = 'student' AND is_active = 1");
$totalLessons = (int) db_value("SELECT COUNT(*) FROM lessons WHERE is_published = 1");
$totalSubjects = (int) db_value("SELECT COUNT(*) FROM subjects WHERE is_active = 1");

// Get featured levels
$levels = db_query("SELECT * FROM levels WHERE is_active = 1 ORDER BY display_order ASC LIMIT 6");

$pageTitle = t('welcome', 'Welcome to Learning Platform', 'مرحبا بك في منصة التعليم');
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <?php if ($isRtl): ?>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/rtl.css">
    <?php endif; ?>
</head>
<body class="landing-page">
    <!-- Header -->
    <header class="header-landing">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1><?php echo t('app_name', 'Learning Platform', 'منصة التعليم'); ?></h1>
                </div>
                
                <nav class="nav-landing">
                    <a href="#features"><?php echo t('features', 'Features', 'المميزات'); ?></a>
                    <a href="#levels"><?php echo t('levels', 'Levels', 'المستويات'); ?></a>
                    <a href="#about"><?php echo t('about', 'About', 'حول'); ?></a>
                    <a href="login.php" class="btn btn-outline"><?php echo t('login', 'Login', 'تسجيل الدخول'); ?></a>
                    <a href="register.php" class="btn btn-primary"><?php echo t('register', 'Register', 'التسجيل'); ?></a>
                </nav>
                
                <!-- Language Switcher -->
                <div class="language-switcher">
                    <a href="?lang=ar" class="<?php echo $currentLang === 'ar' ? 'active' : ''; ?>">العربية</a>
                    <a href="?lang=fr" class="<?php echo $currentLang === 'fr' ? 'active' : ''; ?>">Français</a>
                    <a href="?lang=en" class="<?php echo $currentLang === 'en' ? 'active' : ''; ?>">English</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">
                    <?php echo t('hero_title', 'Transform Your Learning Journey', 'حوّل رحلتك التعليمية'); ?>
                </h1>
                <p class="hero-subtitle">
                    <?php echo t('hero_subtitle', 'Interactive lessons, engaging quizzes, and personalized learning paths for students', 'دروس تفاعلية، اختبارات مشوقة، ومسارات تعلم شخصية للطلاب'); ?>
                </p>
                <div class="hero-actions">
                    <a href="register.php" class="btn btn-primary btn-lg">
                        <?php echo t('get_started', 'Get Started Free', 'ابدأ مجاناً'); ?>
                    </a>
                    <a href="#features" class="btn btn-outline btn-lg">
                        <?php echo t('learn_more', 'Learn More', 'اعرف المزيد'); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($totalStudents); ?>+</div>
                    <div class="stat-label"><?php echo t('students', 'Students', 'طالب'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($totalLessons); ?>+</div>
                    <div class="stat-label"><?php echo t('lessons', 'Lessons', 'درس'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($totalSubjects); ?>+</div>
                    <div class="stat-label"><?php echo t('subjects', 'Subjects', 'مادة'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">100%</div>
                    <div class="stat-label"><?php echo t('free', 'Free', 'مجاني'); ?></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <h2 class="section-title"><?php echo t('features_title', 'Why Choose Our Platform?', 'لماذا تختار منصتنا؟'); ?></h2>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📚</div>
                    <h3><?php echo t('interactive_lessons', 'Interactive Lessons', 'دروس تفاعلية'); ?></h3>
                    <p><?php echo t('interactive_lessons_desc', 'Engaging video lessons with step-by-step explanations', 'دروس فيديو مشوقة مع شروحات تفصيلية'); ?></p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🎯</div>
                    <h3><?php echo t('quizzes', 'Smart Quizzes', 'اختبارات ذكية'); ?></h3>
                    <p><?php echo t('quizzes_desc', 'Test your knowledge with interactive quizzes and instant feedback', 'اختبر معلوماتك مع اختبارات تفاعلية وتقييم فوري'); ?></p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">⭐</div>
                    <h3><?php echo t('progress_tracking', 'Progress Tracking', 'تتبع التقدم'); ?></h3>
                    <p><?php echo t('progress_tracking_desc', 'Monitor your learning journey with detailed progress reports', 'راقب رحلتك التعليمية مع تقارير تقدم مفصلة'); ?></p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🏆</div>
                    <h3><?php echo t('achievements', 'Achievements', 'الإنجازات'); ?></h3>
                    <p><?php echo t('achievements_desc', 'Earn XP points and unlock achievements as you learn', 'اكسب نقاط الخبرة وافتح الإنجازات أثناء التعلم'); ?></p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🌍</div>
                    <h3><?php echo t('multilingual', 'Multilingual', 'متعدد اللغات'); ?></h3>
                    <p><?php echo t('multilingual_desc', 'Learn in Arabic, French, or English', 'تعلم بالعربية أو الفرنسية أو الإنجليزية'); ?></p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3><?php echo t('mobile_friendly', 'Mobile Friendly', 'متوافق مع الجوال'); ?></h3>
                    <p><?php echo t('mobile_friendly_desc', 'Learn anywhere, anytime on any device', 'تعلم في أي مكان وأي وقت على أي جهاز'); ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Levels Section -->
    <section id="levels" class="levels-section">
        <div class="container">
            <h2 class="section-title"><?php echo t('available_levels', 'Available Levels', 'المستويات المتاحة'); ?></h2>
            
            <div class="levels-grid">
                <?php foreach ($levels as $level): ?>
                    <div class="level-card">
                        <h3><?php echo $level['name_' . $currentLang]; ?></h3>
                        <p><?php echo $level['description_' . $currentLang]; ?></p>
                        <a href="register.php" class="btn btn-outline">
                            <?php echo t('start_learning', 'Start Learning', 'ابدأ التعلم'); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2><?php echo t('cta_title', 'Ready to Start Learning?', 'هل أنت مستعد للبدء؟'); ?></h2>
            <p><?php echo t('cta_subtitle', 'Join thousands of students on their learning journey', 'انضم لآلاف الطلاب في رحلتهم التعليمية'); ?></p>
            <a href="register.php" class="btn btn-primary btn-lg">
                <?php echo t('register_now', 'Register Now', 'سجل الآن'); ?>
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer-landing">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4><?php echo t('app_name', 'Learning Platform', 'منصة التعليم'); ?></h4>
                    <p><?php echo t('footer_desc', 'Your gateway to quality education', 'بوابتك للتعليم الجيد'); ?></p>
                </div>
                
                <div class="footer-section">
                    <h4><?php echo t('quick_links', 'Quick Links', 'روابط سريعة'); ?></h4>
                    <ul>
                        <li><a href="login.php"><?php echo t('login', 'Login', 'تسجيل الدخول'); ?></a></li>
                        <li><a href="register.php"><?php echo t('register', 'Register', 'التسجيل'); ?></a></li>
                        <li><a href="#features"><?php echo t('features', 'Features', 'المميزات'); ?></a></li>
                        <li><a href="#about"><?php echo t('about', 'About', 'حول'); ?></a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4><?php echo t('contact', 'Contact', 'تواصل'); ?></h4>
                    <p>Email: info@learningplatform.com</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo t('all_rights_reserved', 'All rights reserved', 'جميع الحقوق محفوظة'); ?></p>
            </div>
        </div>
    </footer>
</body>
</html>
