<?php
// Basic configuration for getting Gitz up-and-running.

define('USER', 'gitwastaken');
define('HOUSEKEEPING', false);
define('MAX_COMMITS', 5);
define('MAX_REPOS', 7);
define('UNLISTED', true);

define('SCAN_PATH', getenv("SCAN_PATH", local_only: true) ?: '/home/' . USER);
define('NAMESPACES', ['axcelott', 'dupunkto', 'sites', 'meta', 'scttr', 'neopub', 'grape-lang', 'nindo', 'skylight', 'unlibrary', 'legacy', 'forks']);
define('LEGACY', ['neopub', 'grape-lang', 'nindo','skylight', 'unlibrary', 'legacy']);

// Disable error logging in prod environments
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);
