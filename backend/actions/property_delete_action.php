<?php
// Delete a property and all its data
require_once __DIR__ . "/../config/app.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth.php";

// Only logged-in users can delete
require_login();

$user_id = (int)$_SESSION["user_id"];
$property_id = (int)($_POST["id"] ?? 0);

// Check if ID is valid
if ($property_id <= 0) {
    redirect("/properties.php");
}

// Make sure the property exists and belongs to this user
$stmt = $pdo->prepare("SELECT id FROM properties WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$property_id, $user_id]);
$exists = $stmt->fetchColumn();

if (!$exists) {
    redirect("/properties.php");
}

// Delete everything related to this property first
// Need to delete images, owners, follow-ups, and predictions before deleting the property
$pdo->prepare("DELETE FROM property_images WHERE property_id = ?")->execute([$property_id]);
$pdo->prepare("DELETE FROM owners WHERE property_id = ?")->execute([$property_id]);
$pdo->prepare("DELETE FROM followups WHERE property_id = ?")->execute([$property_id]);
$pdo->prepare("DELETE FROM predictions WHERE property_id = ?")->execute([$property_id]);
// Finally delete the property itself
$pdo->prepare("DELETE FROM properties WHERE id = ? AND user_id = ?")->execute([$property_id, $user_id]);

redirect("/properties.php");