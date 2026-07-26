<?php
// Follow-up deletion controller - removes follow-up record
require_once __DIR__ . "/../config/app.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth.php";

// Ensure user is authenticated
require_login();

// Get current user ID
$user_id = (int)$_SESSION["user_id"];
// Get follow-up ID to delete
$id = (int)($_POST["id"] ?? 0);

// Validate ID is positive
if ($id <= 0) {
    redirect("/followups.php");
}

// Delete follow-up only if it belongs to a property owned by current user (security check)
$stmt = $pdo->prepare("
    DELETE f
    FROM followups f
    JOIN properties p ON p.id = f.property_id
    WHERE f.id = ? AND p.user_id = ?
");
$stmt->execute([$id, $user_id]);

// Show success message
$_SESSION["flash_success"] = "Follow up deleted.";
// Redirect back to follow-ups list
redirect("/followups.php");