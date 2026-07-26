<?php
// Follow-up creation controller - creates reminder/task for property
require_once __DIR__ . "/../config/app.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth.php";

// Ensure user is logged in
require_login();


// Get current user ID
$user_id = (int)$_SESSION["user_id"];

// Get follow-up details from form
$property_id = (int)($_POST["property_id"] ?? 0);
$task_type = trim($_POST["title"] ?? "");
$due_date = trim($_POST["due_date"] ?? "");
$notes = trim($_POST["notes"] ?? "");

// Validate required fields (property and title)
if ($property_id <= 0 || $task_type === "") {
    $_SESSION["flash_error"] = "Property and title are required.";
    redirect("/followup_add.php");
}

// Check that property exists and belongs to current user
$stmt = $pdo->prepare("SELECT id FROM properties WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$property_id, $user_id]);
if (!$stmt->fetchColumn()) {
    $_SESSION["flash_error"] = "Property not found.";
    redirect("/followup_add.php");
}

// Convert due_date to null if not provided (allows nullable date field)
$due_date_value = null;
if ($due_date !== "") {
    $due_date_value = $due_date;
}

// Create new follow-up record with pending status
$stmt = $pdo->prepare("
    INSERT INTO followups (property_id, task_type, due_date, status, notes)
    VALUES (?, ?, ?, 'pending', ?)
");
$stmt->execute([$property_id, $task_type, $due_date_value, $notes]);

// Show success message
$_SESSION["flash_success"] = "Follow up created.";
// Redirect to follow-ups list filtered by property
redirect("/followups.php?property_id=" . $property_id);