<?php
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare(
    "SELECT p.*, d.name AS dept_name, r.name AS rank_name FROM personnel p
     LEFT JOIN departments d ON d.id = p.department_id
     LEFT JOIN ranks r ON r.id = p.rank_id
     WHERE p.id = ?"
);
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { die('Personnel record not found.'); }
audit_log('VERIFICATION_SLIP_PRINTED', 'personnel', $id);

// Centralized NPF Logo path
$logo_url = url('assets/images/policelogo.jpeg'); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verification Slip &mdash; <?= e($p['full_name']) ?></title>
<style>
  * { box-sizing: border-box; }
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f4f7f6;
    margin: 0;
    padding: 2rem;
    color: #333;
  }
  
  .no-print {
    text-align: center;
    margin-bottom: 1.5rem;
  }
  
  .btn-print {
    background-color: #0b2545;
    color: #ffffff;
    border: none;
    padding: 0.65rem 1.5rem;
    font-size: 0.95rem;
    font-weight: 600;
    border-radius: 4px;
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0,0,0,0.15);
    transition: background-color 0.2s ease;
  }

  .btn-print:hover {
    background-color: #134074;
  }

  /* Main Frame Container */
  .slip-container {
    position: relative;
    max-width: 560px;
    margin: 0 auto;
    background: #ffffff;
    border: 8px double #0b2545; /* Certificate-style outer frame */
    border-radius: 8px;
    padding: 2rem;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    overflow: hidden;
  }

  /* Background Image Watermark using $logo_url */
  .watermark {
    position: absolute;
    top: 55%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 320px;
    height: 320px;
    background-image: url('<?= e($logo_url) ?>');
    background-repeat: no-repeat;
    background-position: center;
    background-size: contain;
    opacity: 0.12; /* Slightly increased for sharp print dynamic */
    pointer-events: none;
    z-index: 1;

    /* Forces browser to output background image in PDF/Print */
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  .slip-content {
    position: relative;
    z-index: 2; /* Keeps all text above the watermark */
  }

  /* Header Section */
  .header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1.25rem;
    border-bottom: 2px solid #0b2545;
    padding-bottom: 1rem;
    margin-bottom: 1rem;
  }

  .header-logo {
    width: 75px;
    height: 75px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #0b2545;
    padding: 2px;
    background-color: #fff;
    flex-shrink: 0;
  }

  .header-title {
    text-align: left;
  }

  .header-title h2 {
    margin: 0;
    font-size: 1.15rem;
    color: #0b2545;
    letter-spacing: 0.5px;
    font-weight: 800;
  }

  .header-title h4 {
    margin: 3px 0;
    font-size: 0.85rem;
    color: #134074;
    font-weight: 700;
  }

  .header-title p {
    margin: 0;
    font-size: 0.75rem;
    color: #555;
    font-weight: 600;
  }

  .sub {
    text-align: center;
    font-size: 0.85rem;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #0b2545;
    background-color: #eef4fb;
    padding: 6px;
    border-radius: 4px;
    margin-bottom: 1.25rem;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  .profile-photo {
    display: block;
    margin: 0 auto 1.25rem;
    width: 125px;
    height: 125px;
    object-fit: cover;
    border-radius: 6px;
    border: 2px solid #0b2545;
    box-shadow: 0 3px 6px rgba(0,0,0,0.1);
  }

  /* Data Table */
  table {
    width: 100%;
    border-collapse: collapse;
  }

  th, td {
    padding: 8px 10px;
    font-size: 0.9rem;
  }

  th {
    text-align: left;
    width: 38%;
    color: #0b2545;
    font-weight: 700;
    border-bottom: 1px solid #eef2f5;
  }

  td {
    color: #222;
    border-bottom: 1px solid #eef2f5;
  }

  .status-badge {
    display: inline-block;
    padding: 2px 8px;
    font-size: 0.8rem;
    font-weight: bold;
    color: #0f5132;
    background-color: #d1e7dd;
    border-radius: 4px;
    text-transform: uppercase;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  .footer-seal {
    margin-top: 1.5rem;
    text-align: right;
    font-size: 0.75rem;
    color: #777;
    font-style: italic;
  }

  /* Specific Print Adjustments */
  @media print {
    body {
      background-color: #ffffff;
      padding: 0;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    .no-print {
      display: none;
    }
    .slip-container {
      box-shadow: none;
      border: 6px double #0b2545;
      margin: 0 auto;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    .watermark {
      opacity: 0.10 !important;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
  }
</style>
</head>
<body>

<div class="no-print">
  <button class="btn-print" onclick="window.print()">Print / Save as PDF</button>
</div>

<div class="slip-container">
  <!-- Background Image Watermark -->
  <div class="watermark"></div>

  <div class="slip-content">
    <!-- Header with logo side-by-side with title -->
    <div class="header">
      <img src="<?= e($logo_url) ?>" alt="NPF Logo" class="header-logo">
      <div class="header-title">
        <h2>THE NIGERIA POLICE FORCE</h2>
        <h4>IGP-SECRETARIAT</h4>
        <p>FHQ, ABUJA</p>
      </div>
    </div>

    <div class="sub">Personnel Verification Slip</div>

    <img src="<?= e(url($p['photo_path'] ?: '/uploads/officer.png')) ?>" alt="Personnel Photo" class="profile-photo">

    <table>
      <tr>
        <th>AP-F Number</th>
        <td><strong><?= e($p['ap_number']) ?></strong></td>
      </tr>
      <tr>
        <th>Full Name</th>
        <td><?= e($p['full_name']) ?></td>
      </tr>
      <tr>
        <th>Rank</th>
        <td><?= e($p['rank_name']) ?></td>
      </tr>
      <tr>
        <th>Department</th>
        <td><?= e($p['dept_name']) ?></td>
      </tr>
      <tr>
        <th>Status</th>
        <td><span class="status-badge"><?= e($p['status']) ?></span></td>
      </tr>
      <tr>
        <th>Verified On</th>
        <td><?= date('d M Y, H:i') ?></td>
      </tr>
    </table>

    <div class="footer-seal">
      Official Verification Document &bull; Validated
    </div>
  </div>
</div>

</body>
</html>