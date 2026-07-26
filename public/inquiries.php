<?php
require_once __DIR__ . "/../backend/config/app.php";
require_once __DIR__ . "/../backend/config/db.php";

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$agent_id = (int)($_SESSION['user_id'] ?? 0);
if ($agent_id <= 0) {
  redirect(BASE_URL . "/login.php");
}

$stmt = $pdo->prepare("
  SELECT i.*, p.address, p.postcode
  FROM inquiries i
  JOIN properties p ON p.id = i.property_id
  WHERE i.agent_id = ?
  ORDER BY i.created_at DESC
");
$stmt->execute([$agent_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Enquiries</h2>

<?php if (empty($items)): ?>
  <p>No enquiries yet.</p>
<?php else: ?>
  <table border="1" cellpadding="8" cellspacing="0">
    <tr>
      <th>Date</th>
      <th>Property</th>
      <th>From</th>
      <th>Email</th>
      <th>Phone</th>
      <th>Message</th>
      <th>Status</th>
    </tr>
    <?php foreach ($items as $row): ?>
      <tr>
        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
        <td>
          <?php echo htmlspecialchars(($row['address'] ?? '') . " " . ($row['postcode'] ?? '')); ?>
        </td>
        <td><?php echo htmlspecialchars($row['name']); ?></td>
        <td><?php echo htmlspecialchars($row['email']); ?></td>
        <td><?php echo htmlspecialchars($row['phone'] ?? ''); ?></td>
        <td><?php echo nl2br(htmlspecialchars($row['message'])); ?></td>
        <td><?php echo htmlspecialchars($row['status']); ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>