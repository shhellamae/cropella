<?php
require_once "auth_check.php";
require_once "Database.php";
require_once "db_function.php";

$db = new Database();
$pdo = $db->connect();
$wala = new Wala($pdo, $_SESSION['user_id']);

$reportType = $_GET['report_type'] ?? 'harvests';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$searchKeyword = $_GET['search'] ?? '';
$sort = (isset($_GET['sort']) && $_GET['sort'] === 'asc') ? 'ASC' : 'DESC';
$next = ($sort === 'ASC') ? 'desc' : 'asc';

try {
    if ($reportType === 'farmers_farms') {
        $reportData = $wala->getReportFarmersAndFarms($searchKeyword);
    } elseif ($reportType === 'crops_per_farm') {
        $reportData = $wala->getReportCropsPlantedPerFarm();
    } elseif ($reportType === 'plantings_by_status') {
        $reportData = $wala->getReportPlantingsByStatus();
    } else {
        $reportData = $wala->getReportHarvests($startDate, $endDate);
    }

    $allHarvests = $wala->getReportHarvests($startDate, $endDate);
    $totalHarvestQuantity = 0;
    $totalEstimatedValue = 0;
    foreach ($allHarvests as $h) {
        $totalHarvestQuantity += $h['quantity_kg'];
        $totalEstimatedValue += $h['estimated_value'];
    }
} catch (\Exception $e) {
    $reportData = [];
    $totalHarvestQuantity = $totalEstimatedValue = 0;
    $connection_error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Agri-IS | Reports & Analytics</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <style>
    .text-pink-primary { color: #d63384 !important; }
    .btn-pink { background-color: #d63384; border-color: #d63384; color: white; }
    .btn-pink:hover { background-color: #b02a70; border-color: #b02a70; color: white; }
    .table-compact th, .table-compact td {
      font-size: 0.85rem;
      padding-top: 0.55rem;
      padding-bottom: 0.55rem;
      vertical-align: middle;
    }
    @media print {
      .sidebar, .no-print { display: none !important; }
      main { width: 100% !important; margin: 0 !important; padding: 0 !important; }
      body { background-color: white !important; }
    }
  </style>
</head>
<body>

<div class="text-white d-md-none p-3 d-flex justify-content-between align-items-center w-100 shadow-sm no-print" style="background: linear-gradient(135deg, #d63384 0%, #85144b 100%);">
  <div class="d-flex align-items-center"><i class="fa-solid fa-wheat-awn me-2"></i><span class="fw-bold fs-5">Cropella AIS</span></div>
  <button class="btn btn-outline-light btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu"><i class="fa-solid fa-bars"></i></button>
</div>

<div class="container-fluid">
  <div class="row">
    <nav class="col-md-3 col-lg-2 d-md-block sidebar p-3 collapse no-print" id="sidebarMenu">
      <div class="d-flex align-items-center mb-4 px-2 d-none d-md-flex"><i class="fa-solid fa-seedling me-2"></i><span class="fs-4 fw-bold"> Cropella AIS</span></div>
      
      <hr class="text-white-50 d-none d-md-block">
      <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item"><a href="draftdashboard.php" class="nav-link"><i class="fa-solid fa-chart-pie me-2"></i> Dashboard</a></li>
        <li><a href="farmerdraft.php" class="nav-link"><i class="fa-solid fa-user-gear me-2"></i> Farmers</a></li>
        <li><a href="farm.php" class="nav-link"><i class="fa-solid fa-location-dot me-2"></i> Farms</a></li>
        <li><a href="crop.php" class="nav-link"><i class="fa-solid fa-seedling me-2"></i> Crops</a></li>
        <li><a href="planting.php" class="nav-link"><i class="fa-solid fa-calendar-days me-2"></i> Planting</a></li>
        <li><a href="harvest.php" class="nav-link"><i class="fa-solid fa-basket-shopping me-2"></i> Harvest</a></li>
        <li><a href="report.php" class="nav-link active"><i class="fa-solid fa-file-invoice me-2"></i> Reports</a></li>
        <?php if ($isAdmin): ?><li><a href="audit_log.php" class="nav-link"><i class="fa-solid fa-clock-rotate-left me-2"></i> Audit Logs</a></li><?php endif; ?>
      </ul>
      <hr class="text-white-50">
      <div class="dropdown px-2">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown"><i class="fa-solid fa-circle-user fa-lg me-2"></i><strong><?php echo htmlspecialchars($currentUserName); ?></strong></a>
       <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                    <li><a class="dropdown-item" href="profile.php"><i class="fa-solid fa-user-pen me-2"></i> Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Sign out</a></li>
                </ul>  </div>
    </nav>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <div><h1 class="h2 text-dark fw-bold">AGRICULTURAL REPORTS</h1><p class="text-muted mb-0">Official database-driven agricultural analytics.</p></div>
        <div class="no-print">
          <button onclick="window.print()" class="btn btn-outline-secondary shadow-sm"><i class="fa-solid fa-print me-1"></i> Print Report</button>
        </div>
      </div>

      <?php if (isset($connection_error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($connection_error); ?></div><?php endif; ?>

      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <div class="card border-0 shadow-sm p-3 bg-white card-stat" style="border-left: 4px solid #d63384;">
            <h6 class="text-muted small fw-bold">TOTAL HARVESTED QUANTITY</h6>
            <h3 class="fw-bold mb-0 text-dark"><?php echo number_format($totalHarvestQuantity, 2); ?> <span class="fs-6 text-muted">kg</span></h3>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card border-0 shadow-sm p-3 bg-white card-stat" style="border-left: 4px solid #d63384;">
            <h6 class="text-muted small fw-bold">ESTIMATED TOTAL HARVEST VALUE</h6>
            <h3 class="fw-bold mb-0 text-pink-primary">₱<?php echo number_format($totalEstimatedValue, 2); ?></h3>
          </div>
        </div>
      </div>

      <div class="card border-0 shadow-sm rounded-3 mb-4 no-print">
        <div class="card-body bg-white py-3">
          <form method="GET" action="report.php" class="row g-3 align-items-end">
            <div class="col-md-4">
              <label class="form-label small fw-bold text-secondary">Select Report Type</label>
              <select name="report_type" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="harvests" <?php echo ($reportType === 'harvests') ? 'selected' : ''; ?>>Harvest Records & Values</option>
                <option value="farmers_farms" <?php echo ($reportType === 'farmers_farms') ? 'selected' : ''; ?>>Farmers and Their Farms</option>
                <option value="crops_per_farm" <?php echo ($reportType === 'crops_per_farm') ? 'selected' : ''; ?>>Crops Planted per Farm</option>
                <option value="plantings_by_status" <?php echo ($reportType === 'plantings_by_status') ? 'selected' : ''; ?>>Planting Records by Status</option>
              </select>
            </div>
            <?php if ($reportType === 'harvests'): ?>
              <div class="col-md-3"><label class="form-label small fw-bold text-secondary">Start Date</label><input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" class="form-control form-control-sm"></div>
              <div class="col-md-3"><label class="form-label small fw-bold text-secondary">End Date</label><input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" class="form-control form-control-sm"></div>
            <?php elseif ($reportType === 'farmers_farms'): ?>
              <div class="col-md-6"><label class="form-label small fw-bold text-secondary">Search</label><input type="text" name="search" value="<?php echo htmlspecialchars($searchKeyword); ?>" class="form-control form-control-sm" placeholder="Search farmer or location..."></div>
            <?php endif; ?>
            <div class="col-md-2"><button type="submit" class="btn btn-pink btn-sm w-100"><i class="fa-solid fa-filter me-1"></i> Apply</button></div>
          </form>
        </div>
      </div>

      <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3"><h5 class="fw-bold mb-0 text-secondary">Report Results</h5></div>
        <div class="table-responsive">
          <table class="table table-hover align-middle table-compact mb-0">
            <thead class="table-light">
              <?php if ($reportType === 'farmers_farms'): ?>
                <tr>
                  <th><a href="?report_type=<?php echo $reportType; ?>&sort=<?php echo $next; ?>" class="text-dark text-decoration-none">ID <i class="fa-solid fa-sort"></i></a></th>
                  <th>Farmer Name</th><th>Contact</th><th>Farm Name</th><th>Location</th><th class="text-end">Area (ha)</th>
                </tr>
              <?php elseif ($reportType === 'crops_per_farm'): ?>
                <tr>
                  <th><a href="?report_type=<?php echo $reportType; ?>&sort=<?php echo $next; ?>" class="text-dark text-decoration-none">Farm Name <i class="fa-solid fa-sort"></i></a></th>
                  <th>Owner</th><th>Crop Name</th><th>Variety</th><th>Planting Date</th><th class="text-end">Area (ha)</th><th>Status</th>
                </tr>
              <?php elseif ($reportType === 'plantings_by_status'): ?>
                <tr>
                  <th><a href="?report_type=<?php echo $reportType; ?>&sort=<?php echo $next; ?>" class="text-dark text-decoration-none">Status <i class="fa-solid fa-sort"></i></a></th>
                  <th class="text-end">Total Count</th><th class="text-end">Total Area (ha)</th>
                </tr>
              <?php else: ?>
                <tr>
                  <th><a href="?report_type=<?php echo $reportType; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>&sort=<?php echo $next; ?>" class="text-dark text-decoration-none">ID <i class="fa-solid fa-sort"></i></a></th>
                  <th>Farm</th><th>Crop</th><th>Date</th><th class="text-end">Qty (kg)</th><th class="text-end">Price/kg</th><th class="text-end">Est. Value</th><th>Grade</th><th>Remarks</th>
                </tr>
              <?php endif; ?>
            </thead>
            <tbody>
              <?php if (!empty($reportData)): ?>
                <?php foreach ($reportData as $row): ?>
                  <?php if ($reportType === 'farmers_farms'): ?>
                    <tr>
                      <td>#<?php echo $row['farmer_id']; ?></td>
                      <td class="fw-bold"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                      <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                      <td><?php echo htmlspecialchars($row['farm_name'] ?? 'N/A'); ?></td>
                      <td><?php echo htmlspecialchars($row['location'] ?? 'N/A'); ?></td>
                      <td class="text-end"><?php echo $row['area_hectares'] ? number_format($row['area_hectares'], 2) : 'N/A'; ?></td>
                    </tr>
                  <?php elseif ($reportType === 'crops_per_farm'): ?>
                    <tr>
                      <td class="fw-bold"><?php echo htmlspecialchars($row['farm_name']); ?></td>
                      <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                      <td><?php echo htmlspecialchars($row['crop_name']); ?></td>
                      <td><?php echo htmlspecialchars($row['variety']); ?></td>
                      <td><?php echo htmlspecialchars($row['planting_date']); ?></td>
                      <td class="text-end"><?php echo number_format($row['area_planted'], 2); ?></td>
                      <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['planting_status']); ?></span></td>
                    </tr>
                  <?php elseif ($reportType === 'plantings_by_status'): ?>
                    <tr>
                      <td><span class="badge text-white" style="background-color: #d63384;"><?php echo htmlspecialchars($row['planting_status']); ?></span></td>
                      <td class="text-end"><?php echo number_format($row['total_plantings']); ?></td>
                      <td class="text-end"><?php echo number_format($row['total_area_planted'], 2); ?> ha</td>
                    </tr>
                  <?php else: ?>
                    <tr>
                      <td>#<?php echo $row['harvest_id']; ?></td>
                      <td><?php echo htmlspecialchars($row['farm_name']); ?></td>
                      <td><?php echo htmlspecialchars($row['crop_name']); ?></td>
                      <td><?php echo htmlspecialchars($row['harvest_date']); ?></td>
                      <td class="text-end"><?php echo number_format($row['quantity_kg'], 2); ?></td>
                      <td class="text-end"><?php echo number_format($row['selling_price_per_kg'], 2); ?></td>
                      <td class="text-end fw-bold text-pink-primary">₱<?php echo number_format($row['estimated_value'], 2); ?></td>
                      <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['quality_grade'] ?? 'N/A'); ?></span></td>
                      <td><?php echo htmlspecialchars($row['harvest_remark'] ?? 'N/A'); ?></td>
                    </tr>
                  <?php endif; ?>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="9" class="text-center py-4 text-muted">No records found for this report.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>