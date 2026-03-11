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
        setcookie("remember_user", "", time() - 3600, "/", "", true, true);
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
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];

                setcookie("remember_user", $user['id'], [
                    'expires' => time() + 86400 * 30,
                    'path' => '/',
                    'secure' => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);

                header("Location: index.php?page=" . ($from_checkout ? "address" : "account"));
                exit;
            } else {
                $login_error = "Email ou mot de passe invalide.";
            }
        } else {
            $login_error = "Veuillez entrer un email et un mot de passe valides.";
        }
    }

    if (isset($_POST['signup'])) {
        $name = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
        $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';

        if ($name && $email && $password) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $signup_error = "Un compte avec cet email existe déjà.";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, created_at) VALUES (?, ?, ?, NOW())");

                if ($stmt->execute([$name, $email, $password_hash])) {
                    $user_id = $pdo->lastInsertId();

                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_name'] = $name;

                    setcookie("remember_user", $user_id, [
                        'expires' => time() + 86400 * 30,
                        'path' => '/',
                        'secure' => isset($_SERVER['HTTPS']),
                        'httponly' => true,
                        'samesite' => 'Strict'
                    ]);

                    header("Location: index.php?page=" . ($from_checkout ? "address" : "home"));
                    exit;
                } else {
                    $signup_error = "Une erreur est survenue lors de l'inscription.";
                }
            }
        } else {
            $signup_error = "Veuillez remplir correctement tous les champs.";
        }
    }
}
?>

<main class="container">

  <?php if ($from_checkout): ?>
  <section class="steps">
    <div class="step">01. Résumé</div>
    <div class="step active">02. Connexion</div>
    <div class="step">03. Adresse</div>
    <div class="step">04. Livraison</div>
    <div class="step">05. Paiement</div>
  </section>
  <?php endif; ?>

  <div class="tab-switch">
    <button id="showLogin" class="tab-btn active" onclick="showTab('login')">Connexion</button>
    <button id="showSignup" class="tab-btn" onclick="showTab('signup')">Créer un compte</button>
  </div>

  <section class="form-section login-section active">
    <h2>Connectez-vous à votre compte</h2>
    <form method="POST">
      <label for="loginEmail">Email</label>
      <input type="email" id="loginEmail" name="email" required placeholder="Entrez votre email" />

      <label for="loginPassword">Mot de passe</label>
      <input type="password" id="loginPassword" name="password" required placeholder="Entrez votre mot de passe" />

      <?php if (!empty($login_error)) echo "<p class='error-msg'>" . htmlspecialchars($login_error) . "</p>"; ?>

      <button type="submit" name="login">Connexion</button>
    </form>
  </section>

  <section class="form-section signup-section">
    <h2>Créer un nouveau compte</h2>
    <form method="POST">
    <label for="signupName">Nom complet</label>
      <input type="text" id="signupName" name="name" required placeholder="Votre nom complet" />

      <label for="signupEmail">Email</label>
      <input type="email" id="signupEmail" name="email" required placeholder="Votre email" />

      <label for="signupPassword">Mot de passe</label>
      <input type="password" id="signupPassword" name="password" required placeholder="Créez un mot de passe" />

      <?php if (!empty($signup_error)) echo "<p class='error-msg'>" . htmlspecialchars($signup_error) . "</p>"; ?>

      <button type="submit" name="signup">S'inscrire</button>
    </form>
  </section>

</main>

<style>
.error-msg { color:red; font-size:0.9em; margin-top:5px; }
</style>
