<?php
// Development server. Contains certain routes that
// the production server doesn't need, as they're configured in Apache.

require_once __DIR__ . "/src/core.php";
require_once __DIR__ . '/vendor/autoload.php';

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