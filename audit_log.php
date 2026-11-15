<?php
require_once "auth_check.php";
require_once "Database.php";
require_once "db_function.php";

if (!$isAdmin) {
    header("Location: draftdashboard.php");
    exit();
}

$db = new Database();
$pdo = $db->connect();
$wala = new Wala($pdo, $_SESSION['user_id']);

$sort = (isset($_GET['sort']) && $_GET['sort'] === 'asc') ? 'ASC' : 'DESC';
$next = ($sort === 'ASC') ? 'desc' : 'asc';

try {
    $logs = $wala->getAllAuditLogs($sort);
} catch (\Exception $e) {
    $logs = [];
    $connection_error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cropella | Audit Logs</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
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
        <li class="nav-item"><a href="draftdashboard.php" class="nav-link"><i class="fa-solid fa-chart-pie me-2"></i> Dashboard</a></li>
        <li><a href="farmerdraft.php" class="nav-link"><i class="fa-solid fa-user-gear me-2"></i> Farmers</a></li>
        <li><a href="farm.php" class="nav-link"><i class="fa-solid fa-location-dot me-2"></i> Farms</a></li>
        <li><a href="crop.php" class="nav-link"><i class="fa-solid fa-seedling me-2"></i> Crops Catalog</a></li>
        <li><a href="planting.php" class="nav-link"><i class="fa-solid fa-calendar-days me-2"></i> Planting Records</a></li>
        <li><a href="harvest.php" class="nav-link"><i class="fa-solid fa-basket-shopping me-2"></i> Harvest Records</a></li>
        <li><a href="report.php" class="nav-link"><i class="fa-solid fa-file-invoice me-2"></i> Reports</a></li>
        <?php if ($isAdmin): ?><li><a href="audit_log.php" class="nav-link active"><i class="fa-solid fa-clock-rotate-left me-2"></i> Audit Logs</a></li><?php endif; ?>
      </ul>
      <hr class="text-white-50">
      <div class="dropdown px-2">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown"><i class="fa-solid fa-circle-user fa-lg me-2"></i><strong><?php echo htmlspecialchars($currentUserName); ?></strong></a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                    <li><a class="dropdown-item" href="profile.php"><i class="fa-solid fa-user-pen me-2"></i> Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="login.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Sign out</a></li>
                </ul>  </div>
    </nav>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <div><h1 class="h2 text-dark fw-bold">SYSTEM AUDIT LOGS</h1><p class="text-muted mb-0">Admin-only tracking of all database actions (INSERT, UPDATE, DELETE).</p></div>
      </div>

      <?php if (isset($connection_error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($connection_error); ?></div><?php endif; ?>

      <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body bg-white py-3">
          <div class="row g-2 align-items-end">
            <div class="col-md-3">
              <label class="form-label small fw-bold text-secondary">Filter By</label>
              <select id="filterCategory" class="form-select form-select-sm">
                <option value="all">All Fields</option><option value="id">Log ID</option><option value="user">User</option><option value="table">Table</option><option value="action">Action</option>
              </select>
            </div>
            <div class="col-md-7">
              <label class="form-label small fw-bold text-secondary">Search Keyword</label>
              <input type="text" id="liveSearchInput" class="form-control form-control-sm" placeholder="Search logs...">
            </div>
            <div class="col-md-2"><button type="button" id="resetFilterBtn" class="btn btn-outline-secondary btn-sm w-100"><i class="fa-solid fa-rotate-left me-1"></i> Clear</button></div>
          </div>
        </div>
      </div>

      <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3"><h5 class="fw-bold mb-0 text-secondary">Activity Logs (<span id="visibleCount"><?php echo count($logs); ?></span>)</h5></div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr><th><a href="?sort=<?php echo $next; ?>" class="text-dark text-decoration-none">Log ID <i class="fa-solid fa-sort"></i></a></th><th>User</th><th>Table Name</th><th>Record ID</th><th>Action</th><th>Timestamp</th></tr>
            </thead>
            <tbody>
              <?php if (!empty($logs)): ?>
                <?php foreach ($logs as $l): ?>
                  <tr class="data-row">
                    <td class="col-id">#<?php echo $l['log_id']; ?></td>
                    <td class="col-user fw-semibold"><?php echo htmlspecialchars($l['full_name'] ?? $l['username'] ?? 'System'); ?></td>
                    <td class="col-table"><code><?php echo htmlspecialchars($l['table_name']); ?></code></td>
                    <td>#<?php echo $l['record_id']; ?></td>
                    <td class="col-action">
                      <?php 
                        $badgeBg = 'bg-secondary';
                        if ($l['action'] === 'INSERT') $badgeBg = 'bg-success';
                        elseif ($l['action'] === 'UPDATE') $badgeBg = 'bg-primary';
                        elseif ($l['action'] === 'DELETE') $badgeBg = 'bg-danger';
                      ?>
                      <span class="badge <?php echo $badgeBg; ?>"><?php echo htmlspecialchars($l['action']); ?></span>
                    </td>
                    <td><?php echo htmlspecialchars($l['action_at']); ?></td>
                  </tr>
                <?php endforeach; ?>
                <tr id="noMatchesRow" style="display: none;"><td colspan="6" class="text-center py-4 text-muted">No audit logs matched your search.</td></tr>
              <?php else: ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">No audit logs recorded.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="table_filter.js"></script>
</body>
</html>