<?php
// Public rendering engine.

require_once __DIR__ . "/src/core.php";
require_once __DIR__ . '/vendor/autoload.php';

$git = new CzProject\GitPhp\Git;

$alnum = '([a-zA-Z0-9_\-\.]+)';
$ns_pattern = "^/~?{$alnum}";
$repo_pattern = "{$ns_pattern}/{$alnum}";

switch(true) {
  case $path == "/":
    $page ??= "listing";
    break;

  case route("@{$repo_pattern}.git/(.*)@"):
    $page ??= "dumb";
    $query = $params[3];

  case route("@{$repo_pattern}$@"):
    $page ??= "summary";

    $namespace = $params[1];
    $repo_name = $params[2];

    $repo_path = path_join(SCAN_PATH, $namespace, $repo_name);

    if(in_array($namespace, NAMESPACES) and \core\repoExists($repo_path)) {
      $repo = $git->open($repo_path);
      break;
    }

  // Redirect bare namespaces to /
  case route("@{$ns_pattern}/?$@"):
    header("Location: /");
    exit;

  default:
    http_response_code(404);
    $page = "404";

    break;
}

if($page == "dumb") \core\handleDumbClone($repo, $query);

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>{du}punkto git repositories</title>
    <style>
      <?php include __DIR__ . "/gui/main.css" ?>
    </style>
  </head>
  <body>
    <?php if(isset($repo)) include __DIR__ . "/gui/header.php" ?>
    <?php include __DIR__ . "/gui/$page.php" ?>
  </body>
</html>
