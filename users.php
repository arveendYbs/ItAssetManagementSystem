<?php
/**
 * Users Management Page
 * Location: users.php (root directory)
 * Purpose: Admin-only page for managing system users
 */

$pageTitle = 'Users Management';
require_once 'config/database.php';    // Database connection
require_once 'includes/auth.php';      // Authentication functions
require_once 'models/models.php';      // Data models

// Only admins can access this page - this will redirect if not admin
requireAdmin();

$userModel = new User();
$action = $_GET['action'] ?? 'list';  // Default action is 'list'
$id = $_GET['id'] ?? null;            // Get user ID if provided

// Handle form submissions (POST requests)
if ($_POST) {
    // Collect form data
    $data = [
        'name' => trim($_POST['name']),
        'email' => trim($_POST['email']),
        'password' => $_POST['password'] ?? '',
        'role' => $_POST['role']
    ];
    
    // Validate email uniqueness
    $emailExists = $userModel->emailExists($data['email'], $action == 'edit' ? $id : null);
    
    if ($emailExists) {
        $error = 'Email address already exists.';
    } elseif ($action == 'create') {
        // Creating new user - password is required
        if (empty($data['password'])) {
            $error = 'Password is required for new users.';
        } elseif ($userModel->create($data)) {
            header('Location: users.php?success=created');
            exit;
        } else {
            $error = 'Failed to create user.';
        }
    } elseif ($action == 'edit' && $id) {
        // Editing existing user
        if ($userModel->update($id, $data)) {
            header('Location: users.php?success=updated');
            exit;
        } else {
            $error = 'Failed to update user.';
        }
    }
}

// Handle delete requests
if ($action == 'delete' && $id && $_POST) {
    // Prevent deleting the current user (prevent lockout)
    $currentUser = getCurrentUser();
    if ($id == $currentUser['id']) {
        header('Location: users.php?error=cannot_delete_self');
        exit;
    }
    
    if ($userModel->delete($id)) {
        header('Location: users.php?success=deleted');
        exit;
    } else {
        header('Location: users.php?error=delete_failed');
        exit;
    }
}

// Get data for forms and display
$user = null;
if (($action == 'edit' || $action == 'view') && $id) {
    $user = $userModel->getById($id);
    if (!$user) {
        header('Location: users.php?error=not_found');
        exit;
    }
}

// Start output buffering to capture content
ob_start();
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="bi bi-person-gear me-2"></i>
        <?php 
        switch($action) {
            case 'create': echo 'Add New User'; break;
            case 'edit': echo 'Edit User'; break;
            case 'view': echo 'User Details'; break;
            default: echo 'Users Management';
        }
        ?>
    </h1>
    <?php if ($action == 'list'): ?>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="users.php?action=create" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle"></i> Add User
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger">
    <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['error']) && $_GET['error'] == 'cannot_delete_self'): ?>
<div class="alert alert-warning">
    You cannot delete your own user account.
</div>
<?php endif; ?>

<?php if ($action == 'list'): ?>
    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-10">
                    <label for="search" class="form-label">Search Users</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Username or email..." 
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Users List Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $searchTerm = $_GET['search'] ?? '';
                        $users = $userModel->getAll($searchTerm);
                        $currentUserId = getCurrentUser()['id'];
                        
                        foreach($users as $usr):
                        ?>
                        <tr <?php echo $usr['id'] == $currentUserId ? 'class="table-info"' : ''; ?>>
                            <td>
                                <strong><?php echo htmlspecialchars($usr['name']); ?></strong>
                                <?php if ($usr['id'] == $currentUserId): ?>
                                    <span class="badge bg-primary ms-2">You</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($usr['email']); ?></td>
                            <td>
                                <?php
                                $roleColor = $usr['role'] == 'Admin' ? 'danger' : 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $roleColor; ?>"><?php echo htmlspecialchars($usr['role']); ?></span>
                            </td>
                            <td>
                                <?php if ($usr['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($usr['created_at'])); ?></td>
                            <td class="table-actions">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="users.php?action=view&id=<?php echo $usr['id']; ?>" 
                                       class="btn btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="users.php?action=edit&id=<?php echo $usr['id']; ?>" 
                                       class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($usr['id'] != $currentUserId): ?>
                                        <?php if ($usr['is_active']): ?>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="deactivateUser(<?php echo $usr['id']; ?>)" title="Deactivate">
                                                <i class="bi bi-person-x"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-outline-success" 
                                                    onclick="reactivateUser(<?php echo $usr['id']; ?>)" title="Reactivate">
                                                <i class="bi bi-person-check"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($users)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="bi bi-person-x display-1"></i><br>
                                No users found
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($action == 'create' || $action == 'edit'): ?>
    <!-- User Form (Create/Edit) -->
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                Password <?php echo $action == 'create' ? '*' : '(leave empty to keep current)'; ?>
                            </label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   <?php echo $action == 'create' ? 'required' : ''; ?>>
                            <?php if ($action == 'edit'): ?>
                            <div class="form-text">Leave empty to keep the current password.</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label">Role *</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="Admin" <?php echo ($user['role'] ?? '') == 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="User" <?php echo ($user['role'] ?? '') == 'User' ? 'selected' : ''; ?>>User</option>
                                <option value="Staff" <?php echo ($user['role'] ?? '') == 'Staff' ? 'selected' : ''; ?>>Staff</option>
                                <option value="Manager" <?php echo ($user['role'] ?? '') == 'Manager' ? 'selected' : ''; ?>>Manager</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="is_active" class="form-label">Status *</label>
                            <select class="form-select" id="is_active" name="is_active" required>
                                <option value="1" <?php echo ($user['is_active'] ?? 1) == 1 ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo ($user['is_active'] ?? 1) == 0 ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Role Permissions Info -->
                <div class="alert alert-info">
                    <h6><i class="bi bi-info-circle"></i> Role Permissions:</h6>
                    <ul class="mb-0">
                        <li><strong>Admin:</strong> Full access to all modules including user management</li>
                        <li><strong>User:</strong> Can manage assets and employees only</li>
                        <li><strong>Staff:</strong> Limited access to view and basic operations</li>
                        <li><strong>Manager:</strong> Enhanced access for department management</li>
                    </ul>
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="users.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to List
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> <?php echo $action == 'create' ? 'Create User' : 'Update User'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

<?php elseif ($action == 'view'): ?>
    <!-- User View (Read-only) -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">User Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold">Username:</td>
                            <td>
                                <?php echo htmlspecialchars($user['name']); ?>
                                <?php if ($user['id'] == getCurrentUser()['id']): ?>
                                    <span class="badge bg-primary ms-2">You</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Email:</td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Role:</td>
                            <td>
                                <?php
                                $roleColor = $user['role'] == 'Admin' ? 'danger' : 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $roleColor; ?>"><?php echo htmlspecialchars($user['role']); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Created:</td>
                            <td><?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?></td>
                        </tr>
                        <?php if (isset($user['updated_at']) && $user['updated_at']): ?>
                        <tr>
                            <td class="fw-bold">Last Updated:</td>
                            <td><?php echo date('M j, Y g:i A', strtotime($user['updated_at'])); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
                
                <!-- Role Permissions Display -->
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-header">
                            <h6 class="mb-0">Permissions for <?php echo htmlspecialchars($user['role']); ?> Role</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($user['role'] == 'Admin'): ?>
                            <ul class="list-unstyled mb-0">
                                <li><i class="bi bi-check-circle text-success"></i> Manage Assets</li>
                                <li><i class="bi bi-check-circle text-success"></i> Manage Employees</li>
                                <li><i class="bi bi-check-circle text-success"></i> Manage Users</li>
                                <li><i class="bi bi-check-circle text-success"></i> View Reports</li>
                                <li><i class="bi bi-check-circle text-success"></i> Export Data</li>
                            </ul>
                            <?php elseif ($user['role'] == 'Manager'): ?>
                            <ul class="list-unstyled mb-0">
                                <li><i class="bi bi-check-circle text-success"></i> Manage Assets</li>
                                <li><i class="bi bi-check-circle text-success"></i> Manage Employees</li>
                                <li><i class="bi bi-x-circle text-danger"></i> Manage Users</li>
                                <li><i class="bi bi-check-circle text-success"></i> View Reports</li>
                                <li><i class="bi bi-check-circle text-success"></i> Export Data</li>
                            </ul>
                            <?php elseif ($user['role'] == 'Staff'): ?>
                            <ul class="list-unstyled mb-0">
                                <li><i class="bi bi-check-circle text-success"></i> View Assets</li>
                                <li><i class="bi bi-check-circle text-success"></i> View Employees</li>
                                <li><i class="bi bi-x-circle text-danger"></i> Manage Users</li>
                                <li><i class="bi bi-check-circle text-success"></i> View Reports</li>
                                <li><i class="bi bi-x-circle text-danger"></i> Export Data</li>
                            </ul>
                            <?php else: ?>
                            <ul class="list-unstyled mb-0">
                                <li><i class="bi bi-check-circle text-success"></i> Manage Assets</li>
                                <li><i class="bi bi-check-circle text-success"></i> Manage Employees</li>
                                <li><i class="bi bi-x-circle text-danger"></i> Manage Users</li>
                                <li><i class="bi bi-check-circle text-success"></i> View Reports</li>
                                <li><i class="bi bi-check-circle text-success"></i> Export Data</li>
                            </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between mt-4">
                <a href="users.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
                <div>
                    <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Edit User
                    </a>
                    <?php if ($user['id'] != getCurrentUser()['id']): ?>
                        <?php if ($user['is_active']): ?>
                            <button type="button" class="btn btn-warning" onclick="deactivateUser(<?php echo $user['id']; ?>)">
                                <i class="bi bi-person-x"></i> Deactivate User
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-success" onclick="reactivateUser(<?php echo $user['id']; ?>)">
                                <i class="bi bi-person-check"></i> Reactivate User
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Deactivate/Reactivate Modals -->
<div class="modal fade" id="deactivateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deactivation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to deactivate this user? They will not be able to log in until reactivated.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deactivateForm" method="POST" style="display: inline;">
                    <button type="submit" class="btn btn-warning">Deactivate</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reactivateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Reactivation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to reactivate this user? They will be able to log in again.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="reactivateForm" method="POST" style="display: inline;">
                    <button type="submit" class="btn btn-success">Reactivate</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Capture the output and assign to $content variable
$content = ob_get_clean();

// JavaScript for deactivate/reactivate confirmation
$additionalJs = "
<script>
function deactivateUser(id) {
    const deactivateModal = new bootstrap.Modal(document.getElementById('deactivateModal'));
    document.getElementById('deactivateForm').action = 'users.php?action=delete&id=' + id;
    deactivateModal.show();
}

function reactivateUser(id) {
    const reactivateModal = new bootstrap.Modal(document.getElementById('reactivateModal'));
    document.getElementById('reactivateForm').action = 'users.php?action=reactivate&id=' + id;
    reactivateModal.show();
}

// Legacy function for backward compatibility
function deleteUser(id) {
    deactivateUser(id);
}
</script>
";

// Include the main layout template
require_once 'includes/layout.php';
?>
