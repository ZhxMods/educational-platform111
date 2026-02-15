<?php
/**
 * includes/functions.php — Helper Functions
 * FIXED: XP progress calculation, level calculation, French default
 */

declare(strict_types=1);

if (!defined('DEFAULT_LANG')) {
    require_once __DIR__ . '/../config.php';
}

// ══════════════════════════════════════════════════════════════════════════
//  LANGUAGE FUNCTIONS
// ══════════════════════════════════════════════════════════════════════════

/**
 * Get current language from session or default
 * FIXED: Default is now French (fr)
 */
function getCurrentLang(): string {
    $lang = $_SESSION['lang'] ?? DEFAULT_LANG;
    return in_array($lang, AVAILABLE_LANGS, true) ? $lang : 'fr';  // FIXED: Fallback to fr
}

/**
 * Set language in session
 */
function setLanguage(string $lang): bool {
    if (in_array($lang, AVAILABLE_LANGS, true)) {
        $_SESSION['lang'] = $lang;
        return true;
    }
    return false;
}

/**
 * Get text direction for current language
 */
function getDirection(): string {
    return getCurrentLang() === 'ar' ? 'rtl' : 'ltr';
}

/**
 * Load language translations from JSON
 */
function loadLanguage(?string $lang = null): array {
    $lang = $lang ?? getCurrentLang();
    $file = LANG_DIR . $lang . '.json';
    
    if (!file_exists($file)) {
        $file = LANG_DIR . 'fr.json';  // FIXED: Fallback to French
    }
    
    $json = @file_get_contents($file);
    if ($json === false) return [];
    
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

/**
 * Translate a key (with fallback)
 */
function t(string $key): string {
    static $translations = null;
    
    if ($translations === null) {
        $translations = loadLanguage();
    }
    
    return $translations[$key] ?? $key;
}

// ══════════════════════════════════════════════════════════════════════════
//  AUTHENTICATION FUNCTIONS
// ══════════════════════════════════════════════════════════════════════════

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Require login (redirect if not logged in)
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect(SITE_URL . '/login.php');
    }
}

/**
 * Require specific role(s)
 */
function requireRole(array $roles): void {
    requireLogin();
    
    if (!in_array($_SESSION['user_role'] ?? '', $roles, true)) {
        $target = in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin'], true)
            ? SITE_URL . '/admin/dashboard.php'
            : SITE_URL . '/dashboard.php';
        redirect($target);
    }
}

/**
 * Get current user data from database
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    
    if (!function_exists('db_row')) {
        require_once __DIR__ . '/db.php';
    }
    
    return db_row('SELECT * FROM users WHERE id = ?', [(int) $_SESSION['user_id']]);
}

// ══════════════════════════════════════════════════════════════════════════
//  PASSWORD FUNCTIONS (MD5 with bcrypt migration support)
// ══════════════════════════════════════════════════════════════════════════

/**
 * Hash password (MD5 for compatibility, bcrypt for new passwords)
 * 
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword(string $password): string {
    // Use MD5 for compatibility with existing database
    return md5($password);
}

/**
 * Verify password (supports both MD5 and bcrypt)
 * 
 * @param string $password Plain text password
 * @param string $hash Stored hash
 * @return bool True if password matches
 */
function verifyPassword(string $password, string $hash): bool {
    // Check if it's a bcrypt hash (starts with $2y$, $2a$, or $2b$)
    if (preg_match('/^\$2[ayb]\$/', $hash)) {
        return password_verify($password, $hash);
    }
    
    // Otherwise use MD5
    return md5($password) === $hash;
}

/**
 * Check if password needs rehashing (MD5 → bcrypt migration)
 * 
 * @param string $hash Stored hash
 * @return bool True if needs rehashing
 */
function passwordNeedsRehash(string $hash): bool {
    // MD5 hashes are 32 characters
    return strlen($hash) === 32;
}

// ══════════════════════════════════════════════════════════════════════════
//  XP / GAMIFICATION FUNCTIONS (FIXED)
// ══════════════════════════════════════════════════════════════════════════

/**
 * Calculate level from XP
 * FIXED: Proper floor division
 * 
 * @param int $xp Total XP points
 * @return int Current level (starts at 1)
 */
function calculateLevel(int $xp): int {
    return (int) floor($xp / XP_PER_LEVEL) + 1;
}

/**
 * Get XP progress towards next level (percentage)
 * FIXED: Now correctly shows 0-100% within each level
 * 
 * @param int $xp Total XP points
 * @return float Progress percentage (0-100)
 */
function getXPProgress(int $xp): float {
    $level = calculateLevel($xp);
    $xpInCurrentLevel = $xp - (($level - 1) * XP_PER_LEVEL);
    
    return round(($xpInCurrentLevel / XP_PER_LEVEL) * 100, 2);
}

/**
 * Get total XP required for next level
 * 
 * @param int $xp Current XP points
 * @return int Total XP needed for next level
 */
function getXPForNextLevel(int $xp): int {
    $level = calculateLevel($xp);
    return $level * XP_PER_LEVEL;
}

/**
 * Get XP remaining to next level
 * 
 * @param int $xp Current XP points
 * @return int XP points remaining
 */
function getXPToNextLevel(int $xp): int {
    return getXPForNextLevel($xp) - $xp;
}

/**
 * Add XP to user (with level-up detection)
 * 
 * @param int $userId User ID
 * @param int $amount XP amount to add
 * @return array Result with level-up info
 */
function addXP(int $userId, int $amount): array {
    if (!function_exists('db_row')) {
        require_once __DIR__ . '/db.php';
    }
    
    $user = db_row('SELECT xp_points FROM users WHERE id = ?', [$userId]);
    if (!$user) {
        return ['success' => false, 'leveled_up' => false];
    }
    
    $oldXP = (int) $user['xp_points'];
    $newXP = $oldXP + $amount;
    
    $oldLevel = calculateLevel($oldXP);
    $newLevel = calculateLevel($newXP);
    $leveledUp = $newLevel > $oldLevel;
    
    db_run(
        'UPDATE users SET xp_points = ?, current_level = ? WHERE id = ?',
        [$newXP, $newLevel, $userId]
    );
    
    return [
        'success' => true,
        'old_xp' => $oldXP,
        'new_xp' => $newXP,
        'xp_earned' => $amount,
        'old_level' => $oldLevel,
        'new_level' => $newLevel,
        'leveled_up' => $leveledUp,
        'xp_progress' => getXPProgress($newXP),
        'xp_to_next' => getXPToNextLevel($newXP),
    ];
}

// ══════════════════════════════════════════════════════════════════════════
//  SANITIZATION FUNCTIONS
// ══════════════════════════════════════════════════════════════════════════

/**
 * Sanitize string for display (XSS prevention)
 */
function sanitize(string $str): string {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Sanitize for HTML attributes
 */
function sanitizeAttr(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// ══════════════════════════════════════════════════════════════════════════
//  FLASH MESSAGE FUNCTIONS
// ══════════════════════════════════════════════════════════════════════════

/**
 * Set flash message for next request
 */
function setFlashMessage(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'text' => $message];
}

/**
 * Get and clear flash message
 */
function getFlashMessage(): ?array {
    if (!isset($_SESSION['flash'])) return null;
    
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

// ══════════════════════════════════════════════════════════════════════════
//  REDIRECT FUNCTION
// ══════════════════════════════════════════════════════════════════════════

/**
 * Redirect to URL and exit
 */
function redirect(string $url): never {
    header('Location: ' . $url, true, 302);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
//  YOUTUBE HELPER FUNCTIONS
// ══════════════════════════════════════════════════════════════════════════

/**
 * Extract YouTube video ID from URL
 * 
 * @param string $url YouTube URL
 * @return string|null Video ID or null
 */
function getYouTubeVideoId(string $url): ?string {
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)'
             . '|youtu\.be\/)([^"&?\/ ]{11})/i';
    
    if (preg_match($pattern, $url, $matches)) {
        return $matches[1];
    }
    
    return null;
}

/**
 * Get YouTube embed URL from any YouTube URL
 * 
 * @param string $url YouTube URL
 * @return string Embed URL or original URL if invalid
 */
function getYouTubeEmbedUrl(string $url): string {
    $videoId = getYouTubeVideoId($url);
    
    if ($videoId) {
        return 'https://www.youtube.com/embed/' . $videoId . '?rel=0';
    }
    
    return $url;
}

/**
 * Check if URL is a YouTube URL
 */
function isYouTubeUrl(string $url): bool {
    return (bool) preg_match('/(?:youtube\.com|youtu\.be)/i', $url);
}

/**
 * Check if URL is a MediaFire URL
 */
function isMediaFireUrl(string $url): bool {
    return (bool) preg_match('/mediafire\.com/i', $url);
}

// ══════════════════════════════════════════════════════════════════════════
//  CSRF TOKEN FUNCTIONS
// ══════════════════════════════════════════════════════════════════════════

/**
 * Generate CSRF token (stored in session)
 */
function getCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token (constant-time comparison)
 */
function verifyCsrfToken(string $submitted): bool {
    $stored = $_SESSION['csrf_token'] ?? '';
    
    if (empty($stored) || empty($submitted)) {
        return false;
    }
    
    return hash_equals($stored, $submitted);
}

/**
 * Regenerate CSRF token (call after sensitive operations)
 */
function regenerateCsrfToken(): string {
    unset($_SESSION['csrf_token']);
    return getCsrfToken();
}

// ══════════════════════════════════════════════════════════════════════════
//  DATE/TIME HELPER FUNCTIONS
// ══════════════════════════════════════════════════════════════════════════

/**
 * Format date for display (respects current language)
 */
function formatDate(string $date, string $format = 'short'): string {
    $timestamp = strtotime($date);
    if ($timestamp === false) return $date;
    
    $lang = getCurrentLang();
    
    if ($format === 'short') {
        return date('j M Y', $timestamp);
    } elseif ($format === 'long') {
        return date('l, j F Y', $timestamp);
    } else {
        return date('Y-m-d H:i:s', $timestamp);
    }
}

/**
 * Get relative time (e.g., "2 hours ago")
 */
function timeAgo(string $date): string {
    $timestamp = strtotime($date);
    if ($timestamp === false) return $date;
    
    $diff = time() - $timestamp;
    
    if ($diff < 60) return t('just_now') ?: 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' ' . (t('minutes_ago') ?: 'minutes ago');
    if ($diff < 86400) return floor($diff / 3600) . ' ' . (t('hours_ago') ?: 'hours ago');
    if ($diff < 604800) return floor($diff / 86400) . ' ' . (t('days_ago') ?: 'days ago');
    
    return formatDate($date);
}

// ══════════════════════════════════════════════════════════════════════════
//  VALIDATION FUNCTIONS
// ══════════════════════════════════════════════════════════════════════════

/**
 * Validate email address
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate username (alphanumeric + underscore, 3-20 chars)
 */
function isValidUsername(string $username): bool {
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username) === 1;
}

/**
 * Validate password strength (min 6 chars)
 */
function isValidPassword(string $password): bool {
    return strlen($password) >= 6;
}

/**
 * Validate URL
 */
function isValidUrl(string $url): bool {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

// ══════════════════════════════════════════════════════════════════════════
//  ARRAY HELPER FUNCTIONS
// ══════════════════════════════════════════════════════════════════════════

/**
 * Get array value by key with default
 */
function array_get(array $array, string $key, $default = null) {
    return $array[$key] ?? $default;
}

/**
 * Check if array is associative
 */
function is_assoc_array(array $array): bool {
    if (empty($array)) return false;
    return array_keys($array) !== range(0, count($array) - 1);
}

// ══════════════════════════════════════════════════════════════════════════
//  DEBUG HELPER FUNCTIONS (Remove in production)
// ══════════════════════════════════════════════════════════════════════════

/**
 * Debug dump and die
 */
function dd($var): never {
    echo '<pre style="background:#1e293b;color:#e2e8f0;padding:1rem;border-radius:8px;font-size:0.875rem;overflow:auto;">';
    var_dump($var);
    echo '</pre>';
    exit;
}

/**
 * Debug dump (no die)
 */
function dump($var): void {
    echo '<pre style="background:#1e293b;color:#e2e8f0;padding:1rem;border-radius:8px;font-size:0.875rem;overflow:auto;">';
    var_dump($var);
    echo '</pre>';
}
