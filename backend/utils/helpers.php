<?php
// Utility helper functions used across the application
// These are common functions to keep code DRY (Don't Repeat Yourself)

// Format a price value as a UK pounds string
// Useful for displaying prices consistently throughout the app
function format_price($price) {
    if ($price === null || $price === "") {
        return "-";
    }
    return "£" . number_format((float)$price, 2);
}

// Check if a string is a valid email format
// Uses basic regex check (more thorough validation happens on server-side)
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Sanitize user input to prevent XSS (cross-site scripting) attacks
// Always use this when displaying user data in HTML
function safe_html($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Truncate a string to a maximum length and add ellipsis
// Useful for listing summaries or preview text
function truncate($string, $max_length = 50) {
    if (strlen($string) > $max_length) {
        return substr($string, 0, $max_length) . "...";
    }
    return $string;
}

// Convert database timestamp to human-readable format
// Example: "2025-03-03 14:30:45" becomes "3 Mar 2025, 2:30 PM"
function format_date($timestamp) {
    if ($timestamp === null || $timestamp === "") {
        return "-";
    }
    $date = new DateTime($timestamp);
    return $date->format("j M Y, g:i A");
}

// Get the initials from a full name
// Useful for avatar circles or quick user identification
function get_initials($name) {
    $parts = explode(" ", trim($name));
    $initials = "";
    foreach ($parts as $part) {
        if (!empty($part)) {
            $initials .= strtoupper($part[0]);
        }
    }
    return $initials;
}

// Check if current user owns a resource (property, followup, etc)
// Always check ownership before allowing edits/deletes to prevent unauthorized access
function user_owns_resource($user_id, $resource_user_id) {
    return (int)$user_id === (int)$resource_user_id;
}
?>
