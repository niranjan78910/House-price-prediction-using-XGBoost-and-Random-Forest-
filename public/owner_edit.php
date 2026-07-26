<?php
require_once __DIR__ . "/../backend/config/app.php";
require_once __DIR__ . "/../backend/config/db.php";
require_once __DIR__ . "/../backend/middleware/auth.php";

require_login();

$user_id = (int)$_SESSION["user_id"];
$property_id = (int)($_GET["property_id"] ?? 0);

if ($property_id <= 0) {
    redirect("/properties.php");
}

$stmt = $pdo->prepare("SELECT id, address, city, postcode FROM properties WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$property_id, $user_id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    redirect("/properties.php");
}

$stmt = $pdo->prepare("SELECT * FROM owners WHERE property_id = ? LIMIT 1");
$stmt->execute([$property_id]);
$owner = $stmt->fetch(PDO::FETCH_ASSOC);

$error = $_SESSION["flash_error"] ?? "";
$success = $_SESSION["flash_success"] ?? "";
unset($_SESSION["flash_error"], $_SESSION["flash_success"]);

include __DIR__ . "/includes/header_agent.php";
include __DIR__ . "/includes/sidebar_agent.php";
?>

<div class="page-title">
  <h2>Owner details</h2>
  <a class="btn btn-ghost" href="property_view.php?id=<?php echo (int)$property["id"]; ?>">Back</a>
</div>

<div class="card" style="max-width:980px;">
  <div class="muted">
    Property: <?php echo htmlspecialchars($property["address"]); ?>, <?php echo htmlspecialchars($property["city"]); ?>, <?php echo htmlspecialchars($property["postcode"]); ?>
  </div>

  <?php if ($error): ?>
    <div class="card" style="margin-top:12px;border-color:rgba(220,38,38,0.25);background:rgba(220,38,38,0.06);">
      <div style="font-weight:900;color:#dc2626;font-size:13px;"><?php echo htmlspecialchars($error); ?></div>
    </div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="card" style="margin-top:12px;border-color:rgba(22,163,74,0.25);background:rgba(22,163,74,0.08);">
      <div style="font-weight:900;color:#16a34a;font-size:13px;"><?php echo htmlspecialchars($success); ?></div>
    </div>
  <?php endif; ?>

  <form method="post" action="owner_save_action.php" style="margin-top:14px;">
    <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">

    <div class="form-row">
      <div>
        <label>Full name</label>
        <input type="text" name="full_name" required value="<?php echo htmlspecialchars($owner["full_name"] ?? ""); ?>">
      </div>
      <div>
        <label>Preferred contact</label>
        <select name="preferred_contact">
          <?php
            $opts = ["phone","email","either"];
            $current = $owner["preferred_contact"] ?? "phone";
            foreach ($opts as $o) {
              $sel = ($current === $o) ? "selected" : "";
              echo "<option value=\"$o\" $sel>$o</option>";
            }
          ?>
        </select>
      </div>
    </div>

    <div style="height:10px;"></div>

    <div class="form-row">
      <div>
        <label>Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($owner["email"] ?? ""); ?>">
      </div>
      <div>
        <label>Phone</label>
        <input type="text" name="phone" value="<?php echo htmlspecialchars($owner["phone"] ?? ""); ?>">
      </div>
    </div>

    <div style="height:10px;"></div>

    <label>Notes</label>
    <textarea name="notes" rows="4" placeholder="Seller situation, timeline, any key points"><?php echo htmlspecialchars($owner["notes"] ?? ""); ?></textarea>

    <div style="height:14px;"></div>

    <button class="btn btn-primary" type="submit">Save owner</button>
    <a class="btn btn-ghost" href="property_view.php?id=<?php echo $property_id; ?>">Cancel</a>
  </form>
</div>

<?php include __DIR__ . "/includes/footer_agent.php"; ?>