<?php
/**
 * register.php — User Registration
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

// ═══════════════════════════════════════════════════════════════════════════
//  FORM SUBMISSION
// ═══════════════════════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName  = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = trim($_POST['password'] ?? '');
    $password2 = trim($_POST['password2'] ?? '');
    $levelId   = (int) ($_POST['level_id'] ?? 0);
    $lang      = trim($_POST['preferred_lang'] ?? 'fr');
    
    // Validation
    if (empty($fullName) || empty($username) || empty($email) || empty($password)) {
        $error = t('error_empty_fields') ?: 'Please fill in all required fields';
    } elseif (!isValidEmail($email)) {
        $error = t('error_invalid_email') ?: 'Invalid email address';
    } elseif (!isValidUsername($username)) {
        $error = t('error_invalid_username') ?: 'Username must be 3-20 characters (letters, numbers, underscore)';
    } elseif (!isValidPassword($password)) {
        $error = t('error_weak_password') ?: 'Password must be at least 6 characters';
    } elseif ($password !== $password2) {
        $error = t('error_password_mismatch') ?: 'Passwords do not match';
    } elseif ($levelId <= 0) {
        $error = t('error_select_level') ?: 'Please select your grade level';
    } elseif (userExists($username, $email)) {
        $error = t('error_user_exists') ?: 'Username or email already exists';
    } else {
        // Create user
        $userData = [
            'username'       => $username,
            'email'          => $email,
            'password'       => hashPassword($password),
            'full_name'      => $fullName,
            'role'           => 'student',
            'level_id'       => $levelId,
            'preferred_lang' => in_array($lang, AVAILABLE_LANGS, true) ? $lang : 'fr',
            'is_active'      => 1
        ];
        
        $userId = createUser($userData);
        
        if ($userId) {
            setFlashMessage('success', t('success_registration') ?: 'Registration successful! Please login.');
            redirect(SITE_URL . '/login.php');
        } else {
            $error = t('error_registration_failed') ?: 'Registration failed. Please try again.';
        }
    }
}

// Fetch levels for dropdown
$levels = db_all('SELECT * FROM levels WHERE is_active = 1 ORDER BY display_order ASC');

$pageTitle = t('register') . ' — ' . t('app_name');
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

    .register-container {
      width: 100%;
      max-width: 520px;
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
    .form-group label .required {
      color: #dc2626;
    }
    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }
    .input-wrapper input,
    .input-wrapper select {
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
    .input-wrapper input:focus,
    .input-wrapper select:focus {
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

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }

    .password-hint {
      font-size: 0.75rem;
      color: var(--gray-500);
      margin-top: 0.35rem;
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
      margin-top: 0.5rem;
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
    @media (max-width: 600px) {
      body {
        padding: 1rem;
      }
      .card {
        padding: 2rem 1.5rem;
      }
      .form-row {
        grid-template-columns: 1fr;
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

<div class="register-container">
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
      <h2><?php echo t('create_account'); ?></h2>
      <p><?php echo t('register_subtitle') ?: 'Join thousands of students learning excellence'; ?></p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error">
      <i data-lucide="alert-circle" width="16" height="16"></i>
      <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label for="full_name">
          <?php echo t('full_name'); ?> <span class="required">*</span>
        </label>
        <div class="input-wrapper">
          <i data-lucide="user" width="18" height="18" class="input-icon"></i>
          <input 
            type="text" 
            id="full_name" 
            name="full_name" 
            required 
            autofocus
            placeholder="<?php echo t('enter_full_name') ?: 'Enter your full name'; ?>"
            value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
          >
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="username">
            <?php echo t('username'); ?> <span class="required">*</span>
          </label>
          <div class="input-wrapper">
            <i data-lucide="at-sign" width="18" height="18" class="input-icon"></i>
            <input 
              type="text" 
              id="username" 
              name="username" 
              required
              pattern="[a-zA-Z0-9_]{3,20}"
              placeholder="<?php echo t('enter_username') ?: 'username'; ?>"
              value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
            >
          </div>
          <div class="password-hint">3-20 chars, letters/numbers/_</div>
        </div>

        <div class="form-group">
          <label for="email">
            <?php echo t('email'); ?> <span class="required">*</span>
          </label>
          <div class="input-wrapper">
            <i data-lucide="mail" width="18" height="18" class="input-icon"></i>
            <input 
              type="email" 
              id="email" 
              name="email" 
              required
              placeholder="you@example.com"
              value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
            >
          </div>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="password">
            <?php echo t('password'); ?> <span class="required">*</span>
          </label>
          <div class="input-wrapper">
            <i data-lucide="lock" width="18" height="18" class="input-icon"></i>
            <input 
              type="password" 
              id="password" 
              name="password" 
              required
              minlength="6"
              placeholder="<?php echo t('enter_password') ?: '••••••'; ?>"
            >
          </div>
          <div class="password-hint">Min 6 characters</div>
        </div>

        <div class="form-group">
          <label for="password2">
            <?php echo t('confirm_password'); ?> <span class="required">*</span>
          </label>
          <div class="input-wrapper">
            <i data-lucide="lock" width="18" height="18" class="input-icon"></i>
            <input 
              type="password" 
              id="password2" 
              name="password2" 
              required
              minlength="6"
              placeholder="<?php echo t('confirm_password') ?: '••••••'; ?>"
            >
          </div>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="level_id">
            <?php echo t('grade_level'); ?> <span class="required">*</span>
          </label>
          <div class="input-wrapper">
            <i data-lucide="graduation-cap" width="18" height="18" class="input-icon"></i>
            <select id="level_id" name="level_id" required>
              <option value=""><?php echo t('select_level') ?: 'Select grade'; ?></option>
              <?php foreach ($levels as $level):
                $nameKey = 'name_' . $currentLang;
                $selected = isset($_POST['level_id']) && $_POST['level_id'] == $level['id'] ? 'selected' : '';
              ?>
              <option value="<?php echo $level['id']; ?>" <?php echo $selected; ?>>
                <?php echo htmlspecialchars($level[$nameKey] ?? $level['name_ar']); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label for="preferred_lang">
            <?php echo t('language'); ?>
          </label>
          <div class="input-wrapper">
            <i data-lucide="globe" width="18" height="18" class="input-icon"></i>
            <select id="preferred_lang" name="preferred_lang">
              <option value="fr" <?php echo (($_POST['preferred_lang'] ?? 'fr') === 'fr') ? 'selected' : ''; ?>>Français</option>
              <option value="ar" <?php echo (($_POST['preferred_lang'] ?? '') === 'ar') ? 'selected' : ''; ?>>العربية</option>
              <option value="en" <?php echo (($_POST['preferred_lang'] ?? '') === 'en') ? 'selected' : ''; ?>>English</option>
            </select>
          </div>
        </div>
      </div>

      <button type="submit" class="btn-submit">
        <?php echo t('create_account'); ?>
      </button>
    </form>

    <div class="divider">
      <span><?php echo t('or') ?: 'OR'; ?></span>
    </div>

    <div class="card-footer">
      <?php echo t('already_have_account') ?: "Already have an account?"; ?>
      <a href="login.php"><?php echo t('login'); ?></a>
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

  // Password match validation
  const password2 = document.getElementById('password2');
  const password = document.getElementById('password');
  
  password2.addEventListener('input', function() {
    if (this.value && this.value !== password.value) {
      this.setCustomValidity('<?php echo t('error_password_mismatch') ?: 'Passwords do not match'; ?>');
    } else {
      this.setCustomValidity('');
    }
  });
</script>

</body>
</html>
