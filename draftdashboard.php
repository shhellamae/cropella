<?php
require_once "auth_check.php";
require_once "Database.php";
require_once "db_function.php";

$db = new Database();
$pdo = $db->connect();
$wala = new Wala($pdo, $_SESSION['user_id']);

try {
    $farmers = $wala->getAllFarmers('ASC');
    $farms = $wala->getAllFarms('ASC');
    $crops = $wala->getAllCrops('ASC');
    $plantings = $wala->getAllPlantings('DESC');
    $harvests = $wala->getAllHarvests('DESC');

    $totalFarmers = count($farmers);
    $totalFarms = count($farms);
    $totalCrops = count($crops);
    $totalPlantings = count($plantings);
    $totalHarvests = count($harvests);
    
    // Planting Status Breakdown for Donut Chart
    $totalPlantedCount = 0;
    $totalGrowingCount = 0;
    $totalHarvestedCount = 0;

    foreach ($plantings as $p) {
        $status = strtolower(trim($p['planting_status'] ?? ''));
        if ($status === 'planted') {
            $totalPlantedCount++;
        } elseif ($status === 'growing') {
            $totalGrowingCount++;
        } elseif ($status === 'harvested') {
            $totalHarvestedCount++;
        }
    }
    
    $totalStatusSum = max(1, $totalPlantedCount + $totalGrowingCount + $totalHarvestedCount);
    $growingStop = ($totalGrowingCount / $totalStatusSum) * 100;
    $harvestedStop = $growingStop + (($totalHarvestedCount / $totalStatusSum) * 100);

    // Cumulative Yield & Revenue
    $totalYieldKg = 0;
    $totalRevenue = 0;
    foreach ($harvests as $h) {
        $totalYieldKg += $h['quantity_kg'];
        $totalRevenue += ($h['quantity_kg'] * $h['selling_price_per_kg']);
    }
    
    $recentHarvests = array_slice($harvests, 0, 5);
    $recentPlantings = array_slice($plantings, 0, 5);
} catch (\Exception $e) {
    $totalFarmers = $totalFarms = $totalCrops = $totalPlantings = $totalHarvests = 0;
    $totalPlantedCount = $totalGrowingCount = $totalHarvestedCount = 0;
    $growingStop = $harvestedStop = 0;
    $totalYieldKg = $totalRevenue = 0;
    $recentHarvests = $recentPlantings = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cropella | System Dashboard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
  <style>
    .kpi-card {
      border: 1px solid rgba(214, 51, 132, 0.2);
      border-radius: 12px;
      transition: all 0.2s ease;
      text-decoration: none;
      background: #ffffff;
    }
    .kpi-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(214, 51, 132, 0.12) !important;
      border-color: #d63384 !important;
    }
    .sticker-box {
      width: 44px;
      height: 44px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 10px;
      background-color: rgba(214, 51, 132, 0.1);
      color: #d63384;
    }
    .harvest-banner {
      background: linear-gradient(135deg, #d63384 0%, #85144b 100%);
      color: white;
    }
    .text-pink-primary { color: #d63384 !important; }
    
    /* Donut Chart Styling */
    .donut-chart {
      width: 170px;
      height: 170px;
      border-radius: 50%;
      position: relative;
      margin: 0 auto;
    }
    .donut-hole {
      width: 96px;
      height: 96px;
      background: #ffffff;
      border-radius: 50%;
      position: absolute;
      top: 37px;
      left: 37px;
    }
  </style>
</head>
<body>

<div class="text-white d-md-none p-3 d-flex justify-content-between align-items-center w-100 shadow-sm" style="background: linear-gradient(135deg, #d63384 0%, #85144b 100%);">
  <div class="d-flex align-items-center"><i class="fa-solid fa-seedling me-2"></i><span class="fw-bold fs-5">Cropella</span></div>
  <button class="btn btn-outline-light btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu"><i class="fa-solid fa-bars"></i> Menu</button>
</div>

<div class="container-fluid">
  <div class="row">
    
    <nav class="col-md-3 col-lg-2 d-md-block sidebar p-3 collapse" id="sidebarMenu">
      <div class="d-flex align-items-center mb-4 px-2 d-none d-md-flex"><i class="fa-solid fa-seedling me-2"></i><span class="fs-4 fw-bold"> Cropella AIS</span></div>
      
      <hr class="text-white-50 d-none d-md-block">
      <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item"><a href="draftdashboard.php" class="nav-link active"><i class="fa-solid fa-chart-pie me-2"></i> Dashboard</a></li>
        <li><a href="farmerdraft.php" class="nav-link"><i class="fa-solid fa-user-gear me-2"></i> Farmers</a></li>
        <li><a href="farm.php" class="nav-link"><i class="fa-solid fa-location-dot me-2"></i> Farms</a></li>
        <li><a href="crop.php" class="nav-link"><i class="fa-solid fa-seedling me-2"></i> Crops Catalog</a></li>
        <li><a href="planting.php" class="nav-link"><i class="fa-solid fa-calendar-days me-2"></i> Planting Records</a></li>
        <li><a href="harvest.php" class="nav-link"><i class="fa-solid fa-basket-shopping me-2"></i> Harvest Records</a></li>
        <li><a href="report.php" class="nav-link"><i class="fa-solid fa-file-invoice me-2"></i> Reports</a></li>
        
        <?php if ($isAdmin): ?><li><a href="audit_log.php" class="nav-link"><i class="fa-solid fa-clock-rotate-left me-2"></i> Audit Logs</a></li><?php endif; ?>
      </ul>
      <hr class="text-white-50">
      <div class="dropdown px-2">
        
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown"><i class="fa-solid fa-circle-user fa-lg me-2"></i><strong><?php echo htmlspecialchars($currentUserName); ?></strong></a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                    <li><a class="dropdown-item" href="profile.php"><i class="fa-solid fa-user-pen me-2"></i> Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="login.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Sign out</a></li>
                </ul>
      </div>
    </nav>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h1 class="h2 text-dark fw-bold mb-1">Dashboard</h1>
          <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($currentUserName); ?>. Here is what is happening on the farms.</p>
        </div>
        <span class="badge bg-white text-dark border px-3 py-2 shadow-sm d-none d-sm-inline-block">
          <i class="fa-regular fa-calendar me-1 text-pink-primary"></i> <?php echo date('M j, Y'); ?>
        </span>
      </div>

      <div class="row g-4">
        <!-- LEFT COLUMN -->
        <div class="col-lg-5 d-flex flex-column gap-4">
          <div class="row g-3">
            <div class="col-6">
              <a href="farmerdraft.php" class="card kpi-card shadow-sm p-3 h-100 d-flex flex-row align-items-center justify-content-between">
                <div><span class="text-muted small fw-semibold d-block mb-1">Total Farmers</span><h3 class="fw-bold mb-0 text-dark"><?php echo number_format($totalFarmers); ?></h3></div>
                <div class="sticker-box"><i class="fa-solid fa-users"></i></div>
              </a>
            </div>
            <div class="col-6">
              <a href="farm.php" class="card kpi-card shadow-sm p-3 h-100 d-flex flex-row align-items-center justify-content-between">
                <div><span class="text-muted small fw-semibold d-block mb-1">Total Farms</span><h3 class="fw-bold mb-0 text-dark"><?php echo number_format($totalFarms); ?></h3></div>
                <div class="sticker-box"><i class="fa-solid fa-location-dot"></i></div>
              </a>
            </div>
            <div class="col-6">
              <a href="crop.php" class="card kpi-card shadow-sm p-3 h-100 d-flex flex-row align-items-center justify-content-between">
                <div><span class="text-muted small fw-semibold d-block mb-1">Total Crops</span><h3 class="fw-bold mb-0 text-dark"><?php echo number_format($totalCrops); ?></h3></div>
                <div class="sticker-box"><i class="fa-solid fa-seedling"></i></div>
              </a>
            </div>
            <div class="col-6">
              <a href="planting.php" class="card kpi-card shadow-sm p-3 h-100 d-flex flex-row align-items-center justify-content-between">
                <div><span class="text-muted small fw-semibold d-block mb-1">Total Plantings</span><h3 class="fw-bold mb-0 text-dark"><?php echo number_format($totalPlantings); ?></h3></div>
                <div class="sticker-box"><i class="fa-solid fa-calendar-days"></i></div>
              </a>
            </div>
            <div class="col-12">
              <a href="harvest.php" class="card kpi-card shadow-sm p-3 h-100 d-flex flex-row align-items-center justify-content-between">
                <div><span class="text-muted small fw-semibold d-block mb-1">Total Harvest Records</span><h3 class="fw-bold mb-0 text-dark"><?php echo number_format($totalHarvests); ?></h3></div>
                <div class="sticker-box"><i class="fa-solid fa-basket-shopping"></i></div>
              </a>
            </div>
          </div>

          <div class="card border-0 shadow-sm p-4 bg-white rounded-3 flex-grow-1">
            <h5 class="fw-bold mb-3 text-dark"><i class="fa-solid fa-chart-pie me-2 text-pink-primary"></i> Planting Status Breakdown</h5>
            <div class="py-3 text-center">
              <div class="donut-chart shadow-sm" style="background: conic-gradient(#f783ac 0% <?php echo $growingStop; ?>%, #d63384 <?php echo $growingStop; ?>% <?php echo $harvestedStop; ?>%, #fcc2d7 <?php echo $harvestedStop; ?>% 100%);">
                <div class="donut-hole"></div>
              </div>
            </div>
            <div class="d-flex justify-content-center flex-wrap gap-3 mt-3">
              <div class="d-flex align-items-center small"><span class="d-inline-block rounded-circle me-1" style="width: 12px; height: 12px; background-color: #f783ac;"></span><span>Growing (<?php echo $totalGrowingCount; ?>)</span></div>
              <div class="d-flex align-items-center small"><span class="d-inline-block rounded-circle me-1" style="width: 12px; height: 12px; background-color: #d63384;"></span><span>Harvested (<?php echo $totalHarvestedCount; ?>)</span></div>
              <div class="d-flex align-items-center small"><span class="d-inline-block rounded-circle me-1" style="width: 12px; height: 12px; background-color: #fcc2d7;"></span><span>Planted (<?php echo $totalPlantedCount; ?>)</span></div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="col-lg-7 d-flex flex-column gap-4">
          <div class="card border-0 shadow-sm p-4 harvest-banner rounded-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
              <div>
                <span class="text-white-50 small fw-semibold text-uppercase">Total Harvested Quantity</span>
                <h2 class="fw-bold mb-0 mt-1 display-5"><?php echo number_format($totalYieldKg, 2); ?> <span class="fs-5 fw-normal text-white-50">kg</span></h2>
              </div>
              <div class="text-md-end bg-white bg-opacity-10 px-3 py-2 rounded-3">
                <span class="small text-white-50 d-block">Estimated Gross Value</span>
                <span class="fw-bold fs-4">₱<?php echo number_format($totalRevenue, 2); ?></span>
              </div>
            </div>
          </div>

          <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
              <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-calendar-days me-2 text-pink-primary"></i> Recent Plantings</h6>
              <a href="planting.php" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Farm</th><th>Crop</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                  <?php if (!empty($recentPlantings)): ?>
                    <?php foreach ($recentPlantings as $p): ?>
                      <tr>
                        <td class="fw-semibold"><?php echo htmlspecialchars($p['farm_name']); ?></td>
                        <td><?php echo htmlspecialchars($p['crop_name']); ?></td>
                        <td><?php echo htmlspecialchars($p['planting_date']); ?></td>
                        <td><span class="badge bg-danger bg-opacity-10 text-pink-primary border border-danger border-opacity-25"><?php echo htmlspecialchars($p['planting_status'] ?? 'N/A'); ?></span></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr><td colspan="4" class="text-center py-3 text-muted">No records found.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
              <h6 class="fw-bold mb-0 text-dark"><i class="fa-solid fa-basket-shopping me-2 text-pink-primary"></i> Recent Harvests</h6>
              <a href="harvest.php" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th>Farm</th><th>Crop</th><th>Qty (kg)</th><th>Value</th></tr></thead>
                <tbody>
                  <?php if (!empty($recentHarvests)): ?>
                    <?php foreach ($recentHarvests as $h): ?>
                      <?php $val = $h['quantity_kg'] * $h['selling_price_per_kg']; ?>
                      <tr>
                        <td class="fw-semibold"><?php echo htmlspecialchars($h['farm_name']); ?></td>
                        <td><?php echo htmlspecialchars($h['crop_name']); ?></td>
                        <td><?php echo number_format($h['quantity_kg'], 2); ?></td>
                        <td class="fw-bold text-pink-primary">₱<?php echo number_format($val, 2); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr><td colspan="4" class="text-center py-3 text-muted">No records found.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>