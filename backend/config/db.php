<?php
// Database configuration centralized in one place for easy updates
// In production, these would be loaded from .env file for security
$host = "127.0.0.1";
$port = 3306;
$dbname = "prop_flow_ai";
$user = "root";
$pass = "";

try {
    // PDO (PHP Data Objects) provides a consistent interface for database operations
    // Using prepared statements with ? placeholders prevents SQL injection attacks
    // charset=utf8mb4 supports full Unicode including emoji (important for international users)
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass
    );
    // ERRMODE_EXCEPTION throws PDOException on errors instead of silent failures
    // This makes bugs obvious during development and easier to debug
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Exit immediately if database connection fails - no point continuing without data access
    die("DB connection failed: " . $e->getMessage());
}
