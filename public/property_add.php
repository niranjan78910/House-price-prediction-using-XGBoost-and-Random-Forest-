<?php
require_once __DIR__ . "/../backend/config/app.php";
require_once __DIR__ . "/../backend/middleware/auth.php";
require_login();

$error = $_SESSION["flash_error"] ?? "";
unset($_SESSION["flash_error"]);

include __DIR__ . "/includes/header_agent.php";
include __DIR__ . "/includes/sidebar_agent.php";
?>

<div class="page-title">
  <h2>Add property</h2>
  <div class="muted">Create a new seller lead in your portfolio</div>
</div>

<div class="card">
  <?php if ($error): ?>
    <div class="card" style="margin-bottom:12px;border-color:rgba(220,38,38,0.25);background:rgba(220,38,38,0.06);">
      <div style="font-weight:800;color:#dc2626;font-size:13px;">
        <?php echo htmlspecialchars($error); ?>
      </div>
    </div>
  <?php endif; ?>

  <form method="post" action="property_create_action.php" enctype="multipart/form-data">
    <label>Address</label>
    <input type="text" name="address" required>

    <div style="height:10px;"></div>

    <div class="form-row">
      <div>
        <label>City</label>
        <input type="text" name="city" required>
      </div>
      <div>
        <label>Postcode</label>
        <input type="text" name="postcode" required placeholder="e.g. M1 1AA">
      </div>
    </div>

    <div style="height:10px;"></div>


    <div class="form-row">
      <div>
        <label>Bedrooms (Rooms)</label>
        <input type="number" name="bedrooms" min="1" max="20" required placeholder="e.g. 3">
      </div>
      <div>
        <label>Bathrooms</label>
        <input type="number" name="bathrooms" min="0" max="20" required>
      </div>
      <div>
        <label>Floor Area (sqm)</label>
        <input type="number" name="floor_area" min="10" max="1000" required placeholder="e.g. 85">
      </div>
    </div>

    <div style="height:10px;"></div>

    <div class="form-row">
      <div>
        <label>Tenure</label>
        <select name="freehold" required>
          <option value="F">Freehold</option>
          <option value="L">Leasehold</option>
        </select>
      </div>
    </div>

    <div style="height:10px;"></div>

    <div class="form-row">
      <div>
        <label>Property type</label>
        <select name="property_type" required>
          <option value="">Select</option>
          <option>Detached</option>
          <option>Semi-detached</option>
          <option>Terraced</option>
          <option>Flat</option>
          <option>Bungalow</option>
        </select>
      </div>
      <div>
        <label>Status</label>
        <select name="status" required>
          <option>lead</option>
          <option>valued</option>
          <option>listed</option>
          <option>viewing</option>
          <option>offer_received</option>
          <option>sold</option>
          <option>withdrawn</option>
        </select>
      </div>
    </div>

    <div style="height:10px;"></div>

    <label>Asking price (optional)</label>
    <input type="number" step="0.01" name="asking_price" placeholder="e.g. 325000">

    <div style="height:10px;"></div>

    <label>Agent notes (optional)</label>
    <textarea name="agent_notes" rows="4" placeholder="Notes about the seller, condition, next steps"></textarea>

    <div style="height:10px;"></div>

    <label>Property photos (multiple allowed)</label>
    <input type="file" name="property_images[]" accept="image/*" multiple>

    <div style="height:10px;"></div>

    <div class="form-row">
      <div>
        <label>Publish to public listings?</label>
        <select name="is_published">
          <option value="0">No</option>
          <option value="1">Yes</option>
        </select>
      </div>
      <div>
        <label>Public description (optional)</label>
        <input type="text" name="public_description" placeholder="Short summary for public page">
      </div>
    </div>

    <div style="height:14px;"></div>

    <button class="btn btn-primary" type="submit">Save property</button>
    <a class="btn btn-ghost" href="properties.php">Cancel</a>
  </form>
</div>

<?php include __DIR__ . "/includes/footer_agent.php"; ?>
