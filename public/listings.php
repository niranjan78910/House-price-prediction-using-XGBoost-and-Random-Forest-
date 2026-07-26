<?php
require_once __DIR__ . "/../backend/config/app.php";
require_once __DIR__ . "/../backend/config/db.php";

$q = trim($_GET["q"] ?? "");
$type = trim($_GET["type"] ?? "");
$min_price = trim($_GET["min_price"] ?? "");
$max_price = trim($_GET["max_price"] ?? "");
$beds = trim($_GET["beds"] ?? "");
$sort = trim($_GET["sort"] ?? "newest");

$where = [];
$params = [];

$where[] = "p.is_published = 1";

if ($q !== "") {
    $where[] = "(p.postcode LIKE ? OR p.city LIKE ? OR p.address LIKE ?)";
    $like = "%" . $q . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($type !== "") {
    $where[] = "p.property_type = ?";
    $params[] = $type;
}

if ($beds !== "" && ctype_digit($beds)) {
    $where[] = "p.bedrooms >= ?";
    $params[] = (int)$beds;
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
$listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . "/includes/header_public.php";
?>

<div class="page-title">
  <h2>Listings</h2>
  <div class="muted">Search and filter published properties</div>
</div>

<div class="card" style="margin-bottom:14px;">
  <form method="get" action="listings.php">
    <div class="form-row">
      <div>
        <label>Search</label>
        <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Postcode, city, address">
      </div>
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
        <label>Bedrooms</label>
        <select name="beds">
          <option value="">Any</option>
          <?php
            for ($i = 1; $i <= 6; $i++) {
              $sel = ($beds !== "" && (int)$beds === $i) ? "selected" : "";
              echo "<option value=\"$i\" $sel>$i+</option>";
            }
          ?>
        </select>
      </div>
      <div>
        <label>Sort</label>
        <select name="sort">
          <option value="newest" <?php echo ($sort === "newest") ? "selected" : ""; ?>>Newest</option>
          <option value="price_low" <?php echo ($sort === "price_low") ? "selected" : ""; ?>>Price low to high</option>
          <option value="price_high" <?php echo ($sort === "price_high") ? "selected" : ""; ?>>Price high to low</option>
        </select>
      </div>
    </div>

    <div style="height:14px;"></div>

    <button class="btn btn-primary" type="submit">Apply</button>
    <a class="btn btn-ghost" href="listings.php">Reset</a>
  </form>
</div>

<?php if (!$listings): ?>
  <div class="card">
    <div class="muted">No results. Change filters and try again.</div>
  </div>
<?php endif; ?>

<div class="grid-3">
  <?php foreach ($listings as $p): ?>
    <div class="card">
      <?php if (!empty($p["image_path"])): ?>
        <img
          src="uploads/<?php echo htmlspecialchars($p["image_path"]); ?>"
          style="width:100%;height:210px;object-fit:cover;border-radius:14px;border:1px solid rgba(15,23,42,0.10);"
        >
      <?php else: ?>
        <div class="card" style="text-align:center;padding:30px;">
          <div class="muted">No photo</div>
        </div>
      <?php endif; ?>

      <div style="height:10px;"></div>

      <div class="stat"><?php echo htmlspecialchars($p["property_type"]); ?></div>
      <div style="font-weight:900;font-size:16px;margin-top:6px;">
        <?php echo htmlspecialchars($p["address"]); ?>
      </div>
      <div class="muted" style="margin-top:4px;">
        <?php echo htmlspecialchars($p["city"]); ?>, <?php echo htmlspecialchars($p["postcode"]); ?>
      </div>

      <div style="height:10px;"></div>

      <div style="display:flex;gap:10px;align-items:center;justify-content:space-between;">
        <div class="badge">
          <?php echo (int)$p["bedrooms"]; ?> bed, <?php echo (int)$p["bathrooms"]; ?> bath
        </div>

        <div style="font-weight:900;">
          <?php
            if ($p["asking_price"] !== null) {
              echo "£" . number_format((float)$p["asking_price"], 0);
            } else {
              echo "Price on request";
            }
          ?>
        </div>
      </div>

      <div style="height:12px;"></div>

      <a class="btn btn-primary" style="width:100%;" href="listing.php?slug=<?php echo urlencode($p["public_slug"]); ?>">
        View details
      </a>
    </div>
  <?php endforeach; ?>
</div>

<?php include __DIR__ . "/includes/footer_public.php"; ?>