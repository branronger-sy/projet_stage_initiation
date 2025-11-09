<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$success = '';
$error   = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $error = "⚠️ Soumission du formulaire invalide. Veuillez réessayer.";
    } else {
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name !== '' && $email !== '' && $message !== '') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "⚠️ Veuillez fournir une adresse e-mail valide.";
            } else {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO contact_messages (name, email, subject, message, created_at) 
                        VALUES (:name, :email, :subject, :message, NOW())
                    ");
                    $stmt->bindValue(':name', $name, PDO::PARAM_STR);
                    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
                    $stmt->bindValue(':subject', $subject, PDO::PARAM_STR);
                    $stmt->bindValue(':message', $message, PDO::PARAM_STR);
                    $stmt->execute();

                    $success = "✅ Votre message a été envoyé avec succès !";
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } catch (PDOException $e) {
                    error_log("Erreur DB formulaire de contact : " . $e->getMessage());
                    $error = "⚠️ Une erreur s’est produite lors de l’enregistrement de votre message. Veuillez réessayer plus tard.";
                }
            }
        } else {
            $error = "⚠️ Veuillez remplir tous les champs obligatoires.";
        }
    }
}
?>

<main class="contact-main">
    <h1>Contactez-nous</h1>
    <p class="intro">Nous sommes là pour répondre à vos questions et vous accompagner. N’hésitez pas à nous écrire.</p>

    <?php if ($success): ?>
        <p style="color: green; font-weight: bold;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
    <?php elseif ($error): ?>
        <p style="color: red; font-weight: bold;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <section class="contact-form">
      <form action="" method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
        
        <input type="text" name="name" placeholder="Votre Nom" required />
        <input type="email" name="email" placeholder="Votre Email" required />
        <input type="text" name="subject" placeholder="Sujet" />
        <textarea name="message" rows="6" placeholder="Votre Message" required></textarea>
        <button type="submit">Envoyer le message</button>
      </form>
    </section>

    <section class="contact-info">
      <h2>Nos Coordonnées</h2>
      <p><i class="fas fa-map-marker-alt"></i> Coopérative Arjana – Essaouira, Maroc</p>
      <p><i class="fas fa-phone"></i> +212 766 77 28 03</p>
      <p><i class="fas fa-envelope"></i> cooparjana@hotmail.com</p>
    </section>

    <div class="social-links">
      <a href="https://facebook.com/CoopArjana" target="_blank" rel="noopener noreferrer"><i class="fab fa-facebook-f"></i></a>
      <a href="https://instagram.com/CoopArjana" target="_blank" rel="noopener noreferrer"><i class="fab fa-instagram"></i></a>
      <a href="https://wa.me/212766772803" target="_blank" rel="noopener noreferrer"><i class="fab fa-whatsapp"></i></a>
    </div>
</main>
