<?php
require_once __DIR__ . '/includes/functions.php';
start_secure_session();

$featuredStaff = db()->query(
    "SELECT p.id, p.full_name, p.photo_path, r.name AS rank_name, d.name AS dept_name
     FROM featured_staff fs
     JOIN personnel p ON p.id = fs.personnel_id
     LEFT JOIN ranks r ON r.id = p.rank_id
     LEFT JOIN departments d ON d.id = p.department_id
     WHERE p.status != 'Archived'
     ORDER BY fs.display_order ASC, p.full_name ASC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e(setting('site_name', 'Nominal Roll Management System')) ?> | IGP-Secretariat</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark nrms-navbar px-3">
  <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/"><i class="bi bi-shield-shaded"></i> NRMS</a>
  <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-light btn-sm ms-auto">Staff Login</a>
</nav>

<style>
  .landing-hero {
    background: linear-gradient(135deg, #0d47a1 0%, #1565c0 50%, #1976d2 100%);
    min-height: 75vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 2rem 1.5rem;
    position: relative;
    overflow: hidden;
  }

  .landing-hero::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 120px;
    background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='%230a3d8f' fill-opacity='0.3' d='M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E");
    background-size: cover;
    background-position: center bottom;
    
    /* --- FIX: Allows clicks to pass through the wave to the button --- */
    pointer-events: none; 
    z-index: 0;
  }

  /* Ensure all actual content sits above the decorative wave */
  .landing-hero > * {
    position: relative;
    z-index: 1;
  }

  .landing-logo {
    max-width: 150px;
    height: auto;
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.3));
    animation: float 3s ease-in-out infinite;
  }

  @keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-8px); }
  }

  .landing-hero h1 {
    color: #ffffff;
    font-size: 2.5rem;
    font-weight: 700;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    letter-spacing: -0.5px;
  }

  .landing-hero .lead {
    color: #ffffff;
    font-size: 1.25rem;
    font-weight: 500;
    margin-bottom: 1rem;
    opacity: 0.95;
  }

  .landing-hero .text-white-50 {
    color: rgba(255, 255, 255, 0.85) !important;
    font-size: 1.05rem;
    max-width: 650px;
    margin: 0 auto 2rem;
    line-height: 1.6;
  }

  .landing-hero .btn-warning {
    background: linear-gradient(135deg, #ffd700 0%, #ffc400 100%);
    border: none;
    color: #0d47a1;
    padding: 0.9rem 2.5rem;
    font-size: 1.15rem;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
  }

  .landing-hero .btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 215, 0, 0.6);
    background: linear-gradient(135deg, #ffe033 0%, #ffd700 100%);
    color: #0a3d8f;
  }

  .landing-hero .btn-warning:active {
    transform: translateY(1px);
    box-shadow: 0 2px 10px rgba(255, 215, 0, 0.5);
    background: linear-gradient(135deg, #e6c200 0%, #d4a800 100%);
  }

  .landing-hero .btn-warning:focus {
    outline: 3px solid rgba(255, 215, 0, 0.5);
    outline-offset: 3px;
  }

  .landing-hero .btn-warning i {
    font-size: 1.2rem;
    transition: transform 0.3s ease;
  }

  .landing-hero .btn-warning:hover i {
    transform: translateX(5px);
  }

  @media (max-width: 768px) {
    .landing-hero {
      min-height: 70vh;
      padding: 1.5rem 1rem;
    }

    .landing-hero h1 {
      font-size: 1.75rem;
      margin-top: 1rem;
    }
    
    .landing-hero .lead {
      font-size: 1rem;
    }
    
    .landing-hero .text-white-50 {
      font-size: 0.95rem;
    }
    
    .landing-logo {
      max-width: 120px;
    }

    .landing-hero .btn-warning {
      padding: 0.8rem 2rem;
      font-size: 1.05rem;
    }
  }
</style>

<section class="landing-hero text-center">
  <img src="<?= BASE_URL ?>/assets/images/nigeria-police-logo.png"
       alt="Nigeria Police Force Logo"
       class="landing-logo">

  <h1>
    <?= e(setting('site_name', 'Nominal Roll Management System')) ?>
  </h1>

  <p class="lead">
    Office of the Inspector General of Police Secretariat &mdash; Nigeria Police Force
  </p>

  <p class="text-white-50">
    A secure, centralized platform for documenting, verifying, and reporting on
    personnel attached to the IGP-Secretariat and its associated units.
  </p>

  <a href="<?= BASE_URL ?>/login.php"
     class="btn btn-lg btn-warning fw-semibold">
    Staff Login <i class="bi bi-arrow-right"></i>
  </a>
</section>

<?php if ($featuredStaff): ?>
<section class="container-fluid py-5" style="background-color: #bed9ff;">
  <div class="container">
    <h4 class="text-center mb-4">Leadership</h4>

    <div class="row g-4 justify-content-center">
      <?php foreach ($featuredStaff as $fs): ?>
      <div class="col-6 col-md-3 text-center">
        <img src="<?= e(url($fs['photo_path'] ?: '/uploads/officer.png')) ?>"
             class="rounded-circle mb-2 border"
             style="width:120px;height:120px;object-fit:cover;">

        <div class="fw-semibold"><?= e($fs['full_name']) ?></div>
        <div class="text-muted small"><?= e($fs['rank_name']) ?></div>

        <?php if ($fs['dept_name']): ?>
          <div class="text-muted small"><?= e($fs['dept_name']) ?></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="container py-5">
  <div class="row g-4 text-center">
    <div class="col-md-4">
      <i class="bi bi-database-check display-5 text-primary"></i>
      <h5 class="mt-3">Centralized Records</h5>
      <p class="text-muted small">One accurate, auditable nominal roll replacing manual paper files across every unit.</p>
    </div>
    <div class="col-md-4">
      <i class="bi bi-patch-check display-5 text-primary"></i>
      <h5 class="mt-3">Fast Verification</h5>
      <p class="text-muted small">Verify any officer by AP number, name, or IPPIS number in seconds, complete with photo match.</p>
    </div>
    <div class="col-md-4">
      <i class="bi bi-bar-chart-line display-5 text-primary"></i>
      <h5 class="mt-3">Instant Reporting</h5>
      <p class="text-muted small">Generate nominal rolls and management reports, export to Excel or PDF, on demand.</p>
    </div>
  </div>
</section>

<section class="bg-light py-5">
  <div class="container">
    <h4 class="text-center mb-4">Documentation Units Covered</h4>
    <div class="row row-cols-2 row-cols-md-4 g-2 small text-muted">
      <?php
      $units = ['IGP Secretariat','ONSA','NRS','IGP Body Guards','Protocol','Escort','NASS','AM CON',
                'NAPTIP','PMU','STFPIB','JAMB','ICPC','EFCC','NAFDAC','SEC','Civilian Staff','TIU',
                'IGP Project Monitoring Unit','IGP Aides','POWA','NPF Trust Fund','PSIU','IGP Desk','IGP Guard House'];
      foreach ($units as $u) echo '<div class="col"><i class="bi bi-dot"></i>' . e($u) . '</div>';
      ?>
    </div>
  </div>
</section>

<footer class="text-center text-muted small py-4">
  &copy; <?= date('Y') ?> Nigeria Police Force &mdash; IGP-Secretariat. All rights reserved.
</footer>
</body>
</html>
