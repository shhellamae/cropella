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
        try { $wala->DeleteFarm($_GET['delete_id']); header("Location: farm.php"); exit(); }
        catch (\Exception $e) { $delete_error = $e->getMessage(); }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_farm'])) {
        try { $wala->addFarm($_POST); header("Location: farm.php"); exit(); }
        catch (\Exception $e) { $insertion_error = $e->getMessage(); }
    } elseif (isset($_POST['update_farm'])) {
        try { $wala->updateFarm($_POST); header("Location: farm.php"); exit(); }
        catch (\Exception $e) { $update_error = $e->getMessage(); }
    }
}

try {
    $farms = $wala->getAllFarms($sort);
    if (!is_array($farms)) { $farms = []; }
    $farmers = $wala->getAllFarmers('ASC');
    if (!is_array($farmers)) { $farmers = []; }
    $soilTypes = $wala->getAllSoilTypes();
    if (!is_array($soilTypes)) { $soilTypes = []; }
    $irrigationTypes = $wala->getAllIrrigationTypes();
    if (!is_array($irrigationTypes)) { $irrigationTypes = []; }
} catch (\Exception $e) {
    $farms = $farmers = $soilTypes = $irrigationTypes = [];
    $connection_error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cropella | Farm Management</title>
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
        <li><a href="farm.php" class="nav-link active"><i class="fa-solid fa-location-dot me-2"></i> Farms</a></li>
        <li><a href="crop.php" class="nav-link"><i class="fa-solid fa-seedling me-2"></i> Crops</a></li>
        <li><a href="planting.php" class="nav-link"><i class="fa-solid fa-calendar-days me-2"></i> Planting</a></li>
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
            <li><a class="dropdown-item text-danger" href="login.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Sign out</a></li>
        </ul>
      </div>
    </nav>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <div><h1 class="h2 text-dark fw-bold">FARM MANAGEMENT</h1><p class="text-muted mb-0">Manage farm properties and soil attributes.</p></div>
        <button type="button" class="btn btn-pink shadow-sm" data-bs-toggle="modal" data-bs-target="#newFarmModal"><i class="fa-solid fa-plus me-1"></i> Add Farm</button>
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
                <option value="all">All Fields</option><option value="id">ID</option><option value="farmer">Farmer</option><option value="farmname">Farm Name</option><option value="location">Location</option>
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
        <div class="card-header bg-white py-3"><h5 class="fw-bold mb-0 text-secondary">Farms (<span id="visibleCount"><?php echo count($farms); ?></span>)</h5></div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr><th><a href="?sort=<?php echo $next; ?>" class="text-dark text-decoration-none">ID <i class="fa-solid fa-sort"></i></a></th><th>Farmer</th><th>Farm Name</th><th>Location</th><th>Area (ha)</th><th>Soil</th><th>Irrigation</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
              <?php if (!empty($farms)): ?>
                <?php foreach ($farms as $f): ?>
                  <tr class="data-row">
                    <td class="col-id">#<?php echo $f['farm_id']; ?></td>
                    <td class="col-farmer fw-semibold"><?php echo htmlspecialchars($f['first_name'] . ' ' . $f['last_name']); ?></td>
                    <td class="col-farmname"><?php echo htmlspecialchars($f['farm_name']); ?></td>
                    <td class="col-location"><?php echo htmlspecialchars($f['location']); ?></td>
                    <td><?php echo $f['area_hectares']; ?></td>
                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($f['soil_type'] ?? 'N/A'); ?></span></td>
                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($f['irrigation_type'] ?? 'N/A'); ?></span></td>
                    <td class="text-end">
                      <div class="d-flex justify-content-end gap-1">
                        <button class="btn btn-sm btn-outline-info view-btn" data-bs-toggle="modal" data-bs-target="#viewFarmModal" data-id="<?php echo $f['farm_id']; ?>" data-farmer="<?php echo htmlspecialchars($f['first_name'] . ' ' . $f['last_name']); ?>" data-farmname="<?php echo htmlspecialchars($f['farm_name']); ?>" data-location="<?php echo htmlspecialchars($f['location']); ?>" data-area="<?php echo $f['area_hectares']; ?>" data-soil="<?php echo htmlspecialchars($f['soil_type'] ?? 'N/A'); ?>" data-irrigation="<?php echo htmlspecialchars($f['irrigation_type'] ?? 'N/A');?>"><i class="fa-solid fa-eye"></i></button>
                        <button class="btn btn-sm btn-outline-primary edit-btn" onclick='openEditModal(<?php echo json_encode($f); ?>)'><i class="fa-solid fa-pen"></i></button>
                        <?php if ($isAdmin): ?><button class="btn btn-sm btn-outline-danger delete-btn" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?php echo $f['farm_id']; ?>"><i class="fa-solid fa-trash"></i></button><?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <tr id="noMatchesRow" style="display: none;"><td colspan="8" class="text-center py-4 text-muted">No records matched.</td></tr>
              <?php else: ?>
                <tr><td colspan="8" class="text-center py-4 text-muted">No records found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewFarmModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header border-0 bg-light"><h5 class="modal-title fw-bold">Farm Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body py-4"><ul class="list-group list-group-flush">
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">ID:</span><strong id="view_farm_id"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Farm Name:</span><strong id="view_farm_name"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Owner:</span><strong id="view_farmer_name"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Location:</span><strong id="view_location"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Area (ha):</span><strong id="view_area"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Soil Type:</span><strong id="view_soil"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Irrigation:</span><strong id="view_irrigation"></strong></li>
  </ul></div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button></div>
</div></div></div>

<!-- Add Modal -->
<div class="modal fade" id="newFarmModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <form action="farm.php" method="POST" id="addFarmForm">
    <div class="modal-header border-0">
      <h5 class="modal-title fw-bold">Add Farm</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="resetFarmForm('addFarmForm', 'addFarmError')"></button>
    </div>
    <div class="modal-body">
      <div id="addFarmError" class="alert alert-danger py-2 small mb-3" style="display: none;"><i class="fa-solid fa-triangle-exclamation me-1"></i>This data already exists.</div>
      <input type="hidden" name="add_farm" value="1">
      <div class="mb-3">
        <label class="form-label">Farmer <span class="text-danger">*</span></label>
        <select class="form-select" name="farmer_id" required>
          <option value="" disabled selected>-- Select Farmer --</option>
          <?php foreach ($farmers as $f): ?><option value="<?php echo $f['farmer_id']; ?>"><?php echo htmlspecialchars($f['first_name'] . ' ' . $f['last_name']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3"><label class="form-label">Farm Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="farm_name" required></div>
      <div class="mb-3"><label class="form-label">Location <span class="text-danger">*</span></label><input type="text" class="form-control" name="location" required></div>
      <div class="row g-2 mb-3">
        <div class="col-6"><label class="form-label">Area (ha) <span class="text-danger">*</span></label><input type="number" step="0.01" class="form-control" name="area_hectares" required></div>
        <div class="col-6">
          <label class="form-label">Soil Type <span class="text-danger">*</span></label>
          <select class="form-select" name="soil_id" required>
            <option value="" disabled selected>-- Select Soil --</option>
            <?php foreach ($soilTypes as $st): ?><option value="<?php echo $st['soil_id']; ?>"><?php echo htmlspecialchars($st['soil_description']); ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Irrigation Type <span class="text-danger">*</span></label>
        <select class="form-select" name="irrigation_id" required>
          <option value="" disabled selected>-- Select Irrigation --</option>
          <?php foreach ($irrigationTypes as $it): ?><option value="<?php echo $it['irrigation_id']; ?>"><?php echo htmlspecialchars($it['irrigation_description']); ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="modal-footer border-0">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" onclick="resetFarmForm('addFarmForm', 'addFarmError')">Cancel</button>
      <button type="submit" class="btn btn-pink btn-sm">Save Farm</button>
    </div>
  </form>
</div></div></div>

<!-- Add Confirmation Sub-Modal -->
<div class="modal fade" id="confirmAddFarmModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header border-0"><h5 class="modal-title fw-bold text-warning"><i class="fa-solid fa-triangle-exclamation me-2"></i>Confirm Addition</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><p class="text-muted">Are you sure you want to add this new farm record?</p></div>
  <div class="modal-footer border-0">
    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-pink btn-sm" onclick="document.getElementById('addFarmForm').submit()">Yes, Save</button>
  </div>
</div></div></div>

<!-- Edit Modal -->
<div class="modal fade" id="editFarmModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <form action="farm.php" method="POST" id="editFarmForm">
    <div class="modal-header border-0">
      <h5 class="modal-title fw-bold">Edit Farm</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="resetFarmForm('editFarmForm', 'editFarmError')"></button>
    </div>
    <div class="modal-body">
      <!-- Alert banner for No-Changes detection -->
      <div id="editFarmAlert" class="alert alert-danger py-2 small mb-3" style="display: none;">
        <i class="fa-solid fa-triangle-exclamation me-1"></i><span id="editFarmAlertText">No changes were made to this farm record.</span>
      </div>

      <div id="editFarmError" class="alert alert-danger py-2 small mb-3" style="display: none;"><i class="fa-solid fa-triangle-exclamation me-1"></i>This data already exists.</div>
      
      <input type="hidden" name="update_farm" value="1">
      <input type="hidden" id="edit_farm_id" name="farm_id">

      <!-- Hidden Original State Inputs for No-Change Detection -->
      <input type="hidden" id="original_farmer_id">
      <input type="hidden" id="original_farm_name">
      <input type="hidden" id="original_location">
      <input type="hidden" id="original_area_hectares">
      <input type="hidden" id="original_soil_id">
      <input type="hidden" id="original_irrigation_id">

      <div class="mb-3">
        <label class="form-label">Farmer <span class="text-danger">*</span></label>
        <select class="form-select" id="edit_farmer_id" name="farmer_id" required>
          <?php foreach ($farmers as $f): ?><option value="<?php echo $f['farmer_id']; ?>"><?php echo htmlspecialchars($f['first_name'] . ' ' . $f['last_name']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3"><label class="form-label">Farm Name <span class="text-danger">*</span></label><input type="text" class="form-control" id="edit_farm_name" name="farm_name" required></div>
      <div class="mb-3"><label class="form-label">Location <span class="text-danger">*</span></label><input type="text" class="form-control" id="edit_location" name="location" required></div>
      <div class="row g-2 mb-3">
        <div class="col-6"><label class="form-label">Area (ha) <span class="text-danger">*</span></label><input type="number" step="0.01" class="form-control" id="edit_area_hectares" name="area_hectares" required></div>
        <div class="col-6">
          <label class="form-label">Soil Type <span class="text-danger">*</span></label>
          <select class="form-select" id="edit_soil_id" name="soil_id" required>
            <?php foreach ($soilTypes as $st): ?><option value="<?php echo $st['soil_id']; ?>"><?php echo htmlspecialchars($st['soil_description']); ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="mb-3">
        <label class="form-label">Irrigation Type <span class="text-danger">*</span></label>
        <select class="form-select" id="edit_irrigation_id" name="irrigation_id" required>
          <?php foreach ($irrigationTypes as $it): ?><option value="<?php echo $it['irrigation_id']; ?>"><?php echo htmlspecialchars($it['irrigation_description']); ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="modal-footer border-0">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" onclick="resetFarmForm('editFarmForm', 'editFarmError')">Cancel</button>
      <button type="submit" class="btn btn-pink btn-sm">Update Changes</button>
    </div>
  </form>
</div></div></div>

<!-- Edit Confirmation Sub-Modal -->
<div class="modal fade" id="confirmEditFarmModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header border-0"><h5 class="modal-title fw-bold text-warning"><i class="fa-solid fa-triangle-exclamation me-2"></i>Confirm Updates</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><p class="text-muted">Are you sure you want to save changes to this farm record?</p></div>
  <div class="modal-footer border-0">
    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-pink btn-sm" onclick="document.getElementById('editFarmForm').submit()">Yes, Save</button>
  </div>
</div></div></div>

<?php if ($isAdmin): ?>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header border-0"><h5 class="modal-title fw-bold text-danger">Confirm Deletion</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><p class="text-muted">Are you sure you want to delete this farm record?</p></div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><a href="#" id="confirmDeleteBtn" class="btn btn-danger btn-sm">Yes, Delete</a></div>
</div></div></div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const existingFarms = <?php echo json_encode(array_map(function($f) {
    return [
      'id' => (string)$f['farm_id'],
      'farmer_id' => (string)$f['farmer_id'],
      'farm_name' => strtolower(trim($f['farm_name'])),
      'location' => strtolower(trim($f['location'] ?? ''))
    ];
  }, $farms)); ?>;

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
    document.getElementById('view_farm_id').textContent = '#' + this.dataset.id;
    document.getElementById('view_farm_name').textContent = this.dataset.farmname;
    document.getElementById('view_farmer_name').textContent = this.dataset.farmer;
    document.getElementById('view_location').textContent = this.dataset.location || 'N/A';
    document.getElementById('view_area').textContent = this.dataset.area + ' ha';
    document.getElementById('view_soil').textContent = this.dataset.soil;
    document.getElementById('view_irrigation').textContent = this.dataset.irrigation;
  }));

  function openEditModal(farm) {
    document.getElementById('edit_farm_id').value = farm.farm_id;
    document.getElementById('edit_farmer_id').value = farm.farmer_id;
    document.getElementById('edit_farm_name').value = farm.farm_name;
    document.getElementById('edit_location').value = farm.location || '';
    document.getElementById('edit_area_hectares').value = farm.area_hectares;
    document.getElementById('edit_soil_id').value = farm.soil_id || '';
    document.getElementById('edit_irrigation_id').value = farm.irrigation_id || '';

    // Store original values for no-change detection
    document.getElementById('original_farmer_id').value = farm.farmer_id;
    document.getElementById('original_farm_name').value = farm.farm_name;
    document.getElementById('original_location').value = farm.location || '';
    document.getElementById('original_area_hectares').value = farm.area_hectares;
    document.getElementById('original_soil_id').value = farm.soil_id || '';
    document.getElementById('original_irrigation_id').value = farm.irrigation_id || '';
    
    document.getElementById('editFarmError').style.display = 'none';
    const editAlert = document.getElementById('editFarmAlert');
    if (editAlert) editAlert.style.display = 'none';

    new bootstrap.Modal(document.getElementById('editFarmModal')).show();
  }

  function resetFarmForm(formId, errorId) {
    document.getElementById(formId).reset();
    document.getElementById(errorId).style.display = 'none';
    const editAlert = document.getElementById('editFarmAlert');
    if (editAlert) editAlert.style.display = 'none';
  }

  document.getElementById('addFarmForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const farmerId = this.querySelector('[name="farmer_id"]').value.trim();
    const farmName = this.querySelector('[name="farm_name"]').value.trim();
    const location = this.querySelector('[name="location"]').value.trim();
    const errorEl = document.getElementById('addFarmError');

    const isDuplicate = existingFarms.some(f => {
      return f.farmer_id === farmerId &&
             f.farm_name === farmName.toLowerCase() &&
             f.location === location.toLowerCase();
    });

    if (isDuplicate) {
      errorEl.style.display = 'block';
      return;
    }

    errorEl.style.display = 'none';
    bootstrap.Modal.getInstance(document.getElementById('newFarmModal')).hide();
    new bootstrap.Modal(document.getElementById('confirmAddFarmModal')).show();
  });

  document.getElementById('editFarmForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const currentId = document.getElementById('edit_farm_id').value;
    const farmerId = document.getElementById('edit_farmer_id').value.trim();
    const farmName = document.getElementById('edit_farm_name').value.trim();
    const location = document.getElementById('edit_location').value.trim();
    const area = document.getElementById('edit_area_hectares').value.trim();
    const soilId = document.getElementById('edit_soil_id').value;
    const irrigationId = document.getElementById('edit_irrigation_id').value;

    const origFarmerId = document.getElementById('original_farmer_id').value;
    const origFarmName = document.getElementById('original_farm_name').value;
    const origLocation = document.getElementById('original_location').value;
    const origArea = document.getElementById('original_area_hectares').value;
    const origSoilId = document.getElementById('original_soil_id').value;
    const origIrrigationId = document.getElementById('original_irrigation_id').value;

    const alertDiv = document.getElementById('editFarmAlert');
    const errorEl = document.getElementById('editFarmError');

    // Check if nothing was changed
    if (farmerId === origFarmerId && farmName === origFarmName && location === origLocation && area === origArea && soilId === origSoilId && irrigationId === origIrrigationId) {
      alertDiv.style.display = 'block';
      errorEl.style.display = 'none';
      return;
    }
    alertDiv.style.display = 'none';

    const isDuplicate = existingFarms.some(f => {
      if (f.id === String(currentId)) return false;
      return f.farmer_id === farmerId &&
             f.farm_name === farmName.toLowerCase() &&
             f.location === location.toLowerCase();
    });

    if (isDuplicate) {
      errorEl.style.display = 'block';
      return;
    }

    errorEl.style.display = 'none';
    bootstrap.Modal.getInstance(document.getElementById('editFarmModal')).hide();
    new bootstrap.Modal(document.getElementById('confirmEditFarmModal')).show();
  });

  <?php if ($isAdmin): ?>
  document.querySelectorAll('.delete-btn').forEach(b => b.addEventListener('click', function() {
    document.getElementById('confirmDeleteBtn').href = 'farm.php?delete_id=' + this.dataset.id;
  }));
  <?php endif; ?>
</script>
<script src="table_filter.js"></script>
</body>
</html>