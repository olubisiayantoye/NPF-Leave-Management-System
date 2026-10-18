<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('personnel.search');

// Server-rendered search: works with JavaScript off, and — unlike the old
// version of this page — doesn't depend on a PHP-computed BASE_URL being
// correct in the browser, since the form just does a normal GET to itself.
$q = trim($_GET['q'] ?? '');
$results = $q !== '' ? search_personnel($q, 30) : [];

$statusBadgeClass = ['active' => 'success', 'on_leave' => 'warning', 'suspended' => 'secondary', 'retired' => 'secondary'];

$pageTitle = 'Find Personnel';
require __DIR__ . '/../includes/header.php';
?>

<p class="text-muted">Search by F‑AP Number, name, rank, or department. Select a record to view the profile and, from there, apply for leave.</p>

<form method="get" action="search.php" class="mb-3" style="max-width: 480px;">
    <div class="input-group input-group-lg">
        <input type="text" name="q" id="search-input" class="form-control"
               placeholder="e.g. 315443, Adeyemi, Inspector, IGP Secretariat"
               autocomplete="off" autofocus value="<?= e($q) ?>">
        <button class="btn btn-npf-gold" type="submit"><i class="bi bi-search"></i></button>
    </div>
</form>

<div id="search-status" class="text-muted small mb-2">
    <?php if ($q !== '' && mb_strlen($q) < 2): ?>
        Type at least 2 characters to search.
    <?php elseif ($q !== '' && !$results): ?>
        No matching personnel found for "<?= e($q) ?>".
    <?php elseif ($results): ?>
        <?= count($results) ?> result(s) for "<?= e($q) ?>".
    <?php endif; ?>
</div>

<div class="table-responsive">
<table class="table table-hover bg-white shadow-sm align-middle" id="results-table" <?= $results ? '' : 'style="display:none;"' ?>>
    <thead>
        <tr>
            <th>F‑AP No.</th>
            <th>Name</th>
            <th>Rank</th>
            <th>Department</th>
            <th>Status</th>
            <th></th>
        </tr>
    </thead>
    <tbody id="results-body">
        <?php foreach ($results as $p): ?>
        <tr>
            <td class="font-monospace"><?= e($p['f_ap_number']) ?></td>
            <td><?= e($p['name']) ?></td>
            <td><?= e($p['rank']) ?></td>
            <td><?= e($p['department']) ?></td>
            <td><span class="badge text-bg-<?= e($statusBadgeClass[$p['status']] ?? 'secondary') ?>"><?= e(str_replace('_', ' ', $p['status'])) ?></span></td>
            <td class="text-end"><a href="view.php?id=<?= (int) $p['id'] ?>" class="btn btn-sm btn-npf-gold">View / Apply for Leave</a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<script>
// Progressive enhancement only — the page above already works with this
// script disabled or failing. This just makes results update as you type,
// without a full page reload, via a path relative to this page (so it
// can't break even if BASE_URL is ever misconfigured).
(function () {
    const input = document.getElementById('search-input');
    const status = document.getElementById('search-status');
    const table = document.getElementById('results-table');
    const body = document.getElementById('results-body');
    const statusLabels = { active: 'success', on_leave: 'warning', suspended: 'secondary', retired: 'secondary' };
    let debounceTimer;
    let currentRequest = 0;

    function el(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    async function runSearch(q) {
        if (q.length < 2) {
            table.style.display = 'none';
            status.textContent = q.length === 0 ? '' : 'Type at least 2 characters to search.';
            return;
        }
        const requestId = ++currentRequest;
        status.textContent = 'Searching…';
        try {
            const res = await fetch('../api/personnel_search.php?q=' + encodeURIComponent(q));
            if (requestId !== currentRequest) return; // a newer keystroke already superseded this request
            if (!res.ok) throw new Error('Search failed');
            const data = await res.json();

            body.textContent = '';
            if (!data.results.length) {
                table.style.display = 'none';
                status.textContent = 'No matching personnel found.';
                return;
            }

            data.results.forEach(p => {
                const tr = document.createElement('tr');
                tr.appendChild(el('td', 'font-monospace', p.f_ap_number));
                tr.appendChild(el('td', null, p.name));
                tr.appendChild(el('td', null, p.rank));
                tr.appendChild(el('td', null, p.department));

                const statusTd = el('td');
                const badge = el('span', 'badge text-bg-' + (statusLabels[p.status] || 'secondary'), String(p.status).replace('_', ' '));
                statusTd.appendChild(badge);
                tr.appendChild(statusTd);

                const actionTd = el('td', 'text-end');
                const link = el('a', 'btn btn-sm btn-npf-gold', 'View / Apply for Leave');
                link.href = 'view.php?id=' + encodeURIComponent(p.id);
                actionTd.appendChild(link);
                tr.appendChild(actionTd);

                body.appendChild(tr);
            });
            table.style.display = '';
            status.textContent = data.results.length + ' result(s).';
        } catch (err) {
            if (requestId !== currentRequest) return;
            status.textContent = 'Something went wrong searching — please try again.';
        }
    }

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => runSearch(input.value.trim()), 250);
    });
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
