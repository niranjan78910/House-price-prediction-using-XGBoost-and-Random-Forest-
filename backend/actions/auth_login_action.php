<?php
// Login controller - validates credentials and establishes user session
require_once __DIR__ . "/../config/app.php";
require_once __DIR__ . "/../config/db.php";

// Trim whitespace from email input - users often accidentally add spaces
// ?? "" provides default empty string if email isn't submitted
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

// Validate required fields before querying database
// Fail fast to avoid unnecessary database operations
if ($email === "" || $password === "") {
    $_SESSION["flash_error"] = "Enter email and password.";
    redirect("/login.php");
}

// Use prepared statement with ? placeholder to prevent SQL injection
// Even though we're using PDO, parameterized queries are still the best practice
// LIMIT 1 optimization - we only expect one user per email
$stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Combined check: user exists AND password is correct
// Use password_verify() which uses bcrypt/argon2 timing-safe comparison
// This prevents timing attacks where attackers guess passwords by measuring response time
// Also, we send generic error message to prevent username enumeration
// (attacker can't tell if email doesn't exist or password is wrong)
if (!$user || !password_verify($password, $user["password"])) {
    $_SESSION["flash_error"] = "Invalid login details.";
    redirect("/login.php");
}

// Credentials valid - establish session
// Cast user_id to int to prevent type juggling attacks
// Store in session (server-side) not cookie - more secure
$_SESSION["user_id"] = (int)$user["id"];
$_SESSION["user_name"] = $user["name"];
$_SESSION["user_email"] = $user["email"];

// Redirect authenticated user to dashboard
redirect("/dashboard.php");