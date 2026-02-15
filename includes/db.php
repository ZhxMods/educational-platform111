<?php
/**
 * includes/db.php — Database Helper Functions
 * FIXED: All references use preferred_lang (not preferred_language)
 * ADDED: db() wrapper for consistency, anti-cheat support
 */

declare(strict_types=1);

if (!defined('DB_HOST')) {
    die('Direct access not permitted');
}

/**
 * Get PDO database connection (Singleton)
 * @return PDO Database connection
 * @throws PDOException on connection failure
 */
function db(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('[DB] Connection failed: ' . $e->getMessage());
            http_response_code(500);
            die('Database connection failed. Please contact administrator.');
        }
    }
    
    return $pdo;
}

/**
 * Legacy alias for backwards compatibility
 */
function getDbConnection(): PDO {
    return db();
}

/**
 * Execute query and return all rows
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @return array Array of rows
 */
function db_all(string $sql, array $params = []): array {
    try {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('[DB] Query failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
        return [];
    }
}

/**
 * Alias for db_all (backwards compatibility)
 */
function db_query(string $sql, array $params = []): array {
    return db_all($sql, $params);
}

/**
 * Execute query and return single row
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @return array|null Single row or null
 */
function db_row(string $sql, array $params = []): ?array {
    try {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    } catch (PDOException $e) {
        error_log('[DB] Query failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
        return null;
    }
}

/**
 * Execute query and return single value
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @return mixed Single value or null
 */
function db_value(string $sql, array $params = []) {
    try {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('[DB] Query failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
        return null;
    }
}

/**
 * Execute INSERT/UPDATE/DELETE query
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @return bool Success status
 */
function db_run(string $sql, array $params = []): bool {
    try {
        $stmt = db()->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log('[DB] Execute failed: ' . $e->getMessage() . ' | SQL: ' . $sql);
        return false;
    }
}

/**
 * Alias for db_run (backwards compatibility)
 */
function db_execute(string $sql, array $params = []): bool {
    return db_run($sql, $params);
}

/**
 * Get last insert ID
 * @return int Last insert ID
 */
function db_last_id(): int {
    return (int) db()->lastInsertId();
}

/**
 * Alias for db_last_id (backwards compatibility)
 */
function db_insert_id(): int {
    return db_last_id();
}

/**
 * Begin database transaction
 * @return bool Success status
 */
function db_begin(): bool {
    try {
        return db()->beginTransaction();
    } catch (PDOException $e) {
        error_log('[DB] Transaction begin failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Commit database transaction
 * @return bool Success status
 */
function db_commit(): bool {
    try {
        return db()->commit();
    } catch (PDOException $e) {
        error_log('[DB] Transaction commit failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Rollback database transaction
 * @return bool Success status
 */
function db_rollback(): bool {
    try {
        return db()->rollBack();
    } catch (PDOException $e) {
        error_log('[DB] Transaction rollback failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Sanitize input for safe display (not for SQL - use prepared statements!)
 * @param string $input Input string
 * @return string Sanitized string
 */
function db_escape(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// ════════════════════════════════════════════════════════════════════════════
//  USER FUNCTIONS (FIXED: Uses preferred_lang)
// ════════════════════════════════════════════════════════════════════════════

/**
 * Check if user exists by username or email
 * @param string $username Username
 * @param string $email Email
 * @return bool True if exists
 */
function userExists(string $username, string $email): bool {
    $sql = "SELECT COUNT(*) FROM users WHERE username = ? OR email = ?";
    return (int) db_value($sql, [$username, $email]) > 0;
}

/**
 * Get user by username
 * FIXED: Uses preferred_lang column
 * @param string $username Username
 * @return array|null User data or null
 */
function getUserByUsername(string $username): ?array {
    $sql = "SELECT id, username, email, password, full_name, role, 
                   level_id, xp_points, current_level, profile_picture,
                   preferred_lang, is_active, last_login
            FROM users 
            WHERE username = ? AND is_active = 1 
            LIMIT 1";
    return db_row($sql, [$username]);
}

/**
 * Get user by email
 * FIXED: Uses preferred_lang column
 * @param string $email Email address
 * @return array|null User data or null
 */
function getUserByEmail(string $email): ?array {
    $sql = "SELECT id, username, email, password, full_name, role,
                   level_id, xp_points, current_level, profile_picture,
                   preferred_lang, is_active, last_login
            FROM users 
            WHERE email = ? AND is_active = 1 
            LIMIT 1";
    return db_row($sql, [$email]);
}

/**
 * Get user by ID
 * FIXED: Uses preferred_lang column
 * @param int $userId User ID
 * @return array|null User data or null
 */
function getUserById(int $userId): ?array {
    $sql = "SELECT id, username, email, full_name, role,
                   level_id, xp_points, current_level, profile_picture,
                   preferred_lang, is_active, last_login, created_at
            FROM users 
            WHERE id = ? 
            LIMIT 1";
    return db_row($sql, [$userId]);
}

/**
 * Create new user
 * FIXED: Uses preferred_lang column
 * @param array $data User data
 * @return int|bool User ID on success, false on failure
 */
function createUser(array $data) {
    $sql = "INSERT INTO users (
                username, email, password, full_name, role,
                level_id, preferred_lang, is_active, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $params = [
        $data['username'],
        $data['email'],
        $data['password'],
        $data['full_name'],
        $data['role'] ?? 'student',
        $data['level_id'] ?? 1,
        $data['preferred_lang'] ?? 'fr',  // FIXED: Default to French
        $data['is_active'] ?? 1
    ];
    
    if (db_run($sql, $params)) {
        return db_last_id();
    }
    return false;
}

/**
 * Update user's last login time
 * @param int $userId User ID
 * @return bool Success status
 */
function updateLastLogin(int $userId): bool {
    $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
    return db_run($sql, [$userId]);
}

/**
 * Update user profile
 * FIXED: Uses preferred_lang column
 * @param int $userId User ID
 * @param array $data Updated data
 * @return bool Success status
 */
function updateUserProfile(int $userId, array $data): bool {
    $allowedFields = ['full_name', 'email', 'preferred_lang', 'profile_picture'];
    $updates = [];
    $params = [];
    
    foreach ($data as $field => $value) {
        if (in_array($field, $allowedFields)) {
            $updates[] = "$field = ?";
            $params[] = $value;
        }
    }
    
    if (empty($updates)) {
        return false;
    }
    
    $params[] = $userId;
    $sql = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
    
    return db_run($sql, $params);
}

/**
 * Update user password
 * @param int $userId User ID
 * @param string $newPassword New hashed password
 * @return bool Success status
 */
function updateUserPassword(int $userId, string $newPassword): bool {
    $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
    return db_run($sql, [$newPassword, $userId]);
}

/**
 * Add XP points to user and recalculate level
 * FIXED: Proper level calculation
 * @param int $userId User ID
 * @param int $xpPoints XP points to add
 * @return bool Success status
 */
function addUserXP(int $userId, int $xpPoints): bool {
    // Get current XP
    $user = db_row('SELECT xp_points FROM users WHERE id = ?', [$userId]);
    if (!$user) return false;
    
    $newXP = (int) $user['xp_points'] + $xpPoints;
    $newLevel = (int) floor($newXP / XP_PER_LEVEL) + 1;
    
    $sql = "UPDATE users SET xp_points = ?, current_level = ?, updated_at = NOW() WHERE id = ?";
    return db_run($sql, [$newXP, $newLevel, $userId]);
}

// ════════════════════════════════════════════════════════════════════════════
//  LESSON FUNCTIONS
// ════════════════════════════════════════════════════════════════════════════

/**
 * Get all levels
 * @return array Array of levels
 */
function getLevels(): array {
    $sql = "SELECT * FROM levels WHERE is_active = 1 ORDER BY display_order ASC";
    return db_all($sql);
}

/**
 * Get subjects by level
 * @param int $levelId Level ID
 * @return array Array of subjects
 */
function getSubjectsByLevel(int $levelId): array {
    $sql = "SELECT * FROM subjects WHERE level_id = ? AND is_active = 1 ORDER BY display_order ASC";
    return db_all($sql, [$levelId]);
}

/**
 * Get lessons by subject
 * @param int $subjectId Subject ID
 * @return array Array of lessons
 */
function getLessonsBySubject(int $subjectId): array {
    $sql = "SELECT * FROM lessons WHERE subject_id = ? AND is_published = 1 ORDER BY display_order ASC";
    return db_all($sql, [$subjectId]);
}

/**
 * Get lesson progress for user
 * FIXED: Includes anti_cheat_verified field
 * @param int $userId User ID
 * @param int $lessonId Lesson ID
 * @return array|null Progress data or null
 */
function getLessonProgress(int $userId, int $lessonId): ?array {
    $sql = "SELECT * FROM lesson_progress WHERE user_id = ? AND lesson_id = ? LIMIT 1";
    return db_row($sql, [$userId, $lessonId]);
}

/**
 * Update lesson progress
 * FIXED: Supports anti_cheat_verified field
 * @param int $userId User ID
 * @param int $lessonId Lesson ID
 * @param array $data Progress data
 * @return bool Success status
 */
function updateLessonProgress(int $userId, int $lessonId, array $data): bool {
    $existing = getLessonProgress($userId, $lessonId);
    
    if ($existing) {
        $sql = "UPDATE lesson_progress 
                SET status = ?, completion_percentage = ?, watch_duration = ?, 
                    xp_earned = ?, anti_cheat_verified = ?, completed_at = ?, updated_at = NOW()
                WHERE user_id = ? AND lesson_id = ?";
        $params = [
            $data['status'] ?? $existing['status'],
            $data['completion_percentage'] ?? $existing['completion_percentage'],
            $data['watch_duration'] ?? $existing['watch_duration'],
            $data['xp_earned'] ?? $existing['xp_earned'],
            $data['anti_cheat_verified'] ?? $existing['anti_cheat_verified'],
            $data['completed_at'] ?? $existing['completed_at'],
            $userId,
            $lessonId
        ];
    } else {
        $sql = "INSERT INTO lesson_progress 
                (user_id, lesson_id, status, completion_percentage, watch_duration, 
                 xp_earned, anti_cheat_verified, completed_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [
            $userId,
            $lessonId,
            $data['status'] ?? 'not_started',
            $data['completion_percentage'] ?? 0,
            $data['watch_duration'] ?? 0,
            $data['xp_earned'] ?? 0,
            $data['anti_cheat_verified'] ?? 0,
            $data['completed_at'] ?? null
        ];
    }
    
    return db_run($sql, $params);
}

/**
 * Get user's completed lessons count
 * @param int $userId User ID
 * @return int Count of completed lessons
 */
function getCompletedLessonsCount(int $userId): int {
    $sql = "SELECT COUNT(*) FROM lesson_progress WHERE user_id = ? AND status = 'completed'";
    return (int) db_value($sql, [$userId]);
}

// ════════════════════════════════════════════════════════════════════════════
//  QUIZ FUNCTIONS
// ════════════════════════════════════════════════════════════════════════════

/**
 * Get user's quiz attempts
 * @param int $userId User ID
 * @param int|null $quizId Optional quiz ID to filter
 * @return array Array of attempts
 */
function getUserQuizAttempts(int $userId, ?int $quizId = null): array {
    if ($quizId) {
        $sql = "SELECT * FROM quiz_attempts WHERE user_id = ? AND quiz_id = ? ORDER BY created_at DESC";
        return db_all($sql, [$userId, $quizId]);
    }
    $sql = "SELECT * FROM quiz_attempts WHERE user_id = ? ORDER BY created_at DESC";
    return db_all($sql, [$userId]);
}

/**
 * Save quiz attempt
 * @param array $data Quiz attempt data
 * @return int|bool Attempt ID on success, false on failure
 */
function saveQuizAttempt(array $data) {
    $sql = "INSERT INTO quiz_attempts 
            (quiz_id, user_id, score, total_questions, correct_answers, 
             time_taken_seconds, xp_earned, passed, answers, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $params = [
        $data['quiz_id'],
        $data['user_id'],
        $data['score'],
        $data['total_questions'],
        $data['correct_answers'],
        $data['time_taken_seconds'] ?? null,
        $data['xp_earned'] ?? 0,
        $data['passed'] ? 1 : 0,
        $data['answers'] ?? null
    ];
    
    if (db_run($sql, $params)) {
        return db_last_id();
    }
    return false;
}

/**
 * Get user's average quiz score
 * FIXED: Proper calculation
 * @param int $userId User ID
 * @return float Average score (0-100)
 */
function getUserAverageQuizScore(int $userId): float {
    $sql = "SELECT AVG(score) as avg_score FROM quiz_attempts WHERE user_id = ? AND passed = 1";
    $result = db_value($sql, [$userId]);
    return $result ? (float) $result : 0.0;
}

// ════════════════════════════════════════════════════════════════════════════
//  ACHIEVEMENT FUNCTIONS
// ════════════════════════════════════════════════════════════════════════════

/**
 * Get user's achievements
 * @param int $userId User ID
 * @return array Array of achievements
 */
function getUserAchievements(int $userId): array {
    $sql = "SELECT a.*, ua.earned_at 
            FROM achievements a
            INNER JOIN user_achievements ua ON a.id = ua.achievement_id
            WHERE ua.user_id = ?
            ORDER BY ua.earned_at DESC";
    return db_all($sql, [$userId]);
}

/**
 * Award achievement to user (idempotent)
 * @param int $userId User ID
 * @param int $achievementId Achievement ID
 * @return bool Success status
 */
function awardAchievement(int $userId, int $achievementId): bool {
    $sql = "INSERT IGNORE INTO user_achievements (user_id, achievement_id, earned_at)
            VALUES (?, ?, NOW())";
    return db_run($sql, [$userId, $achievementId]);
}

// ════════════════════════════════════════════════════════════════════════════
//  NOTIFICATION FUNCTIONS
// ════════════════════════════════════════════════════════════════════════════

/**
 * Get unread notifications for user
 * @param int $userId User ID
 * @return array Array of notifications
 */
function getUnreadNotifications(int $userId): array {
    $sql = "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC";
    return db_all($sql, [$userId]);
}

/**
 * Mark notification as read
 * @param int $notificationId Notification ID
 * @return bool Success status
 */
function markNotificationRead(int $notificationId): bool {
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = ?";
    return db_run($sql, [$notificationId]);
}

// ════════════════════════════════════════════════════════════════════════════
//  SETTINGS FUNCTIONS
// ════════════════════════════════════════════════════════════════════════════

/**
 * Get system setting
 * @param string $key Setting key
 * @return mixed Setting value or null
 */
function getSetting(string $key) {
    $sql = "SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1";
    return db_value($sql, [$key]);
}

/**
 * Update system setting
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @return bool Success status
 */
function updateSetting(string $key, $value): bool {
    $sql = "INSERT INTO settings (setting_key, setting_value, updated_at) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()";
    return db_run($sql, [$key, $value, $value]);
}
