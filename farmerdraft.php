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
    if (!$isAdmin) { $delete_error = "Access denied: Admins only."; }
    else {
        try { $wala->DeleteFarmer($_GET['delete_id']); header("Location: farmerdraft.php"); exit(); }
        catch (\Exception $e) { $delete_error = $e->getMessage(); }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_farmer'])) {
        try { $wala->addFarmer($_POST); header("Location: farmerdraft.php"); exit(); }
        catch (\Exception $e) { $insertion_error = $e->getMessage(); }
    } elseif (isset($_POST['update_farmer'])) {
        try { $wala->updateFarmer($_POST); header("Location: farmerdraft.php"); exit(); }
        catch (\Exception $e) { $update_error = $e->getMessage(); }
    }
}

try { 
    $farmers = $wala->getAllFarmers($sort); 
    if (!is_array($farmers)) { $farmers = []; }
} catch (\Exception $e) { 
    $farmers = []; 
    $connection_error = $e->getMessage(); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cropella | Farmers Management</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Mobile Header Bar: Burger icon & Cropella on the top-left -->
<div class="text-white d-md-none p-3 d-flex align-items-center w-100 shadow-sm gap-3" style="background: linear-gradient(135deg, #d63384 0%, #85144b 100%);">
  <button class="btn btn-outline-light btn-sm px-2 py-1" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-label="Toggle navigation">
    <i class="fa-solid fa-bars"></i>
  </button>
  <div class="d-flex align-items-center">
    <i class="fa-solid fa-seedling me-2"></i>
    <span class="fw-bold fs-5">Cropella</span>
  </div>
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
        <li><a href="farmerdraft.php" class="nav-link active"><i class="fa-solid fa-user-gear me-2"></i> Farmers</a></li>
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
        </ul> </div>
    </nav>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
      <?php if (isset($insertion_error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($insertion_error); ?></div><?php endif; ?>
      <?php if (isset($update_error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($update_error); ?></div><?php endif; ?>
      <?php if (isset($delete_error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($delete_error); ?></div><?php endif; ?>
      <?php if (isset($connection_error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($connection_error); ?></div><?php endif; ?>

      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <div><h1 class="h2 text-dark fw-bold">FARMERS MANAGEMENT</h1><p class="text-muted mb-0">Manage registered producer records.</p></div>
        <button type="button" class="btn btn-pink shadow-sm" data-bs-toggle="modal" data-bs-target="#newFarmerModal"><i class="fa-solid fa-plus me-1"></i> Add Farmer</button>
      </div>

      <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body bg-white py-3">
          <div class="row g-2 align-items-end">
            <div class="col-md-3">
              <label class="form-label small fw-bold text-secondary">Filter By</label>
              <select id="filterCategory" class="form-select form-select-sm">
                <option value="all">All Fields</option><option value="id">ID</option><option value="name">Name</option><option value="contact">Contact</option><option value="address">Address</option>
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
        <div class="card-header bg-white py-3"><h5 class="fw-bold mb-0 text-secondary">Farmers (<span id="visibleCount"><?php echo count($farmers); ?></span>)</h5></div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="farmersTable">
            <thead class="table-light">
              <tr><th><a href="?sort=<?php echo $next; ?>" class="text-dark text-decoration-none">ID <i class="fa-solid fa-sort"></i></a></th><th>Full Name</th><th>Contact</th><th>Address</th><th>Registered</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
              <?php if (!empty($farmers)): ?>
                <?php foreach ($farmers as $f): ?>
                  <tr class="data-row">
                    <td class="col-id">#<?php echo $f['farmer_id']; ?></td>
                    <td class="col-name fw-semibold"><?php echo htmlspecialchars($f['first_name'] . ' ' . $f['last_name']); ?></td>
                    <td class="col-contact"><?php echo htmlspecialchars($f['contact_number']); ?></td>
                    <td class="col-address"><?php echo htmlspecialchars($f['address']); ?></td>
                    <td><?php echo htmlspecialchars($f['date_registered']); ?></td>
                    <td class="text-end">
                      <div class="d-flex justify-content-end gap-1">
                        <button class="btn btn-sm btn-outline-info view-btn" data-bs-toggle="modal" data-bs-target="#viewFarmerModal" data-id="<?php echo $f['farmer_id']; ?>" data-firstname="<?php echo htmlspecialchars($f['first_name']); ?>" data-lastname="<?php echo htmlspecialchars($f['last_name']); ?>" data-contact="<?php echo htmlspecialchars($f['contact_number']); ?>" data-address="<?php echo htmlspecialchars($f['address']); ?>" data-registered="<?php echo htmlspecialchars($f['date_registered']); ?>"><i class="fa-solid fa-eye"></i></button>
                        <button class="btn btn-sm btn-outline-primary edit-btn" onclick='openEditModal(<?php echo json_encode($f); ?>)'><i class="fa-solid fa-pen"></i></button>
                        <?php if ($isAdmin): ?><button class="btn btn-sm btn-outline-danger delete-btn" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?php echo $f['farmer_id']; ?>"><i class="fa-solid fa-trash"></i></button><?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <tr id="noMatchesRow" style="display: none;"><td colspan="6" class="text-center py-4 text-muted">No records matched.</td></tr>
              <?php else: ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">No records found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewFarmerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header border-0 bg-light"><h5 class="modal-title fw-bold">Farmer Profile</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body py-4"><ul class="list-group list-group-flush">
      <li class="list-group-item d-flex justify-content-between"><span class="text-muted">ID:</span><strong id="view_farmer_id"></strong></li>
      <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Name:</span><strong id="view_full_name"></strong></li>
      <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Contact:</span><strong id="view_contact_number"></strong></li>
      <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Address:</span><strong id="view_address"></strong></li>
      <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Registered:</span><strong id="view_date_registered"></strong></li>
    </ul></div>
    <div class="modal-footer border-0"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button></div>
  </div></div>
</div>

<!-- Add Farmer Modal -->
<div class="modal fade" id="newFarmerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form action="farmerdraft.php" method="POST" id="addFarmerForm" onsubmit="return handleAddFarmerSubmit(event)">
      <div class="modal-header border-0"><h5 class="modal-title fw-bold">Add New Farmer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="add_farmer" value="1">
        <div class="row g-2 mb-3">
          <div class="col-6"><label class="form-label">First Name</label><input type="text" class="form-control" name="first_name" required></div>
          <div class="col-6"><label class="form-label">Last Name</label><input type="text" class="form-control" name="last_name" required></div>
        </div>
        <div class="mb-3">
          <label class="form-label">Contact Number</label>
          <input type="text" class="form-control" name="contact_number" id="addContactNumber" maxlength="11" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="e.g. 09123456789" required>
          <div id="addContactError" class="text-danger small mt-1" style="display: none;"><i class="fa-solid fa-triangle-exclamation me-1"></i><span id="addContactErrorText">Contact number must be 11 digits.</span></div>
        </div>
        <div class="mb-3"><label class="form-label">Address</label><input type="text" class="form-control" name="address" required></div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-pink btn-sm">Save Farmer</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Add Confirmation Sub-Modal -->
<div class="modal fade" id="confirmAddFarmerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header border-0"><h5 class="modal-title fw-bold text-warning"><i class="fa-solid fa-triangle-exclamation me-2"></i>Confirm Addition</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><p class="text-muted">Are you sure you want to add this new farmer record?</p></div>
    <div class="modal-footer border-0">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
      <button type="button" class="btn btn-pink btn-sm" id="confirmAddSubmitBtn">Yes, Save</button>
    </div>
  </div></div>
</div>

<!-- Edit Farmer Modal -->
<div class="modal fade" id="editFarmerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form action="farmerdraft.php" method="POST" id="editFarmerForm" onsubmit="return handleEditFarmerSubmit(event)">
      <div class="modal-header border-0"><h5 class="modal-title fw-bold">Edit Farmer</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="update_farmer" value="1">
        <input type="hidden" id="edit_farmer_id" name="farmer_id">
        <input type="hidden" id="editOriginalContact">
        <div class="row g-2 mb-3">
          <div class="col-6"><label class="form-label">First Name</label><input type="text" class="form-control" id="edit_first_name" name="first_name" required></div>
          <div class="col-6"><label class="form-label">Last Name</label><input type="text" class="form-control" id="edit_last_name" name="last_name" required></div>
        </div>
        <div class="mb-3">
          <label class="form-label">Contact Number</label>
          <input type="text" class="form-control" id="edit_contact_number" name="contact_number" maxlength="11" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
          <div id="editContactError" class="text-danger small mt-1" style="display: none;"><i class="fa-solid fa-triangle-exclamation me-1"></i><span id="editContactErrorText">Contact number must be 11 digits.</span></div>
        </div>
        <div class="mb-3"><label class="form-label">Address</label><input type="text" class="form-control" id="edit_address" name="address" required></div>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-pink btn-sm">Update Changes</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Edit Confirmation Sub-Modal -->
<div class="modal fade" id="confirmEditFarmerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header border-0"><h5 class="modal-title fw-bold text-warning"><i class="fa-solid fa-triangle-exclamation me-2"></i>Confirm Updates</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><p class="text-muted">Are you sure you want to save changes to this farmer record?</p></div>
    <div class="modal-footer border-0">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
      <button type="button" class="btn btn-pink btn-sm" id="confirmEditSubmitBtn">Yes, Save</button>
    </div>
  </div></div>
</div>

<?php if ($isAdmin): ?>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header border-0"><h5 class="modal-title fw-bold text-danger">Confirm Deletion</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><p class="text-muted">Are you sure you want to delete this record?</p></div>
  <div class="modal-footer border-0"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button><a href="#" id="confirmDeleteBtn" class="btn btn-danger btn-sm">Yes, Delete</a></div>
</div></div></div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const existingContacts = [
    <?php if (!empty($farmers) && is_array($farmers)) { foreach ($farmers as $f) { if(!empty($f['contact_number'])) { echo '"' . htmlspecialchars($f['contact_number']) . '",'; } } } ?>
  ];

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
    document.getElementById('view_farmer_id').textContent = '#' + this.dataset.id;
    document.getElementById('view_full_name').textContent = this.dataset.firstname + ' ' + this.dataset.lastname;
    document.getElementById('view_contact_number').textContent = this.dataset.contact || 'N/A';
    document.getElementById('view_address').textContent = this.dataset.address || 'N/A';
    document.getElementById('view_date_registered').textContent = this.dataset.registered;
  }));

  function openEditModal(farmer) {
    document.getElementById('edit_farmer_id').value = farmer.farmer_id;
    document.getElementById('edit_first_name').value = farmer.first_name;
    document.getElementById('edit_last_name').value = farmer.last_name;
    document.getElementById('edit_contact_number').value = farmer.contact_number;
    document.getElementById('editOriginalContact').value = farmer.contact_number;
    document.getElementById('edit_address').value = farmer.address;
    document.getElementById('editContactError').style.display = 'none';
    document.getElementById('edit_contact_number').classList.remove('is-invalid');
    new bootstrap.Modal(document.getElementById('editFarmerModal')).show();
  }

  let isAddConfirmed = false;
  function handleAddFarmerSubmit(event) {
    if (isAddConfirmed) return true;
    event.preventDefault();
    let form = document.getElementById('addFarmerForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return false;
    }

    let contactInput = document.getElementById('addContactNumber');
    let contactVal = contactInput.value.trim();
    let errorDiv = document.getElementById('addContactError');
    let errorText = document.getElementById('addContactErrorText');

    if (contactVal.length !== 11) {
        errorText.textContent = 'Contact number must be exactly 11 digits.';
        errorDiv.style.display = 'block';
        contactInput.classList.add('is-invalid');
        return false;
    }

    if (existingContacts.includes(contactVal)) {
        errorText.textContent = 'Contact number already exists.';
        errorDiv.style.display = 'block';
        contactInput.classList.add('is-invalid');
        return false;
    }

    errorDiv.style.display = 'none';
    contactInput.classList.remove('is-invalid');

    bootstrap.Modal.getInstance(document.getElementById('newFarmerModal')).hide();
    let confirmModal = new bootstrap.Modal(document.getElementById('confirmAddFarmerModal'));
    confirmModal.show();

    document.getElementById('confirmAddSubmitBtn').onclick = function() {
        isAddConfirmed = true;
        form.submit();
    };
    return false;
  }

  let isEditConfirmed = false;
  function handleEditFarmerSubmit(event) {
    if (isEditConfirmed) return true;
    event.preventDefault();
    let form = document.getElementById('editFarmerForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return false;
    }

    let contactInput = document.getElementById('edit_contact_number');
    let contactVal = contactInput.value.trim();
    let originalVal = document.getElementById('editOriginalContact').value;
    let errorDiv = document.getElementById('editContactError');
    let errorText = document.getElementById('editContactErrorText');

    if (contactVal.length !== 11) {
        errorText.textContent = 'Contact number must be exactly 11 digits.';
        errorDiv.style.display = 'block';
        contactInput.classList.add('is-invalid');
        return false;
    }

    if (contactVal !== originalVal && existingContacts.includes(contactVal)) {
        errorText.textContent = 'Contact number already exists.';
        errorDiv.style.display = 'block';
        contactInput.classList.add('is-invalid');
        return false;
    }

    errorDiv.style.display = 'none';
    contactInput.classList.remove('is-invalid');

    bootstrap.Modal.getInstance(document.getElementById('editFarmerModal')).hide();
    let confirmModal = new bootstrap.Modal(document.getElementById('confirmEditFarmerModal'));
    confirmModal.show();

    document.getElementById('confirmEditSubmitBtn').onclick = function() {
        isEditConfirmed = true;
        form.submit();
    };
    return false;
  }

  <?php if ($isAdmin): ?>
  document.querySelectorAll('.delete-btn').forEach(b => b.addEventListener('click', function() {
    document.getElementById('confirmDeleteBtn').href = 'farmerdraft.php?delete_id=' + this.dataset.id;
  }));
  <?php endif; ?>
</script>
<script src="table_filter.js"></script>
</body>
</html>