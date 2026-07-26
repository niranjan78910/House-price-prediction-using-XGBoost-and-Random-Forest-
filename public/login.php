<?php
require_once __DIR__ . "/../backend/config/app.php";

$error = $_SESSION["flash_error"] ?? "";
unset($_SESSION["flash_error"]);

include __DIR__ . "/includes/header_public.php";
?>

<div class="card" style="max-width:520px;margin:0 auto;">
  <div class="pill">Agent portal</div>
  <h2 style="margin:10px 0 6px;">Login</h2>
  <div class="muted">Sign in to manage your portfolio.</div>

  <?php if ($error): ?>
    <div class="card" style="margin-top:14px;border-color:rgba(220,38,38,0.25);background:rgba(220,38,38,0.06);">
      <div style="font-weight:800;color:#dc2626;font-size:13px;">
        <?php echo htmlspecialchars($error); ?>
      </div>
    </div>
  <?php endif; ?>

  <form method="post" action="../backend/actions/auth_login_action.php" style="margin-top:14px;">
    <label>Email</label>
    <input type="email" name="email" required>

    <div style="height:10px;"></div>

    <label>Password</label>
    <input type="password" name="password" required>

    <div style="height:14px;"></div>

    <button class="btn btn-primary" type="submit" style="width:100%;">Login</button>
  </form>

  <div style="margin-top:14px;" class="muted">
    New here? <a href="register.php" style="font-weight:800;color:#2563eb;text-decoration:none;">Create an account</a>
  </div>
</div>

<?php include __DIR__ . "/includes/footer_public.php"; ?>