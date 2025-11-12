<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login&from=checkout");
    exit;
}

$_SESSION['step'] = 'address';

$user_id = (int) $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT id, name FROM countries ORDER BY name ASC");
    $stmt->execute();
    $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT 
            s.full_name AS s_full_name, s.address AS s_address, s.city AS s_city, s.zip_code AS s_zip, s.country_id AS s_country, s.phone AS s_phone,
            b.full_name AS b_full_name, b.address AS b_address, b.city AS b_city, b.zip_code AS b_zip, b.country_id AS b_country, b.phone AS b_phone
        FROM users u
        LEFT JOIN shipping_addresses s ON s.user_id = u.id
        LEFT JOIN billing_addresses b ON b.user_id = u.id
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $address = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$hasAddress = !empty($address) && !empty($address['s_full_name']);
$_SESSION['checkout_progress']['address'] = true;
$_SESSION['step'] = 'address';
?>

<main class="container">
    <section class="steps">
        <div class="step">01. Summary</div>
        <div class="step">02. Sign in</div>
        <div class="step active">03. Address</div>
        <div class="step">04. Shipping</div>
        <div class="step">05. Payment</div>
    </section>

    <section class="address-form">

        <?php if ($hasAddress): ?>
        <div id="address_view">
            <h2>Your Shipping Address</h2>
            <p><strong><?= $address['s_full_name'] ?></strong></p>
            <p><?= $address['s_address'] ?>, <?= $address['s_city'] ?> <?= $address['s_zip'] ?></p>
            <p><?= $address['s_phone'] ?></p>

            <h2>Your Billing Address</h2>
            <p><strong><?= $address['b_full_name'] ?></strong></p>
            <p><?= $address['b_address'] ?>, <?= $address['b_city'] ?> <?= $address['b_zip'] ?></p>
            <p><?= $address['b_phone'] ?></p>

            <div class="actions">
                <button id="changeAddressBtn" class="btn" type="button">Change Address</button>
                <button type="button" id="continueShippingBtn" class="btn btn-primary">Continue to Shipping</button>
            </div>
        </div>
        <?php endif; ?>

        <div id="address_form" style="<?= $hasAddress ? 'display:none;' : '' ?>">
            <h2><?= $hasAddress ? 'Edit Address' : 'Enter Address' ?></h2>
            <form id="addressForm" method="post">
                
                <label for="shipping_fullName">Full Name</label>
                <input id="shipping_fullName" type="text" name="shipping_fullName" required maxlength="150" value="<?= $address['s_full_name'] ?? '' ?>">

                <label for="shipping_address">Address</label>
                <input id="shipping_address" type="text" name="shipping_address" required maxlength="255" value="<?= $address['s_address'] ?? '' ?>">

                <label for="shipping_city">City</label>
                <input id="shipping_city" type="text" name="shipping_city" required maxlength="100" value="<?= $address['s_city'] ?? '' ?>">

                <label for="shipping_zip">Zip Code</label>
                <input id="shipping_zip" type="text" name="shipping_zip" required maxlength="20" value="<?= $address['s_zip'] ?? '' ?>">

                <label for="shipping_country">Country</label>
                <select id="shipping_country" name="shipping_country" required>
                    <option value="" disabled <?= empty($address['s_country']) ? 'selected' : '' ?>>Choose Country</option>
                    <?php foreach ($countries as $country): ?>
                        <option value="<?= (int)$country['id'] ?>" <?= (isset($address['s_country']) && (int)$address['s_country'] === (int)$country['id']) ? 'selected' : '' ?>>
                            <?= $country['name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="shipping_phone">Phone</label>
                <input id="shipping_phone" type="text" name="shipping_phone" required maxlength="20" value="<?= $address['s_phone'] ?? '' ?>">

                <hr>
                <h2>Billing Address</h2>
                <label>
                    <input type="checkbox" name="same_as_shipping" id="same_as_shipping" onclick="toggleBilling()">
                    Same as shipping
                </label>

                <div id="billing_fields">
                    <label for="billing_fullName">Full Name</label>
                    <input id="billing_fullName" type="text" name="billing_fullName" maxlength="150" value="<?= $address['b_full_name'] ?? '' ?>">

                    <label for="billing_address">Address</label>
                    <input id="billing_address" type="text" name="billing_address" maxlength="255" value="<?= $address['b_address'] ?? '' ?>">

                    <label for="billing_city">City</label>
                    <input id="billing_city" type="text" name="billing_city" maxlength="100" value="<?= $address['b_city'] ?? '' ?>">

                    <label for="billing_zip">Zip Code</label>
                    <input id="billing_zip" type="text" name="billing_zip" maxlength="20" value="<?= $address['b_zip'] ?? '' ?>">

                    <label for="billing_country">Country</label>
                    <select id="billing_country" name="billing_country">
                        <option value="" disabled <?= empty($address['b_country']) ? 'selected' : '' ?>>Choose Country</option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?= (int)$country['id'] ?>" <?= (isset($address['b_country']) && (int)$address['b_country'] === (int)$country['id']) ? 'selected' : '' ?>>
                                <?= $country['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="billing_phone">Phone</label>
                    <input id="billing_phone" type="text" name="billing_phone" maxlength="20" value="<?= $address['b_phone'] ?? '' ?>">
                </div>

                <button type="submit"><?= $hasAddress ? 'Update Address' : 'Continue to Shipping' ?></button>
            </form>
        </div>
    </section>
</main>

<script>
function toggleBilling() {
    const billingFields = document.getElementById('billing_fields');
    billingFields.style.display = document.getElementById('same_as_shipping').checked ? 'none' : 'block';
}

window.onload = function() {
    toggleBilling();
};

document.addEventListener("DOMContentLoaded", function() {
    const changeBtn = document.getElementById("changeAddressBtn");
    const formDiv = document.getElementById("address_form");
    const viewDiv = document.getElementById("address_view");
    const form = document.getElementById("addressForm");
    const continueBtn = document.getElementById("continueShippingBtn");

    if (changeBtn) {
        changeBtn.addEventListener("click", function() {
            viewDiv.style.display = "none";
            formDiv.style.display = "block";
        });
    }

    if (continueBtn) {
        continueBtn.addEventListener("click", function() {
            window.location.href = "index.php?page=shipping";
        });
    }

    form.addEventListener("submit", async function(e) {
        e.preventDefault();
        const formData = new FormData(form);

        const response = await fetch("../includes/save_address.php", {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        if (result.status === "success") {
            window.location.href = result.redirect;
        } else {
            alert(result.message || "Error while saving address");
        }
    });
});
</script>
