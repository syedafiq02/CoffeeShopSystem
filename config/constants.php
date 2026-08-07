<?php
// Site-wide constants used across the whole project.
// Adjust BASE_URL if the project folder name in htdocs is ever renamed.

// All date()/DateTime output across the system uses GMT+8.
date_default_timezone_set('Asia/Kuala_Lumpur');

define('SITE_NAME', 'Nōva Brew');
define('BASE_URL', 'http://localhost/coffee-shop-system/');
define('CURRENCY_SYMBOL', 'RM');
define('DELIVERY_FEE', 5.00);

define('UPLOAD_PRODUCTS_DIR', __DIR__ . '/../uploads/products/');
define('UPLOAD_GALLERY_DIR', __DIR__ . '/../uploads/gallery/');
define('UPLOAD_PRODUCTS_URL', BASE_URL . 'uploads/products/');
define('UPLOAD_GALLERY_URL', BASE_URL . 'uploads/gallery/');
