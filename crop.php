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
    if (!$isAdmin) { $delete_error = "Access denied: Staff cannot manage crops."; }
    else {
        try { $wala->DeleteCrop($_GET['delete_id']); header("Location: crop.php"); exit(); }
        catch (\Exception $e) { $delete_error = $e->getMessage(); }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isAdmin) { $insertion_error = "Access denied: Staff cannot add/edit crops."; }
    elseif (isset($_POST['add_crop'])) {
        try { $wala->addCrop($_POST); header("Location: crop.php"); exit(); }
        catch (\Exception $e) { $insertion_error = $e->getMessage(); }
    } elseif (isset($_POST['update_crop'])) {
        try { $wala->updateCrop($_POST); header("Location: crop.php"); exit(); }
        catch (\Exception $e) { $update_error = $e->getMessage(); }
    }
}

try {
    $crops = $wala->getAllCrops($sort);
    if (!is_array($crops)) { $crops = []; }
    $categories = $wala->getAllCropCategories();
    if (!is_array($categories)) { $categories = []; }
    $seasons = $wala->getAllSeasons();
    if (!is_array($seasons)) { $seasons = []; }
} catch (\Exception $e) {
    $crops = $categories = $seasons = [];
    $connection_error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cropella | Crops Catalog</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
</head>
<body>

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
        <li><a href="crop.php" class="nav-link active"><i class="fa-solid fa-seedling me-2"></i> Crops</a></li>
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
        <div><h1 class="h2 text-dark fw-bold">CROPS</h1><p class="text-muted mb-0">Master reference list of crop varieties and growing cycles.</p></div>
        <?php if ($isAdmin): ?>
          <button type="button" class="btn btn-pink shadow-sm" data-bs-toggle="modal" data-bs-target="#newCropModal"><i class="fa-solid fa-plus me-1"></i> Add Crop</button>
        <?php else: ?>
          <span class="badge bg-secondary px-3 py-2"><i class="fa-solid fa-eye me-1"></i> View Only (Admin Managed)</span>
        <?php endif; ?>
      </div>

      <?php if (isset($delete_error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($delete_error); ?></div><?php endif; ?>
      <?php if (isset($insertion_error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($insertion_error); ?></div><?php endif; ?>
      <?php if (isset($update_error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($update_error); ?></div><?php endif; ?>

      <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body bg-white py-3">
          <div class="row g-2 align-items-end">
            <div class="col-md-3">
              <label class="form-label small fw-bold text-secondary">Filter By</label>
              <select id="filterCategory" class="form-select form-select-sm">
                <option value="all">All Fields</option><option value="id">ID</option><option value="name">Crop Name</option><option value="variety">Variety</option><option value="category">Category</option>
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
        <div class="card-header bg-white py-3"><h5 class="fw-bold mb-0 text-secondary">Crop List (<span id="visibleCount"><?php echo count($crops); ?></span>)</h5></div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr><th><a href="?sort=<?php echo $next; ?>" class="text-dark text-decoration-none">ID <i class="fa-solid fa-sort"></i></a></th><th>Crop Name</th><th>Variety</th><th>Category</th><th>Growing Period (Days)</th><th>Season</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
              <?php if (!empty($crops)): ?>
                <?php foreach ($crops as $c): ?>
                  <tr class="data-row">
                    <td class="col-id">#<?php echo $c['crop_id']; ?></td>
                    <td class="col-name fw-semibold"><?php echo htmlspecialchars($c['crop_name']); ?></td>
                    <td class="col-variety"><?php echo htmlspecialchars($c['variety'] ?? 'N/A'); ?></td>
                    <td class="col-category"><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($c['crop_category'] ?? 'N/A'); ?></span></td>
                    <td><?php echo $c['growing_period_days']; ?></td>
                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($c['recommended_season'] ?? 'N/A'); ?></span></td>
                    <td class="text-end">
                      <div class="d-flex justify-content-end gap-1">
                        <button class="btn btn-sm btn-outline-info view-btn" data-bs-toggle="modal" data-bs-target="#viewCropModal" data-id="<?php echo $c['crop_id']; ?>" data-cropname="<?php echo htmlspecialchars($c['crop_name']); ?>" data-variety="<?php echo htmlspecialchars($c['variety'] ?? 'N/A'); ?>" data-category="<?php echo htmlspecialchars($c['crop_category'] ?? 'N/A'); ?>" data-period="<?php echo $c['growing_period_days']; ?>" data-season="<?php echo htmlspecialchars($c['recommended_season'] ?? 'N/A'); ?>" data-status="<?php echo htmlspecialchars($c['status'] ?? 'Active'); ?>"><i class="fa-solid fa-eye"></i></button>
                        <?php if ($isAdmin): ?>
                          <button class="btn btn-sm btn-outline-primary edit-btn" onclick='openEditModal(<?php echo json_encode($c); ?>)'><i class="fa-solid fa-pen"></i></button>
                          <button class="btn btn-sm btn-outline-danger delete-btn" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?php echo $c['crop_id']; ?>"><i class="fa-solid fa-trash"></i></button>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <tr id="noMatchesRow" style="display: none;"><td colspan="7" class="text-center py-4 text-muted">No records matched.</td></tr>
              <?php else: ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">No records found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewCropModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header border-0 bg-light"><h5 class="modal-title fw-bold">Crop Information</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body py-4"><ul class="list-group list-group-flush">
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">ID:</span><strong id="view_crop_id"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Crop Name:</span><strong id="view_crop_name"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Variety:</span><strong id="view_variety"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Category:</span><strong id="view_category"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Growing Period:</span><strong id="view_period"></strong></li>
    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Season:</span><strong id="view_season"></strong></li>
    </ul></div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button></div>
</div></div></div>

<?php if ($isAdmin): ?>
<!-- Add Modal -->
<div class="modal fade" id="newCropModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <form action="crop.php" method="POST" id="addCropForm">
    <div class="modal-header border-0">
      <h5 class="modal-title fw-bold">Add New Crop</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="resetCropForm('addCropForm', 'addCropError')"></button>
    </div>
    <div class="modal-body">
      <div id="addCropError" class="alert alert-danger py-2 small mb-3" style="display: none;"><i class="fa-solid fa-triangle-exclamation me-1"></i>This crop and variety combination already exists.</div>
      <input type="hidden" name="add_crop" value="1">
      <div class="mb-3"><label class="form-label">Crop Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="crop_name" required></div>
      <div class="mb-3"><label class="form-label">Variety <span class="text-danger">*</span></label><input type="text" class="form-control" name="variety" required></div>
      <div class="mb-3">
        <label class="form-label">Category <span class="text-danger">*</span></label>
        <select class="form-select" name="category_id" required>
          <option value="">-- Select Category --</option>
          <?php foreach ($categories as $cat): ?><option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_description']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Growing Period (Days) <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="growing_period_days" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Season <span class="text-danger">*</span></label>
        <select class="form-select" name="season_id" required>
          <option value="">-- Select Season --</option>
          <?php foreach ($seasons as $sea): ?><option value="<?php echo $sea['season_id']; ?>"><?php echo htmlspecialchars($sea['season_description']); ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="modal-footer border-0">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" onclick="resetCropForm('addCropForm', 'addCropError')">Cancel</button>
      <button type="submit" class="btn btn-pink btn-sm">Save Crop</button>
    </div>
  </form>
</div></div></div>

<!-- Add Confirmation Sub-Modal -->
<div class="modal fade" id="confirmAddCropModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header border-0"><h5 class="modal-title fw-bold text-warning"><i class="fa-solid fa-triangle-exclamation me-2"></i>Confirm Addition</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><p class="text-muted">Are you sure you want to add this new crop record?</p></div>
  <div class="modal-footer border-0">
    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-pink btn-sm" onclick="document.getElementById('addCropForm').submit()">Yes, Save</button>
  </div>
</div></div></div>

<!-- Edit Modal -->
<div class="modal fade" id="editCropModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <form action="crop.php" method="POST" id="editCropForm">
    <div class="modal-header border-0">
      <h5 class="modal-title fw-bold">Edit Crop</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="resetCropForm('editCropForm', 'editCropError')"></button>
    </div>
    <div class="modal-body">
      <div id="editCropError" class="alert alert-danger py-2 small mb-3" style="display: none;"><i class="fa-solid fa-triangle-exclamation me-1"></i><span id="editCropErrorText">This crop and variety combination already exists.</span></div>
      <input type="hidden" name="update_crop" value="1">
      <input type="hidden" id="edit_crop_id" name="crop_id">
      
      <!-- Hidden Original State Inputs for No-Change Detection -->
      <input type="hidden" id="original_crop_name">
      <input type="hidden" id="original_variety">
      <input type="hidden" id="original_category_id">
      <input type="hidden" id="original_growing_period_days">
      <input type="hidden" id="original_season_id">

      <div class="mb-3"><label class="form-label">Crop Name <span class="text-danger">*</span></label><input type="text" class="form-control" id="edit_crop_name" name="crop_name" required></div>
      <div class="mb-3"><label class="form-label">Variety <span class="text-danger">*</span></label><input type="text" class="form-control" id="edit_variety" name="variety" required></div>
      <div class="mb-3">
        <label class="form-label">Category <span class="text-danger">*</span></label>
        <select class="form-select" id="edit_category_id" name="category_id" required>
          <option value="">-- Select Category --</option>
          <?php foreach ($categories as $cat): ?><option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_description']); ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Growing Period (Days) <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="edit_growing_period_days" name="growing_period_days" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Season <span class="text-danger">*</span></label>
        <select class="form-select" id="edit_season_id" name="season_id" required>
          <option value="">-- Select Season --</option>
          <?php foreach ($seasons as $sea): ?><option value="<?php echo $sea['season_id']; ?>"><?php echo htmlspecialchars($sea['season_description']); ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="modal-footer border-0">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" onclick="resetCropForm('editCropForm', 'editCropError')">Cancel</button>
      <button type="submit" class="btn btn-pink btn-sm">Update Changes</button>
    </div>
  </form>
</div></div></div>

<!-- Edit Confirmation Sub-Modal -->
<div class="modal fade" id="confirmEditCropModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header border-0"><h5 class="modal-title fw-bold text-warning"><i class="fa-solid fa-triangle-exclamation me-2"></i>Confirm Updates</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><p class="text-muted">Are you sure you want to save changes to this crop record?</p></div>
  <div class="modal-footer border-0">
    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-pink btn-sm" onclick="document.getElementById('editCropForm').submit()">Yes, Save</button>
  </div>
</div></div></div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header border-0"><h5 class="modal-title fw-bold text-danger">Confirm Deletion</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><p class="text-muted">Are you sure you want to delete this crop record?</p></div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><a href="#" id="confirmDeleteBtn" class="btn btn-danger btn-sm">Yes, Delete</a></div>
</div></div></div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const existingCrops = <?php echo json_encode(array_map(function($c) {
    return [
      'id' => (string)$c['crop_id'],
      'crop_name' => strtolower(trim($c['crop_name'])),
      'variety' => strtolower(trim($c['variety'] ?? ''))
    ];
  }, $crops)); ?>;

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
    document.getElementById('view_crop_id').textContent = '#' + this.dataset.id;
    document.getElementById('view_crop_name').textContent = this.dataset.cropname;
    document.getElementById('view_variety').textContent = this.dataset.variety;
    document.getElementById('view_category').textContent = this.dataset.category;
    document.getElementById('view_period').textContent = this.dataset.period + ' days';
    document.getElementById('view_season').textContent = this.dataset.season;
  }));

  function openEditModal(crop) {
    document.getElementById('edit_crop_id').value = crop.crop_id;
    document.getElementById('edit_crop_name').value = crop.crop_name;
    document.getElementById('edit_variety').value = crop.variety || '';
    document.getElementById('edit_category_id').value = crop.category_id || '';
    document.getElementById('edit_growing_period_days').value = crop.growing_period_days;
    document.getElementById('edit_season_id').value = crop.season_id || '';

    // Store original values for no-change comparison
    document.getElementById('original_crop_name').value = crop.crop_name;
    document.getElementById('original_variety').value = crop.variety || '';
    document.getElementById('original_category_id').value = crop.category_id || '';
    document.getElementById('original_growing_period_days').value = crop.growing_period_days;
    document.getElementById('original_season_id').value = crop.season_id || '';
    
    document.getElementById('editCropError').style.display = 'none';
    new bootstrap.Modal(document.getElementById('editCropModal')).show();
  }

  function resetCropForm(formId, errorId) {
    document.getElementById(formId).reset();
    document.getElementById(errorId).style.display = 'none';
  }

  document.getElementById('addCropForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const cropName = this.querySelector('[name="crop_name"]').value.trim();
    const variety = this.querySelector('[name="variety"]').value.trim();
    const errorEl = document.getElementById('addCropError');

    const isDuplicate = existingCrops.some(c => {
      return c.crop_name === cropName.toLowerCase() && c.variety === variety.toLowerCase();
    });

    if (isDuplicate) {
      errorEl.textContent = 'This crop and variety combination already exists.';
      errorEl.style.display = 'block';
      return;
    }

    errorEl.style.display = 'none';
    bootstrap.Modal.getInstance(document.getElementById('newCropModal')).hide();
    new bootstrap.Modal(document.getElementById('confirmAddCropModal')).show();
  });

  document.getElementById('editCropForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const currentId = document.getElementById('edit_crop_id').value;
    const cropName = document.getElementById('edit_crop_name').value.trim();
    const variety = document.getElementById('edit_variety').value.trim();
    const categoryId = document.getElementById('edit_category_id').value;
    const growingPeriod = document.getElementById('edit_growing_period_days').value.trim();
    const seasonId = document.getElementById('edit_season_id').value;
    
    const origCropName = document.getElementById('original_crop_name').value;
    const origVariety = document.getElementById('original_variety').value;
    const origCategoryId = document.getElementById('original_category_id').value;
    const origGrowingPeriod = document.getElementById('original_growing_period_days').value;
    const origSeasonId = document.getElementById('original_season_id').value;

    const errorEl = document.getElementById('editCropError');
    const errorText = document.getElementById('editCropErrorText');

    // Check if nothing was changed
    if (cropName === origCropName && variety === origVariety && categoryId === origCategoryId && growingPeriod === origGrowingPeriod && seasonId === origSeasonId) {
      errorText.textContent = 'No changes were made to this crop record.';
      errorEl.style.display = 'block';
      return;
    }

    const isDuplicate = existingCrops.some(c => {
      if (c.id === String(currentId)) return false;
      return c.crop_name === cropName.toLowerCase() && c.variety === variety.toLowerCase();
    });

    if (isDuplicate) {
      errorText.textContent = 'This crop and variety combination already exists.';
      errorEl.style.display = 'block';
      return;
    }

    errorEl.style.display = 'none';
    bootstrap.Modal.getInstance(document.getElementById('editCropModal')).hide();
    new bootstrap.Modal(document.getElementById('confirmEditCropModal')).show();
  });

  <?php if ($isAdmin): ?>
  document.querySelectorAll('.delete-btn').forEach(b => b.addEventListener('click', function() {
    document.getElementById('confirmDeleteBtn').href = 'crop.php?delete_id=' + this.dataset.id;
  }));
  <?php endif; ?>
</script>
<script src="table_filter.js"></script>
</body>
</html>