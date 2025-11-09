<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$lang = $_SESSION['lang'] ?? 'en';

$texts = [
    'en' => [
        'home' => 'Home',
        'products' => 'Products',
        'benefits' => 'Benefits',
        'about' => 'About Us',
        'contact' => 'Contact',
        'terms' => 'Terms',
        'search_placeholder' => 'Search...',
        'your_cart' => 'Your Cart',
        'total' => 'Total:',
        'checkout' => 'Checkout',
        'wishlist' => 'Wishlist',
        'my_account' => 'My Account',

        'info' => 'Informations',
        'our_stores' => 'Our Stores',
        'delivery' => 'Delivery',
        'terms_conditions' => 'Terms and Conditions',
        'account' => 'My Account',
        'my_orders' => 'My Orders',
        'personal_info' => 'Personal Info',
        'contact_title' => 'Contact',
        'rights' => 'All rights reserved.'
    ],

    'fr' => [
        'home' => 'Accueil',
        'products' => 'Produits',
        'benefits' => 'Bienfaits',
        'about' => 'À propos',
        'contact' => 'Contact',
        'terms' => 'Conditions',
        'search_placeholder' => 'Rechercher...',
        'your_cart' => 'Votre Panier',
        'total' => 'Total :',
        'checkout' => 'Commander',
        'wishlist' => 'Favoris',
        'my_account' => 'Mon Compte',

        'info' => 'Informations',
        'our_stores' => 'Nos Magasins',
        'delivery' => 'Livraison',
        'terms_conditions' => 'Conditions Générales',
        'account' => 'Mon Compte',
        'my_orders' => 'Mes Commandes',
        'personal_info' => 'Informations Personnelles',
        'contact_title' => 'Contact',
        'rights' => 'Tous droits réservés.'
    ],
];
