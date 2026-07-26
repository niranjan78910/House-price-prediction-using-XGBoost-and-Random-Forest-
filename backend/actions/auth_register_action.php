<?php
// Registration controller - creates new user account with validated input
require_once __DIR__ . "/../config/app.php";
require_once __DIR__ . "/../config/db.php";

// Trim whitespace from user inputs - users often accidentally add leading/trailing spaces
$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

// Validate all required fields are filled before proceeding
// Fail fast with clear error messages
if ($name === "" || $email === "" || $password === "") {
    $_SESSION["flash_error"] = "Please fill all fields.";
    redirect("/register.php");
}

// Use filter_var() to validate email format matches RFC standards
// This prevents invalid emails from being stored in database
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION["flash_error"] = "Invalid email.";
    redirect("/register.php");
}

// Enforce minimum password length - 6 characters is basic requirement
// In production, enforce stronger requirements (uppercase, numbers, symbols, 12+ chars)
if (strlen($password) < 6) {
    $_SESSION["flash_error"] = "Password must be at least 6 characters.";
    redirect("/register.php");
}

try {
    // Use password_hash() with PASSWORD_DEFAULT to use best available algorithm (bcrypt/argon2)
    // This ensures passwords are not stored as plain text - critical security requirement
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Use prepared statement with placeholders (?) to prevent SQL injection
    // Prepared statements separate data from SQL code
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $hash]);

    // Registration successful - redirect to login page
    redirect("/login.php");
} catch (PDOException $e) {
    // Check if error is due to duplicate email (unique constraint violation)
    // This means user already has an account with that email
    if (str_contains($e->getMessage(), "Duplicate")) {
        $_SESSION["flash_error"] = "Email already registered.";
        redirect("/register.php");
    }
    // Generic error message - don't expose database details to user
    $_SESSION["flash_error"] = "Something went wrong.";
    redirect("/register.php");
}