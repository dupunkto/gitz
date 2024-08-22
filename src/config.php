<?php
// Contains some basic configuration for getting gitz
// up-and-running.

define('SCAN_PATH', getenv("SCAN_PATH", local_only: true) ?: "/home/robinb");
define('NAMESPACES', ['axcelott', 'dupunkto', 'forks', 'meta']);
