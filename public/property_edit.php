<?php
require_once __DIR__ . "/../backend/config/app.php";
require_once __DIR__ . "/../backend/config/db.php";
require_once __DIR__ . "/../backend/middleware/auth.php";

require_login();

$user_id = (int)$_SESSION["user_id"];
$property_id = (int)($_GET["id"] ?? 0);

if ($property_id <= 0) {
    redirect("/properties.php");
}

$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$property_id, $user_id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    redirect("/properties.php");
}

$stmt_imgs = $pdo->prepare("SELECT id, image_path FROM property_images WHERE property_id = ? ORDER BY id ASC");
$stmt_imgs->execute([$property_id]);
$images = $stmt_imgs->fetchAll(PDO::FETCH_ASSOC);

$error = $_SESSION["flash_error"] ?? "";
unset($_SESSION["flash_error"]);

include __DIR__ . "/includes/header_agent.php";
include __DIR__ . "/includes/sidebar_agent.php";
?>

<div class="page-title">
  <h2>Edit property</h2>
  <div style="display:flex;gap:10px;">
    <a class="btn btn-ghost" href="property_view.php?id=<?php echo $property_id; ?>">Back</a>
  </div>
</div>

<div class="card" style="max-width:980px;">
  <?php if ($error): ?>
    <div class="card" style="margin-bottom:12px;border-color:rgba(220,38,38,0.25);background:rgba(220,38,38,0.06);">
      <div style="font-weight:900;color:#dc2626;font-size:13px;">
        <?php echo htmlspecialchars($error); ?>
      </div>
    </div>
  <?php endif; ?>

  <form method="post" action="property_update_action.php" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?php echo $property_id; ?>">

    <div class="form-row">
      <div>
        <label>Address</label>
        <input type="text" name="address" required value="<?php echo htmlspecialchars($property["address"]); ?>">
      </div>
      <div>
        <label>City</label>
        <input type="text" name="city" required value="<?php echo htmlspecialchars($property["city"]); ?>">
      </div>
    </div>

    <div style="height:10px;"></div>

    <div class="form-row">
      <div>
        <label>Postcode</label>
        <input type="text" name="postcode" required value="<?php echo htmlspecialchars($property["postcode"]); ?>">
      </div>
      <div>
        <label>Property type</label>
        <select name="property_type" required>
          <?php
            $types = ["Detached","Semi-detached","Terraced","Flat","Bungalow"];
            foreach ($types as $t) {
              $selected = ($property["property_type"] === $t) ? "selected" : "";
              echo "<option $selected>$t</option>";
            }
          ?>
        </select>
      </div>
    </div>

    <div style="height:10px;"></div>

    <div class="form-row">
      <div>
        <label>Bedrooms</label>
        <input type="number" name="bedrooms" min="0" max="20" required value="<?php echo (int)$property["bedrooms"]; ?>">
      </div>
      <div>
        <label>Bathrooms</label>
        <input type="number" name="bathrooms" min="0" max="20" required value="<?php echo (int)$property["bathrooms"]; ?>">
      </div>
      <div>
        <label>Floor Area (sqm)</label>
        <input type="number" name="floor_area" min="10" max="1000" value="<?php echo htmlspecialchars((string)($property["floor_area"] ?? "")); ?>" placeholder="e.g. 85">
      </div>
    </div>

    <div style="height:10px;"></div>

    <div class="form-row">
      <div>
        <label>Tenure</label>
        <select name="freehold" required>
          <option value="F" <?php echo (($property["freehold"] ?? "F") === "F") ? "selected" : ""; ?>>Freehold</option>
          <option value="L" <?php echo (($property["freehold"] ?? "F") === "L") ? "selected" : ""; ?>>Leasehold</option>
        </select>
      </div>
    </div>

    <div style="height:10px;"></div>

    <div class="form-row">
      <div>
        <label>Status</label>
        <select name="status" required>
          <?php
            $statuses = ["lead","valued","listed","viewing","offer_received","sold","withdrawn"];
            foreach ($statuses as $s) {
              $selected = ($property["status"] === $s) ? "selected" : "";
              echo "<option value=\"$s\" $selected>$s</option>";
            }
          ?>
        </select>
      </div>
      <div>
        <label>Asking price</label>
        <input type="number" step="0.01" name="asking_price" value="<?php echo htmlspecialchars((string)($property["asking_price"] ?? "")); ?>">
      </div>
    </div>

    <div style="height:10px;"></div>

    <label>Agent notes</label>
    <textarea name="agent_notes" rows="4"><?php echo htmlspecialchars($property["agent_notes"] ?? ""); ?></textarea>

    <div style="height:10px;"></div>

    <div class="form-row">
      <div>
        <label>Publish to public listings?</label>
        <select name="is_published">
          <option value="0" <?php echo ((int)$property["is_published"] === 0) ? "selected" : ""; ?>>No</option>
          <option value="1" <?php echo ((int)$property["is_published"] === 1) ? "selected" : ""; ?>>Yes</option>
        </select>
      </div>
      <div>
        <label>Public description</label>
        <input type="text" name="public_description" value="<?php echo htmlspecialchars($property["public_description"] ?? ""); ?>">
      </div>
    </div>

    <div style="height:12px;"></div>

    <div class="card">
      <div class="stat">Property photos</div>
      <div class="muted" style="margin-top:6px;">Upload more photos. Existing photos stay.</div>

      <div style="height:10px;"></div>

      <?php if (!empty($images)): ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;">
          <?php foreach ($images as $img): ?>
            <img
              src="uploads/<?php echo htmlspecialchars($img["image_path"]); ?>"
              style="width:100%;height:160px;object-fit:cover;border-radius:14px;border:1px solid rgba(15,23,42,0.10);"
            >
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="muted">No photos yet</div>
      <?php endif; ?>

      <div style="height:10px;"></div>

      <label>Add more photos (multiple allowed)</label>
      <input type="file" name="property_images[]" accept="image/*" multiple>
    </div>

    <div style="height:14px;"></div>

    <button class="btn btn-primary" type="submit">Save changes</button>
    <a class="btn btn-ghost" href="property_view.php?id=<?php echo $property_id; ?>">Cancel</a>
  </form>

</div>

<?php include __DIR__ . "/includes/footer_agent.php"; ?>
