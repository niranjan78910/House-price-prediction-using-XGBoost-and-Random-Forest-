<?php
require_once __DIR__ . "/../backend/config/app.php";
require_once __DIR__ . "/../backend/config/db.php";
require_once __DIR__ . "/../backend/middleware/auth.php";

require_login();

$user_id = (int)$_SESSION["user_id"];
$property_id = (int)($_GET["property_id"] ?? 0);

$stmt = $pdo->prepare("SELECT id, address, city, postcode FROM properties WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$props = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = $_SESSION["flash_error"] ?? "";
unset($_SESSION["flash_error"]);

include __DIR__ . "/includes/header_agent.php";
include __DIR__ . "/includes/sidebar_agent.php";
?>

<div class="page-title">
  <h2>Add follow up</h2>
  <a class="btn btn-ghost" href="followups.php<?php echo ($property_id > 0) ? "?property_id=" . $property_id : ""; ?>">Back</a>
</div>

<div class="card" style="max-width:980px;">
  <?php if ($error): ?>
    <div class="card" style="margin-bottom:12px;border-color:rgba(220,38,38,0.25);background:rgba(220,38,38,0.06);">
      <div style="font-weight:900;color:#dc2626;font-size:13px;"><?php echo htmlspecialchars($error); ?></div>
    </div>
  <?php endif; ?>

  <form method="post" action="followup_create_action.php">
    <div class="form-row">
      <div>
        <label>Property</label>
        <select name="property_id" required>
          <option value="">Select</option>
          <?php foreach ($props as $p): ?>
            <?php
              $sel = ($property_id > 0 && (int)$p["id"] === $property_id) ? "selected" : "";
              $label = $p["address"] . ", " . $p["city"] . ", " . $p["postcode"];
            ?>
            <option value="<?php echo (int)$p["id"]; ?>" <?php echo $sel; ?>>
              <?php echo htmlspecialchars($label); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label>Due date</label>
        <input type="date" name="due_date">
      </div>
    </div>

    <div style="height:10px;"></div>

    <label>Title</label>
    <input type="text" name="title" required placeholder="Call seller, arrange valuation, confirm viewing">

    <div style="height:10px;"></div>

    <label>Notes</label>
    <textarea name="notes" rows="4" placeholder="Any details for this follow up"></textarea>

    <div style="height:14px;"></div>

    <button class="btn btn-primary" type="submit">Save follow up</button>
    <a class="btn btn-ghost" href="followups.php<?php echo ($property_id > 0) ? "?property_id=" . $property_id : ""; ?>">Cancel</a>
  </form>
</div>

<?php include __DIR__ . "/includes/footer_agent.php"; ?>