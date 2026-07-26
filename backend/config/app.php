<?php
// Start session early to ensure $_SESSION is available throughout the application
// PHP sessions are stored server-side and only session ID is sent to browser (more secure)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define BASE_URL once and reuse everywhere to handle subdirectory deployments
// Without this, redirects break if app moves from /PROP-FLOW-AI/public to /public or /prod
// The guard prevents redefinition errors since this file gets included multiple times
if (!defined("BASE_URL")) {
    define("BASE_URL", "/PROP-FLOW-AI/public");
}

// AI prediction API endpoint.
// macOS can reserve port 5000 for system services (e.g., AirPlay/AirTunes),
// so default to 5001. Can be overridden with env var PROP_FLOW_AI_PREDICT_URL.
if (!defined("AI_PREDICT_URL")) {
  $aiPredictUrl = getenv("PROP_FLOW_AI_PREDICT_URL") ?: "http://127.0.0.1:5001/predict";
  define("AI_PREDICT_URL", $aiPredictUrl);
}

if (!function_exists('redirect')) {
  function redirect($path) {
    // Check if $path is already an absolute URL (http:// or https://)
    // This lets us redirect to external sites if needed without prepending BASE_URL
    if (!preg_match('#^https?://#i', $path)) {
      // For relative paths, prepend BASE_URL so redirects work from any location
      // Example: redirect("/login.php") becomes /PROP-FLOW-AI/public/login.php
      // This is crucial for subdirectory installations
      if (defined('BASE_URL')) {
        // Avoid duplicating BASE_URL if it's already in the path (idempotent)
        if (strpos($path, BASE_URL) !== 0) {
          $path = rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
        }
      }
    }
    header("Location: " . $path);
    exit;
  }
}
