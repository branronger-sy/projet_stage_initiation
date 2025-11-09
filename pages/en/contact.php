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
        $error = "⚠️ Invalid form submission. Please try again.";
    } else {
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name !== '' && $email !== '' && $message !== '') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "⚠️ Please provide a valid email address.";
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

                    $success = "✅ Your message has been sent successfully!";
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } catch (PDOException $e) {
                    error_log("Contact form DB error: " . $e->getMessage());
                    $error = "⚠️ An error occurred while saving your message. Please try again later.";
                }
            }
        } else {
            $error = "⚠️ Please fill in all required fields.";
        }
    }
}
?>

<main class="contact-main">
    <h1>Contact Us</h1>
    <p class="intro">We’re here to answer your questions and support you. Reach out anytime.</p>

    <?php if ($success): ?>
        <p style="color: green; font-weight: bold;"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
    <?php elseif ($error): ?>
        <p style="color: red; font-weight: bold;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <section class="contact-form">
      <form action="" method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
        
        <input type="text" name="name" placeholder="Your Name" required />
        <input type="email" name="email" placeholder="Your Email" required />
        <input type="text" name="subject" placeholder="Subject" />
        <textarea name="message" rows="6" placeholder="Your Message" required></textarea>
        <button type="submit">Send Message</button>
      </form>
    </section>

    <section class="contact-info">
      <h2>Our Contact Information</h2>
      <p><i class="fas fa-map-marker-alt"></i> Coopérative Arjana – Essaouira, Morocco</p>
      <p><i class="fas fa-phone"></i> +212 766 77 28 03</p>
      <p><i class="fas fa-envelope"></i> cooparjana@hotmail.com</p>
    </section>

    <div class="social-links">
      <a href="https://facebook.com/CoopArjana" target="_blank" rel="noopener noreferrer"><i class="fab fa-facebook-f"></i></a>
      <a href="https://instagram.com/CoopArjana" target="_blank" rel="noopener noreferrer"><i class="fab fa-instagram"></i></a>
      <a href="https://wa.me/212766772803" target="_blank" rel="noopener noreferrer"><i class="fab fa-whatsapp"></i></a>
    </div>
</main>
