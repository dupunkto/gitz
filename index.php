<?php
// Public rendering engine.

require_once __DIR__ . "/src/core.php";
require_once __DIR__ . '/vendor/autoload.php';

$git = new CzProject\GitPhp\Git;

$alnum = '([a-zA-Z0-9_\-\.]+)';
$ns_pattern = "^/~?{$alnum}";

function mountRepo($namespace, $repo_name) {
  global $git;

  $repo_path = path_join(SCAN_PATH, $namespace, $repo_name);

  if(!in_array($namespace, NAMESPACES)) return false;
  if(!\core\repoExists($repo_path)) return false;
  else return $git->open($repo_path);
}

switch(true) {
  case $path == "/":
    $page ??= "listing";
    break;

  case route("@/api/graph/?(\d{4})?@"):
    header('Content-Type: image/svg+xml');
    header('Cache-Control: max-age=86400');

    $year = $params[1] ?? date("Y");
    $color = @$_GET['c'] ?? '7426e2';
    $mode = @$_GET['m'] ?? 'light';

    echo \core\generateGraph($git, $year, $color, $mode);
    exit;

  // Redirect bare namespaces to /
  case route("@{$ns_pattern}/?$@"):
    header("Location: /");
    exit;

  case scope("@{$ns_pattern}/{$alnum}.git/(.*)@"):
    $namespace = $params[1];
    $repo_name = $params[2];

    if($repo = mountRepo($namespace, $repo_name)) {
      header('Content-Type: application/octet-stream');
      $request_path = \core\resolveDumbClone($repo, $params[3]);

      match(true) {
        $request_path == false => http_response_code(403),
        !is_file($request_path) => http_response_code(404),
        default => readfile($request_path),
      };

      exit;
    }

  case scope("@{$ns_pattern}/{$alnum}@"):
    $namespace = $params[1];
    $repo_name = $params[2];

    $repo_url = "/~{$namespace}/{$repo_name}";

    if($repo = mountRepo($namespace, $repo_name)) {
      switch(true) {
        case route("@^/?$@"):
          $page ??= "summary";
          break;
  
        case route("@/log/(.*)$@"):
          $page ??= "log";
          $branch = $params[1];
          break;

        case route("@/commit/(.*)$@"):
          $page ??= "commit";
          $hash = $params[1];
          break;

        case route("@/tree/{$alnum}(.*)$@"):
          $page ??= "tree";

          if(\core\isCommitHash($params[1])) {
            $hash = $params[1];
          } else {
            $branch = $params[1];
            $hash = \core\getLatestCommits($repo, $branch, 1)[0]['hash'];
          }

          $request_path = trim($params[2], "/");
          break;

        case route("@/blob/{$alnum}(.*)$@"):
          $page ??= "blob";

          if(\core\isCommitHash($params[1])) {
            $hash = $params[1];
          } else {
            $branch = $params[1];
            $hash = \core\getLatestCommits($repo, $branch, 1)[0]['hash'];
          }

          $request_path = trim($params[2], "/");
          break;
      }

      if(isset($page)) break;
    }

  default:
    http_response_code(404);
    $page = "404";
    break;
}

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
    <?php if(isset($repo) && $repo != false) include __DIR__ . "/gui/header.php" ?>
    <?php if(isset($page) && $page != false) include __DIR__ . "/gui/$page.php" ?>
  </body>
</html>
