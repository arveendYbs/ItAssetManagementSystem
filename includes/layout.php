<?php
/**
 * Main Layout Template
 */
require_once 'includes/auth.php';
requireLogin();
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>IT Asset Management</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }
        
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        .navbar-brand {
            padding-top: .75rem;
            padding-bottom: .75rem;
        }
        
        .navbar .navbar-toggler {
            top: .25rem;
            right: 1rem;
        }
        
        .main {
            margin-left: 240px;
            padding: 20px;
        }
        
        @media (max-width: 767.98px) {
            .sidebar {
                position: relative;
                height: auto;
            }
            .main {
                margin-left: 0;
            }
        }
        
        .nav-link.active {
            background-color: #0d6efd;
            color: white !important;
        }
        
        .card-stats {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .table-actions {
            min-width: 120px;
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="dashboard.php">
            <i class="bi bi-pc-display"></i> IT Asset Management
        </a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="navbar-nav">
            <div class="nav-item text-nowrap">
                <div class="dropdown">
                    <a class="nav-link px-3 dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($currentUser['username']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text">Role: <?php echo htmlspecialchars($currentUser['role']); ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation -->
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="bi bi-house-door"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'assets.php') ? 'active' : ''; ?>" href="assets.php">
                                <i class="bi bi-laptop"></i> Assets
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'employees.php') ? 'active' : ''; ?>" href="employees.php">
                                <i class="bi bi-people"></i> Employees
                            </a>
                        </li>
                        <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>" href="users.php">
                                <i class="bi bi-person-gear"></i> Users
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>" href="reports.php">
                                <i class="bi bi-bar-chart"></i> Reports
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main">
                <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    switch($_GET['success']) {
                        case 'created': echo 'Record created successfully!'; break;
                        case 'updated': echo 'Record updated successfully!'; break;
                        case 'deleted': echo 'Record deleted successfully!'; break;
                        default: echo 'Operation completed successfully!';
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    switch($_GET['error']) {
                        case 'access_denied': echo 'Access denied! Admin privileges required.'; break;
                        case 'not_found': echo 'Record not found!'; break;
                        case 'delete_failed': echo 'Cannot delete record. It may be referenced by other records.'; break;
                        case 'email_exists': echo 'Email already exists!'; break;
                        default: echo 'An error occurred. Please try again.';
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php echo $content ?? ''; ?>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Confirm delete actions
        function confirmDelete(message = 'Are you sure you want to delete this record?') {
            return confirm(message);
        }
    </script>
    
    <?php if (isset($additionalJs)): ?>
        <?php echo $additionalJs; ?>
    <?php endif; ?>
</body>
</html>