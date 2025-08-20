<?php
/**
 * Asset Model
 */
class Asset {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function getAll($search = '', $filter = []) {
        $sql = "SELECT a.*, 
                       cu.name as current_user_name,
                       pu.name as previous_user_name
                FROM assets a
                LEFT JOIN employees cu ON a.current_user_id = cu.id
                LEFT JOIN employees pu ON a.previous_user_id = pu.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (a.serial_number LIKE ? OR a.model LIKE ? OR cu.name LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if (!empty($filter['device_type'])) {
            $sql .= " AND a.device_type = ?";
            $params[] = $filter['device_type'];
        }
        
        if (!empty($filter['status'])) {
            $sql .= " AND a.status = ?";
            $params[] = $filter['status'];
        }
        
        $sql .= " ORDER BY a.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT a.*, 
                                           cu.name as current_user_name,
                                           pu.name as previous_user_name
                                    FROM assets a
                                    LEFT JOIN employees cu ON a.current_user_id = cu.id
                                    LEFT JOIN employees pu ON a.previous_user_id = pu.id
                                    WHERE a.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        $sql = "INSERT INTO assets (serial_number, model, device_type, site, purchased_by, 
                                   current_user_id, previous_user_id, license, status, ram, os, 
                                   purchase_date, warranty_expiry, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['serial_number'], $data['model'], $data['device_type'], $data['site'],
            $data['purchased_by'], $data['current_user_id'], $data['previous_user_id'],
            $data['license'], $data['status'], $data['ram'], $data['os'],
            $data['purchase_date'], $data['warranty_expiry'], $data['notes']
        ]);
    }
    
    public function update($id, $data) {
        $sql = "UPDATE assets SET serial_number=?, model=?, device_type=?, site=?, 
                                 purchased_by=?, current_user_id=?, previous_user_id=?, 
                                 license=?, status=?, ram=?, os=?, purchase_date=?, 
                                 warranty_expiry=?, notes=?, updated_at=NOW()
                WHERE id=?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['serial_number'], $data['model'], $data['device_type'], $data['site'],
            $data['purchased_by'], $data['current_user_id'], $data['previous_user_id'],
            $data['license'], $data['status'], $data['ram'], $data['os'],
            $data['purchase_date'], $data['warranty_expiry'], $data['notes'], $id
        ]);
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM assets WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function getStats() {
        // Total assets
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM assets");
        $stmt->execute();
        $total = $stmt->fetch()['total'];
        
        // By device type
        $stmt = $this->db->prepare("SELECT device_type, COUNT(*) as count FROM assets GROUP BY device_type");
        $stmt->execute();
        $byType = $stmt->fetchAll();
        
        // By status
        $stmt = $this->db->prepare("SELECT status, COUNT(*) as count FROM assets GROUP BY status");
        $stmt->execute();
        $byStatus = $stmt->fetchAll();
        
        return [
            'total' => $total,
            'by_type' => $byType,
            'by_status' => $byStatus
        ];
    }
}

/**
 * Employee Model
 */
class Employee {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function getAll($search = '') {
        $sql = "SELECT * FROM employees WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (name LIKE ? OR email LIKE ? OR department LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        $sql .= " ORDER BY name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM employees WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO employees (name, department, company, email) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$data['name'], $data['department'], $data['company'], $data['email']]);
    }
    
    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE employees SET name=?, department=?, company=?, email=?, updated_at=NOW() WHERE id=?");
        return $stmt->execute([$data['name'], $data['department'], $data['company'], $data['email'], $id]);
    }
    
    public function delete($id) {
        // Check if employee is assigned to any assets
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM assets WHERE current_user_id = ? OR previous_user_id = ?");
        $stmt->execute([$id, $id]);
        $count = $stmt->fetch()['count'];
        
        if ($count > 0) {
            return false; // Cannot delete if assigned to assets
        }
        
        $stmt = $this->db->prepare("DELETE FROM employees WHERE id = ?");
        return $stmt->execute([$id]);
    }
}

/**
 * User Model
 */
class User {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function getAll($search = '') {
        $sql = "SELECT id, username, email, role, created_at FROM users WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (username LIKE ? OR email LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        $sql .= " ORDER BY username ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT id, username, email, role, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$data['username'], $data['email'], $hashedPassword, $data['role']]);
    }
    
    public function update($id, $data) {
        if (!empty($data['password'])) {
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET username=?, email=?, password=?, role=?, updated_at=NOW() WHERE id=?");
            return $stmt->execute([$data['username'], $data['email'], $hashedPassword, $data['role'], $id]);
        } else {
            $stmt = $this->db->prepare("UPDATE users SET username=?, email=?, role=?, updated_at=NOW() WHERE id=?");
            return $stmt->execute([$data['username'], $data['email'], $data['role'], $id]);
        }
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
        $params = [$email];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['count'] > 0;
    }
}
?>