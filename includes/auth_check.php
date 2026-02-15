<?php
/**
 * includes/auth_check.php
 * Session guard for protected pages. Include at top of pages requiring login.
 * Works for both student and admin roles.
 */

declare(strict_types=1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_rootDir = dirname(__DIR__);

if (!defined('DB_HOST'))              require_once $_rootDir . '/config.php';
if (!function_exists('db_row'))       require_once $_rootDir . '/includes/db.php';
if (!function_exists('t'))            require_once $_rootDir . '/includes/functions.php';
if (!function_exists('getCsrfToken')) require_once $_rootDir . '/includes/csrf.php';

// ══════════════════════════════════════════════════════════════════════════
// 1. CHECK IF USER IS LOGGED IN
// ══════════════════════════════════════════════════════════════════════════

if (empty($_SESSION['user_id'])) {
    // Not logged in - redirect to login with return URL
    $returnUrl = $_SERVER['REQUEST_URI'] ?? '/dashboard.php';
    header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode($returnUrl), true, 302);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// 2. VERIFY USER EXISTS AND IS ACTIVE
// ══════════════════════════════════════════════════════════════════════════

try {
    $currentUser = db_row(
        "SELECT id, username, email, full_name, role, level_id,
                xp_points, current_level, preferred_lang, is_active,
                profile_picture, last_login, created_at
         FROM   users
         WHERE  id = ?
         LIMIT  1",
        [(int) $_SESSION['user_id']]
    );
} catch (Exception $e) {
    error_log('Auth check failed: ' . $e->getMessage());
    $currentUser = null;
}

// User not found or inactive
if (!$currentUser || !(bool) $currentUser['is_active']) {
    session_unset();
    session_destroy();
    
    header('Location: ' . SITE_URL . '/login.php?error=disabled', true, 302);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// 3. SYNC SESSION DATA
// ══════════════════════════════════════════════════════════════════════════

// Update session with fresh data
$_SESSION['username']   = $currentUser['username'];
$_SESSION['full_name']  = $currentUser['full_name'];
$_SESSION['user_role']  = $currentUser['role'];
$_SESSION['level_id']   = $currentUser['level_id'];
$_SESSION['xp_points']  = $currentUser['xp_points'];

// ══════════════════════════════════════════════════════════════════════════
// 4. LANGUAGE HANDLING
// ══════════════════════════════════════════════════════════════════════════

// Set language from user preference if not set
if (empty($_SESSION['lang'])) {
    $_SESSION['lang'] = $currentUser['preferred_lang'] ?? 'fr';
}

// Allow language switching via URL parameter
if (isset($_GET['lang']) && in_array($_GET['lang'], ['ar', 'fr', 'en'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
    
    // Update user's preferred language in database
    try {
        db_execute(
            "UPDATE users SET preferred_lang = ? WHERE id = ?",
            [$_GET['lang'], (int) $_SESSION['user_id']]
        );
    } catch (Exception $e) {
        error_log('Language update failed: ' . $e->getMessage());
    }
}

// ══════════════════════════════════════════════════════════════════════════
// 5. SET GLOBAL CONVENIENCE VARIABLES
// ══════════════════════════════════════════════════════════════════════════

$currentLang = getCurrentLang();
$dir         = getDirection();
$isRtl       = ($dir === 'rtl');
$csrfToken   = getCsrfToken();

// User role helpers
$isAdmin   = ($currentUser['role'] === 'admin' || $currentUser['role'] === 'super_admin');
$isStudent = ($currentUser['role'] === 'student');

// ══════════════════════════════════════════════════════════════════════════
// 6. OPTIONAL: UPDATE LAST SEEN (throttled to once per 5 minutes)
// ══════════════════════════════════════════════════════════════════════════

$lastSeenKey = 'last_seen_update_' . $_SESSION['user_id'];
if (!isset($_SESSION[$lastSeenKey]) || (time() - $_SESSION[$lastSeenKey]) > 300) {
    try {
        db_execute(
            "UPDATE users SET last_login = NOW() WHERE id = ?",
            [(int) $_SESSION['user_id']]
        );
        $_SESSION[$lastSeenKey] = time();
    } catch (Exception $e) {
        error_log('Last seen update failed: ' . $e->getMessage());
    }
}

// ══════════════════════════════════════════════════════════════════════════
// AUTHENTICATION COMPLETE - USER IS VERIFIED AND ACTIVE
// ══════════════════════════════════════════════════════════════════════════
