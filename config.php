<?php
/**
 * Educational Platform — Configuration
 * FIXED: Default language set to French (FR)
 */

declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════════════
//  DATABASE CONFIGURATION
// ═══════════════════════════════════════════════════════════════════════════

define('DB_HOST', 'sql305.infinityfree.com');
define('DB_NAME', 'if0_41161464_testbymoha');
define('DB_USER', 'if0_41161464');
define('DB_PASS', 'YhOYcxnUnM');
define('DB_CHARSET', 'utf8mb4');

// ═══════════════════════════════════════════════════════════════════════════
//  SITE CONFIGURATION
// ═══════════════════════════════════════════════════════════════════════════

define('SITE_URL', 'https://testsitebymoha.free.nf');
define('SITE_NAME', 'Plateforme Éducative | منصة التعليم');

// ═══════════════════════════════════════════════════════════════════════════
//  LANGUAGE SETTINGS — FIXED: DEFAULT IS FRENCH
// ═══════════════════════════════════════════════════════════════════════════

define('DEFAULT_LANG', 'fr');  // FIXED: Was 'ar', now 'fr'
define('AVAILABLE_LANGS', ['ar', 'fr', 'en']);
define('LANG_DIR', __DIR__ . '/lang/');

// ═══════════════════════════════════════════════════════════════════════════
//  GAMIFICATION / XP SYSTEM
// ═══════════════════════════════════════════════════════════════════════════

define('XP_LESSON_COMPLETE', 10);
define('XP_QUIZ_PASS', 50);
define('XP_PER_LEVEL', 100);

// Anti-cheat settings
define('ANTI_CHEAT_ENABLED', true);
define('ANTI_CHEAT_MIN_WATCH_TIME', 60);  // Minimum 60 seconds for PDFs

// ═══════════════════════════════════════════════════════════════════════════
//  SECURITY
// ═══════════════════════════════════════════════════════════════════════════

define('SESSION_LIFETIME', 86400);  // 24 hours

// ═══════════════════════════════════════════════════════════════════════════
//  TIMEZONE (Morocco)
// ═══════════════════════════════════════════════════════════════════════════

date_default_timezone_set('Africa/Casablanca');

// ═══════════════════════════════════════════════════════════════════════════
//  ERROR REPORTING
//  ⚠️ PRODUCTION: Set display_errors to '0'
// ═══════════════════════════════════════════════════════════════════════════

error_reporting(E_ALL);
ini_set('display_errors', '1');  // Change to '0' for production
ini_set('log_errors', '1');

$logDir = __DIR__ . '/logs';
if (!file_exists($logDir)) {
    @mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/php_errors.log');

// ═══════════════════════════════════════════════════════════════════════════
//  SESSION CONFIGURATION
// ═══════════════════════════════════════════════════════════════════════════

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);
    
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    
    try {
        session_start();
    } catch (Exception $e) {
        error_log('[CONFIG] Session start failed: ' . $e->getMessage());
    }
}

// ═══════════════════════════════════════════════════════════════════════════
//  INFINITYFREE OPTIMIZATIONS
// ═══════════════════════════════════════════════════════════════════════════

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}

@ini_set('memory_limit', '128M');
@set_time_limit(30);

if (!ob_get_level()) {
    ob_start();
}
