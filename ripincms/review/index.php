<?php
// ripincms/review/index.php — Review Queue
require_once dirname(dirname(__DIR__)) . '/includes/config.php';
require_once dirname(dirname(__DIR__)) . '/includes/auth.php';
requireLogin();
requireEditor(); // Only editors and admins

$db   = getDB();
$user = currentUser();

// ── Handle approval / changes requested / decline ──────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $queueId     = (int) ($_POST['queue_id']    ?? 0);
    $action      = $_POST['action']              ?? '';
    $reviewNote  = trim($_POST['review_note']   ?? '');
    $allowed     = ['approved', 'changes_requested', 'declined'];

    if ($queueId && in_array($action, $allowed)) {
        // Get queue item
        $item = $db->prepare('SELECT * FROM review_queue WHERE id = ? AND action = "pending" LIMIT 1');
        $item->execute([$queueId]);
        $queue = $item->fetch();

        if ($queue) {
            // Update queue entry
            $db->prepare(
                'UPDATE review_queue SET action = ?, reviewed_by = ?, reviewed_at = NOW(), review_note = ?
                 WHERE id = ?'
            )->execute([$action, $user['id'], $reviewNote, $queueId]);

            // Update the actual content item
            $table  = match($queue['content_type']) {
                'page'     => 'pages',
                'post'     => 'posts',
                'event'    => 'events',
                'resource' => 'resources',
                default    => null,
            };

            if ($table) {
                $newStatus = $action === 'approved' ? 'published' : 'draft';
                $publishedAt = $action === 'approved' ? ', published_at = NOW()' : '';
                $db->prepare(
                    "UPDATE {$table} SET status = ?, review_note = ?, reviewed_by = ?{$publishedAt} WHERE id = ?"
                )->execute([$newStatus, $reviewNote, $user['id'], $queue['content_id']]);
            }

            $actionLabels = ['approved' => 'approved and published', 'changes_requested' => 'sent back for changes', 'declined' => 'declined'];
            flashSuccess('"' . $queue['content_title'] . '" has been ' . $actionLabels[$action] . '.');
            redirect(CMS_PATH . '/review/');
        }
    }
}

// ── Fetch pending items ────────────────────────────────────────
$pending = $db->query(
    'SELECT q.*, u.name as submitter_name
     FROM review_queue q
     LEFT JOIN users u ON q.submitted_by = u.id
     WHERE q.action = "pending"
     ORDER BY q.submitted_at ASC'
)->fetchAll();

// ── Fetch recently reviewed ────────────────────────────────────
$recent = $db->query(
    'SELECT q.*, u.name as submitter_name, r.name as reviewer_name
     FROM review_queue q
     LEFT JOIN users u ON q.submitted_by = u.id
     LEFT JOIN users r ON q.reviewed_by = r.id
     WHERE q.action != "pending"
     ORDER BY q.reviewed_at DESC
     LIMIT 10'
)->fetchAll();

$pageTitle  = 'Review Queue';
$breadcrumb = ['Review Queue' => null];
include dirname(__DIR__) . '/includes/admin-header.php';
?>

<div class="admin-content">

  <div class="d-flex align-items-center justify-content-between mb-5 flex-wrap gap-3">
    <div>
      <h1 class="admin-page-title">🔔 Review Queue</h1>
      <p class="text-muted mb-0">
        <?= count($pending) ?> item<?= count($pending) !== 1 ? 's' : '' ?> waiting for review
      </p>
    </div>
  </div>

  <!-- ── Pending items ─────────────────────────────────────── -->
  <?php if ($pending): ?>
    <div class="admin-card mb-5">
      <div class="admin-card-header">
        <h5 class="mb-0 d-flex align-items-center gap-2">
          ⏳ Pending Review
          <span class="badge bg-warning text-dark"><?= count($pending) ?></span>
        </h5>
      </div>

      <?php foreach ($pending as $item): ?>
        <div class="review-item">
          <!-- Type badge -->
          <span class="review-type-badge review-type-<?= $item['content_type'] ?>">
            <?= ucfirst($item['content_type']) ?>
          </span>

          <!-- Content info -->
          <div class="flex-grow-1">
            <div class="fw-semibold text-ripin-navy"><?= e($item['content_title']) ?></div>
            <div class="text-muted small mt-0.5">
              Submitted by <?= e($item['submitter_name']) ?>
              · <?= formatDateTime($item['submitted_at']) ?>
            </div>
          </div>

          <!-- Preview link -->
          <?php
          $previewUrls = [
            'page'     => CMS_PATH . '/pages/edit.php?id='     . $item['content_id'],
            'post'     => CMS_PATH . '/posts/edit.php?id='     . $item['content_id'],
            'event'    => CMS_PATH . '/events/edit.php?id='    . $item['content_id'],
            'resource' => CMS_PATH . '/resources/edit.php?id=' . $item['content_id'],
          ];
          $previewUrl = $previewUrls[$item['content_type']] ?? '#';
          ?>
          <a href="<?= e($previewUrl) ?>" class="btn btn-sm btn-outline-secondary me-1">
            👁 Preview
          </a>

          <!-- Action button -->
          <button class="btn btn-sm btn-ripin-primary"
                  onclick="openReviewModal(<?= $item['id'] ?>, '<?= e(addslashes($item['content_title'])) ?>')">
            Review →
          </button>
        </div>
      <?php endforeach; ?>
    </div>

  <?php else: ?>
    <div class="admin-card mb-5">
      <div class="text-center py-5 text-muted">
        <div style="font-size:3rem;margin-bottom:1rem">✅</div>
        <h5 class="fw-bold">All caught up!</h5>
        <p>No items waiting for review.</p>
      </div>
    </div>
  <?php endif; ?>

  <!-- ── Recently reviewed ──────────────────────────────────── -->
  <?php if ($recent): ?>
    <div class="admin-card">
      <div class="admin-card-header">
        <h5 class="mb-0">Recently Reviewed</h5>
      </div>
      <?php foreach ($recent as $item): ?>
        <div class="review-item">
          <span class="review-type-badge review-type-<?= $item['content_type'] ?>">
            <?= ucfirst($item['content_type']) ?>
          </span>
          <div class="flex-grow-1">
            <div class="fw-semibold"><?= e($item['content_title']) ?></div>
            <div class="text-muted small">
              By <?= e($item['submitter_name']) ?>
              · Reviewed by <?= e($item['reviewer_name'] ?? 'Unknown') ?>
              · <?= formatDate($item['reviewed_at'] ?? '', 'M j') ?>
            </div>
            <?php if ($item['review_note']): ?>
              <div class="small text-muted fst-italic mt-1">"<?= e($item['review_note']) ?>"</div>
            <?php endif; ?>
          </div>
          <span class="status-badge status-<?= $item['action'] ?>">
            <?= ucwords(str_replace('_', ' ', $item['action'])) ?>
          </span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<!-- ── Review modal ──────────────────────────────────────────── -->
<div class="modal fade" id="reviewModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 rounded-4 overflow-hidden">
      <div class="modal-header" style="background:var(--ripin-blue);color:#fff;border:none">
        <h5 class="modal-title fw-bold font-display" id="reviewModalTitle">Review Submission</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="queue_id"   id="reviewQueueId">

        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label">Note to contributor (optional)</label>
            <textarea name="review_note" class="form-control" rows="3"
                      placeholder="Explain what changes are needed, or leave a note for the contributor…"></textarea>
          </div>
        </div>

        <div class="modal-footer border-0 p-4 pt-0 gap-2 flex-wrap">
          <button type="submit" name="action" value="approved"
                  class="btn btn-ripin-success flex-grow-1">
            ✅ Approve & Publish
          </button>
          <button type="submit" name="action" value="changes_requested"
                  class="btn btn-ripin-warning flex-grow-1">
            ✏️ Request Changes
          </button>
          <button type="submit" name="action" value="declined"
                  class="btn btn-ripin-danger flex-grow-1"
                  onclick="return confirm('Decline this submission?')">
            ✗ Decline
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php
$extraJS = <<<JS
<script>
function openReviewModal(queueId, title) {
  document.getElementById('reviewQueueId').value  = queueId;
  document.getElementById('reviewModalTitle').textContent = 'Review: ' + title;
  new bootstrap.Modal(document.getElementById('reviewModal')).show();
}
</script>
JS;
include dirname(__DIR__) . '/includes/admin-footer.php';
?>
