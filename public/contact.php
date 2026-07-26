<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
require_once __DIR__ . "/../backend/config/app.php";
require_once __DIR__ . "/../backend/config/db.php";

$errors = [];
$success = false;

function h($v){
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$name = '';
$agency = '';
$email = '';
$phone = '';
$listings = '';
$next_step = 'demo';
$message = '';
$website = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $name = trim($_POST['name'] ?? '');
  $agency = trim($_POST['agency'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $listings = trim($_POST['listings'] ?? '');
  $next_step = trim($_POST['next_step'] ?? 'demo');
  $message = trim($_POST['message'] ?? '');
  $website = trim($_POST['website'] ?? '');

  if ($website !== '') {
    $errors[] = 'Request failed.';
  }

  if ($name === '' || strlen($name) < 2) $errors[] = 'Enter your name.';
  if ($agency === '' || strlen($agency) < 2) $errors[] = 'Enter your agency name.';
  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
  if ($phone === '' || strlen($phone) < 6) $errors[] = 'Enter a phone number.';
  if ($listings === '' || !preg_match('/^\d+$/', $listings)) $errors[] = 'Enter number of active listings.';
  if ($message === '' || strlen($message) < 10) $errors[] = 'Add a short message.';

  $allowed_steps = ['demo','trial','questions'];
  if (!in_array($next_step, $allowed_steps, true)) {
    $next_step = 'demo';
  }

  if (!$errors) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    try {
      $stmt = $pdo->prepare("
        INSERT INTO contact_messages
        (full_name, agency, email, phone, active_listings, next_step, message, ip_address)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->execute([
        $name,
        $agency,
        $email,
        $phone,
        (int)$listings,
        $next_step,
        $message,
        $ip
      ]);

      $success = true;
      $name = $agency = $email = $phone = $listings = $message = '';
      $next_step = 'demo';

    } catch (PDOException $e) {
      $errors[] = 'Could not save your request. Please try again.';
    }
  }
}

include "includes/header_public.php";
?>

<div class="page">

  <section class="hero" style="grid-template-columns: 1fr; min-height:auto;">
    <div class="card">
      <div class="pill">Request a demo. UK estate agents.</div>
      <h1>Book a demo for Prop Flow AI</h1>
      <p>See how seller leads, follow ups, listings, and AI valuation fit into your agency workflow.</p>

      <?php if ($success): ?>
        <div class="card" style="margin-top:14px;border-color:rgba(22,163,74,0.25);background:rgba(22,163,74,0.06);">
          <div class="stat" style="color:var(--good);">Request received</div>
          <div class="muted" style="margin-top:8px;">We will contact you soon.</div>
        </div>
      <?php endif; ?>

      <?php if ($errors): ?>
        <div class="card" style="margin-top:14px;border-color:rgba(220,38,38,0.22);background:rgba(220,38,38,0.06);">
          <div class="stat" style="color:var(--bad);">Fix these fields</div>
          <ul style="margin:10px 0 0;padding-left:18px;">
            <?php foreach ($errors as $e): ?>
              <li class="muted" style="color:rgba(220,38,38,0.9);"><?php echo h($e); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" action="contact.php" style="margin-top:16px;">
        <div class="form-row">
          <div>
            <label>Your name</label>
            <input name="name" value="<?php echo h($name); ?>" placeholder="Full name" required>
          </div>
          <div>
            <label>Agency name</label>
            <input name="agency" value="<?php echo h($agency); ?>" placeholder="Agency Ltd" required>
          </div>
        </div>

        <div class="form-row" style="margin-top:12px;">
          <div>
            <label>Email</label>
            <input type="email" name="email" value="<?php echo h($email); ?>" placeholder="name@agency.co.uk" required>
          </div>
          <div>
            <label>Phone</label>
            <input name="phone" value="<?php echo h($phone); ?>" placeholder="07..." required>
          </div>
        </div>

        <div class="form-row" style="margin-top:12px;">
          <div>
            <label>Active listings</label>
            <input name="listings" value="<?php echo h($listings); ?>" placeholder="e.g. 120" inputmode="numeric" required>
          </div>
          <div>
            <label>Best next step</label>
            <select name="next_step">
              <option value="demo" <?php echo ($next_step === 'demo') ? 'selected' : ''; ?>>Book a demo</option>
              <option value="trial" <?php echo ($next_step === 'trial') ? 'selected' : ''; ?>>Ask for agent access</option>
              <option value="questions" <?php echo ($next_step === 'questions') ? 'selected' : ''; ?>>Questions</option>
            </select>
          </div>
        </div>

        <div style="margin-top:12px;">
          <label>Message</label>
          <textarea name="message" rows="5" required><?php echo h($message); ?></textarea>
        </div>

        <div style="position:absolute;left:-9999px;top:-9999px;">
          <label>Website</label>
          <input name="website" value="<?php echo h($website); ?>" tabindex="-1" autocomplete="off">
        </div>

        <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap;">
          <button class="btn btn-primary" type="submit">Request demo</button>
          <a class="btn btn-ghost" href="listings.php">View public listings</a>
        </div>
      </form>
    </div>
  </section>

</div>

<?php include "includes/footer_public.php"; ?>