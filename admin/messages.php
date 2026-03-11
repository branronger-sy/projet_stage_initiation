<?php
include "includes/auth.php";
include "includes/header.php";
include "includes/db.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
}
$result = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
?>

<link rel="stylesheet" href="styles/admin.css">
<link rel="stylesheet" href="styles/messages.css">
</head>
<body class="admin">

<div class="layout">
  <aside class="sidebar">
    <div class="brand">My Admin</div>
    <nav class="nav">
      <a href="dashboard.php">Dashboard</a>
      <a href="products.php">Products</a>
      <a href="categories.php">Categories</a>
      <a href="orders.php">Orders</a>
      <a href="customers.php">Customers</a>
      <a href="messages.php"class="active">Messages</a>
    </nav>
  </aside>

  <div class="main">
  <header class="topbar">
      <button class="sidebar-toggle">☰</button>
      <div>Welcome, Admin</div>
    </header>
    <main class="content">
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Subject</th>
            <th>Message</th>
            <th>Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><a href="mailto:<?= htmlspecialchars($row['email']) ?>"><?= htmlspecialchars($row['email']) ?></a></td>
                <td><?= htmlspecialchars($row['subject']) ?></td>
                <td><?= nl2br(htmlspecialchars($row['message'])) ?></td>
                <td><?= $row['created_at'] ?></td>
                <td>
                  <form method="POST" onsubmit="return confirm('Are you sure you want to delete this message?');">
                    <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                    <button type="submit" class="btn btn-danger">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" style="text-align:center;">No messages found</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </main>
  </div>
</div>

<?php include "includes/footer.php"; ?>
