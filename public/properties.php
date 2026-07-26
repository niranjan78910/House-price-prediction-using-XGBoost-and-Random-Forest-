<?php
require_once __DIR__ . "/../backend/config/app.php";
require_once __DIR__ . "/../backend/config/db.php";
require_once __DIR__ . "/../backend/middleware/auth.php";

require_login();

$user_id = (int)$_SESSION["user_id"];

$q = trim($_GET["q"] ?? "");
$status = trim($_GET["status"] ?? "");
$type = trim($_GET["type"] ?? "");
$min_price = trim($_GET["min_price"] ?? "");
$max_price = trim($_GET["max_price"] ?? "");
$published = trim($_GET["published"] ?? "");
$sort = trim($_GET["sort"] ?? "newest");

$where = [];
$params = [];

$where[] = "p.user_id = ?";
$params[] = $user_id;

if ($q !== "") {
    $where[] = "(p.postcode LIKE ? OR p.city LIKE ? OR p.address LIKE ?)";
    $like = "%" . $q . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($status !== "") {
    $where[] = "p.status = ?";
    $params[] = $status;
}

if ($type !== "") {
    $where[] = "p.property_type = ?";
    $params[] = $type;
}

if ($published !== "") {
    if ($published === "1" || $published === "0") {
        $where[] = "p.is_published = ?";
        $params[] = (int)$published;
    }
}

if ($min_price !== "" && is_numeric($min_price)) {
    $where[] = "p.asking_price IS NOT NULL AND p.asking_price >= ?";
    $params[] = (float)$min_price;
}

if ($max_price !== "" && is_numeric($max_price)) {
    $where[] = "p.asking_price IS NOT NULL AND p.asking_price <= ?";
    $params[] = (float)$max_price;
}

$order_by = "p.created_at DESC";
if ($sort === "price_low") {
    $order_by = "p.asking_price IS NULL, p.asking_price ASC, p.created_at DESC";
}
if ($sort === "price_high") {
    $order_by = "p.asking_price IS NULL, p.asking_price DESC, p.created_at DESC";
}

$where_sql = implode(" AND ", $where);

$sql = "
  SELECT p.*,
         (SELECT image_path
            FROM property_images
           WHERE property_id = p.id
           ORDER BY id ASC
           LIMIT 1) AS image_path
  FROM properties p
  WHERE $where_sql
  ORDER BY $order_by
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success = $_SESSION["flash_success"] ?? "";
$error = $_SESSION["flash_error"] ?? "";
unset($_SESSION["flash_success"], $_SESSION["flash_error"]);

include __DIR__ . "/includes/header_agent.php";
include __DIR__ . "/includes/sidebar_agent.php";
?>

<div class="page-title">
  <h2>Properties</h2>
  <a class="btn btn-primary" href="property_add.php">Add property</a>
</div>

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

<div class="card" style="margin-bottom:14px;">
  <form method="get" action="properties.php">
    <div class="form-row">
      <div>
        <label>Search</label>
        <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Postcode, city, address">
      </div>
      <div>
        <label>Status</label>
        <select name="status">
          <option value="">Any</option>
          <?php
            $statuses = ["lead","valued","listed","viewing","offer_received","sold","withdrawn"];
            foreach ($statuses as $s) {
              $sel = ($status === $s) ? "selected" : "";
              echo "<option value=\"$s\" $sel>$s</option>";
            }
          ?>
        </select>
      </div>
    </div>

    <div style="height:10px;"></div>

    <div class="form-row">
      <div>
        <label>Property type</label>
        <select name="type">
          <option value="">Any</option>
          <?php
            $types = ["Detached","Semi-detached","Terraced","Flat","Bungalow"];
            foreach ($types as $t) {
              $sel = ($type === $t) ? "selected" : "";
              echo "<option value=\"" . htmlspecialchars($t) . "\" $sel>" . htmlspecialchars($t) . "</option>";
            }
          ?>
        </select>
      </div>
      <div>
        <label>Published</label>
        <select name="published">
          <option value="">Any</option>
          <option value="1" <?php echo ($published === "1") ? "selected" : ""; ?>>Yes</option>
          <option value="0" <?php echo ($published === "0") ? "selected" : ""; ?>>No</option>
        </select>
      </div>
    </div>

    <div style="height:10px;"></div>

    <div class="form-row">
      <div>
        <label>Min price</label>
        <input type="number" name="min_price" value="<?php echo htmlspecialchars($min_price); ?>" placeholder="e.g. 200000">
      </div>
      <div>
        <label>Max price</label>
        <input type="number" name="max_price" value="<?php echo htmlspecialchars($max_price); ?>" placeholder="e.g. 450000">
      </div>
    </div>

    <div style="height:10px;"></div>

    <div class="form-row">
      <div>
        <label>Sort</label>
        <select name="sort">
          <option value="newest" <?php echo ($sort === "newest") ? "selected" : ""; ?>>Newest</option>
          <option value="price_low" <?php echo ($sort === "price_low") ? "selected" : ""; ?>>Price low to high</option>
          <option value="price_high" <?php echo ($sort === "price_high") ? "selected" : ""; ?>>Price high to low</option>
        </select>
      </div>
      <div style="display:flex;gap:10px;align-items:flex-end;">
        <button class="btn btn-primary" type="submit">Apply</button>
        <a class="btn btn-ghost" href="properties.php">Reset</a>
      </div>
    </div>
  </form>
</div>

<div class="card">
  <table class="table">
    <thead>
      <tr>
        <th>Photo</th>
        <th>Address</th>
        <th>Postcode</th>
        <th>Beds</th>
        <th>Type</th>
        <th>Status</th>
        <th>Asking</th>
        <th>Published</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$properties): ?>
      <tr>
        <td colspan="9" class="muted">No results. Change filters and try again.</td>
      </tr>
    <?php endif; ?>

    <?php foreach ($properties as $p): ?>
      <tr>
        <td>
          <?php if (!empty($p["image_path"])): ?>
            <img
              src="uploads/<?php echo htmlspecialchars($p["image_path"]); ?>"
              style="width:72px;height:52px;object-fit:cover;border-radius:12px;border:1px solid rgba(15,23,42,0.10);"
            >
          <?php else: ?>
            <span class="muted">No photo</span>
          <?php endif; ?>
        </td>

        <td><?php echo htmlspecialchars($p["address"]); ?>, <?php echo htmlspecialchars($p["city"]); ?></td>
        <td><?php echo htmlspecialchars($p["postcode"]); ?></td>
        <td><?php echo (int)$p["bedrooms"]; ?></td>
        <td><?php echo htmlspecialchars($p["property_type"]); ?></td>
        <td><?php echo htmlspecialchars($p["status"]); ?></td>
        <td><?php echo $p["asking_price"] !== null ? "£" . number_format((float)$p["asking_price"], 0) : "-"; ?></td>
        <td><?php echo ((int)$p["is_published"] === 1) ? "Yes" : "No"; ?></td>
        <td>
          <a href="property_view.php?id=<?php echo (int)$p["id"]; ?>">View</a>
          |
          <a href="property_edit.php?id=<?php echo (int)$p["id"]; ?>">Edit</a>
          |
          <form method="POST" action="property_delete_action.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this property? This cannot be undone.');">
            <input type="hidden" name="id" value="<?php echo (int)$p["id"]; ?>">
            <button type="submit" style="background:none;border:none;color:#dc2626;cursor:pointer;padding:0;">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . "/includes/footer_agent.php"; ?>