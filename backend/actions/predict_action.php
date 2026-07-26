<?php
// Action handler: Get AI price prediction for a property
// This file is called when user clicks "Get AI Prediction" button on property page
// It calls the Python Flask API which runs the machine learning model
// Result is saved to database and displayed to user

require_once __DIR__ . "/../config/app.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth.php";

// Middleware check: User must be logged in to make predictions
// Prevents unauthorized access and ensures we know whose property this is
require_login();

// Get current logged-in user ID from session
// Session data is server-side so it can't be tampered with by user
$user_id = (int)$_SESSION["user_id"];

// Get property ID from POST form submission
// Convert to integer to prevent SQL injection (numeric parameter only)
$property_id = (int)($_POST["id"] ?? 0);

// Validate property ID is not empty or invalid
// A property ID of 0 or less is invalid and indicates a problem
if ($property_id <= 0) {
    redirect("/properties.php");
}

// Fetch property from database using prepared statement
// The WHERE clause checks: property must exist AND belong to current user
// This prevents one user from accessing another user's properties
$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$property_id, $user_id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

// If property doesn't exist or doesn't belong to user, deny access
if (!$property) {
    redirect("/properties.php");
}

// Prepare data for Python API
// Extract property details and set defaults for fields we may not have
$town = $property["city"] ?: "UNKNOWN";
$postcode = $property["postcode"] ?: "UNKNOWN";
$property_type_raw = strtoupper(trim((string)($property["property_type"] ?? "")));
$property_type_map = [
    "DETACHED" => "D",
    "SEMI-DETACHED" => "S",
    "SEMI DETACHED" => "S",
    "TERRACED" => "T",
    "FLAT" => "F",
    "BUNGALOW" => "D",
    "OTHER" => "O",
    "D" => "D",
    "S" => "S",
    "T" => "T",
    "F" => "F",
    "O" => "O",
];
$property_type = $property_type_map[$property_type_raw] ?? "D";
$new_build = "N"; // Default to "No" - could be stored if form collects this
$rooms = max(1, (int)($property["bedrooms"] ?? 1));
$bathrooms = max(0, (int)($property["bathrooms"] ?? 0));
$floor_area = isset($property["floor_area"]) && (float)$property["floor_area"] > 0
    ? (float)$property["floor_area"]
    : max(10, ($rooms * 25) + ($bathrooms * 5) + 20);
$freehold = strtoupper(substr((string)($property["freehold"] ?? "F"), 0, 1));
if (!in_array($freehold, ["F", "L"], true)) {
    $freehold = "F";
}
$district = "UNKNOWN"; // Could be extracted from postcode or stored separately

// Build request payload for AI model
// This dictionary is converted to JSON and sent to Flask API
// All fields must match what the model expects during training
$request_data = [
    "postcode" => $postcode,
    "property_type" => $property_type,
    "town" => $town,
    "district" => $district,
    "new_build" => $new_build,
    "freehold" => $freehold,
    "floor_area" => $floor_area,
    "rooms" => $rooms,
    "year" => (int)date("Y"),
];

// Initialize cURL session to make HTTP request to Python API
// cURL (client URL) is PHP's library for making web requests
$api_url = AI_PREDICT_URL;
$ch = curl_init();

// Configure cURL request settings
curl_setopt($ch, CURLOPT_URL, $api_url);              // Where to send the request
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);       // Return response as string, don't output
curl_setopt($ch, CURLOPT_POST, true);                 // Use POST method
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);          // Fail fast if API is not reachable
curl_setopt($ch, CURLOPT_TIMEOUT, 8);                 // Total request timeout
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);  // Tell API we're sending JSON
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));  // Convert array to JSON and send

// Execute the request and capture response
// This is where PHP actually talks to the Python Flask server
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);   // Get HTTP status code (200 = success)
$curl_error = curl_error($ch);                         // Get any connection errors
curl_close($ch);                                       // Close the connection

// Handle connection errors (e.g., Flask not running, network issues)
if ($response === false) {
    // If curl_exec returns false, something went wrong
    // Store error message in session to display to user
    $_SESSION["flash_error"] = "AI service connection failed: " . htmlspecialchars($curl_error);
} else if ($http_code !== 200) {
    // If API returned error status code (not 200), something failed in the prediction
    $_SESSION["flash_error"] = "AI service error (code " . $http_code . "): " . htmlspecialchars($response);
} else {
    // Success! Parse the JSON response from the API
    // json_decode converts JSON string back to PHP array
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $_SESSION["flash_error"] = "AI service returned invalid JSON.";
        redirect("/property_view.php?id=" . $property_id);
    }
    
    // Check if response contains the predicted price
    if ($data && isset($data["predicted_price"])) {
        // Extract the predicted price value
        $predicted_price = (float)$data["predicted_price"];
        
        // Save prediction to 'predictions' table for history/analytics
        // This table stores all predictions made for record-keeping
        $stmt = $pdo->prepare("INSERT INTO predictions (property_id, predicted_price, model_used) VALUES (?, ?, ?)");
        $stmt->execute([$property_id, $predicted_price, "random_forest_1432_features"]);
        
        // Also update the 'properties' table so we can display latest prediction quickly
        // This avoids needing to query the predictions table every time
        $stmt = $pdo->prepare("UPDATE properties SET predicted_price = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$predicted_price, $property_id, $user_id]);
        
        // Success message stored in session
        // Will be displayed to user on the next page load
        $_SESSION["flash_success"] = "Prediction complete: £" . number_format($predicted_price, 2);
    } else {
        // API responded but didn't send a price - unexpected format
        $_SESSION["flash_error"] = "Invalid API response: " . htmlspecialchars($response);
    }
}

// Redirect user back to property page to see result
// Using property_view.php so they see the updated prediction
redirect("/property_view.php?id=" . $property_id);
?>
