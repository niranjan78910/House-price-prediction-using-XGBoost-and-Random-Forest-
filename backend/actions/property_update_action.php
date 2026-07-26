<?php
// Update property details and images
require_once __DIR__ . "/../config/app.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth.php";

// Must be logged in to update
require_login();

$user_id = (int)$_SESSION["user_id"];
$property_id = (int)($_POST["id"] ?? 0);

// Check property ID is valid
if ($property_id <= 0) {
    redirect("/properties.php");
}

// Get current property - make sure it belongs to this user
$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$property_id, $user_id]);
$current = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current) {
    redirect("/properties.php");
}

// Get all property details from form
$address = trim($_POST["address"] ?? "");
$city = trim($_POST["city"] ?? "");
$postcode = strtoupper(trim($_POST["postcode"] ?? ""));
$bedrooms = (int)($_POST["bedrooms"] ?? 0);
$bathrooms = (int)($_POST["bathrooms"] ?? 0);
$floor_area_raw = trim($_POST["floor_area"] ?? "");
$floor_area = ($floor_area_raw !== "") ? (float)$floor_area_raw : null;
$freehold = strtoupper(substr(trim($_POST["freehold"] ?? ($current["freehold"] ?? "F")), 0, 1));
if (!in_array($freehold, ["F", "L"], true)) {
    $freehold = "F";
}
$property_type = trim($_POST["property_type"] ?? "");
$status = trim($_POST["status"] ?? "lead");
$asking_price_raw = trim($_POST["asking_price"] ?? "");
$agent_notes = trim($_POST["agent_notes"] ?? "");
$is_published = (int)($_POST["is_published"] ?? 0);
$public_description = trim($_POST["public_description"] ?? "");

// Check all required fields are filled
if ($address === "" || $city === "" || $postcode === "" || $property_type === "") {
    $_SESSION["flash_error"] = "Please fill all required fields.";
    redirect("/property_edit.php?id=" . $property_id);
}

if ($bedrooms < 0 || $bathrooms < 0) {
    $_SESSION["flash_error"] = "Bedrooms and bathrooms must be 0 or more.";
    redirect("/property_edit.php?id=" . $property_id);
}

if ($floor_area !== null && ($floor_area < 10 || $floor_area > 1000)) {
    $_SESSION["flash_error"] = "Floor area must be between 10 and 1000 sqm.";
    redirect("/property_edit.php?id=" . $property_id);
}

// Extract postcode area (first part before space)
$postcode_area = preg_split("/\\s+/", $postcode)[0] ?? "";
if ($postcode_area === "") {
    $postcode_area = $postcode;
}

// Convert asking price to float if provided
$asking_price = null;
if ($asking_price_raw !== "") {
    $asking_price = (float)$asking_price_raw;
}

// Update property in database
$set_parts = [
    "address = ?",
    "city = ?",
    "postcode = ?",
    "postcode_area = ?",
    "bedrooms = ?",
    "bathrooms = ?",
    "property_type = ?",
    "status = ?",
    "asking_price = ?",
    "agent_notes = ?",
    "is_published = ?",
    "public_description = ?",
    "agent_id = ?"
];
$values = [
    $address,
    $city,
    $postcode,
    $postcode_area,
    $bedrooms,
    $bathrooms,
    $property_type,
    $status,
    $asking_price,
    $agent_notes,
    $is_published,
    $public_description,
    $user_id
];

$db_columns = $pdo->query("SHOW COLUMNS FROM properties")->fetchAll(PDO::FETCH_COLUMN);
if (in_array("floor_area", $db_columns, true)) {
    $set_parts[] = "floor_area = ?";
    $values[] = $floor_area;
}
if (in_array("freehold", $db_columns, true)) {
    $set_parts[] = "freehold = ?";
    $values[] = $freehold;
}

$values[] = $property_id;
$values[] = $user_id;

$stmt = $pdo->prepare("UPDATE properties SET " . implode(", ", $set_parts) . " WHERE id = ? AND user_id = ?");
$stmt->execute($values);

// Handle image uploads if any files were provided
 $uploaded_count = 0;
 $skipped_type_count = 0;
 $skipped_error_count = 0;

if (!empty($_FILES["property_images"])) {

    // Set upload directory
    $upload_dir = __DIR__ . "/../../public/uploads/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Check directory is writable before uploading
    if (!is_writable($upload_dir)) {
        $_SESSION["flash_error"] = "Uploads folder is not writable. Check permissions.";
        redirect("/property_edit.php?id=" . $property_id);
    }

    // Get all uploaded files
    $files = $_FILES["property_images"];

    // Loop through each file
    for ($i = 0; $i < count($files["name"]); $i++) {

        // Skip if no file was provided for this slot
        if ($files["error"][$i] === 4) {
            continue;
        }

        // Skip if there was an upload error
        if ($files["error"][$i] !== 0) {
            $skipped_error_count++;
            continue;
        }

        $tmp_name = $files["tmp_name"][$i];
        $original_name = $files["name"][$i];

        // Check file extension
        $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $allowed = ["jpg", "jpeg", "png", "webp"];

        // Skip if file type not allowed
        if (!in_array($extension, $allowed)) {
            $skipped_type_count++;
            continue;
        }

        // Generate unique filename
        $new_name = uniqid("prop_", true) . "." . $extension;
        $destination = $upload_dir . $new_name;

        // Move file and save to database
        if (move_uploaded_file($tmp_name, $destination)) {
            $pdo->prepare("INSERT INTO property_images (property_id, image_path) VALUES (?, ?)")
                ->execute([$property_id, $new_name]);
            $uploaded_count++;
        } else {
            $skipped_error_count++;
        }
    }
}

if ($uploaded_count > 0 || $skipped_type_count > 0 || $skipped_error_count > 0) {
    $parts = [];
    if ($uploaded_count > 0) {
        $parts[] = $uploaded_count . " photo(s) uploaded";
    }
    if ($skipped_type_count > 0) {
        $parts[] = $skipped_type_count . " skipped (use JPG, JPEG, PNG, WEBP)";
    }
    if ($skipped_error_count > 0) {
        $parts[] = $skipped_error_count . " skipped (upload error)";
    }
    $_SESSION["flash_success"] = "Property updated. " . implode(". ", $parts) . ".";
}

redirect("/property_view.php?id=" . $property_id);
