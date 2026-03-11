# CoopArjana - Natural Argan Oil Cooperative

CoopArjana is an e-commerce platform dedicated to promoting and selling natural Argan oil products from Moroccan cooperatives. The platform provides a seamless shopping experience with multi-language support (English, French, Arabic) and multi-currency options (MAD, USD, EUR).

## Features

- **Multi-language Support**: Fully localized in English, French, and Arabic.
- **Product Management**: Browse products by category, view detailed product information, and manage variants.
- **Shopping Cart**: Add products to cart, update quantities, and proceed to checkout.
- **User Accounts**: Manage personal information, addresses, and view order history.
- **Admin Dashboard**: Comprehensive admin panel for managing products, categories, customers, and orders.
- **Wishlist**: Save favorite products for later.
- **Search**: Advanced search functionality with AJAX-powered suggestions.

## Tech Stack

- **Backend**: PHP (Vanilla)
- **Database**: MySQL (PDO for database interactions)
- **Frontend**: HTML5, CSS3, JavaScript (AJAX, Vanilla JS)
- **Security**: CSRF protection, secure session handling, PDO prepared statements.

## Getting Started

### Prerequisites

- Web server (Apache/Nginx)
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Installation

1.  **Clone the repository**:
    ```bash
    git clone https://github.com/yourusername/cooparjana.git
    cd cooparjana
    ```

2.  **Configure Environment**:
    - Rename `.env.example` to `.env`.
    - Update the database credentials in `.env` (or in `includes/db.php` if you prefer hardcoding for local development).

3.  **Database Setup**:
    - Create a new MySQL database named `cooparjana`.
    - Import the database schema from the provided SQL dump (e.g., `cooparjana.sql`).

4.  **Web Server Setup**:
    - Point your web server's document root to the `public/` directory for better security.
    - Alternatively, you can run it from the root directory if necessary, but ensure `.env` and `includes/` are restricted.

## Project Structure

- `admin/`: Admin panel files.
- `includes/`: Core logic, database connection, and helper functions.
- `pages/`: Page templates for different languages.
- `public/`: Public-facing assets (CSS, JS, images, uploads) and the main entry point (`index.php`).

## Security

- Sensitive configuration is stored in `.env` and excluded from Git.
- Prepared statements are used for all database queries to prevent SQL injection.
- CSRF tokens are implemented for sensitive actions (e.g., checkout).

## License

This project is licensed under the MIT License - see the LICENSE file for details.
