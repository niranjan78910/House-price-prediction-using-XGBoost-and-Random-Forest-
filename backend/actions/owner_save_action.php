<?php
// Save owner information for a property
require_once __DIR__ . "/../config/app.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth.php";

// User must be logged in
require_login();

$user_id = (int)$_SESSION["user_id"];
$property_id = (int)($_POST["property_id"] ?? 0);

// Check property ID is valid
if ($property_id <= 0) {
    redirect("/properties.php");
}

// Make sure property exists and belongs to this user
$stmt = $pdo->prepare("SELECT id FROM properties WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$property_id, $user_id]);
if (!$stmt->fetchColumn()) {
    redirect("/properties.php");
}

// Get owner details from form
$full_name = trim($_POST["full_name"] ?? "");
$email = trim($_POST["email"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$preferred_contact = trim($_POST["preferred_contact"] ?? "phone");
$notes = trim($_POST["notes"] ?? "");

// Name is required
if ($full_name === "") {
    $_SESSION["flash_error"] = "Full name is required.";
    redirect("/owner_edit.php?property_id=" . $property_id);
}

// If email is provided, make sure it looks valid
if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION["flash_error"] = "Email format looks wrong.";
    redirect("/owner_edit.php?property_id=" . $property_id);
}

// Validate contact preference is one of the allowed options
$allowed_contact = ["phone","email","either"];
if (!in_array($preferred_contact, $allowed_contact)) {
    $preferred_contact = "phone";
}

// Check if owner already exists for this property
$stmt = $pdo->prepare("SELECT id FROM owners WHERE property_id = ? LIMIT 1");
$stmt->execute([$property_id]);
$existing_id = (int)($stmt->fetchColumn() ?? 0);

// If owner exists, update it; if not, create new one
if ($existing_id > 0) {
    // Update existing owner
    $stmt = $pdo->prepare("
      UPDATE owners
      SET full_name = ?, email = ?, phone = ?, preferred_contact = ?, notes = ?
      WHERE id = ? AND property_id = ?
    ");
    $stmt->execute([$full_name, $email, $phone, $preferred_contact, $notes, $existing_id, $property_id]);
} else {
    // Create new owner record
    $stmt = $pdo->prepare("
      INSERT INTO owners (property_id, full_name, email, phone, preferred_contact, notes)
      VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$property_id, $full_name, $email, $phone, $preferred_contact, $notes]);
}

$_SESSION["flash_success"] = "Owner saved.";
redirect("/owner_edit.php?property_id=" . $property_id);