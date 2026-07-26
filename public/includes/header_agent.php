<?php
require_once __DIR__ . "/../../backend/config/app.php";
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Agent Portail</title>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/styles.css?v=1">
<script src="assets/js/main.js"></script>
</head>
<body>
  <header class="topbar">
    <div class="topbar-left">
      <div class="brand">Prop Flow AI</div>
      <div class="brand-sub">Agent Portal</div>
    </div>

    <div class="topbar-right">
      <div class="user-chip">
        <div class="user-dot"></div>
        <div class="user-text">
          <div class="user-label">Signed in</div>
          <div class="user-name"><?php echo htmlspecialchars($_SESSION["user_name"] ?? ""); ?></div>
        </div>
      </div>
      <a class="btn btn-ghost" href="logout.php">Logout</a>
    </div>
  </header>

  <div class="app">