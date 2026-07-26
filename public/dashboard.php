<?php
require_once __DIR__ . "/../backend/config/app.php";
require_once __DIR__ . "/../backend/config/db.php";
require_once __DIR__ . "/../backend/middleware/auth.php";

require_login();

$user_id = (int)$_SESSION["user_id"];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_properties = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM followups f JOIN properties p ON p.id = f.property_id WHERE p.user_id = ? AND f.status = 'pending'");
$stmt->execute([$user_id]);
$pending_followups = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE user_id = ? AND status = 'lead'");
$stmt->execute([$user_id]);
$lead_count = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE user_id = ? AND status = 'listed'");
$stmt->execute([$user_id]);
$listed_count = (int)$stmt->fetchColumn();

include __DIR__ . "/includes/header_agent.php";
include __DIR__ . "/includes/sidebar_agent.php";
?>
  <div class="page-title">
  <h2>Dashboard</h2>
  <div class="muted">Overview of your portfolio and tasks</div>
</div>

  <div class="grid-3">
  <a class="card" href="properties.php" style="text-decoration:none;color:inherit;">
    <div class="stat">TOTAL PROPERTIES</div>
    <div class="stat-value"><?php echo (int)$total_properties; ?></div>
  </a>

  <a class="card" href="properties.php?status=lead" style="text-decoration:none;color:inherit;">
    <div class="stat">LEADS</div>
    <div class="stat-value"><?php echo (int)$total_leads; ?></div>
  </a>

  <a class="card" href="properties.php?status=listed" style="text-decoration:none;color:inherit;">
    <div class="stat">LISTED</div>
    <div class="stat-value"><?php echo (int)$total_listed; ?></div>
  </a>

  <a class="card" href="followups.php?status=pending" style="text-decoration:none;color:inherit;">
    <div class="stat">PENDING FOLLOW UPS</div>
    <div class="stat-value"><?php echo (int)$pending_followups; ?></div>
  </a>
</div>

<?php
include __DIR__ . "/includes/footer_agent.php";