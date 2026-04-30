<?php
// ripincms/files/index.php — File Manager
// Accessible by: Admin, Editor (not Contributor)
require_once dirname(dirname(__DIR__)) . '/includes/config.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
requireLogin();

// Contributors cannot access file manager
if (!canManageFiles()) {
    flashError('File management requires Editor or Admin access.');
    redirect(CMS_PATH . '/');
}

$db   = getDB();
$user = currentUser();

// ── Allowed file types (images and PDFs ONLY) ──────────────────
$allowedTypes = [
    // Images
    'image/jpeg' => ['jpg',  'image'],
    'image/png'  => ['png',  'image'],
    'image/gif'  => ['gif',  'image'],
    'image/webp' => ['webp', 'image'],
    // PDFs only — no office files
    'application/pdf' => ['pdf', 'pdf'],
];

// ── Handle upload ──────────────────────────────────────────────
$uploadError = $uploadSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    verifyCsrf();
    $file    = $_FILES['file'];
    $maxSize = 20 * 1024 * 1024; // 20MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadError = 'Upload failed. Please try again.';
    } elseif ($file['size'] > $maxSize) {
        $uploadError = 'File too large. Maximum 20MB.';
    } else {
        // Double-check MIME type using finfo (safer than trusting $_FILES['type'])
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($file['tmp_name']);

        if (!isset($allowedTypes[$realMime])) {
            $uploadError = 'Only JPG, PNG, GIF, WEBP, and PDF files are allowed.';
        } else {
            [$ext, $fileType] = $allowedTypes[$realMime];
            $subDir     = $fileType === 'image' ? 'images' : 'pdfs';
            $safeName   = preg_replace('/[^a-z0-9\-_]/', '', strtolower(pathinfo($file['name'], PATHINFO_FILENAME)));
            $safeName   = substr($safeName ?: 'file', 0, 50);
            $uniqueName = $safeName . '-' . time() . '.' . $ext;
            $destPath   = UPLOAD_DIR . '/' . $subDir . '/' . $uniqueName;
            $fileUrl    = ASSETS_URL . '/' . $subDir . '/' . $uniqueName;
            $altText    = trim($_POST['alt_text'] ?? '');

            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                $db->prepare(
                    'INSERT INTO assets (file_name, original_name, file_path, file_url, file_type, mime_type, file_size, alt_text, uploaded_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                )->execute([
                    $uniqueName, $file['name'], $destPath, $fileUrl,
                    $fileType, $realMime, $file['size'], $altText, $user['id']
                ]);
                $uploadSuccess = $fileUrl;
            } else {
                $uploadError = 'Could not save file. Check folder permissions on /assets/.';
            }
        }
    }
}

// ── Handle delete ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    verifyCsrf();
    $assetId = (int) $_POST['delete_id'];
    $asset   = $db->prepare('SELECT * FROM assets WHERE id = ? LIMIT 1');
    $asset->execute([$assetId]);
    $row = $asset->fetch();

    if ($row) {
        // Admins can delete any file; editors can only delete their own
        if (isAdmin() || (int)$row['uploaded_by'] === $user['id']) {
            if (file_exists($row['file_path'])) unlink($row['file_path']);
            $db->prepare('DELETE FROM assets WHERE id = ?')->execute([$assetId]);
            flashSuccess('File deleted successfully.');
        } else {
            flashError('You can only delete files you uploaded.');
        }
        redirect(CMS_PATH . '/files/');
    }
}

// ── Fetch files ────────────────────────────────────────────────
$typeFilter = $_GET['type'] ?? 'all';
$search     = trim($_GET['q'] ?? '');
$where = []; $params = [];

if ($typeFilter !== 'all') { $where[] = 'a.file_type = ?'; $params[] = $typeFilter; }
if ($search) {
    $where[] = '(a.original_name LIKE ? OR a.file_name LIKE ?)';
    $params[] = "%$search%"; $params[] = "%$search%";
}

$sql = 'SELECT a.*, u.name as uploader FROM assets a
        LEFT JOIN users u ON a.uploaded_by = u.id'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' ORDER BY a.uploaded_at DESC';

$stmt = $db->prepare($sql); $stmt->execute($params);
$files = $stmt->fetchAll();

// File counts for tabs
$counts = $db->query('SELECT file_type, COUNT(*) as cnt FROM assets GROUP BY file_type')->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle  = 'File Manager';
$breadcrumb = ['File Manager' => null];
include dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-content">

  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
      <h1 class="admin-page-title">📁 File Manager</h1>
      <p class="text-muted mb-0 small">Images and PDFs only · Max 20MB per file</p>
    </div>
  </div>

  <!-- ── Upload zone ──────────────────────────────────────── -->
  <div class="admin-form-card mb-4">
    <h5>Upload New File</h5>

    <?php if ($uploadError): ?>
      <div class="alert alert-danger rounded-3 mb-3">⚠️ <?= e($uploadError) ?></div>
    <?php endif; ?>

    <?php if ($uploadSuccess): ?>
      <div class="alert alert-success rounded-3 mb-3">
        <div class="fw-semibold mb-1">✅ File uploaded successfully!</div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <code class="flex-grow-1" style="font-size:.8125rem;word-break:break-all"><?= e($uploadSuccess) ?></code>
          <button class="btn btn-sm btn-ripin-success"
                  onclick="copyToClipboard('<?= e($uploadSuccess) ?>', this)">
            Copy Link
          </button>
        </div>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="uploadForm">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <div class="file-drop-zone mb-3" id="dropZone"
           onclick="document.getElementById('fileInput').click()">
        <div id="dzDefault">
          <div style="font-size:2.5rem;margin-bottom:.5rem">📤</div>
          <p class="fw-semibold mb-1">Drag & drop or click to upload</p>
          <p class="text-muted small mb-0">JPG · PNG · GIF · WEBP · PDF &nbsp;|&nbsp; Max 20MB</p>
        </div>
        <div id="dzPreview" style="display:none">
          <div style="font-size:2rem" id="dzIcon">📄</div>
          <p class="fw-semibold mb-0" id="dzName"></p>
          <p class="text-muted small mb-0" id="dzSize"></p>
        </div>
      </div>

      <input type="file" id="fileInput" name="file" class="d-none"
             accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">

      <div class="mb-3">
        <label class="form-label">Alt text / description (optional)</label>
        <input type="text" name="alt_text" class="form-control"
               placeholder="Describe this file for accessibility…">
      </div>

      <button type="submit" id="uploadBtn" class="btn btn-ripin-primary" disabled>
        Upload File
      </button>
    </form>
  </div>

  <!-- ── Filter tabs ──────────────────────────────────────── -->
  <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <a href="?type=all"   class="btn btn-sm <?= $typeFilter==='all'   ? 'btn-ripin-primary' : 'btn-outline-secondary' ?>">
      All (<?= array_sum($counts) ?>)
    </a>
    <a href="?type=image" class="btn btn-sm <?= $typeFilter==='image' ? 'btn-ripin-primary' : 'btn-outline-secondary' ?>">
      🖼️ Images (<?= $counts['image'] ?? 0 ?>)
    </a>
    <a href="?type=pdf"   class="btn btn-sm <?= $typeFilter==='pdf'   ? 'btn-ripin-primary' : 'btn-outline-secondary' ?>">
      📄 PDFs (<?= $counts['pdf'] ?? 0 ?>)
    </a>

    <form method="GET" class="ms-auto d-flex gap-2">
      <?php if ($typeFilter !== 'all'): ?>
        <input type="hidden" name="type" value="<?= e($typeFilter) ?>">
      <?php endif; ?>
      <input type="text" name="q" class="form-control form-control-sm"
             placeholder="Search files…" value="<?= e($search) ?>" style="width:200px">
      <button type="submit" class="btn btn-sm btn-outline-secondary">Search</button>
    </form>
  </div>

  <!-- ── File grid ────────────────────────────────────────── -->
  <?php if ($files): ?>
    <div class="file-grid">
      <?php foreach ($files as $f):
        $canDelete = isAdmin() || (int)$f['uploaded_by'] === $user['id'];
      ?>
        <div class="file-card">
          <?php if ($f['file_type'] === 'image'): ?>
            <img src="<?= e($f['file_url']) ?>"
                 alt="<?= e($f['alt_text'] ?: $f['original_name']) ?>"
                 class="img-fluid rounded mb-2"
                 style="height:90px;width:100%;object-fit:cover;cursor:pointer"
                 onclick="previewImage('<?= e($f['file_url']) ?>', '<?= e(addslashes($f['original_name'])) ?>')">
          <?php else: ?>
            <div class="file-icon">📄</div>
          <?php endif; ?>

          <div class="file-name" title="<?= e($f['original_name']) ?>">
            <?= e(strlen($f['original_name']) > 24 ? substr($f['original_name'], 0, 21) . '…' : $f['original_name']) ?>
          </div>
          <div class="file-size"><?= humanFileSize($f['file_size']) ?></div>
          <div class="text-muted" style="font-size:.7rem;margin-top:.2rem">
            <?= e($f['uploader'] ?? '?') ?> · <?= date('M j', strtotime($f['uploaded_at'])) ?>
          </div>

          <button class="btn btn-ripin-outline file-copy-btn"
                  onclick="copyToClipboard('<?= e($f['file_url']) ?>', this)">
            📋 Copy Link
          </button>

          <a href="<?= e($f['file_url']) ?>" target="_blank"
             class="btn btn-outline-secondary file-copy-btn mt-1"
             style="font-size:.7rem">
            View ↗
          </a>

          <?php if ($canDelete): ?>
            <form method="POST" onsubmit="return confirm('Delete this file permanently? This cannot be undone.')">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="delete_id"  value="<?= $f['id'] ?>">
              <button type="submit" class="btn btn-outline-danger file-copy-btn mt-1"
                      style="font-size:.7rem">
                Delete
              </button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

  <?php else: ?>
    <div class="text-center py-5 text-muted">
      <div style="font-size:3rem;margin-bottom:1rem">📂</div>
      <h5 class="fw-bold">No files yet</h5>
      <p>Upload your first image or PDF above.</p>
    </div>
  <?php endif; ?>

</div>

<!-- Image preview modal -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 rounded-4">
      <div class="modal-header border-0 pb-0">
        <h6 class="modal-title text-muted" id="previewModalTitle"></h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center p-3">
        <img id="previewModalImg" src="" alt="" class="img-fluid rounded-3">
      </div>
    </div>
  </div>
</div>

<?php
$extraJS = <<<'JS'
<script>
// Drag & drop
const dropZone  = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const uploadBtn = document.getElementById('uploadBtn');

dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
dropZone.addEventListener('drop', e => {
  e.preventDefault();
  dropZone.classList.remove('drag-over');
  if (e.dataTransfer.files[0]) { fileInput.files = e.dataTransfer.files; showPreview(e.dataTransfer.files[0]); }
});
fileInput.addEventListener('change', () => { if (fileInput.files[0]) showPreview(fileInput.files[0]); });

function showPreview(file) {
  document.getElementById('dzDefault').style.display = 'none';
  document.getElementById('dzPreview').style.display = 'block';
  document.getElementById('dzName').textContent = file.name;
  document.getElementById('dzSize').textContent = formatBytes(file.size);
  document.getElementById('dzIcon').textContent = file.type.startsWith('image/') ? '🖼️' : '📄';
  uploadBtn.disabled = false;
}

function formatBytes(b) {
  if (b < 1024) return b + ' B';
  if (b < 1048576) return (b/1024).toFixed(1) + ' KB';
  return (b/1048576).toFixed(1) + ' MB';
}

function copyToClipboard(text, btn) {
  navigator.clipboard.writeText(text).then(() => {
    const orig = btn.textContent;
    btn.textContent = '✅ Copied!';
    btn.classList.add('btn-ripin-success');
    btn.classList.remove('btn-ripin-outline','btn-outline-secondary');
    setTimeout(() => { btn.textContent = orig; btn.classList.remove('btn-ripin-success'); btn.classList.add('btn-ripin-outline'); }, 2500);
  });
}

function previewImage(url, name) {
  document.getElementById('previewModalImg').src  = url;
  document.getElementById('previewModalTitle').textContent = name;
  new bootstrap.Modal(document.getElementById('imagePreviewModal')).show();
}
</script>
JS;

include dirname(__DIR__) . '/includes/admin-footer.php';
?>
