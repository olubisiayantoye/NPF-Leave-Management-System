<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_login();

$pdo = db();

$search = trim($_GET['q'] ?? '');
$sectionFilter = trim($_GET['section'] ?? '');
$bankFilter = $_GET['bank_id'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
['limit' => $limit, 'offset' => $offset] = paginate($page, (int) setting('records_per_page', 20));

$where = ["1=1"];
$params = [];

if ($search !== '') {
    $where[] = '(a.fap_number LIKE ? OR a.full_name LIKE ? OR a.employee_code LIKE ? OR a.account_number LIKE ?)';
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like);
}
if ($sectionFilter !== '') { $where[] = 'a.section = ?'; $params[] = $sectionFilter; }
if ($bankFilter !== '') { $where[] = 'a.bank_id = ?'; $params[] = $bankFilter; }
if ($statusFilter !== '') { $where[] = 'a.status = ?'; $params[] = $statusFilter; }

$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) c FROM monthly_allowance_personnel a WHERE $whereSql");
$countStmt->execute($params);
$total = $countStmt->fetch()['c'];
$totalPages = max(1, ceil($total / $limit));

$stmt = $pdo->prepare(
    "SELECT a.*, r.name AS rank_name, b.name AS bank_name FROM monthly_allowance_personnel a
     LEFT JOIN ranks r ON r.id = a.rank_id
     LEFT JOIN banks b ON b.id = a.bank_id
     WHERE $whereSql
     ORDER BY a.full_name ASC
     LIMIT $limit OFFSET $offset"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$sections = $pdo->query(
    "SELECT DISTINCT section FROM monthly_allowance_personnel WHERE section IS NOT NULL AND section != '' ORDER BY section"
)->fetchAll();

$pageTitle = 'Monthly Allowance';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Monthly Allowance Personnel (<?= $total ?>)</h4>
  <div>
    <a href="<?= BASE_URL ?>/allowance/report.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-earmark-text"></i> Reports</a>
    <?php if (in_array($user['role_name'], ['Super Administrator','Administrator'])): ?>
    <a href="<?= BASE_URL ?>/allowance/bulk_upload.php" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel"></i> Bulk Upload</a>
    <?php endif; ?>
    <?php if (in_array($user['role_name'], ['Super Administrator','Administrator','Documentation Officer'])): ?>
    <a href="<?= BASE_URL ?>/allowance/add.php" class="btn btn-primary btn-sm"><i class="bi bi-person-plus"></i> Register Personnel</a>
    <?php endif; ?>
  </div>
</div>

<form method="get" class="card mb-3">
  <div class="card-body row g-2">
    <div class="col-md-4">
      <input type="text" name="q" class="form-control" placeholder="Search F-AP Number, Name, Employee Code, Account Number..." value="<?= e($search) ?>">
    </div>
    <div class="col-md-3">
      <select name="section" class="form-select">
        <option value="">All Sections</option>
        <?php foreach ($sections as $s): ?>
          <option value="<?= e($s['section']) ?>" <?= $sectionFilter === $s['section'] ? 'selected' : '' ?>><?= e($s['section']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <select name="bank_id" class="form-select">
        <option value="">All Banks</option>
        <?php foreach (lookup('banks') as $b): ?>
          <option value="<?= $b['id'] ?>" <?= $bankFilter == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="status" class="form-select">
        <option value="">All Status</option>
        <option value="Active" <?= $statusFilter === 'Active' ? 'selected' : '' ?>>Active</option>
        <option value="Inactive" <?= $statusFilter === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
      </select>
    </div>
    <div class="col-md-12"><button class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Filter</button></div>
  </div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr><th>F-AP Number</th><th>Rank</th><th>Name</th><th>Account No.</th><th>Bank</th><th>Employee Code</th><th>Section</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $a): ?>
        <tr>
          <td><?= e($a['fap_number']) ?></td>
          <td><?= e($a['rank_name']) ?></td>
          <td><a href="<?= BASE_URL ?>/allowance/view.php?id=<?= $a['id'] ?>"><?= e($a['full_name']) ?></a></td>
          <td><?= e($a['account_number']) ?></td>
          <td><?= e($a['bank_name']) ?></td>
          <td><?= e($a['employee_code']) ?></td>
          <td><?= e($a['section']) ?></td>
          <td><span class="badge <?= $a['status'] === 'Active' ? 'badge-active' : 'bg-secondary' ?>"><?= e($a['status']) ?></span></td>
          <td class="text-end text-nowrap">
            <a href="<?= BASE_URL ?>/allowance/view.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
            <?php if (in_array($user['role_name'], ['Super Administrator','Administrator','Documentation Officer'])): ?>
            <a href="<?= BASE_URL ?>/allowance/edit.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <?php endif; ?>
            <?php if (in_array($user['role_name'], ['Super Administrator','Administrator'])): ?>
            <a href="<?= BASE_URL ?>/allowance/delete.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Delete this allowance record? This cannot be undone."><i class="bi bi-trash"></i></a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="9" class="text-center text-muted py-4">No allowance records found matching your filters.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-3"><ul class="pagination justify-content-center">
  <?php for ($i = 1; $i <= $totalPages; $i++): $qs = $_GET; $qs['page'] = $i; ?>
    <li class="page-item <?= $i == $page ? 'active' : '' ?>"><a class="page-link" href="?<?= http_build_query($qs) ?>"><?= $i ?></a></li>
  <?php endfor; ?>
</ul></nav>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
