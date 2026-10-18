<?php
require_once __DIR__ . '/config/config.php';

// Illustrative dashboard figures for the landing page preview only —
// the real numbers are pulled live from the database once inside the app.
$stats = [
    ['label' => 'Total Personnel',     'value' => '12,450'],
    ['label' => 'Currently On Leave',  'value' => '684'],
    ['label' => 'Pending Applications','value' => '126'],
    ['label' => 'Resuming Today',      'value' => '18'],
    ['label' => 'Overdue',             'value' => '7'],
];

$roles = [
    [
        'code' => '01',
        'title' => 'I-Admin',
        'desc'  => 'Receives the officer\'s leave request, finds their record, fills the form, and submits it — in minutes instead of a paper form by hand.',
    ],
    [
        'code' => '02',
        'title' => 'Administration Officer',
        'desc'  => 'Reviews what the I-Admin submitted, records a recommendation and remarks, and forwards it to a specific PSO for the final decision.',
    ],
    [
        'code' => '03',
        'title' => 'PSO I / PSO II',
        'desc'  => 'Represents the Head of Department — gives the final Approve or Disapprove, one click, with the full record and recommendation on screen.',
    ],
    [
        'code' => '04',
        'title' => 'Super Administrator',
        'desc'  => 'Registers personnel, configures leave types, entitlements and public holidays, and has full visibility across every department and command.',
    ],
];

$workflow = [
    ['n' => '01', 't' => 'Personnel Registered',  'd' => 'F-AP number, rank, name and department entered once, centrally.'],
    ['n' => '02', 't' => 'I-Admin Submits',        'd' => 'Officer\'s request found, form completed, status set to Pending Review.'],
    ['n' => '03', 't' => 'Administration Officer Reviews', 'd' => 'Recommendation and remarks recorded, forwarded to a specific PSO.'],
    ['n' => '04', 't' => 'PSO Decides',             'd' => 'Full history and recommendation on one screen — Approve or Disapprove.'],
    ['n' => '05', 't' => 'Signal Generated',        'd' => 'Approval produces a printable Leave Approval Signal — automatically.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= APP_NAME ?> · <?= APP_AGENCY ?></title>
<meta name="description" content="The Nigeria Police Force Leave Management System — digitising leave application, approval and signal generation.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Spectral:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= base_url('assets/css/landing.css') ?>">
</head>
<body>

<div class="ruled-overlay" aria-hidden="true"></div>

<header class="site-header">
    <div class="wrap header-inner">
        <a class="brand" href="<?= base_url('/') ?>">
            <span class="brand-mark">✺</span>
            <span class="brand-text">
                <strong>NPF&nbsp;·&nbsp;LMS</strong>
                <small>Leave Management System</small>
            </span>
        </a>
        <nav class="nav" aria-label="Primary">
            <a href="#workflow">Workflow</a>
            <a href="#roles">Roles</a>
            <a href="#reports">Reports</a>
        </nav>
        <a class="btn btn-ghost" href="<?= base_url('auth/login.php') ?>">Sign in</a>
    </div>
</header>

<main>

<!-- ===================== HERO ===================== -->
<section class="hero">
    <div class="wrap hero-inner">
        <div class="hero-copy">
            <p class="eyebrow">Nigeria Police Force · Force Headquarters, Abuja</p>
            <h1>From application<br>to approved <span class="ink-underline">signal.</span></h1>
            <p class="lede">
                The manual leave form, digitised end to end. An I-Admin submits
                on an officer's behalf, an Administration Officer reviews and
                recommends, a PSO gives the final decision, and an approved
                application produces its Leave Approval Signal automatically —
                no retyping, no lost paperwork, no guessing at status.
            </p>
            <div class="hero-actions">
                <a href="<?= base_url('auth/login.php') ?>" class="btn btn-gold">Sign in to NPF‑LMS</a>
                <a href="#workflow" class="btn btn-outline">See how it works</a>
            </div>
            <p class="hero-note">Pure PHP · MySQL · Role‑based access · Full audit trail</p>
        </div>

        <div class="hero-signal" aria-hidden="true">
            <div class="signal-card">
                <div class="signal-head">
                    <span>NIGERIA POLICE FORCE</span>
                    <span>FORCE HEADQUARTERS, ABUJA</span>
                </div>
                <div class="signal-title">LEAVE APPROVAL SIGNAL</div>
                <dl class="signal-grid">
                    <div><dt>F‑AP NO</dt><dd>315443</dd></div>
                    <div><dt>RANK</dt><dd>Inspector</dd></div>
                    <div><dt>DEPARTMENT</dt><dd>IGP Secretariat</dd></div>
                    <div><dt>LEAVE TYPE</dt><dd>Annual — 30 Days</dd></div>
                    <div><dt>PROCEEDING</dt><dd>26/08/2026</dd></div>
                    <div><dt>DUE TO RESUME</dt><dd>07/10/2026</dd></div>
                </dl>
                <div class="signal-ref">NPF‑LV‑2026‑000001</div>
                <div class="stamp">APPROVED</div>
            </div>
        </div>
    </div>
</section>

<!-- ===================== STATS STRIP ===================== -->
<section class="stats-strip">
    <div class="wrap stats-grid">
        <?php foreach ($stats as $s): ?>
        <div class="stat">
            <span class="stat-value"><?= htmlspecialchars($s['value']) ?></span>
            <span class="stat-label"><?= htmlspecialchars($s['label']) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ===================== ADVERT ===================== -->
<section class="advert-wrap">
    <div class="wrap">
        <div class="advert-banner" id="advert-banner">
            <button type="button" class="advert-close" aria-label="Dismiss advertisement" onclick="document.getElementById('advert-banner').style.display='none'">&times;</button>
            <span class="advert-tag">Advertisement</span>
            <div class="advert-body">
                <div class="advert-copy">
                    <div class="advert-eyebrow">NPF Cooperative &amp; Thrift Society</div>
                    <h3>Save with your colleagues. Borrow against your service.</h3>
                    <p>Payroll-deducted savings, low-interest personnel loans, and an annual dividend — open to all serving officers, no paperwork beyond your F‑AP number.</p>
                </div>
                <a href="#" class="btn btn-outline advert-cta">Learn More</a>
            </div>
        </div>
    </div>
</section>

<!-- ===================== WORKFLOW ===================== -->
<section class="workflow" id="workflow">
    <div class="wrap">
        <p class="eyebrow">How it moves</p>
        <h2>One record. One route. One printable outcome.</h2>
        <div class="workflow-track">
            <?php foreach ($workflow as $step): ?>
            <div class="workflow-step">
                <span class="workflow-n"><?= $step['n'] ?></span>
                <h3><?= htmlspecialchars($step['t']) ?></h3>
                <p><?= htmlspecialchars($step['d']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===================== ROLES ===================== -->
<section class="roles" id="roles">
    <div class="wrap">
        <p class="eyebrow">Built around who does what</p>
        <h2>Four roles. Nothing more than each one needs.</h2>
        <div class="roles-grid">
            <?php foreach ($roles as $r): ?>
            <div class="role-card">
                <span class="role-code"><?= $r['code'] ?></span>
                <h3><?= htmlspecialchars($r['title']) ?></h3>
                <p><?= htmlspecialchars($r['desc']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===================== REPORTS / CTA ===================== -->
<section class="cta" id="reports">
    <div class="wrap cta-inner">
        <div>
            <p class="eyebrow">Reports, calendar, audit trail</p>
            <h2>Every leave, accounted for — and every action, logged.</h2>
            <p class="lede">
                Leave calendars filterable by department, rank and status. Balance,
                overdue and resumption reports exportable to PDF or CSV. Every login,
                submission, approval and print recorded to an audit log that never
                gets deleted.
            </p>
        </div>
        <a href="<?= base_url('auth/login.php') ?>" class="btn btn-gold">Sign in to NPF‑LMS</a>
    </div>
</section>

</main>

<footer class="site-footer">
    <div class="wrap footer-inner">
        <div>
            <strong>Nigeria Police Force</strong>
            <span>Force Headquarters, Abuja</span>
        </div>
        <div class="footer-meta">
            <?= APP_SHORT_NAME ?> v<?= APP_VERSION ?> — for official use only
        </div>
    </div>
</footer>

<script src="<?= base_url('assets/js/landing.js') ?>"></script>
</body>
</html>
