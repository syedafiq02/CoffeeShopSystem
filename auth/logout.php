<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$_SESSION = [];
session_destroy();

session_start();
set_flash('success', 'You have been logged out.');
redirect('public/index.php');
