<?php
/**
 * includes/auth_check.php — Authentication & Session Handler
 * Include this at the top of protected pages
 */

declare(strict_types=1);

// Prevent direct access
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config.php';
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// ═══════════════════════════════════════════════════════════════════════════
//  START SESSION (if not already started)
// ═══════════════════════════════════════════════════════════════════════════

if (session_status() === PHP_SESSION_NONE) {
    // Secure session configuration
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', ENVIRONMENT === 'production' ? '1' : '0');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    
    session_start();
}

// ═══════════════════════════════════════════════════════════════════════════
//  CHECK IF USER IS LOGGED IN
// ═══════════════════════════════════════════════════════════════════════════

if (!isLoggedIn()) {
    // Store attempted URL for redirect after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/dashboard.php';
    
    // Redirect to login
    redirect(SITE_URL . '/login.php');
}

// ═══════════════════════════════════════════════════════════════════════════
//  FETCH CURRENT USER DATA (refresh from database)
// ═══════════════════════════════════════════════════════════════════════════

$currentUser = getCurrentUser();

if (!$currentUser) {
    // User not found in database - destroy session and redirect
    session_destroy();
    redirect(SITE_URL . '/login.php');
}

// Check if user is active
if (empty($currentUser['is_active'])) {
    session_destroy();
    setFlashMessage('error', 'Your account has been deactivated. Please contact support.');
    redirect(SITE_URL . '/login.php');
}

// ═══════════════════════════════════════════════════════════════════════════
//  SESSION HIJACKING PREVENTION
// ═══════════════════════════════════════════════════════════════════════════

// Store user agent on first visit
if (empty($_SESSION['user_agent'])) {
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
}

// Verify user agent hasn't changed (basic hijacking detection)
if (($_SESSION['user_agent'] ?? '') !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
    session_destroy();
    redirect(SITE_URL . '/login.php');
}

// ═══════════════════════════════════════════════════════════════════════════
//  SESSION REGENERATION (every 30 minutes)
// ═══════════════════════════════════════════════════════════════════════════

if (empty($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// ═══════════════════════════════════════════════════════════════════════════
//  UPDATE SESSION WITH FRESH DATA
// ═══════════════════════════════════════════════════════════════════════════

// Keep session data synced with database
$_SESSION['user_id']    = $currentUser['id'];
$_SESSION['username']   = $currentUser['username'];
$_SESSION['user_role']  = $currentUser['role'];
$_SESSION['full_name']  = $currentUser['full_name'];
$_SESSION['level_id']   = $currentUser['level_id'];
$_SESSION['lang']       = $currentUser['preferred_lang'] ?? 'fr';

// Make current user available to all pages
$GLOBALS['currentUser'] = $currentUser;

// ═══════════════════════════════════════════════════════════════════════════
//  ACTIVITY LOGGING (optional)
// ═══════════════════════════════════════════════════════════════════════════

// Log last activity time (for "online users" feature, idle timeout, etc.)
if (empty($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
} else {
    $idleTime = time() - $_SESSION['last_activity'];
    
    // Auto-logout after 2 hours of inactivity (optional)
    if ($idleTime > 7200) {
        session_destroy();
        setFlashMessage('info', 'Session expired due to inactivity. Please login again.');
        redirect(SITE_URL . '/login.php');
    }
    
    $_SESSION['last_activity'] = time();
}
