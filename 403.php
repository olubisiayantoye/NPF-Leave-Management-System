<?php
require_once __DIR__ . '/includes/functions.php';
start_secure_session();
$pageTitle = 'Access Denied';
include __DIR__ . '/includes/header.php';
?>
<div class="text-center py-5">
  <i class="bi bi-shield-x display-1 text-danger"></i>
  <h2 class="mt-3">Access Denied</h2>
  <p class="text-muted">You don't have permission to view this page. Contact your system administrator if you believe this is an error.</p>
  <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-primary">Back to Dashboard</a>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
