<?php
/**
 * login.php — User Login
 * REDESIGNED: White background with subtle gray accents (was solid blue)
 * Theme: Clean, modern, professional
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

$error   = '';
$success = '';

// Check for flash messages
$flash = getFlashMessage();
if ($flash) {
    if ($flash['type'] === 'success') {
        $success = $flash['text'];
    } else {
        $error = $flash['text'];
    }
}

// ═══════════════════════════════════════════════════════════════════════════
//  FORM SUBMISSION
// ═══════════════════════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = t('error_empty_fields') ?: 'Please fill in all fields';
    } else {
        // Fetch user
        $user = getUserByUsername($username);
        
        if ($user && verifyPassword($password, $user['password'])) {
            // Password correct - set session
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['user_role']  = $user['role'];
            $_SESSION['full_name']  = $user['full_name'];
            $_SESSION['level_id']   = $user['level_id'];
            $_SESSION['lang']       = $user['preferred_lang'] ?? 'fr';
            
            // Update last login
            updateLastLogin((int) $user['id']);
            
            // Check if password needs rehashing (MD5 → bcrypt migration)
            if (passwordNeedsRehash($user['password'])) {
                // Optionally rehash to bcrypt (for future migration)
                // $newHash = password_hash($password, PASSWORD_DEFAULT);
                // updateUserPassword((int) $user['id'], $newHash);
            }
            
            // Redirect based on role
            if (in_array($user['role'], ['admin', 'super_admin'], true)) {
                redirect(SITE_URL . '/admin/dashboard.php');
            } else {
                redirect(SITE_URL . '/dashboard.php');
            }
        } else {
            $error = t('error_invalid_credentials') ?: 'Invalid username or password';
        }
    }
}

$pageTitle = t('login') . ' — ' . t('app_name');
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>" dir="<?php echo $dir; ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

  <style>
    :root {
      --blue-900: #1e3a8a;
      --blue-700: #1d4ed8;
      --blue-600: #2563eb;
      --blue-500: #3b82f6;
      --blue-50:  #eff6ff;
      --white:    #ffffff;
      --gray-50:  #f8fafc;
      --gray-100: #f1f5f9;
      --gray-200: #e2e8f0;
      --gray-300: #cbd5e1;
      --gray-500: #64748b;
      --gray-700: #334155;
      --gray-900: #0f172a;
      --radius:   12px;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: <?php echo $isRtl ? "'Cairo'" : "'Plus Jakarta Sans'"; ?>, sans-serif;
      background: var(--gray-50);
      color: var(--gray-700);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
    }

    .login-container {
      width: 100%;
      max-width: 420px;
    }

    /* Logo/Brand */
    .brand {
      text-align: center;
      margin-bottom: 2rem;
    }
    .brand-icon {
      width: 64px;
      height: 64px;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-500));
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
      box-shadow: 0 8px 24px rgba(29, 78, 216, 0.3);
    }
    .brand h1 {
      font-size: 1.5rem;
      font-weight: 800;
      color: var(--gray-900);
      margin-bottom: 0.25rem;
    }
    .brand p {
      color: var(--gray-500);
      font-size: 0.9rem;
    }

    /* Card */
    .card {
      background: var(--white);
      border-radius: 20px;
      padding: 2.5rem 2rem;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    .card-header {
      text-align: center;
      margin-bottom: 2rem;
    }
    .card-header h2 {
      font-size: 1.75rem;
      font-weight: 800;
      color: var(--gray-900);
      margin-bottom: 0.5rem;
    }
    .card-header p {
      color: var(--gray-500);
      font-size: 0.9rem;
    }

    /* Alerts */
    .alert {
      padding: 0.875rem 1rem;
      border-radius: var(--radius);
      margin-bottom: 1.5rem;
      font-size: 0.875rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .alert-error {
      background: #fee2e2;
      color: #991b1b;
      border: 1px solid #fecaca;
    }
    .alert-success {
      background: #dcfce7;
      color: #166534;
      border: 1px solid #bbf7d0;
    }

    /* Form */
    .form-group {
      margin-bottom: 1.25rem;
    }
    .form-group label {
      display: block;
      font-size: 0.875rem;
      font-weight: 600;
      color: var(--gray-700);
      margin-bottom: 0.5rem;
    }
    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }
    .input-wrapper input {
      width: 100%;
      padding: 0.75rem 1rem;
      padding-<?php echo $isRtl ? 'right' : 'left'; ?>: 2.75rem;
      border: 1.5px solid var(--gray-200);
      border-radius: var(--radius);
      font-family: inherit;
      font-size: 0.9rem;
      color: var(--gray-900);
      background: var(--white);
      transition: all 0.2s;
    }
    .input-wrapper input:focus {
      outline: none;
      border-color: var(--blue-500);
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .input-icon {
      position: absolute;
      <?php echo $isRtl ? 'right' : 'left'; ?>: 0.875rem;
      color: var(--gray-400);
      pointer-events: none;
    }

    .form-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1.5rem;
      font-size: 0.85rem;
    }
    .checkbox-wrapper {
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }
    .checkbox-wrapper input[type="checkbox"] {
      width: auto;
      cursor: pointer;
    }
    .checkbox-wrapper label {
      color: var(--gray-600);
      cursor: pointer;
    }
    .forgot-link {
      color: var(--blue-700);
      text-decoration: none;
      font-weight: 500;
    }
    .forgot-link:hover {
      text-decoration: underline;
    }

    /* Button */
    .btn-submit {
      width: 100%;
      padding: 0.875rem;
      background: linear-gradient(135deg, var(--blue-700), var(--blue-600));
      color: #fff;
      border: none;
      border-radius: var(--radius);
      font-family: inherit;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      box-shadow: 0 4px 16px rgba(29, 78, 216, 0.3);
    }
    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 24px rgba(29, 78, 216, 0.4);
    }
    .btn-submit:active {
      transform: translateY(0);
    }

    /* Divider */
    .divider {
      display: flex;
      align-items: center;
      text-align: center;
      margin: 1.75rem 0;
    }
    .divider::before,
    .divider::after {
      content: '';
      flex: 1;
      border-bottom: 1px solid var(--gray-200);
    }
    .divider span {
      padding: 0 1rem;
      color: var(--gray-400);
      font-size: 0.8rem;
      font-weight: 500;
    }

    /* Links */
    .card-footer {
      text-align: center;
      font-size: 0.9rem;
      color: var(--gray-600);
    }
    .card-footer a {
      color: var(--blue-700);
      text-decoration: none;
      font-weight: 600;
    }
    .card-footer a:hover {
      text-decoration: underline;
    }

    .back-home {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      margin-top: 1.5rem;
      color: var(--gray-500);
      text-decoration: none;
      font-size: 0.875rem;
    }
    .back-home:hover {
      color: var(--blue-700);
    }

    /* Responsive */
    @media (max-width: 480px) {
      body {
        padding: 1rem;
      }
      .card {
        padding: 2rem 1.5rem;
      }
      .brand h1 {
        font-size: 1.3rem;
      }
      .card-header h2 {
        font-size: 1.5rem;
      }
    }
  </style>
</head>
<body>

<div class="login-container">
  <!-- Brand -->
  <div class="brand">
    <div class="brand-icon">
      <i data-lucide="graduation-cap" width="32" height="32" color="white"></i>
    </div>
    <h1><?php echo t('app_name'); ?></h1>
    <p><?php echo t('tagline') ?: 'Excellence Education Platform'; ?></p>
  </div>

  <!-- Card -->
  <div class="card">
    <div class="card-header">
      <h2><?php echo t('welcome_back'); ?></h2>
      <p><?php echo t('login_subtitle') ?: 'Sign in to continue your learning journey'; ?></p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error">
      <i data-lucide="alert-circle" width="16" height="16"></i>
      <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success">
      <i data-lucide="check-circle-2" width="16" height="16"></i>
      <?php echo htmlspecialchars($success); ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label for="username"><?php echo t('username'); ?></label>
        <div class="input-wrapper">
          <i data-lucide="user" width="18" height="18" class="input-icon"></i>
          <input 
            type="text" 
            id="username" 
            name="username" 
            required 
            autofocus
            placeholder="<?php echo t('enter_username') ?: 'Enter your username'; ?>"
            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
          >
        </div>
      </div>

      <div class="form-group">
        <label for="password"><?php echo t('password'); ?></label>
        <div class="input-wrapper">
          <i data-lucide="lock" width="18" height="18" class="input-icon"></i>
          <input 
            type="password" 
            id="password" 
            name="password" 
            required
            placeholder="<?php echo t('enter_password') ?: 'Enter your password'; ?>"
          >
        </div>
      </div>

      <div class="form-footer">
        <div class="checkbox-wrapper">
          <input type="checkbox" id="remember" name="remember">
          <label for="remember"><?php echo t('remember_me') ?: 'Remember me'; ?></label>
        </div>
        <a href="forgot-password.php" class="forgot-link">
          <?php echo t('forgot_password') ?: 'Forgot password?'; ?>
        </a>
      </div>

      <button type="submit" class="btn-submit">
        <?php echo t('login'); ?>
      </button>
    </form>

    <div class="divider">
      <span><?php echo t('or') ?: 'OR'; ?></span>
    </div>

    <div class="card-footer">
      <?php echo t('dont_have_account') ?: "Don't have an account?"; ?>
      <a href="register.php"><?php echo t('sign_up'); ?></a>
    </div>
  </div>

  <div style="text-align: center;">
    <a href="index.php" class="back-home">
      <i data-lucide="arrow-<?php echo $isRtl ? 'right' : 'left'; ?>" width="16" height="16"></i>
      <?php echo t('back_to_home') ?: 'Back to home'; ?>
    </a>
  </div>
</div>

<script>
  lucide.createIcons();
</script>

</body>
</html>
