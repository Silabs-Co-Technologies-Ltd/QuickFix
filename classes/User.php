<?php
/**
 * User Model Class
 * Handles user registration, login, and profile management
 */

require_once __DIR__ . '/Database.php';

class User {
    private $db;
    private $table = 'users';

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Register a new user
     */
    public function register($name, $email, $password = null, $role = 'customer') {
        // Validate inputs
        if (empty($name) || strlen($name) < 2) {
            return ['success' => false, 'message' => 'Name must be at least 2 characters.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address.'];
        }

        if ($password !== null && strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
        }

        if (!in_array($role, ['customer', 'professional', 'admin'])) {
            return ['success' => false, 'message' => 'Invalid role selected.'];
        }

        // Check if email already exists
        $existing = $this->getUserByEmail($email);
        if ($existing) {
            return ['success' => false, 'message' => 'Email address is already registered.'];
        }

        // Hash password if provided
        $hashedPassword = $password !== null ? password_hash($password, PASSWORD_DEFAULT) : null;

        // Insert user
        $query = "INSERT INTO {$this->table} (name, email, password, role, created_at, updated_at) 
                  VALUES (?, ?, ?, ?, NOW(), NOW())";
        $params = [$name, $email, $hashedPassword, $role];
        $types = 'ssss';

        $userId = $this->db->insert($query, $params, $types);

        if ($userId) {
            return ['success' => true, 'message' => 'Registration successful!', 'user_id' => $userId];
        } else {
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }

    /**
     * Login a user
     */
    public function login($email, $password) {
        // Validate inputs
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address.'];
        }

        if (empty($password)) {
            return ['success' => false, 'message' => 'Password is required.'];
        }

        // Get user by email
        $user = $this->getUserByEmail($email);

        if (!$user) {
            return ['success' => false, 'message' => 'Email or password is incorrect.'];
        }

        // Verify password
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Email or password is incorrect.'];
        }

        // Return user data (without password)
        unset($user['password']);
        return ['success' => true, 'message' => 'Login successful!', 'user' => $user];
    }

    /**
     * Get user by email
     */
    public function getUserByEmail($email) {
        $query = "SELECT * FROM {$this->table} WHERE email = ? LIMIT 1";
        $params = [$email];
        $types = 's';

        return $this->db->fetchOne($query, $params, $types);
    }

    /**
     * Get user by ID
     */
    public function getUserById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1";
        $params = [$id];
        $types = 'i';

        $user = $this->db->fetchOne($query, $params, $types);

        if ($user) {
            unset($user['password']);
        }

        return $user;
    }

    /**
     * Update user profile
     */
    public function updateProfile($id, $name, $email) {
        // Validate inputs
        if (empty($name) || strlen($name) < 2) {
            return ['success' => false, 'message' => 'Name must be at least 2 characters.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address.'];
        }

        // Check if new email is already used by another user
        $existing = $this->db->fetchOne(
            "SELECT id FROM {$this->table} WHERE email = ? AND id != ? LIMIT 1",
            [$email, $id],
            'si'
        );

        if ($existing) {
            return ['success' => false, 'message' => 'Email address is already in use.'];
        }

        // Update user
        $query = "UPDATE {$this->table} SET name = ?, email = ?, updated_at = NOW() WHERE id = ?";
        $params = [$name, $email, $id];
        $types = 'ssi';

        $affected = $this->db->update($query, $params, $types);

        if ($affected > 0) {
            return ['success' => true, 'message' => 'Profile updated successfully!'];
        } else {
            return ['success' => false, 'message' => 'No changes made or update failed.'];
        }
    }

    /**
     * Change user password
     */
    public function changePassword($id, $oldPassword, $newPassword) {
        // Validate new password
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'New password must be at least 6 characters.'];
        }

        // Get user
        $user = $this->getUserById($id);

        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        // Verify old password
        $userWithPassword = $this->db->fetchOne(
            "SELECT password FROM {$this->table} WHERE id = ? LIMIT 1",
            [$id],
            'i'
        );

        if (!password_verify($oldPassword, $userWithPassword['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }

        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update password
        $query = "UPDATE {$this->table} SET password = ?, updated_at = NOW() WHERE id = ?";
        $params = [$hashedPassword, $id];
        $types = 'si';

        $affected = $this->db->update($query, $params, $types);

        if ($affected > 0) {
            return ['success' => true, 'message' => 'Password changed successfully!'];
        } else {
            return ['success' => false, 'message' => 'Password change failed.'];
        }
    }

    /**
     * Delete user account
     */
    public function deleteAccount($id) {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $params = [$id];
        $types = 'i';

        $affected = $this->db->delete($query, $params, $types);

        if ($affected > 0) {
            return ['success' => true, 'message' => 'Account deleted successfully!'];
        } else {
            return ['success' => false, 'message' => 'Account deletion failed.'];
        }
    }

    /**
     * Get all users (admin only)
     */
    public function getAllUsers($limit = 50, $offset = 0) {
        $query = "SELECT id, name, email, role, created_at FROM {$this->table} LIMIT ? OFFSET ?";
        $params = [$limit, $offset];
        $types = 'ii';

        return $this->db->fetchAll($query, $params, $types);
    }
}
?>
