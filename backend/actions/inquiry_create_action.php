<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

set_error_handler(function ($severity, $message, $file, $line) {
    http_response_code(500);
    echo "PHP ERROR: $message\nFile: $file\nLine: $line\n";
    exit;
});

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        http_response_code(500);
        echo "FATAL: {$err['message']}\nFile: {$err['file']}\nLine: {$err['line']}\n";
        exit;
    }
});
require_once __DIR__ . "/../config/app.php";
require_once __DIR__ . "/../config/db.php"; // change if your db file path differs

// Basic input read
$property_id = isset($_POST['property_id']) ? (int)$_POST['property_id'] : 0;
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? '');

// Quick validation
if ($property_id <= 0) {
  die("Invalid property id");
}
if ($name === '' || $email === '' || $message === '') {
  die("Please fill all required fields");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  die("Invalid email");
}

// Find agent for this property (agent_id should now exist after migration)
$stmt = $pdo->prepare("SELECT id, agent_id FROM properties WHERE id = ?");
$stmt->execute([$property_id]);
$prop = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prop) {
  die("Property not found");
}

$agent_id = (int)$prop['agent_id'];
if ($agent_id <= 0) {
  die("Property has no agent assigned");
}

// Save enquiry
$stmt = $pdo->prepare("
  INSERT INTO inquiries (property_id, agent_id, name, email, phone, message)
  VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->execute([$property_id, $agent_id, $name, $email, $phone, $message]);

// Go back to listing page with success flag
redirect(BASE_URL . "/listing.php?id=" . $property_id . "&enquiry=sent");