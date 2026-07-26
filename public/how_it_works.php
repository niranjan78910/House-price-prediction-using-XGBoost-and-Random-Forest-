<?php
require_once __DIR__ . "/../backend/config/app.php";
include __DIR__ . "/includes/header_public.php";
?>

<div class="page-title">
  <h2>How it works</h2>
  <div class="muted">Simple workflow for estate agents</div>
</div>

<div class="grid-3">
  <div class="card">
    <div class="stat">1</div>
    <div style="font-weight:900;margin-top:6px;">Add property</div>
    <div class="muted" style="margin-top:6px;">
      Agent creates a new property with seller details and uploads photos.
    </div>
  </div>

  <div class="card">
    <div class="stat">2</div>
    <div style="font-weight:900;margin-top:6px;">Manage workflow</div>
    <div class="muted" style="margin-top:6px;">
      Track status, owner contact, and follow ups in one dashboard.
    </div>
  </div>

  <div class="card">
    <div class="stat">3</div>
    <div style="font-weight:900;margin-top:6px;">Publish listing</div>
    <div class="muted" style="margin-top:6px;">
      Make property visible on public listings page.
    </div>
  </div>

  <div class="card">
    <div class="stat">4</div>
    <div style="font-weight:900;margin-top:6px;">AI valuation</div>
    <div class="muted" style="margin-top:6px;">
      Agent generates machine learning price prediction.
    </div>
  </div>
</div>

<?php include __DIR__ . "/includes/footer_public.php"; ?>