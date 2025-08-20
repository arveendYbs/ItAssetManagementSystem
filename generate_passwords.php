<?php
/**
 * Password Hash Generator
 * Run this script to generate correct password hashes for the default users
 */

// Generate hash for "admin123"
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: " . $password . "\n";
echo "Hash: " . $hash . "\n\n";

// SQL to update the users table with correct hashes
echo "Run this SQL to fix the password hashes:\n\n";
echo "UPDATE users SET password = '$hash' WHERE email = 'admin@company.com';\n";
echo "UPDATE users SET password = '$hash' WHERE email = 'user@company.com';\n\n";

echo "Or delete and recreate the users:\n\n";
echo "DELETE FROM users WHERE email IN ('admin@company.com', 'user@company.com');\n";
echo "INSERT INTO users (username, email, password, role) VALUES \n";
echo "('admin', 'admin@company.com', '$hash', 'Admin'),\n";
echo "('user', 'user@company.com', '$hash', 'User');\n";
?>