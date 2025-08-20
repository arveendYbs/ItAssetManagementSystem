<?php
/**
 * Authentication and Session Management
 */

session_start();
require_once 'config/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function login($email, $password) {
        try {
            $stmt = $this->db->prepare("SELECT id, username, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($user = $stmt->fetch()) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['logged_in'] = true;
                    
                    return true;
                }
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function logout() {
        session_unset();
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'email' => $_SESSION['email'],
                'role' => $_SESSION['role']
            ];
        }
        return null;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }
    
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            header('Location: dashboard.php?error=access_denied');
            exit;
        }
    }
}

// Global auth functions
function auth() {
    static $auth = null;
    if ($auth === null) {
        $auth = new Auth();
    }
    return $auth;
}

function requireLogin() {
    auth()->requireLogin();
}

function requireAdmin() {
    auth()->requireAdmin();
}

function isLoggedIn() {
    return auth()->isLoggedIn();
}

function isAdmin() {
    return auth()->isAdmin();
}

function getCurrentUser() {
    return auth()->getCurrentUser();
}
?>