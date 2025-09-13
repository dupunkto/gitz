<?php
// Development server. Contains certain routes that
// the production server doesn't need, as they're configured in Apache.

require_once __DIR__ . "/core/core.php";
require_once __DIR__ . '/vendor/autoload.php';

// Explicitly enable error reporting 
// for easier debugging in development.
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);

$requested_file = path_join(__DIR__, $path);

switch(true) {
  case is_file($requested_file) and is_builtin():
    // Serve file as-is. Only applies to the development server,
    // in production this will be handled by Apache directly.

    return false;

  default:
    include __DIR__ . "/index.php";
    exit;
}
