<?php
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_user'])) {
    $user_id = intval($_COOKIE['remember_user']);

    $stmt = $pdo->prepare("SELECT id, email, full_name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['full_name'];
    } else {
        setcookie("remember_user", "", time() - 3600, "/");
    }
}

$from_checkout = isset($_GET['from']) && $_GET['from'] === 'checkout';

if ($from_checkout) {
    $_SESSION['checkout_progress']['login'] = true;
    $_SESSION['step'] = 'login';

    if (!isset($_SESSION['checkout_progress']['summary'])) {
        $_SESSION['checkout_progress']['summary'] = true;
    }

    if (isset($_SESSION['user_id'])) {
        header("Location: index.php?page=address");
        exit;
    }
}

$login_error = '';
$signup_error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['login'])) {
        $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';

        if ($email && $password) {
            $stmt = $pdo->prepare("SELECT id, full_name, email, password_hash FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];

                setcookie("remember_user", $user['id'], time() + 86400 * 30, "/");

                header("Location: index.php?page=" . ($from_checkout ? "address" : "account"));
                exit;
            } else {
                $login_error = "Invalid email or password.";
            }
        } else {
            $login_error = "Please enter a valid email and password.";
        }
    }

    if (isset($_POST['signup'])) {
        $name = trim($_POST['name']);
        $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';

        if ($name && $email && $password) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $signup_error = "An account with this email already exists.";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, created_at) VALUES (?, ?, ?, NOW())");

                if ($stmt->execute([$name, $email, $password_hash])) {
                    $user_id = $pdo->lastInsertId();

                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_name'] = $name;

                    setcookie("remember_user", $user_id, time() + 86400 * 30, "/");

                    header("Location: index.php?page=" . ($from_checkout ? "address" : "home"));
                    exit;
                } else {
                    $signup_error = "An error occurred during registration.";
                }
            }
        } else {
            $signup_error = "Please fill all fields correctly.";
        }
    }
}
?>

<main class="container">
  <?php if ($from_checkout): ?>
  <section class="steps">
    <div class="step">01. Summary</div>
    <div class="step active">02. Sign in</div>
    <div class="step">03. Address</div>
    <div class="step">04. Shipping</div>
    <div class="step">05. Payment</div>
  </section>
  <?php endif; ?>

  <div class="tab-switch">
    <button id="showLogin" class="tab-btn active" onclick="showTab('login')">Login</button>
    <button id="showSignup" class="tab-btn" onclick="showTab('signup')">Sign Up</button>
  </div>

  <section class="form-section login-section active">
    <h2>Login to Your Account</h2>
    <form method="POST">
      <label for="loginEmail">Email</label>
      <input type="email" id="loginEmail" name="email" required placeholder="Enter your email" />

      <label for="loginPassword">Password</label>
      <input type="password" id="loginPassword" name="password" required placeholder="Enter your password" />

      <?php if (!empty($login_error)) echo "<p class='error-msg'>" . htmlspecialchars($login_error) . "</p>"; ?>

      <button type="submit" name="login">Login</button>
    </form>
  </section>

  <section class="form-section signup-section">
    <h2>Create a New Account</h2>
    <form method="POST">
      <label for="signupName">Full Name</label>
      <input type="text" id="signupName" name="name" required placeholder="Your full name" />

      <label for="signupEmail">Email</label>
      <input type="email" id="signupEmail" name="email" required placeholder="Your email" />

      <label for="signupPassword">Password</label>
      <input type="password" id="signupPassword" name="password" required placeholder="Create a password" />

      <?php if (!empty($signup_error)) echo "<p class='error-msg'>" . htmlspecialchars($signup_error) . "</p>"; ?>

      <button type="submit" name="signup">Sign Up</button>
    </form>
  </section>
</main>

<style>
.error-msg { color:red; font-size:0.9em; margin-top:5px; }
</style>
