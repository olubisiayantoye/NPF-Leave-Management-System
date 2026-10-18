<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_permission('personnel.manage');

/*
|--------------------------------------------------------------------------
| Get all active personnel
|--------------------------------------------------------------------------
*/
$stmt = db()->prepare("
    SELECT
        p.id,
        p.f_ap_number,
        p.first_name,
        p.surname,
        p.status,
        r.name AS rank_name,
        d.name AS department_name
    FROM personnel p
    LEFT JOIN ranks r ON r.id = p.rank_id
    LEFT JOIN departments d ON d.id = p.department_id
    WHERE p.status = :status
    ORDER BY p.surname ASC, p.first_name ASC
");

$stmt->execute([
    'status' => 'active'
]);

$rows = $stmt->fetchAll();

$pageTitle = 'Active Personnel';

require __DIR__ . '/../includes/header.php';
?>

<!-- DataTables CSS -->
<link
    rel="stylesheet"
    href="https://cdn.datatables.net/2.3.3/css/dataTables.dataTables.min.css"
>

<style>
    /* Personnel DataTable */
    .personnel-card {
        border: 0;
        border-radius: 14px;
        overflow: hidden;
    }

    .personnel-card .card-header {
        background: #ffffff;
        border-bottom: 1px solid #e9ecef;
        padding: 18px 20px;
    }

    .personnel-title {
        font-weight: 700;
        margin: 0;
    }

    .personnel-subtitle {
        font-size: 13px;
        color: #6c757d;
        margin-top: 3px;
    }

    #personnelTable {
        width: 100% !important;
    }

    #personnelTable thead th {
        background: #212529;
        color: #ffffff;
        font-weight: 600;
        font-size: 13px;
        white-space: nowrap;
        padding: 13px 12px;
    }

    #personnelTable tbody td {
        padding: 12px;
        vertical-align: middle;
        font-size: 14px;
    }

    #personnelTable tbody tr:hover {
        background-color: #f8f9fa;
    }

    .fap-number {
        font-family: monospace;
        font-weight: 600;
    }

    .personnel-name {
        font-weight: 600;
    }

    .status-active {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 20px;
        background: #d1e7dd;
        color: #0f5132;
        font-size: 12px;
        font-weight: 600;
        text-transform: capitalize;
    }

    .action-buttons {
        white-space: nowrap;
    }

    .action-buttons .btn {
        margin-left: 3px;
    }

    /* DataTables controls */
    .dt-container {
        padding: 0;
    }

    .dt-layout-row {
        padding: 12px 15px;
    }

    .dt-search input {
        border: 1px solid #ced4da;
        border-radius: 8px;
        padding: 7px 12px;
        margin-left: 5px;
    }

    .dt-length select {
        border: 1px solid #ced4da;
        border-radius: 7px;
        padding: 5px 28px 5px 8px;
    }

    .dt-paging .dt-paging-button {
        border-radius: 6px !important;
        margin: 2px;
    }

    .dt-info {
        color: #6c757d;
        font-size: 13px;
    }

    @media (max-width: 768px) {

        .personnel-card .card-header {
            padding: 15px;
        }

        .personnel-card .card-header .d-flex {
            flex-direction: column;
            align-items: stretch !important;
            gap: 12px;
        }

        .personnel-card .card-header .btn {
            width: 100%;
        }

        #personnelTable thead th,
        #personnelTable tbody td {
            font-size: 12px;
            padding: 9px;
        }
    }
</style>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-3">

    <div>
        <h4 class="mb-1">
            <i class="bi bi-people"></i>
            Active Personnel
        </h4>

        <div class="text-muted small">
            All currently active personnel registered in the system
        </div>
    </div>

    <div class="d-flex gap-2">

        <a
            href="<?= base_url('personnel/create.php') ?>"
            class="btn btn-npf-gold"
        >
            <i class="bi bi-person-plus"></i>
            Register Personnel
        </a>

        <a
            href="<?= base_url('personnel/bulk_upload.php') ?>"
            class="btn btn-outline-dark"
        >
            <i class="bi bi-upload"></i>
            Bulk Upload
        </a>

    </div>

</div>


<!-- Personnel Card -->
<div class="card shadow-sm personnel-card">

    <div class="card-header">

        <div class="d-flex justify-content-between align-items-center">

            <div>
                <div class="personnel-title">
                    Personnel Register
                </div>

                <div class="personnel-subtitle">
                    <?= count($rows) ?> active personnel
                </div>
            </div>

            <div>
                <span class="badge bg-success">
                    <i class="bi bi-check-circle"></i>
                    Active
                </span>
            </div>

        </div>

    </div>


    <div class="card-body p-0">

        <div class="table-responsive">

            <table
                id="personnelTable"
                class="table table-hover mb-0"
            >

                <thead>

                    <tr>

                        <th>#</th>

                        <th>F-AP No.</th>

                        <th>Name</th>

                        <th>Rank</th>

                        <th>Department</th>

                        <th>Status</th>

                        <th>Action</th>

                    </tr>

                </thead>

                <tbody>

                <?php foreach ($rows as $index => $p): ?>

                    <tr>

                        <!-- Number -->
                        <td>
                            <?= $index + 1 ?>
                        </td>


                        <!-- F-AP Number -->
                        <td>
                            <span class="fap-number">
                                <?= e($p['f_ap_number']) ?>
                            </span>
                        </td>


                        <!-- Name -->
                        <td>
                            <span class="personnel-name">
                                <?= e(
                                    $p['surname'] . ' ' . $p['first_name']
                                ) ?>
                            </span>
                        </td>


                        <!-- Rank -->
                        <td>
                            <?= e($p['rank_name'] ?? 'N/A') ?>
                        </td>


                        <!-- Department -->
                        <td>
                            <?= e($p['department_name'] ?? 'N/A') ?>
                        </td>


                        <!-- Status -->
                        <td>

                            <span class="status-active">
                                <i class="bi bi-check-circle"></i>
                                Active
                            </span>

                        </td>


                        <!-- Actions -->
                        <td class="action-buttons">

                            <a
                                href="<?= base_url(
                                    'personnel/view.php?id=' . $p['id']
                                ) ?>"
                                class="btn btn-sm btn-outline-dark"
                                title="View Personnel"
                            >
                                <i class="bi bi-eye"></i>
                                View
                            </a>

                            <a
                                href="<?= base_url(
                                    'personnel/edit.php?id=' . $p['id']
                                ) ?>"
                                class="btn btn-sm btn-outline-dark"
                                title="Edit Personnel"
                            >
                                <i class="bi bi-pencil"></i>
                                Edit
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>


                <?php if (!$rows): ?>

                    <tr>

                        <td
                            colspan="7"
                            class="text-center text-muted py-5"
                        >

                            <i
                                class="bi bi-people"
                                style="font-size: 40px;"
                            ></i>

                            <div class="mt-2">
                                No active personnel found.
                            </div>

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>


<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- DataTables JS -->
<script
    src="https://cdn.datatables.net/2.3.3/js/dataTables.min.js"
></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    new DataTable('#personnelTable', {

        pageLength: 20,

        lengthMenu: [
            [10, 20, 50, 100, -1],
            [10, 20, 50, 100, 'All']
        ],

        order: [
            [2, 'asc']
        ],

        columnDefs: [
            {
                orderable: false,
                searchable: false,
                targets: [0, 6]
            }
        ],

        language: {
            search: 'Search personnel:',
            searchPlaceholder: 'F-AP No., name, rank...',
            lengthMenu: 'Show _MENU_ personnel',
            info: 'Showing _START_ to _END_ of _TOTAL_ personnel',
            infoEmpty: 'No personnel available',
            zeroRecords: 'No matching personnel found',
            emptyTable: 'No active personnel found'
        }
    });

});
</script>


<?php require __DIR__ . '/../includes/footer.php'; ?>

