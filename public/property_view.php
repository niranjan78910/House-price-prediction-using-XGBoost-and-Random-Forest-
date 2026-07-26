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

$stmt_img = $pdo->prepare("SELECT image_path FROM property_images WHERE property_id = ? ORDER BY id ASC");
$stmt_img->execute([$property_id]);
$images = $stmt_img->fetchAll(PDO::FETCH_COLUMN);

$success = $_SESSION["flash_success"] ?? "";
$error = $_SESSION["flash_error"] ?? "";
unset($_SESSION["flash_success"], $_SESSION["flash_error"]);

function status_badge_class($status) {
    $status = (string)$status;
    if ($status === "sold") return "good";
    if ($status === "listed" || $status === "viewing" || $status === "offer_received") return "warn";
    if ($status === "withdrawn") return "bad";
    return "";
}

include __DIR__ . "/includes/header_agent.php";
include __DIR__ . "/includes/sidebar_agent.php";
?>

<div class="page-title">
  <h2>Property details</h2>
  <div style="display:flex;gap:10px;align-items:center;">
    <a class="btn btn-ghost" href="properties.php">Back</a>
    <a class="btn btn-primary" href="property_edit.php?id=<?php echo (int)$property["id"]; ?>">Edit</a>
    
    <!-- AI Prediction Button -->
    <form method="POST" action="../backend/actions/predict_action.php" style="display:inline;">
      <input type="hidden" name="id" value="<?php echo (int)$property["id"]; ?>">
      <button type="submit" class="btn btn-primary" style="background-color:#8b5cf6;">Get AI Prediction</button>
    </form>
  </div>
</div>

<div class="grid-3">
  <div class="card" style="grid-column: span 2;">

    <?php if ($success): ?>
      <div class="card" style="margin-bottom:12px;border-color:rgba(22,163,74,0.25);background:rgba(22,163,74,0.08);">
        <div style="font-weight:900;color:#16a34a;font-size:13px;"><?php echo htmlspecialchars($success); ?></div>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="card" style="margin-bottom:12px;border-color:rgba(220,38,38,0.25);background:rgba(220,38,38,0.06);">
        <div style="font-weight:900;color:#dc2626;font-size:13px;"><?php echo htmlspecialchars($error); ?></div>
      </div>
    <?php endif; ?>

    <?php if (!empty($images)): ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;">
        <?php foreach ($images as $img): ?>
          <img
            src="uploads/<?php echo htmlspecialchars($img); ?>"
            style="width:100%;height:220px;object-fit:cover;border-radius:14px;border:1px solid rgba(15,23,42,0.10);"
          >
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="card" style="text-align:center;padding:40px;">
        <div class="muted">No images uploaded</div>
      </div>
    <?php endif; ?>

    <div style="height:12px;"></div>

    <div class="pill">
      <span class="badge <?php echo status_badge_class($property["status"]); ?>">
        <?php echo htmlspecialchars($property["status"]); ?>
      </span>
      <span style="margin-left:10px;" class="muted">
        Created: <?php echo htmlspecialchars($property["created_at"]); ?>
      </span>
    </div>

    <h2 style="margin:10px 0 6px;">
      <?php echo htmlspecialchars($property["address"]); ?>
    </h2>
    <div class="muted">
      <?php echo htmlspecialchars($property["city"]); ?>, <?php echo htmlspecialchars($property["postcode"]); ?>
    </div>

    <div style="height:14px;"></div>

    <div class="grid-3">
      <div class="card">
        <div class="stat">Bedrooms</div>
        <div class="stat-value"><?php echo (int)$property["bedrooms"]; ?></div>
      </div>
      <div class="card">
        <div class="stat">Bathrooms</div>
        <div class="stat-value"><?php echo (int)$property["bathrooms"]; ?></div>
      </div>
      <div class="card">
        <div class="stat">Type</div>
        <div class="stat-value" style="font-size:18px;"><?php echo htmlspecialchars($property["property_type"]); ?></div>
      </div>
    </div>

    <div style="height:12px;"></div>

    <div class="grid-3">
      <div class="card">
        <div class="stat">Asking price</div>
        <div class="stat-value">
          <?php
            if ($property["asking_price"] !== null) {
              echo "£" . number_format((float)$property["asking_price"], 0);
            } else {
              echo "-";
            }
          ?>
        </div>
      </div>

      <div class="card">
        <div class="stat">AI predicted</div>
        <div class="stat-value">
          <?php
            if ($property["predicted_price"] !== null) {
              echo "£" . number_format((float)$property["predicted_price"], 0);
            } else {
              echo "-";
            }
          ?>
        </div>
      </div>

      <div class="card">
        <div class="stat">Published</div>
        <div class="stat-value"><?php echo ((int)$property["is_published"] === 1) ? "Yes" : "No"; ?></div>
      </div>
    </div>

    <div style="height:12px;"></div>

    <div class="card">
      <div class="stat">Agent notes</div>
      <div style="margin-top:10px;">
        <?php echo nl2br(htmlspecialchars($property["agent_notes"] ?? "")); ?>
      </div>
    </div>

    <?php if (!empty($property["public_description"])): ?>
      <div style="height:12px;"></div>
      <div class="card">
        <div class="stat">Public description</div>
        <div style="margin-top:10px;">
          <?php echo nl2br(htmlspecialchars($property["public_description"])); ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="stat">Quick actions</div>

    <div style="height:12px;"></div>

    <div class="muted">Public link</div>
    <?php if ((int)$property["is_published"] === 1 && !empty($property["public_slug"])): ?>
      <div style="margin-top:8px;">
        <a href="listing.php?slug=<?php echo urlencode($property["public_slug"]); ?>" style="font-weight:900;color:#2563eb;text-decoration:none;">
          View public page
        </a>
      </div>
    <?php else: ?>
      <div style="margin-top:8px;" class="muted">Not published</div>
    <?php endif; ?>

    <div style="height:16px;"></div>

    <div class="muted">Owner and follow ups</div>
    <div style="margin-top:8px;">
      <a class="btn btn-ghost" style="width:100%;" href="owner_edit.php?property_id=<?php echo (int)$property["id"]; ?>">Edit owner</a>
    </div>
    <div style="margin-top:10px;">
      <a class="btn btn-ghost" style="width:100%;" href="followups.php?property_id=<?php echo (int)$property["id"]; ?>">View follow ups</a>
    </div>
  </div>
</div>

<?php include __DIR__ . "/includes/footer_agent.php"; ?>