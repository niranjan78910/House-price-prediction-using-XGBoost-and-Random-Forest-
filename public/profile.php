<?php
require_once __DIR__ . "/../backend/config/app.php";
require_once __DIR__ . "/../backend/config/db.php";
require_once __DIR__ . "/../backend/middleware/auth.php";

require_login();

$user_id = (int)$_SESSION["user_id"];

$stmt = $pdo->prepare("SELECT id, name, email, created_at FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

include __DIR__ . "/includes/header_agent.php";
?>

<div class="page-title">
  <h2>My profile</h2>
  <div class="muted">View and update your account details</div>
</div>

<?php if (!empty($_SESSION["flash_success"])): ?>
  <div class="card" style="border-color:rgba(22,163,74,0.25);background:rgba(22,163,74,0.06);">
    <div class="stat" style="color:var(--good);"><?php echo htmlspecialchars($_SESSION["flash_success"]); ?></div>
  </div>
  <div style="height:12px;"></div>
  <?php unset($_SESSION["flash_success"]); ?>
<?php endif; ?>

<?php if (!empty($_SESSION["flash_error"])): ?>
  <div class="card" style="border-color:rgba(220,38,38,0.22);background:rgba(220,38,38,0.06);">
    <div class="stat" style="color:var(--bad);"><?php echo htmlspecialchars($_SESSION["flash_error"]); ?></div>
  </div>
  <div style="height:12px;"></div>
  <?php unset($_SESSION["flash_error"]); ?>
<?php endif; ?>

<div class="card" style="max-width:900px;">
  <div class="stat">Account</div>
  <div style="height:10px;"></div>

  <form method="post" action="profile_update_action.php">

    <label>Name</label>
    <input type="text" name="name" value="<?php echo htmlspecialchars($user["name"]); ?>" required>

    <div style="height:10px;"></div>

    <label>Email (read only)</label>
    <input type="email" value="<?php echo htmlspecialchars($user["email"]); ?>" disabled>

    <div style="height:10px;"></div>

    <label>New password (optional)</label>
    <input type="password" name="password" placeholder="Leave empty to keep current password">

    <div style="height:10px;"></div>

    <label>Confirm new password</label>
    <input type="password" name="password2" placeholder="Repeat new password">

    <div style="height:14px;"></div>

    <button class="btn btn-primary" type="submit">Save changes</button>
    <a class="btn btn-ghost" href="dashboard.php">Back</a>

    <div class="muted" style="margin-top:12px;">
      Joined: <?php echo htmlspecialchars($user["created_at"]); ?>
    </div>

  </form>
</div>

<?php include __DIR__ . "/includes/footer_agent.php"; ?>