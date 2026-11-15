<?php
require_once "auth_check.php";
require_once "Database.php";
require_once "db_function.php";

$db = new Database();
$pdo = $db->connect();
$currentUserId = $_SESSION['user_id'] ?? null;
$wala = new Wala($pdo, $currentUserId);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $data = [
            'user_id' => $currentUserId, 
            'username' => trim($_POST['username']), 
            'full_name' => trim($_POST['full_name']), 
            'role_id' => $_POST['current_role_id']
        ];
        if ($wala->updateUser($data)) {
            $_SESSION['username'] = $data['username'];
            $_SESSION['full_name'] = $data['full_name'];
            $success = "Profile has been successfully updated!";
        } else {
            $error = "Failed to update profile. Username might already be taken.";
        }
    } elseif (!empty($isAdmin) && $action === 'create_user') {
        $data = [
            'username' => trim($_POST['username']), 
            'full_name' => trim($_POST['full_name']), 
            'password' => $_POST['password'],
            'role_id' => $_POST['role_id']
        ];
        if ($wala->createUser($data)) {
            $success = "User account created successfully.";
        } else {
            $error = "Failed to create user account. The username is already taken.";
        }
    } elseif (!empty($isAdmin) && $action === 'update_user') {
        $data = [
            'user_id' => $_POST['user_id'], 
            'username' => trim($_POST['username']), 
            'full_name' => trim($_POST['full_name']), 
            'role_id' => $_POST['role_id']
        ];
        if ($wala->updateUser($data)) {
            $success = "User account updated successfully.";
        } else {
            $error = "Failed to update user account. The username may already be taken.";
        }
    } elseif (!empty($isAdmin) && $action === 'toggle_status') {
        $target_id = $_POST['user_id'];
        $new_status = $_POST['new_status'];
        if ($target_id != $currentUserId) {
            if ($wala->updateUserStatus($target_id, $new_status)) {
                $success = "Account status successfully changed to " . $new_status . ".";
            } else {
                $error = "Failed to update account status.";
            }
        } else {
            $error = "You cannot change the status of your own active account.";
        }
    } elseif (!empty($isAdmin) && $action === 'delete_user') {
        $delete_id = $_POST['user_id'];
        if ($delete_id != $currentUserId) {
            if ($wala->deleteUser($delete_id)) {
                $success = "User account deleted successfully.";
            } else {
                $error = "Failed to delete user account.";
            }
        } else {
            $error = "You cannot delete your own active account.";
        }
    } elseif ($action === 'update_password') {
        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            $error = "New passwords do not match.";
        } else {
            if ($wala->updatePassword($currentUserId, $_POST['current_password'], $_POST['new_password'])) {
                $success = "Password updated successfully.";
            } else {
                $error = "Incorrect current password.";
            }
        }
    }
}

$currentUserStmt = $pdo->prepare("
    SELECT u.username, u.full_name, u.role_id, COALESCE(ur.role_description, 'Staff') AS role_name, u.created_at, TO_CHAR(u.created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at,u.updated_at 
    ,TO_CHAR(u.updated_at, 'YYYY-MM-DD HH24:MI:SS') AS updated_at 
    FROM users u LEFT JOIN user_role ur ON u.role_id = ur.role_id WHERE u.user_id = ?
");
$currentUserStmt->execute([$currentUserId]);
$currentUserData = $currentUserStmt->fetch(PDO::FETCH_ASSOC);

$displayUsername = $currentUserData['username'] ?? ($_SESSION['username'] ?? '');
$displayFullName = $currentUserData['full_name'] ?? ($_SESSION['full_name'] ?? '');
$displayRoleId   = $currentUserData['role_id'] ?? 1;
$displayRole     = $currentUserData['role_name'] ?? ($_SESSION['role'] ?? 'Staff');

$active_users = [];
$deactivated_users = [];
$roles = [];
if (!empty($isAdmin)) {
    $activeStmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.full_name, u.role_id, ur.role_description AS role, TO_CHAR(u.created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at, 
            TO_CHAR(u.updated_at, 'YYYY-MM-DD HH24:MI:SS') AS updated_at, COALESCE(u.status, 'Active') AS status 
        FROM users u LEFT JOIN user_role ur ON u.role_id = ur.role_id 
        WHERE u.user_id != ? AND (LOWER(u.status) = 'active' OR u.status IS NULL OR u.status = '') ORDER BY u.user_id DESC
    ");
    $activeStmt->execute([$currentUserId]);
    $active_users = $activeStmt->fetchAll(PDO::FETCH_ASSOC);

    $deactivatedStmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.full_name, u.role_id, ur.role_description AS role, TO_CHAR(u.created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at, 
            TO_CHAR(u.updated_at, 'YYYY-MM-DD HH24:MI:SS') AS updated_at, u.status 
        FROM users u LEFT JOIN user_role ur ON u.role_id = ur.role_id 
        WHERE u.user_id != ? AND LOWER(u.status) = 'deactivated' ORDER BY u.user_id DESC
    ");
    $deactivatedStmt->execute([$currentUserId]);
    $deactivated_users = $deactivatedStmt->fetchAll(PDO::FETCH_ASSOC);

    $roles = $wala->getAllUserRoles();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cropella - Profile & Admin Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=2">
    <style>
        .btn-pink { background-color: #d63384; color: white; border: none; }
        .btn-pink:hover { background-color: #b02a6b; color: white; }
        .text-pink { color: #d63384 !important; }
        .border-pink { border-color: #d63384 !important; }
        .nav-tabs .nav-link.active { color: #d63384; font-weight: bold; }
        .form-control:not(:disabled) { color: #000 !important; }
        .form-control:focus, .form-select:focus { border-color: #d63384 !important; box-shadow: 0 0 0 0.25rem rgba(214, 51, 132, 0.25) !important; }
    </style>
</head>
<body class="bg-light">

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
                <?php if (!empty($isAdmin)): ?>
                    <li><a href="audit_log.php" class="nav-link"><i class="fa-solid fa-clock-rotate-left me-2"></i> Audit Logs</a></li>
                <?php endif; ?>
            </ul>
            <hr class="text-white-50">
            <div class="dropdown px-2">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown">
                    <i class="fa-solid fa-circle-user fa-lg me-2"></i><strong><?php echo htmlspecialchars($displayFullName); ?></strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                    <li><a class="dropdown-item" href="profile.php"><i class="fa-solid fa-user-pen me-2"></i> Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="login.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Sign out</a></li>
                </ul>
            </div>
        </nav>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert"><i class="fa-solid fa-circle-check me-2"></i><?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if ($error && $error !== 'Incorrect current password.'): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert"><i class="fa-solid fa-triangle-exclamation me-2"></i><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <div class="card p-4 shadow-sm mb-4 w-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold m-0"><i class="fa-solid fa-user-pen text-pink"></i> My Profile & Password Management</h4>
                    <button class="btn btn-outline-primary btn-sm border-pink text-pink shadow-none" type="button" id="toggleProfileEditBtn" onclick="toggleEdit('profileUsername', 'profileFullName', 'toggleProfileEditBtn', 'profileSaveContainer')">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit Profile
                    </button>
                </div>

                <form method="POST" id="profileForm">
                    <input type="hidden" name="action" value="update_profile">
                    <input type="hidden" name="current_role_id" value="<?php echo $displayRoleId; ?>">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Username</label>
                            <input type="text" name="username" id="profileUsername" class="form-control text-dark" value="<?php echo htmlspecialchars($displayUsername); ?>" disabled required>
                            <div id="profileMessageError" class="text-danger small mt-1" style="display: none;"><i class="fa-solid fa-triangle-exclamation me-1"></i><span id="profileMessageText"></span></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Full Name</label>
                            <input type="text" name="full_name" id="profileFullName" class="form-control text-dark" value="<?php echo htmlspecialchars($displayFullName); ?>" disabled required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Role <span class="text-muted fw-normal">(Fixed)</span></label>
                            <input type="text" class="form-control bg-light text-dark" value="<?php echo htmlspecialchars($displayRole); ?>" disabled>
                        </div>
                    </div>
                    <div id="profileSaveContainer" style="display: none;" class="mt-2">
                        <button type="button" class="btn btn-pink px-4" onclick="validateAndConfirmProfile()"><i class="fa-solid fa-floppy-disk me-1"></i> Save Changes</button>
                        <button type="button" class="btn btn-secondary px-3 ms-2" onclick="cancelEdit('profileUsername', 'profileFullName', 'toggleProfileEditBtn', 'profileSaveContainer')">Cancel</button>
                    </div>

                    <div class="modal fade" id="confirmProfileModal" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i> Confirm Update</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body text-start">Are you sure you want to update your profile details?</div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-pink btn-sm" onclick="document.getElementById('profileForm').submit()">Yes, Update</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                
                <hr class="my-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="fw-bold m-0">Change Password</h5>
                    <button class="btn btn-link text-pink text-decoration-none p-0 fw-semibold shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#passwordCollapse">
                        <i class="fa-solid fa-key me-1"></i> Change Password
                    </button>
                </div>

                <div class="collapse <?php echo ($error === 'Incorrect current password.') ? 'show' : ''; ?>" id="passwordCollapse">
                    <form method="POST" class="mt-3" id="passwordForm">
                        <input type="hidden" name="action" value="update_password">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Current Password</label>
                                <input type="password" name="current_password" id="currentPasswordInput" class="form-control text-dark <?php echo ($error === 'Incorrect current password.') ? 'is-invalid' : ''; ?>" required>
                                <?php if ($error === 'Incorrect current password.'): ?>
                                    <div class="text-danger small mt-1"><i class="fa-solid fa-triangle-exclamation me-1"></i>Incorrect current password.</div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">New Password</label>
                                <input type="password" name="new_password" id="newPasswordInput" class="form-control text-dark" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">Confirm New Password</label>
                                <input type="password" name="confirm_password" id="confirmPasswordInput" class="form-control text-dark" required>
                                <div id="passwordMismatchError" class="text-danger small mt-1" style="display: none;"><i class="fa-solid fa-triangle-exclamation me-1"></i>New passwords do not match.</div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-pink px-4">Save Password</button>
                    </form>
                </div>
            </div>

            <?php if (!empty($isAdmin)): ?>
                <div class="card p-4 shadow-sm w-100">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                        <h4 class="fw-bold m-0"><i class="fa-solid fa-users-gear text-pink"></i> Admin Area: Accounts Management</h4>
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group" style="width: 250px;">
                                <span class="input-group-text bg-white"><i class="fa-solid fa-search text-muted"></i></span>
                                <input type="text" id="accountSearchInput" class="form-control text-dark" placeholder="Search accounts...">
                            </div>
                            <button class="btn btn-pink btn-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#createModal"><i class="fa-solid fa-user-plus me-1"></i> Create Account</button>
                        </div>
                    </div>

                    <ul class="nav nav-tabs mb-3" id="accountTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-accounts" type="button" role="tab">
                                <i class="fa-solid fa-user-check me-1 text-success"></i> Active Accounts (<?php echo count($active_users); ?>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-secondary" id="deactivated-tab" data-bs-toggle="tab" data-bs-target="#deactivated-accounts" type="button" role="tab">
                                <i class="fa-solid fa-user-slash me-1 text-danger"></i> Deactivated Accounts (<?php echo count($deactivated_users); ?>)
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="accountTabsContent">
                        <div class="tab-pane fade show active" id="active-accounts" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle accounts-table">
                                    <thead class="table-dark">
                                        <tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th class="text-end">Actions</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($active_users)): ?>
                                            <tr><td colspan="5" class="text-center text-muted py-3">No active accounts found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($active_users as $u): ?>
                                                <tr>
                                                    <td><?php echo $u['user_id']; ?></td>
                                                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($u['role'] ?? 'User'); ?></span></td>
                                                    <td class="text-end">
                                                        <button class="btn btn-sm btn-outline-info" onclick='openViewModal(<?php echo json_encode($u); ?>)' title="View"><i class="fa-solid fa-eye"></i></button>
                                                        <button class="btn btn-sm btn-outline-primary" onclick='openEditModal(<?php echo json_encode($u); ?>)' title="Edit"><i class="fa-solid fa-pen"></i></button>
                                                        <button class="btn btn-sm btn-outline-warning text-dark" onclick='openStatusModal(<?php echo $u['user_id']; ?>, "<?php echo htmlspecialchars($u['username']); ?>", "Deactivated")' title="Deactivate"><i class="fa-solid fa-user-slash"></i></button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick='openDeleteModal(<?php echo $u['user_id']; ?>, "<?php echo htmlspecialchars($u['username']); ?>")' title="Delete"><i class="fa-solid fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="deactivated-accounts" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle accounts-table">
                                    <thead class="table-dark">
                                        <tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th class="text-end">Actions</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($deactivated_users)): ?>
                                            <tr><td colspan="5" class="text-center text-muted py-3">No deactivated accounts found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($deactivated_users as $u): ?>
                                                <tr>
                                                    <td><?php echo $u['user_id']; ?></td>
                                                    <td><?php echo htmlspecialchars($u['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($u['role'] ?? 'User'); ?></span></td>
                                                    <td class="text-end">
                                                        <button class="btn btn-sm btn-outline-info" onclick='openViewModal(<?php echo json_encode($u); ?>)' title="View"><i class="fa-solid fa-eye"></i></button>
                                                        <button class="btn btn-sm btn-outline-success" onclick='openStatusModal(<?php echo $u['user_id']; ?>, "<?php echo htmlspecialchars($u['username']); ?>", "Active")' title="Activate"><i class="fa-solid fa-user-check"></i></button>
                                                        <button class="btn btn-sm btn-outline-danger" onclick='openDeleteModal(<?php echo $u['user_id']; ?>, "<?php echo htmlspecialchars($u['username']); ?>")' title="Delete"><i class="fa-solid fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php if (!empty($isAdmin)): ?>
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="createAccountForm" onsubmit="return handleCreateSubmit(event)">
                <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-user-plus text-pink"></i> Admin: Create New Account</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body text-start">
                    <input type="hidden" name="action" value="create_user">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" id="createUsername" class="form-control text-dark" required>
                        <div id="usernameError" class="text-danger small mt-1" style="display: none;"><i class="fa-solid fa-triangle-exclamation me-1"></i>Username already exists in the inventory.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" id="createFullName" class="form-control text-dark" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Password <span class="text-danger">*</span> <span class="text-muted fw-normal">(Plain text)</span></label>
                        <input type="password" name="password" id="createPassword" class="form-control text-dark" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Role <span class="text-danger">*</span></label>
                        <select name="role_id" id="createRoleId" class="form-select text-dark" required>
                            <option value="" disabled selected>Select a role...</option>
                            <?php foreach ($roles as $r): ?><option value="<?php echo $r['role_id']; ?>"><?php echo htmlspecialchars($r['role_description']); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-pink">Create Account</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="sharedViewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="viewModalTitle"><i class="fa-solid fa-circle-info text-pink"></i> Account Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-start">
                <ul class="list-group list-group-flush" id="viewModalContent"></ul>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

<div class="modal fade" id="sharedEditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="sharedEditForm" onsubmit="return handleEditSubmit(event)">
                <div class="modal-header"><h5 class="modal-title" id="editModalTitle"><i class="fa-solid fa-pen text-pink"></i> Admin: Edit Account</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body text-start">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" id="editUserId">
                    <input type="hidden" id="editOriginalUsername">
                    <input type="hidden" id="editOriginalFullName">
                    <input type="hidden" id="editOriginalRoleId">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" id="editUsername" class="form-control text-dark" required>
                        <div id="editUsernameError" class="text-danger small mt-1" style="display: none;"><i class="fa-solid fa-triangle-exclamation me-1"></i>Username already exists in the inventory.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" id="editFullName" class="form-control text-dark" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Role <span class="text-danger">*</span></label>
                        <select name="role_id" id="editRoleId" class="form-select text-dark" required>
                            <?php foreach ($roles as $r): ?><option value="<?php echo $r['role_id']; ?>"><?php echo htmlspecialchars($r['role_description']); ?></option><?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-pink">Save Changes</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="sharedStatusModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5 class="modal-title" id="statusModalTitle"><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i> Confirm Status Change</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body text-start">Are you sure you want to change the status to <strong id="statusActionText"></strong> for the account <strong id="statusAccountName"></strong>?</div>
                <div class="modal-footer">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="user_id" id="statusUserId">
                    <input type="hidden" name="new_status" id="statusNewStatus">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-pink btn-sm">Yes, Proceed</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="sharedDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5 class="modal-title"><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i> Confirm Deletion</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body text-start">Are you sure you want to delete the account <strong id="deleteAccountName"></strong>?</div>
                <div class="modal-footer">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">Yes, Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const existingUsernames = ["<?php echo htmlspecialchars($displayUsername); ?>", <?php foreach ($active_users as $u) { echo '"' . htmlspecialchars($u['username']) . '",'; } foreach ($deactivated_users as $u) { echo '"' . htmlspecialchars($u['username']) . '",'; } ?>];
const currentOriginalUsername = "<?php echo htmlspecialchars($displayUsername); ?>";
const currentOriginalFullName = "<?php echo htmlspecialchars($displayFullName); ?>";

function toggleEdit(userField, nameField, btnId, saveId) {
    document.getElementById(userField).removeAttribute('disabled');
    document.getElementById(nameField).removeAttribute('disabled');
    document.getElementById(btnId).style.display = 'none';
    document.getElementById(saveId).style.display = 'block';
    hideProfileMessage();
}

function cancelEdit(userField, nameField, btnId, saveId) {
    document.getElementById(userField).value = currentOriginalUsername;
    document.getElementById(nameField).value = currentOriginalFullName;
    document.getElementById(userField).setAttribute('disabled', 'true');
    document.getElementById(nameField).setAttribute('disabled', 'true');
    document.getElementById(btnId).style.display = 'inline-block';
    document.getElementById(saveId).style.display = 'none';
    hideProfileMessage();
}

function showProfileMessage(message, isError = true) {
    let errorDiv = document.getElementById('profileMessageError');
    let textSpan = document.getElementById('profileMessageText');
    errorDiv.className = isError ? "text-danger small mt-1" : "text-success small mt-1";
    errorDiv.style.display = 'block';
    textSpan.textContent = message;
}

function hideProfileMessage() {
    document.getElementById('profileMessageError').style.display = 'none';
    document.getElementById('profileUsername').classList.remove('is-invalid');
}

function validateAndConfirmProfile() {
    let usernameInput = document.getElementById('profileUsername');
    let fullNameInput = document.getElementById('profileFullName');
    let username = usernameInput.value.trim();
    let fullName = fullNameInput.value.trim();

    if (!username || !fullName) {
        showProfileMessage('Username and Full Name cannot be empty.');
        return;
    }

    if (username === currentOriginalUsername && fullName === currentOriginalFullName) {
        showProfileMessage('No changes were made to your profile.');
        return;
    }

    if (username !== currentOriginalUsername && existingUsernames.includes(username)) {
        showProfileMessage('Username already exists.');
        usernameInput.classList.add('is-invalid');
        return;
    }

    hideProfileMessage();
    new bootstrap.Modal(document.getElementById('confirmProfileModal')).show();
}

function openViewModal(user) {
    document.getElementById('viewModalTitle').innerHTML = `<i class="fa-solid fa-circle-info text-pink"></i> Account Details (ID: ${user.user_id})`;
    document.getElementById('viewModalContent').innerHTML = `
        <li class="list-group-item"><strong>User ID:</strong> ${user.user_id}</li>
        <li class="list-group-item"><strong>Username:</strong> ${user.username}</li>
        <li class="list-group-item"><strong>Full Name:</strong> ${user.full_name}</li>
        <li class="list-group-item"><strong>Role ID:</strong> ${user.role_id}</li>
        <li class="list-group-item"><strong>Role Description:</strong> ${user.role || 'User'}</li>
        <li class="list-group-item"><strong>Created At:</strong> ${user.created_at || 'N/A'}</li>
        <li class="list-group-item"><strong>Updated At:</strong> ${user.updated_at || 'N/A'}</li>
    `;
    new bootstrap.Modal(document.getElementById('sharedViewModal')).show();
}

function openEditModal(user) {
    document.getElementById('editUserId').value = user.user_id;
    document.getElementById('editUsername').value = user.username;
    document.getElementById('editOriginalUsername').value = user.username;
    document.getElementById('editFullName').value = user.full_name;
    document.getElementById('editOriginalFullName').value = user.full_name;
    document.getElementById('editRoleId').value = user.role_id;
    document.getElementById('editOriginalRoleId').value = user.role_id;
    document.getElementById('editUsernameError').style.display = 'none';
    document.getElementById('editUsername').classList.remove('is-invalid');
    document.getElementById('editModalTitle').innerHTML = `<i class="fa-solid fa-pen text-pink"></i> Admin: Edit Account: ${user.username}`;
    new bootstrap.Modal(document.getElementById('sharedEditModal')).show();
}

function openStatusModal(userId, username, targetStatus) {
    document.getElementById('statusUserId').value = userId;
    document.getElementById('statusNewStatus').value = targetStatus;
    document.getElementById('statusAccountName').textContent = username;
    document.getElementById('statusActionText').textContent = targetStatus;
    new bootstrap.Modal(document.getElementById('sharedStatusModal')).show();
}

function openDeleteModal(userId, username) {
    document.getElementById('deleteUserId').value = userId;
    document.getElementById('deleteAccountName').textContent = username;
    new bootstrap.Modal(document.getElementById('sharedDeleteModal')).show();
}

function handleCreateSubmit(event) {
    let username = document.getElementById('createUsername').value.trim();
    let errorDiv = document.getElementById('usernameError');
    if (existingUsernames.includes(username)) {
        event.preventDefault();
        errorDiv.style.display = 'block';
        return false;
    }
    errorDiv.style.display = 'none';
    return true;
}

function handleEditSubmit(event) {
    let username = document.getElementById('editUsername').value.trim();
    let fullName = document.getElementById('editFullName').value.trim();
    let roleId = document.getElementById('editRoleId').value;
    
    let origUsername = document.getElementById('editOriginalUsername').value;
    let origFullName = document.getElementById('editOriginalFullName').value;
    let origRoleId = document.getElementById('editOriginalRoleId').value;
    let errorDiv = document.getElementById('editUsernameError');

    if (username === origUsername && fullName === origFullName && roleId === origRoleId) {
        event.preventDefault();
        alert('No changes were made to this account.');
        return false;
    }

    if (username !== origUsername && existingUsernames.includes(username)) {
        event.preventDefault();
        errorDiv.style.display = 'block';
        document.getElementById('editUsername').classList.add('is-invalid');
        return false;
    }

    errorDiv.style.display = 'none';
    return true;
}

document.getElementById('passwordForm')?.addEventListener('submit', function(event) {
    let newPass = document.getElementById('newPasswordInput').value;
    let confirmPass = document.getElementById('confirmPasswordInput').value;
    let mismatchDiv = document.getElementById('passwordMismatchError');

    if (newPass !== confirmPass) {
        event.preventDefault();
        mismatchDiv.style.display = 'block';
        document.getElementById('confirmPasswordInput').classList.add('is-invalid');
    } else {
        mismatchDiv.style.display = 'none';
        document.getElementById('confirmPasswordInput').classList.remove('is-invalid');
    }
});

document.getElementById('accountSearchInput')?.addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    document.querySelectorAll('.accounts-table tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
    });
});
</script>
</body>
</html>