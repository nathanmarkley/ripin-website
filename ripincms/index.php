<?php
// ripincms/index.php — Dashboard
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
requireLogin();

$db   = getDB();
$user = currentUser();

// Stats
$stats = [
    'pages'     => $db->query('SELECT COUNT(*) FROM pages     WHERE status = "published"')->fetchColumn(),
    'events'    => $db->query('SELECT COUNT(*) FROM events    WHERE status = "published" AND start_date >= NOW()')->fetchColumn(),
    'resources' => $db->query('SELECT COUNT(*) FROM resources WHERE status = "published"')->fetchColumn(),
    'assets'    => $db->query('SELECT COUNT(*) FROM assets')->fetchColumn(),
];

// Pending review (editors+admins see this)
$pendingItems = [];
if (canPublish()) {
    $pendingItems = $db->query(
        'SELECT q.*, u.name as submitter FROM review_queue q
         LEFT JOIN users u ON q.submitted_by = u.id
         WHERE q.action = "pending" ORDER BY q.submitted_at ASC LIMIT 5'
    )->fetchAll();
}

// My recent submissions (contributors see their own)
$mySubmissions = [];
if (!canPublish()) {
    $mySubmissions = $db->query(
        'SELECT q.*, u.name as reviewer FROM review_queue q
         LEFT JOIN users u ON q.reviewed_by = u.id
         WHERE q.submitted_by = ' . $user['id'] . '
         ORDER BY q.submitted_at DESC LIMIT 5'
    )->fetchAll();
}

// Recent pages
$recentPages = $db->query(
    'SELECT p.title, p.slug, p.status, p.updated_at, u.name as editor
     FROM pages p LEFT JOIN users u ON p.updated_by = u.id
     ORDER BY p.updated_at DESC LIMIT 5'
)->fetchAll();

// Upcoming events
$upcomingEvents = $db->query(
    'SELECT title, start_date, location FROM events
     WHERE start_date >= NOW() AND status = "published"
     ORDER BY start_date ASC LIMIT 5'
)->fetchAll();

$pageTitle = 'Dashboard';
include 'includes/admin-header.php';

$hour = (int) date('G');
$greeting = $hour < 12 ? 'morning' : ($hour < 17 ? 'afternoon' : 'evening');
$firstName = explode(' ', $user['name'])[0];
?>

<div class="admin-content">

  <div class="d-flex align-items-start justify-content-between mb-5 flex-wrap gap-3">
    <div>
      <h1 class="admin-page-title">Good <?= $greeting ?>, <?= e($firstName) ?> 👋</h1>
      <p class="text-muted mb-0"><?= date('l, F j, Y') ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <?php if (canPublish()): ?>
        <a href="pages/new.php"     class="btn btn-ripin-primary  btn-sm">+ New Page</a>
        <a href="posts/new.php"     class="btn btn-ripin-outline  btn-sm">+ New Post</a>
        <a href="events/new.php"    class="btn btn-ripin-outline  btn-sm">+ New Event</a>
        <a href="resources/new.php" class="btn btn-ripin-outline  btn-sm">+ New Resource</a>
      <?php else: ?>
        <!-- Contributors see submit buttons -->
        <a href="pages/new.php"     class="btn btn-ripin-primary  btn-sm">✏️ Write Page</a>
        <a href="posts/new.php"     class="btn btn-ripin-outline  btn-sm">✏️ Write Post</a>
        <a href="events/new.php"    class="btn btn-ripin-outline  btn-sm">+ Add Event</a>
        <a href="resources/new.php" class="btn btn-ripin-outline  btn-sm">+ Add Resource</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Contributor notice -->
  <?php if (!canPublish()): ?>
    <div class="contributor-notice mb-4">
      <strong>👋 Your role: Contributor</strong> — You can create and edit content.
      When ready, click <strong>"Submit for Review"</strong> and an Editor will approve and publish it.
    </div>
  <?php endif; ?>

  <!-- ── Stats ──────────────────────────────────────────── -->
  <div class="row g-4 mb-5">
    <?php
    $statItems = [
      ['label'=>'Published Pages',  'value'=>$stats['pages'],     'icon'=>'📄', 'href'=>'pages/',     'color'=>'var(--ripin-blue)'],
      ['label'=>'Upcoming Events',  'value'=>$stats['events'],    'icon'=>'📅', 'href'=>'events/',    'color'=>'var(--ripin-teal)'],
      ['label'=>'Resources',        'value'=>$stats['resources'], 'icon'=>'📚', 'href'=>'resources/', 'color'=>'#7C3AED'],
      ['label'=>'Files Uploaded',   'value'=>$stats['assets'],    'icon'=>'📁', 'href'=>canManageFiles() ? 'files/' : '#', 'color'=>'var(--ripin-orange)'],
    ];
    foreach ($statItems as $s): ?>
    <div class="col-6 col-lg-3">
      <a href="<?= $s['href'] ?>" class="admin-stat-card text-decoration-none d-block">
        <div class="stat-icon" style="background:<?= $s['color'] ?>20;color:<?= $s['color'] ?>">
          <?= $s['icon'] ?>
        </div>
        <div class="stat-value"><?= $s['value'] ?></div>
        <div class="stat-label"><?= $s['label'] ?></div>
        <div class="stat-arrow">→</div>
      </a>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="row g-4">

    <!-- ── Review queue (editors/admins) ───────────────── -->
    <?php if (canPublish() && $pendingItems): ?>
    <div class="col-12">
      <div class="admin-card border-warning" style="border-color:#fbbf24!important">
        <div class="admin-card-header" style="background:#fffbeb">
          <h5 class="mb-0">🔔 Pending Review <span class="badge bg-warning text-dark ms-1"><?= count($pendingItems) ?></span></h5>
          <a href="review/" class="btn btn-sm btn-ripin-outline">View all</a>
        </div>
        <?php foreach ($pendingItems as $item): ?>
          <div class="review-item">
            <span class="review-type-badge review-type-<?= $item['content_type'] ?>">
              <?= ucfirst($item['content_type']) ?>
            </span>
            <div class="flex-grow-1">
              <div class="fw-semibold"><?= e($item['content_title']) ?></div>
              <div class="text-muted small">
                By <?= e($item['submitter'] ?? 'Unknown') ?> ·
                <?= formatDateTime($item['submitted_at']) ?>
              </div>
            </div>
            <a href="review/" class="btn btn-sm btn-ripin-primary">Review →</a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── My submissions (contributors) ───────────────── -->
    <?php if (!canPublish() && $mySubmissions): ?>
    <div class="col-12">
      <div class="admin-card">
        <div class="admin-card-header">
          <h5 class="mb-0">📋 My Submissions</h5>
        </div>
        <?php foreach ($mySubmissions as $item): ?>
          <div class="admin-list-item">
            <span class="review-type-badge review-type-<?= $item['content_type'] ?>">
              <?= ucfirst($item['content_type']) ?>
            </span>
            <div class="flex-grow-1">
              <div class="fw-semibold"><?= e($item['content_title']) ?></div>
              <?php if ($item['review_note']): ?>
                <div class="text-muted small fst-italic">"<?= e($item['review_note']) ?>"</div>
              <?php endif; ?>
            </div>
            <span class="status-badge status-<?= $item['action'] ?>">
              <?= ucwords(str_replace('_', ' ', $item['action'])) ?>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- ── Recent pages ─────────────────────────────────── -->
    <div class="col-lg-6">
      <div class="admin-card">
        <div class="admin-card-header">
          <h5 class="mb-0">Recently Updated Pages</h5>
          <a href="pages/" class="btn btn-sm btn-ripin-outline">View all</a>
        </div>
        <?php if ($recentPages): ?>
          <ul class="list-unstyled mb-0">
            <?php foreach ($recentPages as $page): ?>
            <li class="admin-list-item">
              <a href="pages/edit.php?slug=<?= e($page['slug']) ?>"
                 class="text-decoration-none flex-grow-1">
                <span class="fw-semibold text-ripin-navy"><?= e($page['title']) ?></span>
                <span class="text-muted small d-block">
                  <?= e($page['editor'] ?? '?') ?> · <?= formatDate($page['updated_at'], 'M j') ?>
                </span>
              </a>
              <span class="status-badge status-<?= $page['status'] ?>">
                <?= ucfirst(str_replace('_', ' ', $page['status'])) ?>
              </span>
            </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="text-center py-4 text-muted">
            <p class="mb-2">No pages yet.</p>
            <a href="pages/new.php" class="btn btn-sm btn-ripin-primary">Create First Page</a>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ── Upcoming events ──────────────────────────────── -->
    <div class="col-lg-6">
      <div class="admin-card">
        <div class="admin-card-header">
          <h5 class="mb-0">Upcoming Events</h5>
          <a href="events/" class="btn btn-sm btn-ripin-outline">View all</a>
        </div>
        <?php if ($upcomingEvents): ?>
          <ul class="list-unstyled mb-0">
            <?php foreach ($upcomingEvents as $ev): ?>
            <li class="admin-list-item">
              <div class="event-day-badge">
                <span class="day"><?= date('j', strtotime($ev['start_date'])) ?></span>
                <span class="mon"><?= date('M', strtotime($ev['start_date'])) ?></span>
              </div>
              <div class="flex-grow-1">
                <div class="fw-semibold"><?= e($ev['title']) ?></div>
                <?php if ($ev['location']): ?>
                  <div class="text-muted small">📍 <?= e($ev['location']) ?></div>
                <?php endif; ?>
              </div>
            </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="text-center py-4 text-muted">
            <p class="mb-2">No upcoming events.</p>
            <a href="events/new.php" class="btn btn-sm btn-ripin-primary">Add Event</a>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<?php include 'includes/admin-footer.php'; ?>
