<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
require_once __DIR__ . "/../backend/config/app.php";
require_once __DIR__ . "/../backend/config/db.php";
require_once __DIR__ . "/../backend/middleware/auth.php";

require_login();

$user_id = (int)$_SESSION["user_id"];
$property_id = (int)($_GET["property_id"] ?? 0);

$q = trim($_GET["q"] ?? "");
$status = trim($_GET["status"] ?? "pending");

$where = [];
$params = [];

$where[] = "p.user_id = ?";
$params[] = $user_id;

if ($property_id > 0) {
    $where[] = "f.property_id = ?";
    $params[] = $property_id;
}

if ($q !== "") {
  $where[] = "(f.task_type LIKE ? OR f.notes LIKE ?)";
    $like = "%" . $q . "%";
    $params[] = $like;
    $params[] = $like;
}

if ($status === "pending" || $status === "done") {
    $where[] = "f.status = ?";
    $params[] = $status;
}

$where_sql = implode(" AND ", $where);

$stmt = $pdo->prepare("
  SELECT f.*, f.task_type AS title,
         p.address, p.city, p.postcode
  FROM followups f
  JOIN properties p ON p.id = f.property_id
  WHERE $where_sql
  ORDER BY
    (f.due_date IS NULL) ASC,
    f.due_date ASC,
    f.created_at DESC
");
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success = $_SESSION["flash_success"] ?? "";
$error = $_SESSION["flash_error"] ?? "";
unset($_SESSION["flash_success"], $_SESSION["flash_error"]);

include __DIR__ . "/includes/header_agent.php";
include __DIR__ . "/includes/sidebar_agent.php";
?>

<div class="page-title">
  <h2>Follow ups</h2>
  <a class="btn btn-primary" href="followup_add.php<?php echo ($property_id > 0) ? "?property_id=" . $property_id : ""; ?>">Add follow up</a>
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
  <form method="get" action="followups.php">
    <?php if ($property_id > 0): ?>
      <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">
    <?php endif; ?>

    <div class="form-row">
      <div>
        <label>Search</label>
        <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Title or notes">
      </div>
      <div>
        <label>Status</label>
        <select name="status">
          <option value="pending" <?php echo ($status === "pending") ? "selected" : ""; ?>>pending</option>
          <option value="done" <?php echo ($status === "done") ? "selected" : ""; ?>>done</option>
          <option value="" <?php echo ($status === "") ? "selected" : ""; ?>>all</option>
        </select>
      </div>
    </div>

    <div style="height:12px;"></div>

    <button class="btn btn-primary" type="submit">Apply</button>
    <a class="btn btn-ghost" href="followups.php<?php echo ($property_id > 0) ? "?property_id=" . $property_id : ""; ?>">Reset</a>
  </form>
</div>

<div class="card">
  <table class="table">
    <thead>
      <tr>
        <th>Due</th>
        <th>Title</th>
        <th>Property</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php if (!$items): ?>
      <tr>
        <td colspan="5" class="muted">No follow ups found.</td>
      </tr>
    <?php endif; ?>

    <?php foreach ($items as $f): ?>
      <tr>
        <td><?php echo htmlspecialchars($f["due_date"] ?? ""); ?></td>
        <td>
          <div style="font-weight:900;"><?php echo htmlspecialchars($f["title"]); ?></div>
          <?php if (!empty($f["notes"])): ?>
            <div class="muted"><?php echo htmlspecialchars($f["notes"]); ?></div>
          <?php endif; ?>
        </td>
        <td class="muted">
          <?php echo htmlspecialchars($f["address"]); ?>, <?php echo htmlspecialchars($f["city"]); ?>, <?php echo htmlspecialchars($f["postcode"]); ?>
        </td>
        <td>
          <?php if ($f["status"] === "done"): ?>
            <span class="badge good">done</span>
          <?php else: ?>
            <span class="badge warn">pending</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($f["status"] !== "done"): ?>
            <form method="post" action="followup_done_action.php" style="display:inline;">
              <input type="hidden" name="id" value="<?php echo (int)$f["id"]; ?>">
              <button class="btn btn-ghost" type="submit">Mark done</button>
            </form>
          <?php endif; ?>

          <form method="post" action="followup_delete_action.php" style="display:inline;" onsubmit="return confirm('Delete this follow up?');">
            <input type="hidden" name="id" value="<?php echo (int)$f["id"]; ?>">
            <button class="btn" type="submit" style="background:#dc2626;color:#fff;border-color:#dc2626;">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . "/includes/footer_agent.php"; ?>