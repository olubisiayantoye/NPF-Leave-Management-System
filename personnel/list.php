<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_login();

$pdo = db();

$search = trim($_GET['q'] ?? '');
$deptFilter = $_GET['department_id'] ?? '';
$rankFilter = $_GET['rank_id'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$genderFilter = $_GET['gender'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
['limit' => $limit, 'offset' => $offset] = paginate($page, (int) setting('records_per_page', 20));

$where = ["p.status != 'Archived'"];
$params = [];

if ($search !== '') {
    $where[] = '(p.full_name LIKE ? OR p.ap_number LIKE ? OR p.employee_code LIKE ? OR p.phone_number LIKE ? OR p.ippis_number LIKE ?)';
    $like = "%$search%";
    array_push($params, $like, $like, $like, $like, $like);
}
if ($deptFilter !== '') { $where[] = 'p.department_id = ?'; $params[] = $deptFilter; }
if ($rankFilter !== '') { $where[] = 'p.rank_id = ?'; $params[] = $rankFilter; }
if ($statusFilter !== '') { $where[] = 'p.status = ?'; $params[] = $statusFilter; }
if ($genderFilter !== '') { $where[] = 'p.gender = ?'; $params[] = $genderFilter; }

$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) c FROM personnel p WHERE $whereSql");
$countStmt->execute($params);
$total = $countStmt->fetch()['c'];
$totalPages = max(1, ceil($total / $limit));

$stmt = $pdo->prepare(
    "SELECT p.*, d.name AS dept_name, r.name AS rank_name FROM personnel p
     LEFT JOIN departments d ON d.id = p.department_id
     LEFT JOIN ranks r ON r.id = p.rank_id
     WHERE $whereSql
     ORDER BY p.full_name ASC
     LIMIT $limit OFFSET $offset"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$statusBadge = [
    'Active' => 'badge-active', 'Retired' => 'badge-retired',
    'Transferred' => 'badge-transferred', 'Suspended' => 'badge-suspended', 'Archived' => 'badge-archived',
];

$pageTitle = 'Personnel';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Personnel (<?= $total ?>)</h4>
  <?php if (in_array($user['role_name'], ['Super Administrator','Administrator','Documentation Officer'])): ?>
  <a href="<?= BASE_URL ?>/personnel/add.php" class="btn btn-primary btn-sm"><i class="bi bi-person-plus"></i> Register Personnel</a>
  <?php endif; ?>
</div>

<form method="get" class="card mb-3">
  <div class="card-body row g-2">
    <div class="col-md-4">
      <input type="text" name="q" class="form-control" placeholder="Search name, AP number, employee code, phone, IPPIS..." value="<?= e($search) ?>">
    </div>
    <div class="col-md-2">
      <select name="department_id" class="form-select">
        <option value="">All Departments</option>
        <?php foreach (lookup('departments') as $d): ?>
          <option value="<?= $d['id'] ?>" <?= $deptFilter == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="rank_id" class="form-select">
        <option value="">All Ranks</option>
        <?php foreach (lookup('ranks') as $r): ?>
          <option value="<?= $r['id'] ?>" <?= $rankFilter == $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="status" class="form-select">
        <option value="">All Status</option>
        <?php foreach (['Active','Retired','Transferred','Suspended'] as $s): ?>
          <option value="<?= $s ?>" <?= $statusFilter === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-1">
      <select name="gender" class="form-select">
        <option value="">Sex</option>
        <option value="Male" <?= $genderFilter === 'Male' ? 'selected' : '' ?>>Male</option>
        <option value="Female" <?= $genderFilter === 'Female' ? 'selected' : '' ?>>Female</option>
      </select>
    </div>
    <div class="col-md-1"><button class="btn btn-primary w-100"><i class="bi bi-search"></i></button></div>
  </div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr><th></th><th>AP Number</th><th>Full Name</th><th>Rank</th><th>Department</th><th>Phone</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $p): ?>
        <tr>
          <td><img src="<?= e(url($p['photo_path'] ?: '/uploads/officer.png')) ?>" class="photo-thumb" style="width:40px;height:40px" onerror="this.src='https://via.placeholder.com/40?text=No+Photo'"></td>
          <td><?= e($p['ap_number']) ?></td>
          <td><a href="<?= BASE_URL ?>/personnel/view.php?id=<?= $p['id'] ?>"><?= e($p['full_name']) ?></a></td>
          <td><?= e($p['rank_name']) ?></td>
          <td><?= e($p['dept_name']) ?></td>
          <td><?= e($p['phone_number']) ?></td>
          <td><span class="badge <?= $statusBadge[$p['status']] ?? 'bg-secondary' ?>"><?= e($p['status']) ?></span></td>
          <td class="text-end">
            <a href="<?= BASE_URL ?>/personnel/view.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
            <?php if (in_array($user['role_name'], ['Super Administrator','Administrator','Documentation Officer'])): ?>
            <a href="<?= BASE_URL ?>/personnel/edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <?php endif; ?>
            <?php if (in_array($user['role_name'], ['Super Administrator','Administrator'])): ?>
            <a href="<?= BASE_URL ?>/personnel/delete.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Archive this personnel record?"><i class="bi bi-archive"></i></a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-4">No personnel found matching your filters.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($totalPages > 1): ?>

<nav class="mt-3">
    <?php
    $linksPerRow = 20;
    $totalRows = ceil($totalPages / $linksPerRow);

    for ($row = 0; $row < $totalRows; $row++):
        $start = ($row * $linksPerRow) + 1;
        $end   = min($start + $linksPerRow - 1, $totalPages);
    ?>

        <ul class="pagination justify-content-center mb-1">

            <?php for ($i = $start; $i <= $end; $i++): ?>

                <?php
                $qs = $_GET;
                $qs['page'] = $i;
                ?>

                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query($qs) ?>">
                        <?= $i ?>
                    </a>
                </li>

            <?php endfor; ?>

        </ul>

    <?php endfor; ?>
</nav>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
