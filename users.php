<?php
$pageTitle = 'Users Management';
require_once 'config/database.php';
require_once 'models/models.php';

// Only admins can access this page
requireAdmin();


$userModel = new User();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Handle form submissions
if ($_POST) {
    $data = [
        'username' => trim($_POST['username']),
        'email' => trim($_POST['email']),
        'password' => $_POST['password'] ?? '',
        'role' => $_POST['role']
    ];
    
    // Validate email uniqueness
    $emailExists = $userModel->emailExists($data['email'], $action == 'edit' ? $id : null);
    
    if ($emailExists) {
        $error = 'Email address already exists.';
    } elseif ($action == 'create') {
        if (empty($data['password'])) {
            $error = 'Password is required for new users.';
        } elseif ($userModel->create($data)) {
            header('Location: users.php?success=created');
            exit;
        } else {
            $error = 'Failed to create user.';
        }
    } elseif ($action == 'edit' && $id) {
        if ($userModel->update($id, $data)) {
            header('Location: users.php?success=updated');
            exit;
        } else {
            $error = 'Failed to update user.';
        }
    }
}

// Handle delete
if ($action == 'delete' && $id && $_POST) {
    // Prevent deleting the current user
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
    <!-- Search -->
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

    <!-- Users List -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
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
                                <strong><?php echo htmlspecialchars($usr['username']); ?></strong>
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
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="deleteUser(<?php echo $usr['id']; ?>)" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($users)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
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
    <!-- User Form -->
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
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
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <h6><i class="bi bi-info-circle"></i> Role Permissions:</h6>
                    <ul class="mb-0">
                        <li><strong>Admin:</strong> Full access to all modules including user management</li>
                        <li><strong>User:</strong> Can manage assets and employees only</li>
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
    <!-- User View -->
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
                                <?php echo htmlspecialchars($user['username']); ?>
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
                    </table>
                </div>
                
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
                    <button type="button" class="btn btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">
                        <i class="bi bi-trash"></i> Delete User
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this user? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$additionalJs = "
<script>
function deleteUser(id) {
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    document.getElementById('deleteForm').action = 'users.php?action=delete&id=' + id;
    deleteModal.show();
}
</script>
";

require_once 'includes/layout.php';
?>