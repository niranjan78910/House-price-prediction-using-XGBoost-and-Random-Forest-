<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . "/../backend/config/app.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

session_unset();
session_destroy();

// redirect helper will prepend BASE_URL automatically
redirect("/login.php");