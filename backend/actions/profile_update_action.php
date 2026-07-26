<?php
// Update user profile (name and password)
require_once __DIR__ . "/../config/app.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth.php";

// Must be logged in to update your profile
require_login();

$user_id = (int)$_SESSION["user_id"];

// Only allow POST requests for security
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  redirect(BASE_URL . "/profile.php");
}

// Get form inputs and trim whitespace
$name = trim($_POST["name"] ?? "");
$password = trim($_POST["password"] ?? "");
$password2 = trim($_POST["password2"] ?? "");

// Name is required
if ($name === "") {
  $_SESSION["flash_error"] = "Name is required.";
  redirect(BASE_URL . "/profile.php");
}

// If user wants to change password, check both passwords
if ($password !== "" || $password2 !== "") {
  // Password must be at least 6 characters
  if (strlen($password) < 6) {
    $_SESSION["flash_error"] = "Password must be at least 6 characters.";
    redirect(BASE_URL . "/profile.php");
  }
  // Make sure both password entries match (in case of typo)
  if ($password !== $password2) {
    $_SESSION["flash_error"] = "Passwords do not match.";
    redirect(BASE_URL . "/profile.php");
  }

  // Hash the password before storing
  $hash = password_hash($password, PASSWORD_DEFAULT);

  // Update both name and password
  $stmt = $pdo->prepare("UPDATE users SET name = ?, password = ? WHERE id = ?");
  $stmt->execute([$name, $hash, $user_id]);
} else {
  // Just update name (password wasn't changed)
  $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
  $stmt->execute([$name, $user_id]);
}

// Update session so the new name shows immediately
$_SESSION["user_name"] = $name;
$_SESSION["flash_success"] = "Profile updated.";
redirect(BASE_URL . "/profile.php");