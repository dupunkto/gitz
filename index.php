<?php
// Public rendering engine.

require_once __DIR__ . "/core/core.php";
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . "/extra/markdown.php";

$git = new CzProject\GitPhp\Git;

$alnum_pattern = '([a-zA-Z0-9_\-\.]+)';
$ns_pattern = "^/~?{$alnum_pattern}";

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

  case $path == '/robots.txt' and UNLISTED:
    header("Content-Type: text/plain");
    echo "User-agent: *\n";
    echo "Disallow: /\n";
    exit;

  case route("/api/graph/?(\d{4})?"):
    header('Content-Type: image/svg+xml');
    header('Cache-Control: max-age=86400');

    $year = $params[1] ?? date("Y");
    $color = @$_GET['c'] ?? '7426e2';
    $mode = @$_GET['m'] ?? 'light';
    $author = @$_GET['u'];

    echo \core\generateGraph($git, $year, $color, $mode, author: $author);
    exit;

  // Redirect bare namespaces to /
  case route("{$ns_pattern}/?$"):
    header("Location: /");
    exit;

  case route("{$ns_pattern}/{$alnum_pattern}\\.git/?$"):
    http_response_code(301);
    header("Location: /~{$params[1]}/{$params[2]}");
    exit;

  case scope("{$ns_pattern}/{$alnum_pattern}.git/(.*)"):
    $namespace = $params[1];
    $repo_name = $params[2];
    $git_path = $params[3];

    if($repo = mountRepo($namespace, $repo_name)) {
      if($git_path == 'info/refs' && @$_GET['service'] == 'git-upload-pack') {
        \core\serveSmartInfoRefs($repo);
        exit;
      }

      if($git_path == 'git-upload-pack' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        \core\serveSmartUploadPack($repo);
        exit;
      }

      header('Content-Type: application/octet-stream');
      $request_path = \core\resolveDumbClone($repo, $git_path);

      match(true) {
        $request_path == false => http_response_code(403),
        !is_file($request_path) => http_response_code(404),
        default => readfile($request_path),
      };

      exit;
    }

  case scope("{$ns_pattern}/{$alnum_pattern}"):
    $namespace = $params[1];
    $repo_name = $params[2];

    $repo_url = "/~{$namespace}/{$repo_name}";

    if($repo = mountRepo($namespace, $repo_name)) {
      switch(true) {
        case route("^/?$"):
          $page ??= "summary";
          break;
  
        case route("/log/(.*)$"):
          $page ??= "log";
          $branch = $params[1];
          break;

        case route("/commit/(.*)$"):
          $page ??= "commit";
          $hash = $params[1];
          break;

        case route("/(tree|blob|raw)/{$alnum_pattern}(.*)$"):
          $page ??= $params[1];
          $ref = $params[2];

          if(\core\isCommitHash($ref)) {
            $hash = $ref;
          }
          else {
            $branch = $ref;
            $hash = \core\getLatestHash($repo, $branch);
          }


          $request_path = trim($params[3], "/");
          break;
      }

      if(isset($page)) break;
    }

  default:
    http_response_code(404);
    $page = "404";
    break;
}

if($page == "raw") {
  $blob = \core\getBlob($repo, $request_path, $hash);
  $mime = \core\detectMimeType($repo, $request_path, $hash);

  header("Content-Type: $mime"); echo $blob;
  exit;
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title><?= SITE_TITLE ?></title>
    <?php if(UNLISTED): ?>
      <meta name="robots" content="noindex, nofollow" />
    <?php endif ?>
    <style>
      <?php include __DIR__ . "/partials/main.css" ?>
    </style>
  </head>
  <body>
    <?php if(isset($repo) && $repo != false) include __DIR__ . "/partials/header.php" ?>
    <?php if(isset($page) && $page != false) include __DIR__ . "/partials/$page.php" ?>
  </body>
</html>
