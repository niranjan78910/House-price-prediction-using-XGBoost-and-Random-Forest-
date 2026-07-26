<?php
require_once __DIR__ . "/../backend/config/app.php";
require_once __DIR__ . "/../backend/config/db.php";

$slug = trim($_GET["slug"] ?? "");
if ($slug === "") {
    redirect("/listings.php");
}

$stmt = $pdo->prepare("SELECT * FROM properties WHERE public_slug = ? AND is_published = 1 LIMIT 1");
$stmt->execute([$slug]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    redirect("/listings.php");
}

$stmt_img = $pdo->prepare("SELECT image_path FROM property_images WHERE property_id = ? ORDER BY id ASC");
$stmt_img->execute([(int)$property["id"]]);
$images = $stmt_img->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . "/includes/header_public.php";
?>

<div class="page-title">
  <h2>Listing</h2>
  <a class="btn btn-ghost" href="listings.php">Back to listings</a>
</div>

<div class="grid-3">
  <div class="card" style="grid-column: span 2;">
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
        <div class="muted">No images</div>
      </div>
    <?php endif; ?>

    <div style="height:12px;"></div>

    <h2 style="margin:8px 0 6px;"><?php echo htmlspecialchars($property["address"]); ?></h2>
    <div class="muted"><?php echo htmlspecialchars($property["city"]); ?>, <?php echo htmlspecialchars($property["postcode"]); ?></div>

    <div style="height:12px;"></div>

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

    <?php if (!empty($property["public_description"])): ?>
      <div style="height:12px;"></div>
      <div class="card">
        <div class="stat">Description</div>
        <div style="margin-top:10px;">
          <?php echo nl2br(htmlspecialchars($property["public_description"])); ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="stat">Price</div>
    <div class="stat-value" style="margin-top:10px;">
      <?php
        if ($property["asking_price"] !== null) {
          echo "£" . number_format((float)$property["asking_price"], 0);
        } else {
          echo "Price on request";
        }
      ?>
    </div>

    <div style="height:12px;"></div>

    <div class="stat">Status</div>
    <div style="margin-top:10px;" class="badge">
      <?php echo htmlspecialchars($property["status"]); ?>
    </div>

    <div style="height:16px;"></div>

    <div class="muted">
      <?php if (!empty($_GET['enquiry']) && $_GET['enquiry'] === 'sent'): ?>
  <div style="padding:10px;margin:10px 0;background:#e6ffed;border:1px solid #b7f5c8;">
    Thanks, your enquiry was sent to the agent.
  </div>
<?php endif; ?>

<h3>Enquire about this property</h3>

<form method="post" action="inquiry_create_action.php">
  <input type="hidden" name="property_id" value="<?php echo (int)$property['id']; ?>">

  <div style="margin:8px 0;">
    <label>Your name</label><br>
    <input type="text" name="name" required>
  </div>

  <div style="margin:8px 0;">
    <label>Your email</label><br>
    <input type="email" name="email" required>
  </div>

  <div style="margin:8px 0;">
    <label>Your phone (optional)</label><br>
    <input type="text" name="phone">
  </div>

  <div style="margin:8px 0;">
    <label>Message</label><br>
    <textarea name="message" rows="5" required></textarea>
  </div>

  <button type="submit">Send enquiry</button>
</form>
    </div>
  </div>
</div>

<?php include __DIR__ . "/includes/footer_public.php"; ?>