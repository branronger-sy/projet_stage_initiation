<?php
session_start();
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
  $_SESSION['t']=time();
    header("Location: dashboard.php");
    exit();
}

include "includes/header.php";
include "includes/db.php";
$failed_attempts = $_SESSION['failed_attempts'] ?? 0;
$max_attempts = 5; 
?>
<link rel="stylesheet" href="styles/login.css">
</head>
<body class="admin">

<div class="login-container">
  <h2>Admin Login</h2>

  <?php if (isset($_SESSION['login_error'])): ?>
    <div class="error">
        <?= htmlspecialchars($_SESSION['login_error'], ENT_QUOTES, 'UTF-8'); ?>
        <br>
        <?php if ($failed_attempts > 0): ?>
            ⚠️ Attempts: <?= (int)$failed_attempts; ?> / <?= (int)$max_attempts; ?>
        <?php endif; ?>
    </div>
    <?php unset($_SESSION['login_error']); ?>
  <?php endif; ?>

  <form action="includes/process_login.php" method="POST">
    <label for="username">Username</label>
    <input type="text" name="username" id="username" required>

    <label for="password">Password</label>
    <input type="password" name="password" id="password" required>

    <button type="submit">Login</button>
  </form>
</div>

<?php
if (isset($_SESSION['lockout_seconds'])): ?>
  <script>
    let lockSeconds = <?= (int)$_SESSION['lockout_seconds']; ?>;
  </script>
  <?php unset($_SESSION['lockout_seconds']); ?>
<?php endif; ?>
<script>
if (typeof lockSeconds !== "undefined") {
    const errorDiv = document.querySelector(".error");
    let timeLeft = lockSeconds;

    function formatTime(sec) {
        let m = Math.floor(sec / 60);
        let s = sec % 60;
        return (m > 0 ? m + "m " : "") + s + "s";
    }
    if (errorDiv) {
        errorDiv.textContent = "Trop de tentatives échouées. Réessayez après " + formatTime(timeLeft);
    }

    let interval = setInterval(() => {
        if (timeLeft <= 0) {
            clearInterval(interval);
            location.reload();
        } else {
            timeLeft--;
            if (errorDiv) {
                errorDiv.textContent = "Trop de tentatives échouées. Réessayez après " + formatTime(timeLeft);
            }
        }
    }, 1000);
}
</script>

<?php include "includes/footer.php"; ?>
