<?php
/**
 * admin/manage_lessons.php — Lesson Management with FIXED AJAX
 * FIXED: POST handler processes all fields (AR/FR/EN titles, URLs)
 * ADDED: Live YouTube/MediaFire preview
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireRole(['admin', 'super_admin']);

$currentLang = getCurrentLang();
$dir         = getDirection();
$isRtl       = $dir === 'rtl';

// ═══════════════════════════════════════════════════════════════════════════
//  AJAX HANDLERS (FIXED: Now processes all fields correctly)
// ═══════════════════════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['ajax_action'])) {
    header('Content-Type: application/json; charset=UTF-8');
    
    // CSRF validation
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
    
    $action = $_POST['ajax_action'];
    
    // ───────────────────────────────────────────────────────────────────────
    //  ADD LESSON
    // ───────────────────────────────────────────────────────────────────────
    if ($action === 'add_lesson') {
        $subjectId    = (int) ($_POST['subject_id'] ?? 0);
        $titleAr      = trim($_POST['title_ar'] ?? '');
        $titleFr      = trim($_POST['title_fr'] ?? '');
        $titleEn      = trim($_POST['title_en'] ?? '');
        $descAr       = trim($_POST['description_ar'] ?? '');
        $descFr       = trim($_POST['description_fr'] ?? '');
        $descEn       = trim($_POST['description_en'] ?? '');
        $contentType  = trim($_POST['content_type'] ?? 'video');
        $url          = trim($_POST['url'] ?? '');
        $duration     = (int) ($_POST['duration_minutes'] ?? 0);
        $xpReward     = (int) ($_POST['xp_reward'] ?? 10);
        $isPublished  = !empty($_POST['is_published']) ? 1 : 0;
        $displayOrder = (int) ($_POST['display_order'] ?? 0);
        
        // Validation
        if ($subjectId <= 0 || empty($titleAr) || empty($url)) {
            echo json_encode(['success' => false, 'message' => 'Required fields missing']);
            exit;
        }
        
        // If display_order not set, get max + 1
        if ($displayOrder === 0) {
            $maxOrder = (int) db_value(
                'SELECT COALESCE(MAX(display_order), 0) FROM lessons WHERE subject_id = ?',
                [$subjectId]
            );
            $displayOrder = $maxOrder + 1;
        }
        
        // Insert
        $sql = "INSERT INTO lessons 
                (subject_id, title_ar, title_fr, title_en, 
                 description_ar, description_fr, description_en,
                 content_type, url, duration_minutes, xp_reward,
                 is_published, display_order, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $success = db_run($sql, [
            $subjectId, $titleAr, $titleFr, $titleEn,
            $descAr, $descFr, $descEn,
            $contentType, $url, $duration, $xpReward,
            $isPublished, $displayOrder
        ]);
        
        if ($success) {
            $lessonId = db_last_id();
            echo json_encode([
                'success' => true,
                'message' => 'Lesson added successfully',
                'lesson_id' => $lessonId
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        exit;
    }
    
    // ───────────────────────────────────────────────────────────────────────
    //  EDIT LESSON (FIXED: All fields processed)
    // ───────────────────────────────────────────────────────────────────────
    if ($action === 'edit_lesson') {
        $lessonId     = (int) ($_POST['lesson_id'] ?? 0);
        $subjectId    = (int) ($_POST['subject_id'] ?? 0);
        $titleAr      = trim($_POST['title_ar'] ?? '');
        $titleFr      = trim($_POST['title_fr'] ?? '');
        $titleEn      = trim($_POST['title_en'] ?? '');
        $descAr       = trim($_POST['description_ar'] ?? '');
        $descFr       = trim($_POST['description_fr'] ?? '');
        $descEn       = trim($_POST['description_en'] ?? '');
        $contentType  = trim($_POST['content_type'] ?? 'video');
        $url          = trim($_POST['url'] ?? '');
        $duration     = (int) ($_POST['duration_minutes'] ?? 0);
        $xpReward     = (int) ($_POST['xp_reward'] ?? 10);
        $isPublished  = !empty($_POST['is_published']) ? 1 : 0;
        $displayOrder = (int) ($_POST['display_order'] ?? 0);
        
        if ($lessonId <= 0 || $subjectId <= 0 || empty($titleAr) || empty($url)) {
            echo json_encode(['success' => false, 'message' => 'Required fields missing']);
            exit;
        }
        
        $sql = "UPDATE lessons SET
                subject_id = ?, title_ar = ?, title_fr = ?, title_en = ?,
                description_ar = ?, description_fr = ?, description_en = ?,
                content_type = ?, url = ?, duration_minutes = ?, xp_reward = ?,
                is_published = ?, display_order = ?, updated_at = NOW()
                WHERE id = ?";
        
        $success = db_run($sql, [
            $subjectId, $titleAr, $titleFr, $titleEn,
            $descAr, $descFr, $descEn,
            $contentType, $url, $duration, $xpReward,
            $isPublished, $displayOrder, $lessonId
        ]);
        
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Lesson updated successfully' : 'Database error'
        ]);
        exit;
    }
    
    // ───────────────────────────────────────────────────────────────────────
    //  DELETE LESSON
    // ───────────────────────────────────────────────────────────────────────
    if ($action === 'delete_lesson') {
        $lessonId = (int) ($_POST['lesson_id'] ?? 0);
        
        if ($lessonId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid lesson ID']);
            exit;
        }
        
        // Delete related progress first
        db_run('DELETE FROM lesson_progress WHERE lesson_id = ?', [$lessonId]);
        
        // Delete lesson
        $success = db_run('DELETE FROM lessons WHERE id = ?', [$lessonId]);
        
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Lesson deleted successfully' : 'Database error'
        ]);
        exit;
    }
    
    // ───────────────────────────────────────────────────────────────────────
    //  TOGGLE PUBLISH STATUS
    // ───────────────────────────────────────────────────────────────────────
    if ($action === 'toggle_publish') {
        $lessonId = (int) ($_POST['lesson_id'] ?? 0);
        
        if ($lessonId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid lesson ID']);
            exit;
        }
        
        $current = (int) db_value('SELECT is_published FROM lessons WHERE id = ?', [$lessonId]);
        $newStatus = $current === 1 ? 0 : 1;
        
        $success = db_run('UPDATE lessons SET is_published = ? WHERE id = ?', [$newStatus, $lessonId]);
        
        echo json_encode([
            'success' => $success,
            'new_status' => $newStatus,
            'message' => $success ? 'Status updated' : 'Database error'
        ]);
        exit;
    }
    
    // Unknown action
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════
//  FETCH DATA FOR PAGE RENDER
// ═══════════════════════════════════════════════════════════════════════════

// Get all levels
$levels = db_all('SELECT * FROM levels ORDER BY display_order ASC');

// Get selected level (default to first)
$selectedLevelId = (int) ($_GET['level_id'] ?? ($levels[0]['id'] ?? 0));

// Get subjects for selected level
$subjects = $selectedLevelId
    ? db_all('SELECT * FROM subjects WHERE level_id = ? ORDER BY display_order ASC', [$selectedLevelId])
    : [];

// Get selected subject (default to first)
$selectedSubjectId = (int) ($_GET['subject_id'] ?? ($subjects[0]['id'] ?? 0));

// Get lessons for selected subject
$lessons = $selectedSubjectId
    ? db_all(
        "SELECT l.*, 
                (SELECT COUNT(*) FROM lesson_progress lp WHERE lp.lesson_id = l.id AND lp.status = 'completed') AS completion_count
         FROM lessons l 
         WHERE l.subject_id = ? 
         ORDER BY l.display_order ASC, l.created_at DESC",
        [$selectedSubjectId]
    )
    : [];

$pageTitle = 'Manage Lessons — Admin';
$csrfToken = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>" dir="<?php echo $dir; ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $pageTitle; ?></title>
  <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

  <style>
    :root {
      --blue-900: #1e3a8a; --blue-700: #1d4ed8; --blue-500: #3b82f6;
      --blue-100: #dbeafe; --blue-50: #eff6ff;
      --white: #fff; --gray-50: #f8fafc; --gray-100: #f1f5f9;
      --gray-200: #e2e8f0; --gray-500: #64748b; --gray-700: #334155;
      --gray-900: #0f172a; --radius: 12px;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--gray-100); color: var(--gray-700);
      padding: 1.5rem;
    }

    .container { max-width: 1400px; margin: 0 auto; }
    
    .page-header {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 1.5rem;
    }
    .page-header h1 { font-size: 1.5rem; font-weight: 700; color: var(--gray-900); }
    .btn-back {
      display: inline-flex; align-items: center; gap: .5rem;
      padding: .6rem 1.1rem; background: var(--white);
      border: 1px solid var(--gray-200); border-radius: 8px;
      color: var(--gray-700); text-decoration: none; font-size: .875rem;
      font-weight: 500; transition: all .15s;
    }
    .btn-back:hover { background: var(--blue-50); color: var(--blue-700); border-color: var(--blue-200); }

    .filter-bar {
      display: flex; gap: 1rem; margin-bottom: 1.5rem;
      padding: 1.25rem; background: var(--white);
      border-radius: var(--radius); box-shadow: 0 1px 3px rgba(0,0,0,.05);
    }
    .filter-bar select {
      flex: 1; padding: .6rem .85rem; border: 1px solid var(--gray-200);
      border-radius: 8px; font-family: inherit; font-size: .875rem;
      background: var(--white); color: var(--gray-700);
    }

    .actions-bar {
      display: flex; justify-content: flex-end; margin-bottom: 1rem;
    }
    .btn-add {
      display: inline-flex; align-items: center; gap: .5rem;
      padding: .7rem 1.3rem; background: var(--blue-700);
      color: #fff; border: none; border-radius: 8px;
      font-family: inherit; font-size: .875rem; font-weight: 600;
      cursor: pointer; transition: background .15s;
    }
    .btn-add:hover { background: var(--blue-900); }

    .lessons-table {
      background: var(--white); border-radius: var(--radius);
      overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.05);
    }
    table { width: 100%; border-collapse: collapse; }
    thead { background: var(--gray-50); }
    th, td { padding: .85rem 1rem; text-align: left; font-size: .875rem; }
    th { font-weight: 600; color: var(--gray-700); border-bottom: 1px solid var(--gray-200); }
    tbody tr { border-bottom: 1px solid var(--gray-100); transition: background .15s; }
    tbody tr:hover { background: var(--blue-50); }

    .lesson-title { font-weight: 600; color: var(--gray-900); }
    .lesson-meta { font-size: .78rem; color: var(--gray-500); margin-top: .2rem; }
    
    .badge {
      display: inline-flex; align-items: center; gap: .3rem;
      padding: .2rem .6rem; border-radius: 50px;
      font-size: .72rem; font-weight: 600;
    }
    .badge.published { background: #f0fdf4; color: #16a34a; }
    .badge.draft { background: #fef3c7; color: #ca8a04; }
    .badge.video { background: #dbeafe; color: #1d4ed8; }
    .badge.pdf { background: #fee2e2; color: #dc2626; }
    .badge.book { background: #dcfce7; color: #16a34a; }

    .actions {
      display: flex; align-items: center; gap: .5rem;
    }
    .icon-btn {
      width: 32px; height: 32px; border: none; background: none;
      display: flex; align-items: center; justify-content: center;
      border-radius: 6px; cursor: pointer; transition: background .15s;
    }
    .icon-btn:hover { background: var(--gray-100); }
    .icon-btn.danger:hover { background: #fee2e2; color: #dc2626; }

    .empty-state {
      text-align: center; padding: 3rem 1rem; color: var(--gray-400);
    }

    /* Modal */
    .modal-overlay {
      display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5);
      z-index: 1000; backdrop-filter: blur(2px);
    }
    .modal-overlay.open { display: flex; align-items: center; justify-content: center; }
    .modal {
      background: var(--white); border-radius: var(--radius);
      width: 90%; max-width: 700px; max-height: 90vh;
      overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,.3);
    }
    .modal-header {
      padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--gray-200);
      display: flex; align-items: center; justify-content: space-between;
    }
    .modal-header h3 { font-size: 1.1rem; font-weight: 700; color: var(--gray-900); }
    .modal-close {
      background: none; border: none; cursor: pointer;
      width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
      border-radius: 6px; transition: background .15s;
    }
    .modal-close:hover { background: var(--gray-100); }
    .modal-body { padding: 1.5rem; }
    
    .form-group { margin-bottom: 1.25rem; }
    .form-group label {
      display: block; font-size: .85rem; font-weight: 600;
      color: var(--gray-700); margin-bottom: .4rem;
    }
    .form-group input, .form-group select, .form-group textarea {
      width: 100%; padding: .65rem .85rem; border: 1px solid var(--gray-200);
      border-radius: 8px; font-family: inherit; font-size: .875rem;
      background: var(--white); color: var(--gray-700);
    }
    .form-group textarea { min-height: 80px; resize: vertical; }
    
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    
    .checkbox-group {
      display: flex; align-items: center; gap: .5rem;
      padding: .75rem; background: var(--gray-50); border-radius: 8px;
    }
    .checkbox-group input[type="checkbox"] { width: auto; }

    .modal-footer {
      padding: 1rem 1.5rem; border-top: 1px solid var(--gray-200);
      display: flex; justify-content: flex-end; gap: .75rem;
    }
    .btn {
      padding: .65rem 1.3rem; border: none; border-radius: 8px;
      font-family: inherit; font-size: .875rem; font-weight: 600;
      cursor: pointer; transition: all .15s;
    }
    .btn-primary { background: var(--blue-700); color: #fff; }
    .btn-primary:hover { background: var(--blue-900); }
    .btn-secondary { background: var(--gray-100); color: var(--gray-700); }
    .btn-secondary:hover { background: var(--gray-200); }

    /* ADDED: Live preview box */
    .preview-box {
      margin-top: .75rem; padding: 1rem; background: var(--gray-50);
      border-radius: 8px; border: 1px solid var(--gray-200);
    }
    .preview-box iframe {
      width: 100%; height: 240px; border: none; border-radius: 6px;
    }
    .preview-link {
      display: flex; align-items: center; gap: .5rem;
      color: var(--blue-700); text-decoration: none; font-size: .875rem;
    }
    .preview-link:hover { text-decoration: underline; }
  </style>
</head>
<body>

<div class="container">
  <!-- Page Header -->
  <div class="page-header">
    <h1>📚 Manage Lessons</h1>
    <a href="dashboard.php" class="btn-back">
      <i data-lucide="arrow-left" width="16" height="16"></i>
      Back to Dashboard
    </a>
  </div>

  <!-- Filters -->
  <div class="filter-bar">
    <select id="levelSelect" onchange="filterByLevel()">
      <option value="">Select Level</option>
      <?php foreach ($levels as $lvl): ?>
      <option value="<?php echo $lvl['id']; ?>" <?php echo $lvl['id'] == $selectedLevelId ? 'selected' : ''; ?>>
        <?php echo htmlspecialchars($lvl['name_' . $currentLang] ?? $lvl['name_ar']); ?>
      </option>
      <?php endforeach; ?>
    </select>

    <select id="subjectSelect" onchange="filterBySubject()">
      <option value="">Select Subject</option>
      <?php foreach ($subjects as $sub): ?>
      <option value="<?php echo $sub['id']; ?>" <?php echo $sub['id'] == $selectedSubjectId ? 'selected' : ''; ?>>
        <?php echo htmlspecialchars($sub['name_' . $currentLang] ?? $sub['name_ar']); ?>
      </option>
      <?php endforeach; ?>
    </select>
  </div>

  <!-- Actions -->
  <div class="actions-bar">
    <button class="btn-add" onclick="openAddModal()">
      <i data-lucide="plus" width="16" height="16"></i>
      Add New Lesson
    </button>
  </div>

  <!-- Lessons Table -->
  <div class="lessons-table">
    <?php if (empty($lessons)): ?>
    <div class="empty-state">
      <i data-lucide="inbox" width="48" height="48"></i>
      <p>No lessons found. Add your first lesson!</p>
    </div>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th style="width:40px;">#</th>
          <th>Lesson Title</th>
          <th style="width:100px;">Type</th>
          <th style="width:80px;">Duration</th>
          <th style="width:80px;">XP</th>
          <th style="width:100px;">Status</th>
          <th style="width:100px;">Students</th>
          <th style="width:120px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($lessons as $idx => $lesson):
          $titleKey = 'title_' . $currentLang;
          $lTitle = htmlspecialchars($lesson[$titleKey] ?? $lesson['title_ar']);
          $lType  = $lesson['content_type'];
          $lPub   = (int) $lesson['is_published'];
        ?>
        <tr data-lesson-id="<?php echo $lesson['id']; ?>">
          <td><?php echo $lesson['display_order'] ?: ($idx + 1); ?></td>
          <td>
            <div class="lesson-title"><?php echo $lTitle; ?></div>
            <div class="lesson-meta">
              Order: <?php echo $lesson['display_order']; ?> • 
              Created: <?php echo date('M j, Y', strtotime($lesson['created_at'])); ?>
            </div>
          </td>
          <td><span class="badge <?php echo $lType; ?>"><?php echo ucfirst($lType); ?></span></td>
          <td><?php echo $lesson['duration_minutes']; ?> min</td>
          <td><?php echo $lesson['xp_reward']; ?></td>
          <td>
            <span class="badge <?php echo $lPub ? 'published' : 'draft'; ?>">
              <?php echo $lPub ? 'Published' : 'Draft'; ?>
            </span>
          </td>
          <td><?php echo (int) $lesson['completion_count']; ?> completed</td>
          <td>
            <div class="actions">
              <button class="icon-btn" onclick="openEditModal(<?php echo $lesson['id']; ?>)" title="Edit">
                <i data-lucide="pencil" width="16" height="16"></i>
              </button>
              <button class="icon-btn" onclick="togglePublish(<?php echo $lesson['id']; ?>)" title="Toggle Publish">
                <i data-lucide="<?php echo $lPub ? 'eye-off' : 'eye'; ?>" width="16" height="16"></i>
              </button>
              <button class="icon-btn danger" onclick="deleteLesson(<?php echo $lesson['id']; ?>)" title="Delete">
                <i data-lucide="trash-2" width="16" height="16"></i>
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="lessonModal">
  <div class="modal">
    <div class="modal-header">
      <h3 id="modalTitle">Add New Lesson</h3>
      <button class="modal-close" onclick="closeModal()">
        <i data-lucide="x" width="18" height="18"></i>
      </button>
    </div>
    <form id="lessonForm" onsubmit="handleSubmit(event)">
      <div class="modal-body">
        <input type="hidden" id="lessonId" name="lesson_id">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

        <div class="form-group">
          <label>Subject *</label>
          <select name="subject_id" id="subjectIdField" required>
            <?php foreach ($subjects as $sub): ?>
            <option value="<?php echo $sub['id']; ?>">
              <?php echo htmlspecialchars($sub['name_' . $currentLang] ?? $sub['name_ar']); ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Title (Arabic) *</label>
          <input type="text" name="title_ar" id="titleAr" required>
        </div>

        <div class="form-group">
          <label>Title (French)</label>
          <input type="text" name="title_fr" id="titleFr">
        </div>

        <div class="form-group">
          <label>Title (English)</label>
          <input type="text" name="title_en" id="titleEn">
        </div>

        <div class="form-group">
          <label>Description (Arabic)</label>
          <textarea name="description_ar" id="descAr"></textarea>
        </div>

        <div class="form-group">
          <label>Description (French)</label>
          <textarea name="description_fr" id="descFr"></textarea>
        </div>

        <div class="form-group">
          <label>Description (English)</label>
          <textarea name="description_en" id="descEn"></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Content Type *</label>
            <select name="content_type" id="contentType" required onchange="updatePreview()">
              <option value="video">Video (YouTube)</option>
              <option value="pdf">PDF Document</option>
              <option value="book">Book/Resource</option>
            </select>
          </div>
          <div class="form-group">
            <label>Display Order</label>
            <input type="number" name="display_order" id="displayOrder" min="0" value="0">
          </div>
        </div>

        <div class="form-group">
          <label>URL (YouTube / MediaFire / Direct Link) *</label>
          <input type="url" name="url" id="urlField" required onchange="updatePreview()" onkeyup="updatePreview()">
        </div>

        <!-- ADDED: Live Preview -->
        <div id="previewBox" class="preview-box" style="display:none;">
          <div id="previewContent"></div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Duration (minutes)</label>
            <input type="number" name="duration_minutes" id="durationMin" min="1" value="5">
          </div>
          <div class="form-group">
            <label>XP Reward</label>
            <input type="number" name="xp_reward" id="xpReward" min="1" value="10">
          </div>
        </div>

        <div class="form-group">
          <div class="checkbox-group">
            <input type="checkbox" name="is_published" id="isPublished" value="1">
            <label for="isPublished" style="margin:0;">Publish immediately</label>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn btn-primary" id="submitBtn">Save Lesson</button>
      </div>
    </form>
  </div>
</div>

<script>
lucide.createIcons();

const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// Filter functions
function filterByLevel() {
  const levelId = document.getElementById('levelSelect').value;
  window.location.href = `?level_id=${levelId}`;
}

function filterBySubject() {
  const levelId = document.getElementById('levelSelect').value;
  const subjectId = document.getElementById('subjectSelect').value;
  window.location.href = `?level_id=${levelId}&subject_id=${subjectId}`;
}

// Modal functions
function openAddModal() {
  document.getElementById('modalTitle').textContent = 'Add New Lesson';
  document.getElementById('lessonForm').reset();
  document.getElementById('lessonId').value = '';
  document.getElementById('subjectIdField').value = <?php echo $selectedSubjectId; ?>;
  document.getElementById('lessonModal').classList.add('open');
  document.getElementById('previewBox').style.display = 'none';
}

function closeModal() {
  document.getElementById('lessonModal').classList.remove('open');
}

function openEditModal(lessonId) {
  // Fetch lesson data via AJAX (simplified - you can implement full fetch)
  fetch(`get_lesson.php?id=${lessonId}`)
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const l = data.lesson;
        document.getElementById('modalTitle').textContent = 'Edit Lesson';
        document.getElementById('lessonId').value = l.id;
        document.getElementById('subjectIdField').value = l.subject_id;
        document.getElementById('titleAr').value = l.title_ar;
        document.getElementById('titleFr').value = l.title_fr || '';
        document.getElementById('titleEn').value = l.title_en || '';
        document.getElementById('descAr').value = l.description_ar || '';
        document.getElementById('descFr').value = l.description_fr || '';
        document.getElementById('descEn').value = l.description_en || '';
        document.getElementById('contentType').value = l.content_type;
        document.getElementById('urlField').value = l.url;
        document.getElementById('durationMin').value = l.duration_minutes;
        document.getElementById('xpReward').value = l.xp_reward;
        document.getElementById('displayOrder').value = l.display_order;
        document.getElementById('isPublished').checked = l.is_published == 1;
        document.getElementById('lessonModal').classList.add('open');
        updatePreview();
      }
    });
}

// Form submit (FIXED: Now sends all fields)
function handleSubmit(e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  
  const lessonId = document.getElementById('lessonId').value;
  formData.append('ajax_action', lessonId ? 'edit_lesson' : 'add_lesson');
  
  fetch(window.location.href, {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      alert(data.message);
      closeModal();
      location.reload();
    } else {
      alert('Error: ' + data.message);
    }
  })
  .catch(err => {
    alert('Network error: ' + err.message);
  });
}

// Delete lesson
function deleteLesson(id) {
  if (!confirm('Are you sure you want to delete this lesson? This cannot be undone.')) return;
  
  const formData = new FormData();
  formData.append('ajax_action', 'delete_lesson');
  formData.append('lesson_id', id);
  formData.append('csrf_token', csrfToken);
  
  fetch(window.location.href, {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      location.reload();
    } else {
      alert('Error: ' + data.message);
    }
  });
}

// Toggle publish
function togglePublish(id) {
  const formData = new FormData();
  formData.append('ajax_action', 'toggle_publish');
  formData.append('lesson_id', id);
  formData.append('csrf_token', csrfToken);
  
  fetch(window.location.href, {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      location.reload();
    }
  });
}

// ADDED: Live preview function
function updatePreview() {
  const url = document.getElementById('urlField').value.trim();
  const type = document.getElementById('contentType').value;
  const previewBox = document.getElementById('previewBox');
  const previewContent = document.getElementById('previewContent');
  
  if (!url) {
    previewBox.style.display = 'none';
    return;
  }
  
  previewBox.style.display = 'block';
  
  // YouTube preview
  if (type === 'video' && (url.includes('youtube.com') || url.includes('youtu.be'))) {
    const videoId = extractYouTubeId(url);
    if (videoId) {
      previewContent.innerHTML = `
        <iframe src="https://www.youtube.com/embed/${videoId}" 
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                allowfullscreen></iframe>
      `;
      return;
    }
  }
  
  // MediaFire or PDF preview
  if (type === 'pdf' || url.includes('mediafire.com')) {
    previewContent.innerHTML = `
      <a href="${url}" target="_blank" class="preview-link">
        <i data-lucide="external-link" width="16" height="16"></i>
        Open ${type === 'pdf' ? 'PDF' : 'Resource'} in new tab
      </a>
    `;
    lucide.createIcons();
    return;
  }
  
  // Generic link
  previewContent.innerHTML = `
    <a href="${url}" target="_blank" class="preview-link">
      <i data-lucide="external-link" width="16" height="16"></i>
      Open link in new tab
    </a>
  `;
  lucide.createIcons();
}

function extractYouTubeId(url) {
  const regex = /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i;
  const match = url.match(regex);
  return match ? match[1] : null;
}
</script>

</body>
</html>
