<?php
// Basic configuration for getting Gitz up-and-running.

define('SCAN_PATH', getenv("SCAN_PATH", local_only: true) ?: "/home/" . USER);

define('USER', 'gitwastaken');
define('HOUSEKEEPING', false);
define('MAX_COMMITS', 5);
define('MAX_REPOS', 7);
define('UNLISTED', true);

define('TITLE', getenv("TITLE") ?: "{du}punkto git hosting");
define('CUSTOM_CSS', getenv("CUSTOM_CSS") ?: false);

define('LINK_COLOR_LIGHT', getenv("LINK_COLOR_LIGHT") ?: "#6a17e1");
define('LINK_COLOR_DARK', getenv("LINK_COLOR_DARK") ?: "#c197fc");
define('LINK_UNDERLINE_LIGHT', getenv("LINK_UNDERLINE_LIGHT") ?: "#6a17e1");
define('LINK_UNDERLINE_DARK', getenv("LINK_UNDERLINE_DARK") ?: "#c197fc");
define('GRAPH_COLOR', strip_prefix(getenv("GRAPH_COLOR") ?: "#7426e2", "#"));
define('CONTRIBUTION_COLOR', getenv("CONTRIBUTION_COLOR") ?: "#7426e2");

define('SSH_BASE', getenv("SSH_BASE") ?: "dupunkto.org");
define('HTTP_BASE', rtrim(getenv("HTTP_BASE") ?: "https://git.dupunkto.org", "/"));

define('GITZ_URL', rtrim(getenv("GITZ_URL") ?: "https://git.dupunkto.org", "/"));
define('BUGZ_URL', rtrim(getenv("BUGZ_URL") ?: "https://bugs.dupunkto.org", "/"));

define('NAMESPACES', arr_explode(getenv("NAMESPACES") ?: ""));
define('SHARED_NAMESPACES', arr_explode(getenv("SHARED_NAMESPACES") ?: ""));
define('HIDDEN_NAMESPACES', arr_explode(getenv("HIDDEN_NAMESPACES") ?: ""));
define('LEGACY_NAMESPACES', arr_explode(getenv("LEGACY_NAMESPACES") ?: ""));

// Disable error logging in production environments
ini_set('display_errors', "0");
ini_set('display_startup_errors', "0");
error_reporting(0);
