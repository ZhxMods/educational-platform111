<?php
/**
 * admin/manage_users.php
 * User management interface for administrators
 */

require_once __DIR__ . '/../includes/admin_auth.php';

$pageTitle = t('manage_users', 'Manage Users', 'إدارة المستخدمين');
$currentPage = 'users';

// Handle user actions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    adminVerifyCsrf();
    
    $action = $_POST['action'] ?? '';
    $userId = (int) ($_POST['user_id'] ?? 0);
    
    switch ($action) {
        case 'toggle_status':
            if ($userId && $userId !== $_SESSION['user_id']) {
                $currentStatus = db_value("SELECT is_active FROM users WHERE id = ?", [$userId]);
                $newStatus = $currentStatus ? 0 : 1;
                
                if (db_execute("UPDATE users SET is_active = ? WHERE id = ?", [$newStatus, $userId])) {
                    $message = t('user_status_updated', 'User status updated successfully', 'تم تحديث حالة المستخدم بنجاح');
                    $messageType = 'success';
                }
            }
            break;
            
        case 'delete':
            if ($userId && $userId !== $_SESSION['user_id']) {
                if (db_execute("DELETE FROM users WHERE id = ?", [$userId])) {
                    $message = t('user_deleted', 'User deleted successfully', 'تم حذف المستخدم بنجاح');
                    $messageType = 'success';
                }
            }
            break;
            
        case 'update_role':
            $newRole = $_POST['role'] ?? '';
            if ($userId && $userId !== $_SESSION['user_id'] && in_array($newRole, ['student', 'admin'])) {
                if (db_execute("UPDATE users SET role = ? WHERE id = ?", [$newRole, $userId])) {
                    $message = t('user_role_updated', 'User role updated successfully', 'تم تحديث دور المستخدم بنجاح');
                    $messageType = 'success';
                }
            }
            break;
    }
}

// Get all users with pagination
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';

$whereClause = "WHERE 1=1";
$params = [];

if ($search) {
    $whereClause .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($roleFilter && in_array($roleFilter, ['student', 'admin'])) {
    $whereClause .= " AND u.role = ?";
    $params[] = $roleFilter;
}

$totalUsers = (int) db_value("SELECT COUNT(*) FROM users u $whereClause", $params);
$totalPages = ceil($totalUsers / $perPage);

$users = db_query(
    "SELECT u.id, u.username, u.email, u.full_name, u.role, u.level_id,
            u.xp_points, u.current_level, u.is_active, u.profile_picture,
            u.preferred_lang, u.last_login, u.created_at,
            l.name_" . $currentLang . " as level_name
     FROM users u
     LEFT JOIN levels l ON u.level_id = l.id
     $whereClause
     ORDER BY u.created_at DESC
     LIMIT ? OFFSET ?",
    array_merge($params, [$perPage, $offset])
);

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-content">
    <div class="content-header">
        <h1><?php echo t('manage_users', 'Manage Users', 'إدارة المستخدمين'); ?></h1>
        <div class="header-actions">
            <a href="add_user.php" class="btn btn-primary">
                <i class="icon-user-plus"></i>
                <?php echo t('add_new_user', 'Add New User', 'إضافة مستخدم جديد'); ?>
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="filters-bar">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <input type="text" 
                       name="search" 
                       placeholder="<?php echo t('search_users', 'Search users...', 'البحث عن مستخدمين...'); ?>" 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="filter-group">
                <select name="role">
                    <option value=""><?php echo t('all_roles', 'All Roles', 'جميع الأدوار'); ?></option>
                    <option value="student" <?php echo $roleFilter === 'student' ? 'selected' : ''; ?>>
                        <?php echo t('student', 'Student', 'طالب'); ?>
                    </option>
                    <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>
                        <?php echo t('admin', 'Admin', 'مسؤول'); ?>
                    </option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-secondary">
                <?php echo t('filter', 'Filter', 'تصفية'); ?>
            </button>
            
            <?php if ($search || $roleFilter): ?>
                <a href="manage_users.php" class="btn btn-text">
                    <?php echo t('clear_filters', 'Clear', 'مسح'); ?>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Users Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?php echo t('id', 'ID', 'الرقم'); ?></th>
                    <th><?php echo t('user', 'User', 'المستخدم'); ?></th>
                    <th><?php echo t('email', 'Email', 'البريد'); ?></th>
                    <th><?php echo t('role', 'Role', 'الدور'); ?></th>
                    <th><?php echo t('level', 'Level', 'المستوى'); ?></th>
                    <th><?php echo t('xp', 'XP', 'النقاط'); ?></th>
                    <th><?php echo t('language', 'Language', 'اللغة'); ?></th>
                    <th><?php echo t('status', 'Status', 'الحالة'); ?></th>
                    <th><?php echo t('actions', 'Actions', 'الإجراءات'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="9" class="text-center">
                            <?php echo t('no_users_found', 'No users found', 'لم يتم العثور على مستخدمين'); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo $u['id']; ?></td>
                            <td>
                                <div class="user-info">
                                    <?php if ($u['profile_picture']): ?>
                                        <img src="<?php echo htmlspecialchars($u['profile_picture']); ?>" 
                                             alt="<?php echo htmlspecialchars($u['full_name']); ?>" 
                                             class="user-avatar">
                                    <?php else: ?>
                                        <div class="user-avatar-placeholder">
                                            <?php echo strtoupper(substr($u['full_name'], 0, 2)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="user-name"><?php echo htmlspecialchars($u['full_name']); ?></div>
                                        <div class="user-username">@<?php echo htmlspecialchars($u['username']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td>
                                <span class="badge-status badge-<?php echo $u['role']; ?>">
                                    <?php echo t($u['role'], ucfirst($u['role']), $u['role'] === 'admin' ? 'مسؤول' : 'طالب'); ?>
                                </span>
                            </td>
                            <td><?php echo $u['level_name'] ?? '-'; ?></td>
                            <td><?php echo number_format($u['xp_points']); ?></td>
                            <td><span class="badge-status badge-student"><?php echo strtoupper($u['preferred_lang']); ?></span></td>
                            <td>
                                <span class="badge-status badge-<?php echo $u['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $u['is_active'] ? t('active', 'Active', 'نشط') : t('inactive', 'Inactive', 'غير نشط'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" 
                                                    class="btn-icon btn-<?php echo $u['is_active'] ? 'warning' : 'success'; ?>"
                                                    title="<?php echo $u['is_active'] ? t('deactivate', 'Deactivate', 'تعطيل') : t('activate', 'Activate', 'تفعيل'); ?>">
                                                <i class="icon-<?php echo $u['is_active'] ? 'ban' : 'check'; ?>"></i>
                                            </button>
                                        </form>
                                        
                                        <a href="edit_user.php?id=<?php echo $u['id']; ?>" 
                                           class="btn-icon btn-primary"
                                           title="<?php echo t('edit', 'Edit', 'تعديل'); ?>">
                                            <i class="icon-edit"></i>
                                        </a>
                                        
                                        <form method="POST" 
                                              style="display: inline;"
                                              onsubmit="return confirm('<?php echo t('confirm_delete', 'Are you sure you want to delete this user?', 'هل أنت متأكد من حذف هذا المستخدم؟'); ?>');">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button type="submit" 
                                                    class="btn-icon btn-danger"
                                                    title="<?php echo t('delete', 'Delete', 'حذف'); ?>">
                                                <i class="icon-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted"><?php echo t('current_user', 'Current User', 'المستخدم الحالي'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $roleFilter ? '&role=' . $roleFilter : ''; ?>" 
                   class="btn btn-secondary">
                    <?php echo t('previous', 'Previous', 'السابق'); ?>
                </a>
            <?php endif; ?>
            
            <span class="pagination-info">
                <?php echo t('page', 'Page', 'صفحة'); ?> <?php echo $page; ?> / <?php echo $totalPages; ?>
            </span>
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $roleFilter ? '&role=' . $roleFilter : ''; ?>" 
                   class="btn btn-secondary">
                    <?php echo t('next', 'Next', 'التالي'); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
