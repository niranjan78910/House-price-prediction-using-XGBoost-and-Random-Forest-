<?php
// Mark a follow-up as done
require_once __DIR__ . "/../config/app.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth.php";

// Check user is logged in
require_login();

$user_id = (int)$_SESSION["user_id"];
$id = (int)($_POST["id"] ?? 0);

// Make sure the ID is valid
if ($id <= 0) {
    redirect("/followups.php");
}

// Update status only when the follow-up belongs to a property owned by this user
$stmt = $pdo->prepare("
    UPDATE followups f
    JOIN properties p ON p.id = f.property_id
    SET f.status = 'done'
    WHERE f.id = ? AND p.user_id = ?
");
$stmt->execute([$id, $user_id]);

$_SESSION["flash_success"] = "Marked as done.";
redirect("/followups.php");