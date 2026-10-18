<?php
// Expects $pageTitle to be set by the including page.
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'NRMS') ?> | <?= e(setting('site_name', 'Nominal Roll Management System')) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark nrms-navbar px-3">
  <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/dashboard.php">
    <i class="bi bi-shield-shaded"></i> NRMS <span class="fw-normal small">IGP-Secretariat</span>
  </a>
  <?php if ($user): ?>
  <div class="dropdown ms-auto">
    <button class="btn btn-outline-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
      <i class="bi bi-person-circle"></i> <?= e($user['full_name']) ?> <span class="badge bg-light text-dark"><?= e($user['role_name']) ?></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
      <li><a class="dropdown-item" href="<?= BASE_URL ?>/users/change_password.php">Change Password</a></li>
      <li><hr class="dropdown-divider"></li>
      <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php">Logout</a></li>
    </ul>
  </div>
  <?php endif; ?>
</nav>
<div class="d-flex">
  <?php if ($user) include __DIR__ . '/sidebar.php'; ?>
  <main class="flex-grow-1 p-4 nrms-main">
    <?php
      if ($msg = flash('success')) echo '<div class="alert alert-success alert-dismissible fade show">' . e($msg) . '<button class="btn-close" data-bs-dismiss="alert"></button></div>';
      if ($msg = flash('error')) echo '<div class="alert alert-danger alert-dismissible fade show">' . e($msg) . '<button class="btn-close" data-bs-dismiss="alert"></button></div>';
    ?>
