<?php
require_once "auth_check.php";
require_once "Database.php";
require_once "db_function.php";

$db = new Database();
$pdo = $db->connect();
$wala = new Wala($pdo, $_SESSION['user_id']);

$sort = (isset($_GET['sort']) && $_GET['sort'] === 'asc') ? 'ASC' : 'DESC';
$next = ($sort === 'ASC') ? 'desc' : 'asc';

if (isset($_GET['delete_id'])) {
    if (!$isAdmin) { $delete_error = "Access denied."; }
    else {
        try { $wala->DeleteHarvest($_GET['delete_id']); header("Location: harvest.php"); exit(); }
        catch (\Exception $e) { $delete_error = $e->getMessage(); }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_harvest'])) {
        try { $wala->addHarvest($_POST); header("Location: harvest.php"); exit(); }
        catch (\Exception $e) { $insertion_error = $e->getMessage(); }
    } elseif (isset($_POST['update_harvest'])) {
        try { $wala->updateHarvest($_POST); header("Location: harvest.php"); exit(); }
        catch (\Exception $e) { $update_error = $e->getMessage(); }
    }
}

try {
    $harvests = $wala->getAllHarvests($sort);
    if (!is_array($harvests)) { $harvests = []; }
    $plantings = $wala->getAllPlantings('ASC');
    if (!is_array($plantings)) { $plantings = []; }
    $grades = $wala->getAllQualityGrades();
    if (!is_array($grades)) { $grades = []; }
    $remarks = $wala->getAllHarvestRemarks();
    if (!is_array($remarks)) { $remarks = []; }
} catch (\Exception $e) {
    $harvests = $plantings = $grades = $remarks = [];
    $connection_error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Agri-IS | Harvest Records</title>
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
    }
  </style>
</head>
<body>

<!-- Mobile Header Bar: Burger icon & Cropella on the top-left -->
<div class="text-white d-md-none p-3 d-flex justify-content-between align-items-center w-100 shadow-sm" style="background: linear-gradient(135deg, #d63384 0%, #85144b 100%);">
  <div class="d-flex align-items-center"><i class="fa-solid fa-seedling me-2"></i><span class="fw-bold fs-5">Cropella AIS</span></div>
  <button class="btn btn-outline-light btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu"><i class="fa-solid fa-bars"></i></button>
</div>


<div class="container-fluid">
  <div class="row">
    <nav class="col-md-3 col-lg-2 d-md-block sidebar p-3 collapse" id="sidebarMenu">
      <!-- Mobile Close Button Header inside Sidebar -->
      <div class="d-flex justify-content-between align-items-center mb-3 px-2 d-md-none">
        <div class="d-flex align-items-center">
          <i class="fa-solid fa-seedling text-pink me-2"></i><span class="fs-4 fw-bold text-white">Cropella AIS</span>
        </div>
        <button class="btn btn-outline-light btn-sm px-2 py-1" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-label="Close navigation">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="d-flex align-items-center mb-4 px-2 d-none d-md-flex"><i class="fa-solid fa-seedling me-2"></i><span class="fs-4 fw-bold"> Cropella AIS</span></div>
      
      <hr class="text-white-50 d-none d-md-block">
      <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item"><a href="draftdashboard.php" class="nav-link"><i class="fa-solid fa-chart-pie me-2"></i> Dashboard</a></li>
        <li><a href="farmerdraft.php" class="nav-link"><i class="fa-solid fa-user-gear me-2"></i> Farmers</a></li>
        <li><a href="farm.php" class="nav-link"><i class="fa-solid fa-location-dot me-2"></i> Farms</a></li>
        <li><a href="crop.php" class="nav-link"><i class="fa-solid fa-seedling me-2"></i> Crops</a></li>
        <li><a href="planting.php" class="nav-link"><i class="fa-solid fa-calendar-days me-2"></i> Planting</a></li>
        <li><a href="harvest.php" class="nav-link active"><i class="fa-solid fa-basket-shopping me-2"></i> Harvest</a></li>
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
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <div><h1 class="h2 text-dark fw-bold">HARVEST RECORDS</h1><p class="text-muted mb-0">Manage crop yields, pricing, and quality grades.</p></div>
        <button type="button" class="btn btn-pink shadow-sm" data-bs-toggle="modal" data-bs-target="#newHarvestModal"><i class="fa-solid fa-plus me-1"></i> Add Harvest</button>
      </div>

      <?php if (isset($delete_error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($delete_error); ?></div><?php endif; ?>
      <?php if (isset($insertion_error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($insertion_error); ?></div><?php endif; ?>
      <?php if (isset($update_error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($update_error); ?></div><?php endif; ?>
      <?php if (isset($connection_error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($connection_error); ?></div><?php endif; ?>

      <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body bg-white py-3">
          <div class="row g-2 align-items-end">
            <div class="col-md-3">
              <label class="form-label small fw-bold text-secondary">Filter By</label>
              <select id="filterCategory" class="form-select form-select-sm">
                <option value="all">All Fields</option><option value="id">ID</option><option value="farm">Farm</option><option value="crop">Crop</option><option value="grade">Grade</option>
              </select>
            </div>
            <div class="col-md-7">
              <label class="form-label small fw-bold text-secondary">Search Keyword</label>
              <input type="text" id="liveSearchInput" class="form-control form-control-sm" placeholder="Search keyword...">
            </div>
            <div class="col-md-2"><button type="button" id="resetFilterBtn" class="btn btn-outline-secondary btn-sm w-100"><i class="fa-solid fa-rotate-left me-1"></i> Clear</button></div>
          </div>
        </div>
      </div>

      <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3"><h5 class="fw-bold mb-0 text-secondary">Harvest Yields (<span id="visibleCount"><?php echo count($harvests); ?></span>)</h5></div>
        <div class="table-responsive">
          <table class="table table-hover align-middle table-compact mb-0">
            <thead class="table-light">
              <tr>
                <th><a href="?sort=<?php echo $next; ?>" class="text-dark text-decoration-none">ID <i class="fa-solid fa-sort"></i></a></th>
                <th>Farm</th>
                <th>Crop</th>
                <th>Date</th>
                <th class="text-end">Qty (kg)</th>
                <th class="text-end">Price/kg (₱)</th>
                <th class="text-end">Total Value (₱)</th>
                <th>Grade</th>
                <th>Remarks</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($harvests)): ?>
                <?php foreach ($harvests as $h): ?>
                  <?php $totalVal = $h['quantity_kg'] * $h['selling_price_per_kg']; ?>
                  <tr class="data-row">
                    <td class="col-id">#<?php echo $h['harvest_id']; ?></td>
                    <td class="col-farm fw-semibold"><?php echo htmlspecialchars($h['farm_name']); ?></td>
                    <td class="col-crop"><?php echo htmlspecialchars($h['crop_name']); ?></td>
                    <td><?php echo htmlspecialchars($h['harvest_date']); ?></td>
                    <td class="text-end"><?php echo number_format($h['quantity_kg'], 2); ?></td>
                    <td class="text-end"><?php echo number_format($h['selling_price_per_kg'], 2); ?></td>
                    <td class="text-end fw-bold text-pink-primary">₱<?php echo number_format($totalVal, 2); ?></td>
                    <td class="col-grade"><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($h['quality_grade'] ?? 'N/A'); ?></span></td>
                    <td><?php echo htmlspecialchars($h['harvest_remark'] ?? 'N/A'); ?></td>
                    <td class="text-end">
                      <div class="d-flex justify-content-end gap-1">
                        <button class="btn btn-sm btn-outline-info view-btn py-0 px-1" data-bs-toggle="modal" data-bs-target="#viewHarvestModal" data-id="<?php echo $h['harvest_id']; ?>" data-farm="<?php echo htmlspecialchars($h['farm_name']); ?>" data-crop="<?php echo htmlspecialchars($h['crop_name']); ?>" data-date="<?php echo htmlspecialchars($h['harvest_date']); ?>" data-qty="<?php echo number_format($h['quantity_kg'], 2); ?>" data-price="<?php echo number_format($h['selling_price_per_kg'], 2); ?>" data-total="<?php echo number_format($totalVal, 2); ?>" data-grade="<?php echo htmlspecialchars($h['quality_grade'] ?? 'N/A'); ?>" data-remark="<?php echo htmlspecialchars($h['harvest_remark'] ?? 'N/A'); ?>"><i class="fa-solid fa-eye fa-xs"></i></button>
                        <button class="btn btn-sm btn-outline-primary edit-btn py-0 px-1" onclick='openEditModal(<?php echo json_encode($h); ?>)'><i class="fa-solid fa-pen fa-xs"></i></button>
                        <?php if ($isAdmin): ?><button class="btn btn-sm btn-outline-danger delete-btn py-0 px-1" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?php echo $h['harvest_id']; ?>"><i class="fa-solid fa-trash fa-xs"></i></button><?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <tr id="noMatchesRow" style="display: none;"><td colspan="10" class="text-center py-4 text-muted">No records matched.</td></tr>
              <?php else: ?>
                <tr><td colspan="10" class="text-center py-4 text-muted">No records found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewHarvestModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header border-0 bg-light"><h5 class="modal-title fw-bold">Harvest Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body py-4"><ul class="list-group list-group-flush">
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">ID:</span><strong id="view_harvest_id"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Farm:</span><strong id="view_farm_name"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Crop:</span><strong id="view_crop_name"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Harvest Date:</span><strong id="view_harvest_date"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Quantity (kg):</span><strong id="view_quantity_kg"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Price per kg:</span><strong id="view_price_kg"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Total Gross:</span><strong id="view_total_val" class="text-pink-primary"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Grade:</span><strong id="view_quality_grade"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Remarks:</span><strong id="view_harvest_remark"></strong></li>
  </ul></div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button></div>
</div></div></div>

<!-- Add Modal -->
<div class="modal fade" id="newHarvestModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <form action="harvest.php" method="POST" id="addHarvestForm">
    <div class="modal-header border-0">
      <h5 class="modal-title fw-bold">Add Harvest</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="resetHarvestForm('addHarvestForm', 'addHarvestError')"></button>
    </div>
    <div class="modal-body">
      <div id="addHarvestError" class="alert alert-danger py-2 small mb-3" style="display: none;"><i class="fa-solid fa-triangle-exclamation me-1"></i>This harvest record already exists for this planting and date.</div>
      <input type="hidden" name="add_harvest" value="1">
      <div class="mb-3">
        <label class="form-label">Planting Record <span class="text-danger">*</span></label>
        <select class="form-select" name="planting_id" required>
          <option value="" disabled selected>-- Select Planting --</option>
          <?php foreach ($plantings as $p): ?><option value="<?php echo $p['planting_id']; ?>">ID #<?php echo $p['planting_id']; ?> - <?php echo htmlspecialchars($p['farm_name'] . ' / ' . $p['crop_name']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3"><label class="form-label">Harvest Date <span class="text-danger">*</span></label><input type="date" class="form-control" name="harvest_date" required></div>
      <div class="row g-2 mb-3">
        <div class="col-6"><label class="form-label">Quantity (kg) <span class="text-danger">*</span></label><input type="number" step="0.01" class="form-control" name="quantity_kg" required></div>
        <div class="col-6"><label class="form-label">Price / kg (₱) <span class="text-danger">*</span></label><input type="number" step="0.01" class="form-control" name="selling_price_per_kg" required></div>
      </div>
      <div class="mb-3">
        <label class="form-label">Grade <span class="text-danger">*</span></label>
        <select class="form-select" name="grade_id" required>
          <option value="" disabled selected>-- Select Grade --</option>
          <?php foreach ($grades as $g): ?><option value="<?php echo $g['grade_id']; ?>"><?php echo htmlspecialchars($g['grade_description']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Remarks <span class="text-danger">*</span></label>
        <select class="form-select" name="remark_id" required>
          <option value="" disabled selected>-- Select Remark --</option>
          <?php foreach ($remarks as $r): ?><option value="<?php echo $r['remark_id']; ?>"><?php echo htmlspecialchars($r['remark_description']); ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="modal-footer border-0">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" onclick="resetHarvestForm('addHarvestForm', 'addHarvestError')">Cancel</button>
      <button type="submit" class="btn btn-pink btn-sm">Save Harvest</button>
    </div>
  </form>
</div></div></div>

<!-- Add Confirmation Sub-Modal -->
<div class="modal fade" id="confirmAddHarvestModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header border-0"><h5 class="modal-title fw-bold text-warning"><i class="fa-solid fa-triangle-exclamation me-2"></i>Confirm Addition</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><p class="text-muted">Are you sure you want to add this new harvest record?</p></div>
  <div class="modal-footer border-0">
    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-pink btn-sm" onclick="document.getElementById('addHarvestForm').submit()">Yes, Save</button>
  </div>
</div></div></div>

<!-- Edit Modal -->
<div class="modal fade" id="editHarvestModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <form action="harvest.php" method="POST" id="editHarvestForm">
    <div class="modal-header border-0">
      <h5 class="modal-title fw-bold">Edit Harvest</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="resetHarvestForm('editHarvestForm', 'editHarvestError')"></button>
    </div>
    <div class="modal-body">
      <!-- Alert banner for No-Changes detection -->
      <div id="editHarvestAlert" class="alert alert-danger py-2 small mb-3" style="display: none;">
        <i class="fa-solid fa-triangle-exclamation me-1"></i><span id="editHarvestAlertText">No changes were made to this harvest record.</span>
      </div>

      <div id="editHarvestError" class="alert alert-danger py-2 small mb-3" style="display: none;"><i class="fa-solid fa-triangle-exclamation me-1"></i>This harvest record already exists for this planting and date.</div>
      
      <input type="hidden" name="update_harvest" value="1">
      <input type="hidden" id="edit_harvest_id" name="harvest_id">

      <!-- Hidden Original State Inputs for No-Change Detection -->
      <input type="hidden" id="original_planting_id">
      <input type="hidden" id="original_harvest_date">
      <input type="hidden" id="original_quantity_kg">
      <input type="hidden" id="original_selling_price_per_kg">
      <input type="hidden" id="original_grade_id">
      <input type="hidden" id="original_remark_id">

      <div class="mb-3">
        <label class="form-label">Planting Record <span class="text-danger">*</span></label>
        <select class="form-select" id="edit_planting_id" name="planting_id" required>
          <?php foreach ($plantings as $p): ?><option value="<?php echo $p['planting_id']; ?>">ID #<?php echo $p['planting_id']; ?> - <?php echo htmlspecialchars($p['farm_name'] . ' / ' . $p['crop_name']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3"><label class="form-label">Harvest Date <span class="text-danger">*</span></label><input type="date" class="form-control" id="edit_harvest_date" name="harvest_date" required></div>
      <div class="row g-2 mb-3">
        <div class="col-6"><label class="form-label">Quantity (kg) <span class="text-danger">*</span></label><input type="number" step="0.01" class="form-control" id="edit_quantity_kg" name="quantity_kg" required></div>
        <div class="col-6"><label class="form-label">Price / kg (₱) <span class="text-danger">*</span></label><input type="number" step="0.01" class="form-control" id="edit_selling_price_per_kg" name="selling_price_per_kg" required></div>
      </div>
      <div class="mb-3">
        <label class="form-label">Grade <span class="text-danger">*</span></label>
        <select class="form-select" id="edit_grade_id" name="grade_id" required>
          <?php foreach ($grades as $g): ?><option value="<?php echo $g['grade_id']; ?>"><?php echo htmlspecialchars($g['grade_description']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Remarks <span class="text-danger">*</span></label>
        <select class="form-select" id="edit_remark_id" name="remark_id" required>
          <?php foreach ($remarks as $r): ?><option value="<?php echo $r['remark_id']; ?>"><?php echo htmlspecialchars($r['remark_description']); ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="modal-footer border-0">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" onclick="resetHarvestForm('editHarvestForm', 'editHarvestError')">Cancel</button>
      <button type="submit" class="btn btn-pink btn-sm">Update Changes</button>
    </div>
  </form>
</div></div></div>

<!-- Edit Confirmation Sub-Modal -->
<div class="modal fade" id="confirmEditHarvestModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header border-0"><h5 class="modal-title fw-bold text-warning"><i class="fa-solid fa-triangle-exclamation me-2"></i>Confirm Updates</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><p class="text-muted">Are you sure you want to save changes to this harvest record?</p></div>
  <div class="modal-footer border-0">
    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-pink btn-sm" onclick="document.getElementById('editHarvestForm').submit()">Yes, Save</button>
  </div>
</div></div></div>

<?php if ($isAdmin): ?>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header border-0"><h5 class="modal-title fw-bold text-danger">Confirm Deletion</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><p class="text-muted">Are you sure you want to delete this harvest record?</p></div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><a href="#" id="confirmDeleteBtn" class="btn btn-danger btn-sm">Yes, Delete</a></div>
</div></div></div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const existingHarvests = <?php echo json_encode(array_map(function($h) {
    return [
      'id' => (string)$h['harvest_id'],
      'planting_id' => (string)$h['planting_id'],
      'harvest_date' => $h['harvest_date']
    ];
  }, $harvests)); ?>;

  document.addEventListener('click', function(event) {
      const sidebar = document.getElementById('sidebarMenu');
      const toggleBtns = document.querySelectorAll('[data-bs-target="#sidebarMenu"]');
      let clickedToggle = false;
      toggleBtns.forEach(btn => { if (btn.contains(event.target)) clickedToggle = true; });
      if (sidebar.classList.contains('show') && !sidebar.contains(event.target) && !clickedToggle) {
          const bsCollapse = bootstrap.Collapse.getInstance(sidebar) || new bootstrap.Collapse(sidebar);
          bsCollapse.hide();
      }
  });

  document.querySelectorAll('#sidebarMenu .nav-link').forEach(link => {
      link.addEventListener('click', function() {
          const sidebar = document.getElementById('sidebarMenu');
          if (window.innerWidth < 768 && sidebar.classList.contains('show')) {
              const bsCollapse = bootstrap.Collapse.getInstance(sidebar) || new bootstrap.Collapse(sidebar);
              bsCollapse.hide();
          }
      });
  });

  document.querySelectorAll('.view-btn').forEach(b => b.addEventListener('click', function() {
    document.getElementById('view_harvest_id').textContent = '#' + this.dataset.id;
    document.getElementById('view_farm_name').textContent = this.dataset.farm;
    document.getElementById('view_crop_name').textContent = this.dataset.crop;
    document.getElementById('view_harvest_date').textContent = this.dataset.date;
    document.getElementById('view_quantity_kg').textContent = this.dataset.qty + ' kg';
    document.getElementById('view_price_kg').textContent = '₱' + this.dataset.price;
    document.getElementById('view_total_val').textContent = '₱' + this.dataset.total;
    document.getElementById('view_quality_grade').textContent = this.dataset.grade;
    document.getElementById('view_harvest_remark').textContent = this.dataset.remark;
  }));

  function openEditModal(harvest) {
    document.getElementById('edit_harvest_id').value = harvest.harvest_id;
    document.getElementById('edit_planting_id').value = harvest.planting_id;
    document.getElementById('edit_harvest_date').value = harvest.harvest_date;
    document.getElementById('edit_quantity_kg').value = harvest.quantity_kg;
    document.getElementById('edit_selling_price_per_kg').value = harvest.selling_price_per_kg;
    document.getElementById('edit_grade_id').value = harvest.grade_id || '';
    document.getElementById('edit_remark_id').value = harvest.remark_id || '';

    // Store original values for no-change detection
    document.getElementById('original_planting_id').value = harvest.planting_id;
    document.getElementById('original_harvest_date').value = harvest.harvest_date;
    document.getElementById('original_quantity_kg').value = harvest.quantity_kg;
    document.getElementById('original_selling_price_per_kg').value = harvest.selling_price_per_kg;
    document.getElementById('original_grade_id').value = harvest.grade_id || '';
    document.getElementById('original_remark_id').value = harvest.remark_id || '';
    
    document.getElementById('editHarvestError').style.display = 'none';
    const editAlert = document.getElementById('editHarvestAlert');
    if (editAlert) editAlert.style.display = 'none';

    new bootstrap.Modal(document.getElementById('editHarvestModal')).show();
  }

  function resetHarvestForm(formId, errorId) {
    document.getElementById(formId).reset();
    document.getElementById(errorId).style.display = 'none';
    const editAlert = document.getElementById('editHarvestAlert');
    if (editAlert) editAlert.style.display = 'none';
  }

  document.getElementById('addHarvestForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const plantingId = this.querySelector('[name="planting_id"]').value.trim();
    const harvestDate = this.querySelector('[name="harvest_date"]').value.trim();
    const errorEl = document.getElementById('addHarvestError');

    const isDuplicate = existingHarvests.some(h => {
      return h.planting_id === plantingId && h.harvest_date === harvestDate;
    });

    if (isDuplicate) {
      errorEl.style.display = 'block';
      return;
    }

    errorEl.style.display = 'none';
    bootstrap.Modal.getInstance(document.getElementById('newHarvestModal')).hide();
    new bootstrap.Modal(document.getElementById('confirmAddHarvestModal')).show();
  });

  document.getElementById('editHarvestForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const currentId = document.getElementById('edit_harvest_id').value;
    const plantingId = document.getElementById('edit_planting_id').value.trim();
    const harvestDate = document.getElementById('edit_harvest_date').value.trim();
    const quantityKg = document.getElementById('edit_quantity_kg').value.trim();
    const pricePerKg = document.getElementById('edit_selling_price_per_kg').value.trim();
    const gradeId = document.getElementById('edit_grade_id').value;
    const remarkId = document.getElementById('edit_remark_id').value;

    const origPlantingId = document.getElementById('original_planting_id').value;
    const origHarvestDate = document.getElementById('original_harvest_date').value;
    const origQuantityKg = document.getElementById('original_quantity_kg').value;
    const origPricePerKg = document.getElementById('original_selling_price_per_kg').value;
    const origGradeId = document.getElementById('original_grade_id').value;
    const origRemarkId = document.getElementById('original_remark_id').value;

    const alertDiv = document.getElementById('editHarvestAlert');
    const errorEl = document.getElementById('editHarvestError');

    // Check if nothing was changed
    if (plantingId === origPlantingId && harvestDate === origHarvestDate && quantityKg === origQuantityKg && pricePerKg === origPricePerKg && gradeId === origGradeId && remarkId === origRemarkId) {
      alertDiv.style.display = 'block';
      errorEl.style.display = 'none';
      return;
    }
    alertDiv.style.display = 'none';

    const isDuplicate = existingHarvests.some(h => {
      if (h.id === String(currentId)) return false;
      return h.planting_id === plantingId && h.harvest_date === harvestDate;
    });

    if (isDuplicate) {
      errorEl.style.display = 'block';
      return;
    }

    errorEl.style.display = 'none';
    bootstrap.Modal.getInstance(document.getElementById('editHarvestModal')).hide();
    new bootstrap.Modal(document.getElementById('confirmEditHarvestModal')).show();
  });

  <?php if ($isAdmin): ?>
  document.querySelectorAll('.delete-btn').forEach(b => b.addEventListener('click', function() {
    document.getElementById('confirmDeleteBtn').href = 'harvest.php?delete_id=' + this.dataset.id;
  }));
  <?php endif; ?>
</script>
<script src="table_filter.js"></script>
</body>
</html>