<?php
// Contains some basic configuration for getting gitz
// up-and-running.

define('USER', 'gitwastaken');
define('HOUSEKEEPING', false);
define('MAX_COMMITS', 5);
define('MAX_REPOS', 7);

define('SCAN_PATH', getenv("SCAN_PATH", local_only: true) ?: '/home/' . USER);
define('NAMESPACES', ['axcelott', 'dupunkto', 'sites', 'meta', 'scttr', 'havas', 'neopub', 'grape-lang', 'nindo', 'unlibrary', 'skylight', 'legacy', 'forks']);

// Disable error logging in prod environments
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);
