<?php

// Define the application root directory
define('APP_ROOT', __DIR__);

// Autoload dependencies (if using Composer)
require APP_ROOT . '/vendor/autoload.php';

// Include database connection
require_once APP_ROOT . '/config/database.php';

// Include API routes
require_once APP_ROOT . '/routes/api.php';

// The routing logic is now handled in routes/api.php
?>