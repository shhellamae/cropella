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
        try { $wala->DeletePlanting($_GET['delete_id']); header("Location: planting.php"); exit(); }
        catch (\Exception $e) { $delete_error = $e->getMessage(); }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_planting'])) {
        try { $wala->addPlanting($_POST); header("Location: planting.php"); exit(); }
        catch (\Exception $e) { $insertion_error = $e->getMessage(); }
    } elseif (isset($_POST['update_planting'])) {
        try { $wala->updatePlanting($_POST); header("Location: planting.php"); exit(); }
        catch (\Exception $e) { $update_error = $e->getMessage(); }
    }
}

try {
    $plantings = $wala->getAllPlantings($sort);
    if (!is_array($plantings)) { $plantings = []; }
    $farms = $wala->getAllFarms('ASC');
    if (!is_array($farms)) { $farms = []; }
    $crops = $wala->getAllCrops('ASC');
    if (!is_array($crops)) { $crops = []; }
    $plantingStatuses = $wala->getAllPlantingStatuses();
    if (!is_array($plantingStatuses)) { $plantingStatuses = []; }
} catch (\Exception $e) {
    $plantings = $farms = $crops = $plantingStatuses = [];
    $connection_error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cropella | Planting Records</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
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
        <li><a href="planting.php" class="nav-link active"><i class="fa-solid fa-calendar-days me-2"></i> Planting</a></li>
        <li><a href="harvest.php" class="nav-link"><i class="fa-solid fa-basket-shopping me-2"></i> Harvest</a></li>
        <li><a href="report.php" class="nav-link"><i class="fa-solid fa-file-invoice me-2"></i> Reports</a></li>
        <?php if ($isAdmin): ?><li><a href="audit_log.php" class="nav-link"><i class="fa-solid fa-clock-rotate-left me-2"></i> Audit Logs</a></li><?php endif; ?>
      </ul>
      <hr class="text-white-50">
      <div class="dropdown px-2">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown"><i class="fa-solid fa-circle-user fa-lg me-2"></i><strong><?php echo htmlspecialchars($currentUserName); ?></strong></a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
            <li><a class="dropdown-item" href="profile.php"><i class="fa-solid fa-user-pen me-2"></i> Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Sign out</a></li>
        </ul>
      </div>
    </nav>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <div><h1 class="h2 text-dark fw-bold">PLANTING RECORDS</h1><p class="text-muted mb-0">Manage planting schedules and crop allocations.</p></div>
        <button type="button" class="btn btn-pink shadow-sm" data-bs-toggle="modal" data-bs-target="#newPlantingModal"><i class="fa-solid fa-plus me-1"></i> Add Planting</button>
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
                <option value="all">All Fields</option><option value="id">ID</option><option value="farm">Farm</option><option value="crop">Crop</option><option value="status">Status</option>
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
        <div class="card-header bg-white py-3"><h5 class="fw-bold mb-0 text-secondary">Planting Schedule (<span id="visibleCount"><?php echo count($plantings); ?></span>)</h5></div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr><th><a href="?sort=<?php echo $next; ?>" class="text-dark text-decoration-none">ID <i class="fa-solid fa-sort"></i></a></th><th>Farm</th><th>Crop</th><th>Date</th><th>Area (ha)</th><th>Seed Qty</th><th>Expected Harvest</th><th>Status</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
              <?php if (!empty($plantings)): ?>
                <?php foreach ($plantings as $p): ?>
                  <tr class="data-row">
                    <td class="col-id">#<?php echo $p['planting_id']; ?></td>
                    <td class="col-farm fw-semibold"><?php echo htmlspecialchars($p['farm_name']); ?></td>
                    <td class="col-crop"><?php echo htmlspecialchars($p['crop_name'] . ($p['variety'] ? ' (' . $p['variety'] . ')' : '')); ?></td>
                    <td><?php echo htmlspecialchars($p['planting_date']); ?></td>
                    <td><?php echo $p['area_planted']; ?></td>
                    <td><?php echo $p['seed_quantity']; ?></td>
                    <td><?php echo htmlspecialchars($p['expected_harvest_date']); ?></td>
                    <td class="col-status"><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($p['planting_status'] ?? 'N/A'); ?></span></td>
                    <td class="text-end">
                      <div class="d-flex justify-content-end gap-1">
                        <button class="btn btn-sm btn-outline-info view-btn" data-bs-toggle="modal" data-bs-target="#viewPlantingModal" data-id="<?php echo $p['planting_id']; ?>" data-farm="<?php echo htmlspecialchars($p['farm_name']); ?>" data-crop="<?php echo htmlspecialchars($p['crop_name'] . ($p['variety'] ? ' (' . $p['variety'] . ')' : '')); ?>" data-date="<?php echo htmlspecialchars($p['planting_date']); ?>" data-area="<?php echo $p['area_planted']; ?>" data-seed="<?php echo $p['seed_quantity']; ?>" data-expected="<?php echo htmlspecialchars($p['expected_harvest_date']); ?>" data-status="<?php echo htmlspecialchars($p['planting_status'] ?? 'N/A'); ?>"><i class="fa-solid fa-eye"></i></button>
                        <button class="btn btn-sm btn-outline-primary edit-btn" onclick='openEditModal(<?php echo json_encode($p); ?>)'><i class="fa-solid fa-pen"></i></button>
                        <?php if ($isAdmin): ?><button class="btn btn-sm btn-outline-danger delete-btn" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?php echo $p['planting_id']; ?>"><i class="fa-solid fa-trash"></i></button><?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <tr id="noMatchesRow" style="display: none;"><td colspan="9" class="text-center py-4 text-muted">No records matched.</td></tr>
              <?php else: ?>
                <tr><td colspan="9" class="text-center py-4 text-muted">No records found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewPlantingModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header border-0 bg-light"><h5 class="modal-title fw-bold">Planting Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body py-4"><ul class="list-group list-group-flush">
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">ID:</span><strong id="view_planting_id"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Farm Name:</span><strong id="view_farm_name"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Crop Planted:</span><strong id="view_crop_name"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Planting Date:</span><strong id="view_planting_date"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Area (ha):</span><strong id="view_area_planted"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Seed Quantity:</span><strong id="view_seed_quantity"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Expected Harvest:</span><strong id="view_expected_harvest"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Status:</span><span id="view_status_desc" class="badge bg-success"></span></li>
  </ul></div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button></div>
</div></div></div>

<!-- Add Modal -->
<div class="modal fade" id="newPlantingModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <form action="planting.php" method="POST" id="addPlantingForm">
    <div class="modal-header border-0">
      <h5 class="modal-title fw-bold">Add Planting</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="resetPlantingForm('addPlantingForm', 'addPlantingError')"></button>
    </div>
    <div class="modal-body">
      <div id="addPlantingError" class="alert alert-danger py-2 small mb-3" style="display: none;"><i class="fa-solid fa-triangle-exclamation me-1"></i>This planting record already exists for this farm, crop, and date.</div>
      <input type="hidden" name="add_planting" value="1">
      <div class="mb-3">
        <label class="form-label">Farm <span class="text-danger">*</span></label>
        <select class="form-select" name="farm_id" required>
          <option value="" disabled selected>-- Select Farm --</option>
          <?php foreach ($farms as $f): ?><option value="<?php echo $f['farm_id']; ?>"><?php echo htmlspecialchars($f['farm_name']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Crop <span class="text-danger">*</span></label>
        <select class="form-select" name="crop_id" required>
          <option value="" disabled selected>-- Select Crop --</option>
          <?php foreach ($crops as $c): ?><option value="<?php echo $c['crop_id']; ?>"><?php echo htmlspecialchars($c['crop_name'] . ($c['variety'] ? ' - ' . $c['variety'] : '')); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="row g-2 mb-3">
        <div class="col-6"><label class="form-label">Planting Date <span class="text-danger">*</span></label><input type="date" class="form-control" name="planting_date" required></div>
        <div class="col-6"><label class="form-label">Expected Harvest <span class="text-danger">*</span></label><input type="date" class="form-control" name="expected_harvest_date" required></div>
      </div>
      <div class="row g-2 mb-3">
        <div class="col-6"><label class="form-label">Area (ha) <span class="text-danger">*</span></label><input type="number" step="0.01" class="form-control" name="area_planted" required></div>
        <div class="col-6"><label class="form-label">Seed Qty <span class="text-danger">*</span></label><input type="number" step="0.01" class="form-control" name="seed_quantity" required></div>
      </div>
      <div class="mb-3">
        <label class="form-label">Status <span class="text-danger">*</span></label>
        <select class="form-select" name="status_id" required>
          <option value="" disabled selected>-- Select Status --</option>
          <?php foreach ($plantingStatuses as $ps): ?><option value="<?php echo $ps['status_id']; ?>"><?php echo htmlspecialchars($ps['status_description']); ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="modal-footer border-0">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" onclick="resetPlantingForm('addPlantingForm', 'addPlantingError')">Cancel</button>
      <button type="submit" class="btn btn-pink btn-sm">Save Planting</button>
    </div>
  </form>
</div></div></div>

<!-- Add Confirmation Sub-Modal -->
<div class="modal fade" id="confirmAddPlantingModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header border-0"><h5 class="modal-title fw-bold text-warning"><i class="fa-solid fa-triangle-exclamation me-2"></i>Confirm Addition</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><p class="text-muted">Are you sure you want to add this new planting record?</p></div>
  <div class="modal-footer border-0">
    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-pink btn-sm" onclick="document.getElementById('addPlantingForm').submit()">Yes, Save</button>
  </div>
</div></div></div>

<!-- Edit Modal -->
<div class="modal fade" id="editPlantingModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <form action="planting.php" method="POST" id="editPlantingForm">
    <div class="modal-header border-0">
      <h5 class="modal-title fw-bold">Edit Planting</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="resetPlantingForm('editPlantingForm', 'editPlantingError')"></button>
    </div>
    <div class="modal-body">
      <!-- Alert banner for No-Changes detection -->
      <div id="editPlantingAlert" class="alert alert-danger py-2 small mb-3" style="display: none;">
        <i class="fa-solid fa-triangle-exclamation me-1"></i><span id="editPlantingAlertText">No changes were made to this planting record.</span>
      </div>

      <div id="editPlantingError" class="alert alert-danger py-2 small mb-3" style="display: none;"><i class="fa-solid fa-triangle-exclamation me-1"></i>This planting record already exists for this farm, crop, and date.</div>
      
      <input type="hidden" name="update_planting" value="1">
      <input type="hidden" id="edit_planting_id" name="planting_id">

      <!-- Hidden Original State Inputs for No-Change Detection -->
      <input type="hidden" id="original_farm_id">
      <input type="hidden" id="original_crop_id">
      <input type="hidden" id="original_planting_date">
      <input type="hidden" id="original_expected_harvest_date">
      <input type="hidden" id="original_area_planted">
      <input type="hidden" id="original_seed_quantity">
      <input type="hidden" id="original_status_id">

      <div class="mb-3">
        <label class="form-label">Farm <span class="text-danger">*</span></label>
        <select class="form-select" id="edit_farm_id" name="farm_id" required>
          <?php foreach ($farms as $f): ?><option value="<?php echo $f['farm_id']; ?>"><?php echo htmlspecialchars($f['farm_name']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Crop <span class="text-danger">*</span></label>
        <select class="form-select" id="edit_crop_id" name="crop_id" required>
          <?php foreach ($crops as $c): ?><option value="<?php echo $c['crop_id']; ?>"><?php echo htmlspecialchars($c['crop_name'] . ($c['variety'] ? ' - ' . $c['variety'] : '')); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="row g-2 mb-3">
        <div class="col-6"><label class="form-label">Planting Date <span class="text-danger">*</span></label><input type="date" class="form-control" id="edit_planting_date" name="planting_date" required></div>
        <div class="col-6"><label class="form-label">Expected Harvest <span class="text-danger">*</span></label><input type="date" class="form-control" id="edit_expected_harvest_date" name="expected_harvest_date" required></div>
      </div>
      <div class="row g-2 mb-3">
        <div class="col-6"><label class="form-label">Area (ha) <span class="text-danger">*</span></label><input type="number" step="0.01" class="form-control" id="edit_area_planted" name="area_planted" required></div>
        <div class="col-6"><label class="form-label">Seed Qty <span class="text-danger">*</span></label><input type="number" step="0.01" class="form-control" id="edit_seed_quantity" name="seed_quantity" required></div>
      </div>
      <div class="mb-3">
        <label class="form-label">Status <span class="text-danger">*</span></label>
        <select class="form-select" id="edit_status_id" name="status_id" required>
          <?php foreach ($plantingStatuses as $ps): ?><option value="<?php echo $ps['status_id']; ?>"><?php echo htmlspecialchars($ps['status_description']); ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="modal-footer border-0">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" onclick="resetPlantingForm('editPlantingForm', 'editPlantingError')">Cancel</button>
      <button type="submit" class="btn btn-pink btn-sm">Update Changes</button>
    </div>
  </form>
</div></div></div>

<!-- Edit Confirmation Sub-Modal -->
<div class="modal fade" id="confirmEditPlantingModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header border-0"><h5 class="modal-title fw-bold text-warning"><i class="fa-solid fa-triangle-exclamation me-2"></i>Confirm Updates</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><p class="text-muted">Are you sure you want to save changes to this planting record?</p></div>
  <div class="modal-footer border-0">
    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-pink btn-sm" onclick="document.getElementById('editPlantingForm').submit()">Yes, Save</button>
  </div>
</div></div></div>

<?php if ($isAdmin): ?>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header border-0"><h5 class="modal-title fw-bold text-danger">Confirm Deletion</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><p class="text-muted">Are you sure you want to delete this planting record?</p></div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><a href="#" id="confirmDeleteBtn" class="btn btn-danger btn-sm">Yes, Delete</a></div>
</div></div></div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const existingPlantings = <?php echo json_encode(array_map(function($p) {
    return [
      'id' => (string)$p['planting_id'],
      'farm_id' => (string)$p['farm_id'],
      'crop_id' => (string)$p['crop_id'],
      'planting_date' => $p['planting_date']
    ];
  }, $plantings)); ?>;

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
    document.getElementById('view_planting_id').textContent = '#' + this.dataset.id;
    document.getElementById('view_farm_name').textContent = this.dataset.farm;
    document.getElementById('view_crop_name').textContent = this.dataset.crop;
    document.getElementById('view_planting_date').textContent = this.dataset.date;
    document.getElementById('view_area_planted').textContent = this.dataset.area + ' ha';
    document.getElementById('view_seed_quantity').textContent = this.dataset.seed || 'N/A';
    document.getElementById('view_expected_harvest').textContent = this.dataset.expected || 'N/A';
    document.getElementById('view_status_desc').textContent = this.dataset.status;
  }));

  function openEditModal(planting) {
    document.getElementById('edit_planting_id').value = planting.planting_id;
    document.getElementById('edit_farm_id').value = planting.farm_id;
    document.getElementById('edit_crop_id').value = planting.crop_id;
    document.getElementById('edit_planting_date').value = planting.planting_date;
    document.getElementById('edit_expected_harvest_date').value = planting.expected_harvest_date || '';
    document.getElementById('edit_area_planted').value = planting.area_planted;
    document.getElementById('edit_seed_quantity').value = planting.seed_quantity || '';
    document.getElementById('edit_status_id').value = planting.status_id || '';

    // Store original values for no-change detection
    document.getElementById('original_farm_id').value = planting.farm_id;
    document.getElementById('original_crop_id').value = planting.crop_id;
    document.getElementById('original_planting_date').value = planting.planting_date;
    document.getElementById('original_expected_harvest_date').value = planting.expected_harvest_date || '';
    document.getElementById('original_area_planted').value = planting.area_planted;
    document.getElementById('original_seed_quantity').value = planting.seed_quantity || '';
    document.getElementById('original_status_id').value = planting.status_id || '';
    
    document.getElementById('editPlantingError').style.display = 'none';
    const editAlert = document.getElementById('editPlantingAlert');
    if (editAlert) editAlert.style.display = 'none';

    new bootstrap.Modal(document.getElementById('editPlantingModal')).show();
  }

  function resetPlantingForm(formId, errorId) {
    document.getElementById(formId).reset();
    document.getElementById(errorId).style.display = 'none';
    const editAlert = document.getElementById('editPlantingAlert');
    if (editAlert) editAlert.style.display = 'none';
  }

  document.getElementById('addPlantingForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const farmId = this.querySelector('[name="farm_id"]').value.trim();
    const cropId = this.querySelector('[name="crop_id"]').value.trim();
    const plantingDate = this.querySelector('[name="planting_date"]').value.trim();
    const errorEl = document.getElementById('addPlantingError');

    const isDuplicate = existingPlantings.some(p => {
      return p.farm_id === farmId && p.crop_id === cropId && p.planting_date === plantingDate;
    });

    if (isDuplicate) {
      errorEl.style.display = 'block';
      return;
    }

    errorEl.style.display = 'none';
    bootstrap.Modal.getInstance(document.getElementById('newPlantingModal')).hide();
    new bootstrap.Modal(document.getElementById('confirmAddPlantingModal')).show();
  });

  document.getElementById('editPlantingForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const currentId = document.getElementById('edit_planting_id').value;
    const farmId = document.getElementById('edit_farm_id').value.trim();
    const cropId = document.getElementById('edit_crop_id').value.trim();
    const plantingDate = document.getElementById('edit_planting_date').value.trim();
    const expectedHarvest = document.getElementById('edit_expected_harvest_date').value.trim();
    const areaPlanted = document.getElementById('edit_area_planted').value.trim();
    const seedQty = document.getElementById('edit_seed_quantity').value.trim();
    const statusId = document.getElementById('edit_status_id').value;

    const origFarmId = document.getElementById('original_farm_id').value;
    const origCropId = document.getElementById('original_crop_id').value;
    const origPlantingDate = document.getElementById('original_planting_date').value;
    const origExpectedHarvest = document.getElementById('original_expected_harvest_date').value;
    const origAreaPlanted = document.getElementById('original_area_planted').value;
    const origSeedQty = document.getElementById('original_seed_quantity').value;
    const origStatusId = document.getElementById('original_status_id').value;

    const alertDiv = document.getElementById('editPlantingAlert');
    const errorEl = document.getElementById('editPlantingError');

    // Check if nothing was changed
    if (farmId === origFarmId && cropId === origCropId && plantingDate === origPlantingDate && expectedHarvest === origExpectedHarvest && areaPlanted === origAreaPlanted && seedQty === origSeedQty && statusId === origStatusId) {
      alertDiv.style.display = 'block';
      errorEl.style.display = 'none';
      return;
    }
    alertDiv.style.display = 'none';

    const isDuplicate = existingPlantings.some(p => {
      if (p.id === String(currentId)) return false;
      return p.farm_id === farmId && p.crop_id === cropId && p.planting_date === plantingDate;
    });

    if (isDuplicate) {
      errorEl.style.display = 'block';
      return;
    }

    errorEl.style.display = 'none';
    bootstrap.Modal.getInstance(document.getElementById('editPlantingModal')).hide();
    new bootstrap.Modal(document.getElementById('confirmEditPlantingModal')).show();
  });

  <?php if ($isAdmin): ?>
  document.querySelectorAll('.delete-btn').forEach(b => b.addEventListener('click', function() {
    document.getElementById('confirmDeleteBtn').href = 'planting.php?delete_id=' + this.dataset.id;
  }));
  <?php endif; ?>
</script>
<script src="table_filter.js"></script>
</body>
</html>