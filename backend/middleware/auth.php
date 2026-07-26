<?php
// Middleware to enforce authentication across protected pages
require_once __DIR__ . "/../config/app.php";

function require_login() {
    // Check if user_id exists in session (set during login)
    // This is safer than checking email/name which could be spoofed
    // Session data is stored server-side, so it can't be tampered with by the user
    if (!isset($_SESSION["user_id"])) {
        // User not authenticated, redirect to login page immediately
        // Using redirect() ensures BASE_URL is properly prepended
        redirect("/login.php");
    }
}
