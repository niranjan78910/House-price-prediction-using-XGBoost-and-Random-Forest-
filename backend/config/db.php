<?php
// Configure database credentials through environment variables in deployments.
// Local XAMPP defaults are retained for development compatibility.
$host = getenv("DB_HOST") ?: "127.0.0.1";
$port = (int)(getenv("DB_PORT") ?: 3306);
$dbname = getenv("DB_NAME") ?: "prop_flow_ai";
$user = getenv("DB_USER") ?: "root";
$pass = getenv("DB_PASSWORD");
if ($pass === false) {
    $pass = "";
}

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // Do not disclose connection details or database credentials to visitors.
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(503);
    exit("Service temporarily unavailable.");
}
