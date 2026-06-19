<?php
/**
 * Database Connection Class
 * Handles all database operations for QuickFix
 */

class Database {
    private $connection;
    private $host;
    private $user;
    private $pass;
    private $name;

    public function __construct($host = 'localhost', $user = 'root', $pass = '', $name = 'quickfix') {
        $this->host = $host;
        $this->user = $user;
        $this->pass = $pass;
        $this->name = $name;
        $this->connect();
    }

    /**
     * Connect to the database
     */
    private function connect() {
        try {
            $this->connection = new mysqli(
                $this->host,
                $this->user,
                $this->pass,
                $this->name
            );

            if ($this->connection->connect_error) {
                throw new Exception('Database connection failed: ' . $this->connection->connect_error);
            }

            // Set charset to UTF-8
            $this->connection->set_charset('utf8mb4');
        } catch (Exception $e) {
            error_log('Database Error: ' . $e->getMessage());
            die('Database connection error. Please try again later.');
        }
    }

    /**
     * Get the database connection
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Close the database connection
     */
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }

    /**
     * Execute a prepared statement
     */
    public function execute($query, $params = [], $types = '') {
        try {
            $stmt = $this->connection->prepare($query);

            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $this->connection->error);
            }

            if (!empty($params) && !empty($types)) {
                $stmt->bind_param($types, ...$params);
            }

            if (!$stmt->execute()) {
                throw new Exception('Execute failed: ' . $stmt->error);
            }

            return $stmt;
        } catch (Exception $e) {
            error_log('Query Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch a single row
     */
    public function fetchOne($query, $params = [], $types = '') {
        $stmt = $this->execute($query, $params, $types);

        if (!$stmt) {
            return false;
        }

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row;
    }

    /**
     * Fetch all rows
     */
    public function fetchAll($query, $params = [], $types = '') {
        $stmt = $this->execute($query, $params, $types);

        if (!$stmt) {
            return false;
        }

        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    /**
     * Insert a row and return the last insert ID
     */
    public function insert($query, $params = [], $types = '') {
        $stmt = $this->execute($query, $params, $types);

        if (!$stmt) {
            return false;
        }

        $id = $this->connection->insert_id;
        $stmt->close();

        return $id;
    }

    /**
     * Update rows
     */
    public function update($query, $params = [], $types = '') {
        $stmt = $this->execute($query, $params, $types);

        if (!$stmt) {
            return false;
        }

        $affected = $this->connection->affected_rows;
        $stmt->close();

        return $affected;
    }

    /**
     * Delete rows
     */
    public function delete($query, $params = [], $types = '') {
        return $this->update($query, $params, $types);
    }

    /**
     * Escape a string
     */
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }
}
?>
