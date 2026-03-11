<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT u.full_name, u.email, 
           s.full_name AS s_full_name, s.address AS s_address, s.city AS s_city, s.zip_code AS s_zip_code, s.phone AS s_phone,
           b.full_name AS b_full_name, b.address AS b_address, b.city AS b_city, b.zip_code AS b_zip_code, b.phone AS b_phone
    FROM users u
    LEFT JOIN shipping_addresses s ON s.user_id = u.id
    LEFT JOIN billing_addresses b ON b.user_id = u.id
    WHERE u.id = :id
");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<p class='error'>User not found.</p>";
    exit;
}
$hasShipping = !empty($user['s_full_name']) || !empty($user['s_address']) || !empty($user['s_city']) || !empty($user['s_zip_code']) || !empty($user['s_phone']);
$hasBilling = !empty($user['b_full_name']) || !empty($user['b_address']) || !empty($user['b_city']) || !empty($user['b_zip_code']) || !empty($user['b_phone']);
?>
<main class="personal-info-page">
  <h1>My Personal Information</h1>

  <form id="infoForm" method="post" action="update_info.php" autocomplete="off">
    <div class="info-item">
      <label>Full Name:</label>
      <p class="view"><?php echo htmlspecialchars($user['full_name']); ?></p>
      <input class="edit hidden" type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>">
    </div>
    <div class="info-item">
      <label>Email:</label>
      <p class="view"><?php echo htmlspecialchars($user['email']); ?></p>
      <input class="edit hidden" type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
    </div>
    <div class="info-item">
      <label>Full Name:</label>
      <p class="view"><?php echo htmlspecialchars($user['full_name']); ?></p>
      <input class="edit hidden" type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>">
    </div>
    <div class="info-item hidden password-section">
      <label>New Password:</label>
      <input class="edit" type="password" name="new_password" minlength="8" autocomplete="new-password">
    </div>
    <?php if ($hasShipping): ?>
    <h2>Shipping Address</h2>
    <div class="info-item">
      <label>Full Name:</label>
      <p class="view"><?php echo htmlspecialchars($user['s_full_name']); ?></p>
      <input class="edit hidden" type="text" name="s_full_name" value="<?php echo htmlspecialchars($user['s_full_name']); ?>">
    </div>
    <div class="info-item">
      <label>Address:</label>
      <p class="view"><?php echo htmlspecialchars($user['s_address']); ?></p>
      <input class="edit hidden" type="text" name="s_address" value="<?php echo htmlspecialchars($user['s_address']); ?>">
    </div>
    <div class="info-item">
      <label>City:</label>
      <p class="view"><?php echo htmlspecialchars($user['s_city']); ?></p>
      <input class="edit hidden" type="text" name="s_city" value="<?php echo htmlspecialchars($user['s_city']); ?>">
    </div>
    <div class="info-item">
      <label>Zip Code:</label>
      <p class="view"><?php echo htmlspecialchars($user['s_zip_code']); ?></p>
      <input class="edit hidden" type="text" name="s_zip_code" value="<?php echo htmlspecialchars($user['s_zip_code']); ?>">
    </div>
    <div class="info-item">
      <label>Phone:</label>
      <p class="view"><?php echo htmlspecialchars($user['s_phone']); ?></p>
      <input class="edit hidden" type="text" name="s_phone" value="<?php echo htmlspecialchars($user['s_phone']); ?>">
    </div>
    <?php endif; ?>
    <?php if ($hasBilling): ?>
    <h2>Billing Address</h2>
    <div class="info-item">
      <label>Full Name:</label>
      <p class="view"><?php echo htmlspecialchars($user['b_full_name']); ?></p>
      <input class="edit hidden" type="text" name="b_full_name" value="<?php echo htmlspecialchars($user['b_full_name']); ?>">
    </div>
    <div class="info-item">
      <label>Address:</label>
      <p class="view"><?php echo htmlspecialchars($user['b_address']); ?></p>
      <input class="edit hidden" type="text" name="b_address" value="<?php echo htmlspecialchars($user['b_address']); ?>">
    </div>
    <div class="info-item">
      <label>City:</label>
      <p class="view"><?php echo htmlspecialchars($user['b_city']); ?></p>
      <input class="edit hidden" type="text" name="b_city" value="<?php echo htmlspecialchars($user['b_city']); ?>">
    </div>
    <div class="info-item">
      <label>Zip Code:</label>
      <p class="view"><?php echo htmlspecialchars($user['b_zip_code']); ?></p>
      <input class="edit hidden" type="text" name="b_zip_code" value="<?php echo htmlspecialchars($user['b_zip_code']); ?>">
    </div>
    <div class="info-item">
      <label>Phone:</label>
      <p class="view"><?php echo htmlspecialchars($user['b_phone']); ?></p>
      <input class="edit hidden" type="text" name="b_phone" value="<?php echo htmlspecialchars($user['b_phone']); ?>">
    </div>
    <?php endif; ?>
    <div class="info-actions">
      <button type="button" id="editBtn">Edit Info</button>
      <button type="button" id="passwordBtn" class="hidden">Change Password</button>
      <button type="submit" id="saveBtn" class="hidden">Save</button>
      <button type="button" id="cancelBtn" class="hidden">Cancel</button>
    </div>
  </form>

  <p id="message" class="message"></p>
</main>
