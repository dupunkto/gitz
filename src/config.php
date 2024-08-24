<?php
// Contains some basic configuration for getting gitz
// up-and-running.

define('HOUSEKEEPING', false);
define('MAX', 7);

define('SCAN_PATH', getenv("SCAN_PATH", local_only: true) ?: "/home/robinb");
define('NAMESPACES', ['axcelott', 'dupunkto', 'forks', 'neopub', 'nindo', 'grape-lang', 'legacy', 'sites', 'meta']);
