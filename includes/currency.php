<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_httponly' => true, 
        'cookie_secure'   => isset($_SERVER['HTTPS']),
        'use_strict_mode' => true, 
    ]);
}

const ALLOWED_CURRENCIES = ['MAD', 'USD', 'EUR'];

const RATES = [
    'MAD' => 1.0,
    'USD' => 0.10,
    'EUR' => 0.093,
];

if (
    empty($_SESSION['currency']) ||
    !in_array($_SESSION['currency'], ALLOWED_CURRENCIES, true)
) {
    $_SESSION['currency'] = 'MAD';
}

if (isset($_GET['currency'])) {
    $currency = strtoupper(trim($_GET['currency']));

    if (in_array($currency, ALLOWED_CURRENCIES, true)) {
        $_SESSION['currency'] = $currency;
    }
}

$selectedCurrency = $_SESSION['currency'];

/**
 *
 * @param float|int 
 * @return float 
 */
function convertPrice(float|int $priceMAD): float
{
    $currency = $_SESSION['currency'] ?? 'MAD';
    $rate = RATES[$currency] ?? 1.0;

    return round((float) $priceMAD * $rate, 2);
}
