<?php
// Basic configuration for getting Gitz up-and-running.

define('USER', 'gitwastaken');
define('HOUSEKEEPING', false);
define('MAX_COMMITS', 5);
define('MAX_REPOS', 7);
define('UNLISTED', true);

define('SITE_TITLE', getenv("SITE_TITLE") ?: "{du}punkto git hosting");

define('SSH_BASE', getenv("SSH_BASE") ?: "dupunkto.org");
define('HTTP_BASE', rtrim(getenv("HTTP_BASE") ?: "https://git.dupunkto.org", "/"));

define('GITZ_URL', rtrim(getenv("GITZ_URL") ?: "https://git.dupunkto.org", "/"));
define('BUGZ_URL', rtrim(getenv("BUGZ_URL") ?: "https://bugs.dupunkto.org", "/"));

define('SCAN_PATH', getenv("SCAN_PATH", local_only: true) ?: '/home/' . USER);
define('NAMESPACES', ['axcelott', 'dupunkto', 'sites', 'meta', 'neopub', 'grape-lang', 'nindo', 'skylight', 'unlibrary', 'legacy', 'forks']);
define('LEGACY', ['neopub', 'grape-lang', 'nindo','skylight', 'unlibrary', 'legacy']);

// Disable error logging in production environments
ini_set('display_errors', "0");
ini_set('display_startup_errors', "0");
error_reporting(0);
