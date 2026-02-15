<?php
/**
 * includes/csrf.php — CSRF Token Helper
 * Provides CSRF protection functions
 * Can be included standalone or via auth_check.php
 */

declare(strict_types=1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate and return CSRF token
 * Stores token in session if not present
 * 
 * @return string CSRF token
 */
function getCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify submitted CSRF token against session token
 * Uses constant-time comparison to prevent timing attacks
 * 
 * @param string $submitted Token from form/AJAX request
 * @return bool True if valid, false otherwise
 */
function verifyCsrfToken(string $submitted): bool {
    $stored = $_SESSION['csrf_token'] ?? '';
    
    if (empty($stored) || empty($submitted)) {
        return false;
    }
    
    // Constant-time comparison (prevents timing attacks)
    return hash_equals($stored, $submitted);
}

/**
 * Regenerate CSRF token
 * Call this after sensitive operations (password change, etc.)
 * 
 * @return string New CSRF token
 */
function regenerateCsrfToken(): string {
    unset($_SESSION['csrf_token']);
    return getCsrfToken();
}

/**
 * Generate hidden input field with CSRF token
 * Usage: echo csrfField();
 * 
 * @return string HTML input field
 */
function csrfField(): string {
    $token = getCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Generate meta tag for AJAX requests
 * Usage: echo csrfMeta();
 * 
 * @return string HTML meta tag
 */
function csrfMeta(): string {
    $token = getCsrfToken();
    return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verify CSRF token from POST/AJAX request
 * Checks both $_POST['csrf_token'] and HTTP_X_CSRF_TOKEN header
 * Dies with 403 error if invalid
 * 
 * @return void
 */
function requireValidCsrfToken(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    
    if (!verifyCsrfToken($token)) {
        http_response_code(403);
        
        // If AJAX request, send JSON response
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Invalid CSRF token. Please refresh the page and try again.'
            ]);
        } else {
            echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>403 Forbidden</title>
    <style>
        body { font-family: system-ui, sans-serif; text-align: center; padding: 4rem 2rem; background: #f8fafc; color: #334155; }
        .error-box { max-width: 500px; margin: 0 auto; background: white; padding: 3rem 2rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        h1 { font-size: 4rem; margin: 0 0 1rem; color: #dc2626; }
        h2 { margin: 0 0 1rem; color: #1e293b; }
        p { color: #64748b; line-height: 1.6; margin-bottom: 2rem; }
        a { display: inline-block; padding: 0.75rem 1.5rem; background: #1d4ed8; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; }
        a:hover { background: #1e40af; }
    </style>
</head>
<body>
    <div class="error-box">
        <h1>403</h1>
        <h2>Invalid CSRF Token</h2>
        <p>Your request could not be processed due to an invalid or missing CSRF token. This usually happens when your session expires.</p>
        <a href="' . (SITE_URL ?? '/') . '">Go to Homepage</a>
    </div>
</body>
</html>';
        }
        
        exit;
    }
}
