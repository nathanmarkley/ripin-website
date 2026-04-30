<?php
// ripincms/includes/admin-header.php
$currentUser  = currentUser();
$currentPath  = $_SERVER['PHP_SELF'];
$pendingCount = getPendingReviewCount();

function navActive(string $path): string {
    global $currentPath;
    return strpos($currentPath, $path) !== false ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? e($pageTitle) . ' | ' : '' ?>RIPIN Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/ripincms/assets/admin.css">
</head>
<body class="admin-body">

<div class="sidebar-overlay d-lg-none" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ── Sidebar ──────────────────────────────────────────────── -->
<aside class="admin-sidebar" id="adminSidebar">

  <div class="sidebar-logo">
    <div class="sidebar-logo-icon">R</div>
    <div>
      <div class="sidebar-logo-text">RIPIN</div>
      <div class="sidebar-logo-sub">Staff Portal</div>
    </div>
  </div>

  <nav class="sidebar-nav">

    <div class="sidebar-section-label">Content</div>

    <a href="<?= CMS_PATH ?>/" class="sidebar-link <?= navActive('/ripincms/index') ?>">
      <span class="sidebar-icon">🏠</span> Dashboard
    </a>

    <a href="<?= CMS_PATH ?>/pages/" class="sidebar-link <?= navActive('/ripincms/pages') ?>">
      <span class="sidebar-icon">📄</span> Pages
    </a>

    <a href="<?= CMS_PATH ?>/posts/" class="sidebar-link <?= navActive('/ripincms/posts') ?>">
      <span class="sidebar-icon">📰</span> Blog Posts
    </a>

    <a href="<?= CMS_PATH ?>/events/" class="sidebar-link <?= navActive('/ripincms/events') ?>">
      <span class="sidebar-icon">📅</span> Events
    </a>

    <a href="<?= CMS_PATH ?>/resources/" class="sidebar-link <?= navActive('/ripincms/resources') ?>">
      <span class="sidebar-icon">📚</span> Resources
    </a>

    <?php if (canManageFiles()): ?>
    <a href="<?= CMS_PATH ?>/files/" class="sidebar-link <?= navActive('/ripincms/files') ?>">
      <span class="sidebar-icon">📁</span> File Manager
    </a>
    <?php endif; ?>

    <?php if (canPublish() && $pendingCount > 0): ?>
    <a href="<?= CMS_PATH ?>/review/" class="sidebar-link <?= navActive('/ripincms/review') ?> sidebar-link-alert">
      <span class="sidebar-icon">🔔</span> Review Queue
      <span class="sidebar-badge"><?= $pendingCount ?></span>
    </a>
    <?php elseif (canPublish()): ?>
    <a href="<?= CMS_PATH ?>/review/" class="sidebar-link <?= navActive('/ripincms/review') ?>">
      <span class="sidebar-icon">🔔</span> Review Queue
    </a>
    <?php endif; ?>

    <div class="sidebar-section-label mt-3">Appearance</div>

    <a href="<?= CMS_PATH ?>/banner.php" class="sidebar-link <?= navActive('/ripincms/banner') ?>">
      <span class="sidebar-icon">📢</span> Banner
    </a>

    <a href="<?= CMS_PATH ?>/popups/" class="sidebar-link <?= navActive('/ripincms/popups') ?>">
      <span class="sidebar-icon">💬</span> Popups
    </a>

    <a href="<?= CMS_PATH ?>/content-zones.php" class="sidebar-link <?= navActive('/ripincms/content-zones') ?>">
      <span class="sidebar-icon">✏️</span> Page Content
    </a>

    <?php if (canManageUsers()): ?>
    <div class="sidebar-section-label mt-3">Admin</div>

    <a href="<?= CMS_PATH ?>/users/" class="sidebar-link <?= navActive('/ripincms/users') ?>">
      <span class="sidebar-icon">👥</span> Users
    </a>

    <a href="<?= CMS_PATH ?>/settings.php" class="sidebar-link <?= navActive('/ripincms/settings') ?>">
      <span class="sidebar-icon">⚙️</span> Settings
    </a>
    <?php endif; ?>

  </nav>

  <div class="sidebar-user">
    <div class="sidebar-user-avatar">
      <?= strtoupper(substr($currentUser['name'] ?? '?', 0, 1)) ?>
    </div>
    <div class="sidebar-user-info">
      <div class="sidebar-user-name"><?= e($currentUser['name'] ?? '') ?></div>
      <div class="sidebar-user-role"><?= ucfirst($currentUser['role'] ?? '') ?></div>
    </div>
    <a href="<?= CMS_PATH ?>/logout.php" class="sidebar-logout" title="Sign out">↩</a>
  </div>

</aside>

<!-- ── Main ─────────────────────────────────────────────────── -->
<div class="admin-main">

  <header class="admin-topbar">
    <button class="mobile-menu-btn d-lg-none" onclick="openSidebar()">☰</button>
    <div class="topbar-breadcrumb d-none d-md-flex align-items-center gap-1">
      <span class="text-muted small">RIPIN</span>
      <?php if (isset($breadcrumb)): ?>
        <?php foreach ($breadcrumb as $label => $url): ?>
          <span class="text-muted small">›</span>
          <?php if ($url): ?>
            <a href="<?= e($url) ?>" class="text-muted small text-decoration-none"><?= e($label) ?></a>
          <?php else: ?>
            <span class="small fw-semibold text-ripin-navy"><?= e($label) ?></span>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div class="d-flex align-items-center gap-2">
      <?php if (canPublish() && $pendingCount > 0): ?>
        <a href="<?= CMS_PATH ?>/review/" class="btn btn-sm btn-warning position-relative">
          🔔 <?= $pendingCount ?> pending
        </a>
      <?php endif; ?>
      <a href="/" target="_blank" class="btn btn-sm btn-outline-secondary">🌐 View Site</a>
    </div>
  </header>

  <div class="admin-page-content">

    <?php if (!empty($_SESSION['flash_success'])): ?>
      <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4">
        ✅ <?= e($_SESSION['flash_success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4">
        ⚠️ <?= e($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
