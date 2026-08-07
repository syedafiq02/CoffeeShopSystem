<?php
// Route guards. Call these at the very top of a protected page,
// after requiring config/db.php + includes/functions.php.

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('warning', 'Please log in to continue.');
        redirect('auth/login.php');
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        set_flash('danger', 'You do not have permission to access that page.');
        redirect('public/index.php');
    }
}
