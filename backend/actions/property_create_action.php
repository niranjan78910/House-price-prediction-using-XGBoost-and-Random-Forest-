<?php
// Create a new property
require_once __DIR__ . "/../config/app.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth.php";

// User must be logged in
require_login();

$user_id = (int)$_SESSION["user_id"];

// Get all form data
$address = trim($_POST["address"] ?? "");
$city = trim($_POST["city"] ?? "");
$postcode = strtoupper(trim($_POST["postcode"] ?? ""));
$bedrooms = (int)($_POST["bedrooms"] ?? ($_POST["rooms"] ?? 0));
$bathrooms = (int)($_POST["bathrooms"] ?? 0);
$floor_area_raw = trim($_POST["floor_area"] ?? "");
$floor_area = ($floor_area_raw !== "") ? (float)$floor_area_raw : null;
$freehold = strtoupper(substr(trim($_POST["freehold"] ?? "F"), 0, 1));
if (!in_array($freehold, ["F", "L"], true)) {
    $freehold = "F";
}
$property_type = trim($_POST["property_type"] ?? "");
$status = trim($_POST["status"] ?? "lead");
$asking_price_raw = trim($_POST["asking_price"] ?? "");
$agent_notes = trim($_POST["agent_notes"] ?? "");
$is_published = (int)($_POST["is_published"] ?? 0);
$public_description = trim($_POST["public_description"] ?? "");

// Validate required fields
if ($address === "" || $city === "" || $postcode === "" || $property_type === "") {
    $_SESSION["flash_error"] = "Please fill all required fields.";
    redirect("/property_add.php");
}

// Check bedroom/bathroom are not negative
if ($bedrooms < 0 || $bathrooms < 0) {
    $_SESSION["flash_error"] = "Bedrooms and bathrooms must be 0 or more.";
    redirect("/property_add.php");
}

if ($floor_area !== null && ($floor_area < 10 || $floor_area > 1000)) {
    $_SESSION["flash_error"] = "Floor area must be between 10 and 1000 sqm.";
    redirect("/property_add.php");
}

// Get postcode area (first part)
$postcode_area = preg_split("/\\s+/", $postcode)[0] ?? "";
if ($postcode_area === "") {
    $postcode_area = $postcode;
}

// Convert asking price to float
$asking_price = null;
if ($asking_price_raw !== "") {
    $asking_price = (float)$asking_price_raw;
}

// Generate URL-friendly slug for public listing
$slug_base = strtolower(preg_replace("/[^a-z0-9]+/i", "-", $city . "-" . $property_type . "-" . $bedrooms . "bed"));
$slug_base = trim($slug_base, "-");

// Add random suffix to make slug unique
$random_part = "";
try {
    $random_part = substr(bin2hex(random_bytes(6)), 0, 8);
} catch (Exception $e) {
    $random_part = (string)time();
}

$public_slug = $slug_base . "-" . $random_part;

try {
    // Insert new property into database
    $columns = [
        "user_id", "agent_id", "address", "city", "postcode", "postcode_area",
        "bedrooms", "bathrooms", "property_type", "status", "asking_price",
        "predicted_price", "agent_notes", "is_published", "public_slug",
        "public_description"
    ];
    $values = [
        $user_id,        // owner
        $user_id,        // initial agent the same as owner
        $address,
        $city,
        $postcode,
        $postcode_area,
        $bedrooms,
        $bathrooms,
        $property_type,
        $status,
        $asking_price,
        null,
        $agent_notes,
        $is_published,
        $public_slug,
        $public_description
    ];

    $db_columns = $pdo->query("SHOW COLUMNS FROM properties")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array("floor_area", $db_columns, true)) {
        $columns[] = "floor_area";
        $values[] = $floor_area;
    }
    if (in_array("freehold", $db_columns, true)) {
        $columns[] = "freehold";
        $values[] = $freehold;
    }

    $placeholders = implode(", ", array_fill(0, count($columns), "?"));
    $stmt = $pdo->prepare("INSERT INTO properties (" . implode(", ", $columns) . ") VALUES (" . $placeholders . ")");
    $stmt->execute($values);

    // Get the ID of new property
    $property_id = (int)$pdo->lastInsertId();

    // Handle image uploads if any
    $uploaded_count = 0;
    $skipped_type_count = 0;
    $skipped_error_count = 0;

    if (!empty($_FILES["property_images"])) {

        // Set upload directory
        $upload_dir = __DIR__ . "/../../public/uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Make sure we can write to uploads folder
        if (!is_writable($upload_dir)) {
            $_SESSION["flash_error"] = "Uploads folder is not writable. Check permissions.";
            redirect("/property_add.php");
        }

        // Get all files
        $files = $_FILES["property_images"];

        // Process each file
        for ($i = 0; $i < count($files["name"]); $i++) {

            // Skip if no file in this slot
            if ($files["error"][$i] === 4) {
                continue;
            }

            // Skip if error uploading
            if ($files["error"][$i] !== 0) {
                $skipped_error_count++;
                continue;
            }

            $tmp_name = $files["tmp_name"][$i];
            $original_name = $files["name"][$i];

            // Check file type
            $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            $allowed = ["jpg", "jpeg", "png", "webp"];

            // Skip if not allowed type
            if (!in_array($extension, $allowed)) {
                $skipped_type_count++;
                continue;
            }

            // Create unique filename
            $new_name = uniqid("prop_", true) . "." . $extension;
            $destination = $upload_dir . $new_name;

            // Move file and save in database
            if (move_uploaded_file($tmp_name, $destination)) {
                $stmt_img = $pdo->prepare("INSERT INTO property_images (property_id, image_path) VALUES (?, ?)");
                $stmt_img->execute([$property_id, $new_name]);
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
        $_SESSION["flash_success"] = "Property saved. " . implode(". ", $parts) . ".";
    }

    redirect("/properties.php");

} catch (PDOException $e) {
    $_SESSION["flash_error"] = "Save failed. " . $e->getMessage();
    redirect("/property_add.php");
}
