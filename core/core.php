<?php

namespace core;

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/router.php";
require_once __DIR__ . "/dates.php";
require_once __DIR__ . "/neuro.php";

function resolveDumbClone($repo, $query) {
  $repo_path = $repo->getRepositoryPath();
  $query_path = path_join($repo_path, $query);

  return resolve_path($repo_path, $query_path);
}

function serveSmartInfoRefs($repo) {
  $repo_path = $repo->getRepositoryPath();

  header('Content-Type: application/x-git-upload-pack-advertisement');
  header('Cache-Control: no-cache, max-age=0, must-revalidate');

  $len = strlen("# service=git-upload-pack\n") + 4;
  echo sprintf('%04x', $len) . "# service=git-upload-pack\n";
  echo "0000";

  passthru('git upload-pack --stateless-rpc --advertise-refs ' . escapeshellarg($repo_path));
}

function serveSmartUploadPack($repo) {
  $repo_path = $repo->getRepositoryPath();

  $input = file_get_contents('php://input');

  // Git gzips the request body once it grows large (incremental fetches).
  if (@$_SERVER['HTTP_CONTENT_ENCODING'] == 'gzip') {
    $input = gzdecode($input);
  }

  header('Content-Type: application/x-git-upload-pack-result');
  header('Cache-Control: no-cache, max-age=0, must-revalidate');

  while (ob_get_level()) ob_end_flush();

  $proc = proc_open(
    'git upload-pack --stateless-rpc ' . escapeshellarg($repo_path),
    [['pipe', 'r'], ['pipe', 'w'], ['file', 'php://stderr', 'w']],
    $pipes
  );

  if (!is_resource($proc)) {
    http_response_code(500);
    return;
  }

  stream_set_blocking($pipes[0], false);
  stream_set_blocking($pipes[1], false);

  // Interleave writing the request and draining the packfile, so neither
  // pipe's buffer can fill while we block on the other.
  $len = strlen($input);
  $written = 0;

  while (true) {
    $read = [$pipes[1]];
    $write = $written < $len ? [$pipes[0]] : [];
    $except = null;

    if (stream_select($read, $write, $except, null) === false) break;

    if ($write) {
      $n = fwrite($pipes[0], substr($input, $written, 65536));
      $written += $n === false ? $len - $written : $n;
      if ($written >= $len) fclose($pipes[0]);
    }

    if ($read) {
      $chunk = fread($pipes[1], 65536);
      if ($chunk === '' || $chunk === false) {
        if (feof($pipes[1])) break;
      } else {
        echo $chunk;
        flush();
      }
    }
  }

  fclose($pipes[1]);
  proc_close($proc);
}

function generateGraph($git, $year, $color, $mode) {
  $start = strtotime("$year-01-01");
  $end = strtotime("$year-12-31");
  $heatmap = [];

  foreach(listAllRepositories($git) as $path) {
    $repo = $git->open($path);

    $dates = $repo->execute('log', '--pretty=format:%cd', '--date=short');
    $timestamps = array_map(fn($date) => strtotime($date), $dates);
    $timestamps = array_filter($timestamps, fn($ts) => $ts >= $start && $ts <= $end);

    foreach($timestamps as $ts) {
      $day = date('Y-m-d', $ts);
      @$heatmap[$day] ++;
    }
  }

  $width = 635;
  $height = 84;
  $rectSize = 10;
  $baseColor = "#" . $color;

  switch($mode) {
    case 'dark':
      $colorScale = [
        "#121217",
        darken($baseColor, 0.3),
        darken($baseColor, 0.1),
        $baseColor,
        lighten($baseColor, 0.05),
        lighten($baseColor, 0.1),
        lighten($baseColor, 0.2),
        lighten($baseColor, 0.3),
        lighten($baseColor, 0.35),
        lighten($baseColor, 0.4),
      ];
      break;

    default:
      $colorScale = [
        "#f8f9fa",
        lighten($baseColor, 0.75), 
        lighten($baseColor, 0.6),
        lighten($baseColor, 0.45),
        lighten($baseColor, 0.3),
        lighten($baseColor, 0.2),
        lighten($baseColor, 0.15),
        lighten($baseColor, 0.1),
        lighten($baseColor, 0.05),
        $baseColor,
      ];
      break;
  }

  $svg = '<?xml version="1.0" standalone="no"?>';
  $svg .= '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="' . $width . '" height="' . $height . '">';

  $x = 0;
  $y = 0;
  $today = $start;

  while ($today <= $end) {
    $date = date('Y-m-d', $today);
    $count = isset($heatmap[$date]) ? min($heatmap[$date], 9) : 0;

    $color = $colorScale[$count];
    $svg .= '<rect style="fill:' . $color . ';shape-rendering:crispedges;" data-score="' . $count . '" data-date="' . $date . '" x="' . $x . '" y="' . $y . '" width="' . $rectSize . '" height="' . $rectSize . '"/>';

    $y += $rectSize + 2;
    if ($y >= $height) {
        $y = 0;
        $x += $rectSize + 2;
    }

    $today = strtotime("+1 day", $today);
  }

  $svg .= '</svg>';

  return $svg;
}

function listAllRepositories($git) {
  $repos = [];

  foreach(NAMESPACES as $ns) {
    $paths = array_map(fn($name) => 
      path_join(SCAN_PATH, $ns, $name), 
      listRepositories($git, $ns));

    $repos = array_merge($repos, $paths);
  }

  return $repos;
}

function listRepositories($git, $namespace, $detailed = false) {
  $repositories = [];
  $scan_path = path_join(SCAN_PATH, $namespace);

  if (!is_dir($scan_path)) return $repositories;

  foreach (scandir($scan_path) as $child) {
    if (in_array($child, [".", ".."])) continue;

    $path = path_join($scan_path, $child);
    if(repoExists($path)) {
      $repo = $git->open($path);
      if(repoIsHidden($repo, $path)) continue;

      if(HOUSEKEEPING) {
        $repo->execute('gc', '--auto');
        $repo->execute('update-server-info');
      }

      $commits = $repo->execute('log', '--all', '--format=%cI');
      sort($commits);
      $created = strtotime(@$commits[0]);
      $updated = strtotime(end($commits));

      $repositories[] = [
        'name' => $child,
        'updated' => $updated,
        'created' => $created,
        'recent' => isActive($repo)
      ];
    }
  }

  usort($repositories, function ($a, $b) {
    if ($a['recent'] !== $b['recent'])
      return $b['recent'] <=> $a['recent'];
    if ($a['recent'])
      return $b['updated'] <=> $a['updated'];

    return $b['created'] <=> $a['created'];
  });

  return $detailed ? $repositories :
    array_map(fn($repo) => $repo['name'], $repositories);
}

function repoExists($path) {
  return file_exists(path_join($path, 'git-daemon-export-ok'));
}

function repoIsHidden($repo, $path) {
  if(file_exists(path_join($path, 'git-daemon-export-hidden'))) return true;
}

function isActive($repo) {
  $commits = $repo->execute('log', '--since=1 month ago', '--format=%cI');
  $commits = array_filter($commits); // Removes empty lines
  return count($commits) > 3;
}

function listRemotes($repo) {
  return $repo->execute('remote', 'show', '-n');
}

function getRemoteURL($repo, $remote) {
  try {
    $url = @rtrim($repo->execute('remote', 'get-url', $remote)[0]);
    return str_starts_with($url, "http") ? $url : parseRemoteURL($remote, $url);
  } catch (\Throwable $e) {
    return false;
  } 
}

function parseRemoteURL($remote, $url) {
  [$junk, $juice] = explode(":", $url);
  $domain = lookupRemoteDomain($remote);

  return "https://{$domain}/{$juice}";
}

function lookupRemoteDomain($remote) {
  return match($remote) {
    "codeberg" => "codeberg.org",
    "gitlab" => "gitlab.com",
    default => "github.com",
  };
}

function getLatestHash($repo, $branch) {
  return getLatestCommits($repo, $branch, 1)[0]['hash'];
}

function getDefaultBranch($repo) {
  return str_replace("refs/heads/", "", $repo->execute('symbolic-ref', 'HEAD')[0]);
}

function getTotalCommits($repo) {
  $count = $repo->execute('rev-list', '--count', 'HEAD');
  return (!empty($count) && isset($count[0])) ? (int)$count[0] : 0;
}

function getType($repo, $path, $hash) {
  if(in_array($path, ["", "."])) return 'tree';
  $output = $repo->execute('ls-tree', '-tr', $hash, $path);
  
  foreach($output as $line) {
    [$mode, $type, $hash, $object] = preg_split('/\s+/', $line, 4);
    if($object == $path) return $type;
  }

  return false;
}

function detectMimeType($repo, $path, $hash) {
  $finfo = new \finfo(FILEINFO_MIME_TYPE);

  [$line] = $repo->execute('ls-tree', $hash, $path);
  [$mode, $type, $hash, $object] = preg_split('/\s+/', $line, 4);

  $binary = join('\n', $repo->execute('cat-file', '-p', $hash));
  return $finfo->buffer($binary);
}

function getTree($repo, $path, $hash) {
  $output = $repo->execute('ls-tree', $hash, "./{$path}/");
  $files = [];

  foreach ($output as $line) {
    [$mode, $type, $hash, $object] = preg_split('/\s+/', $line, 4);
    $object = strip_prefix($object, "{$path}/");

    $files[] = [
      'mode' => $mode,
      'type' => $type,
      'path' => $object
    ];
  }

  return $files;
}

function getBlob($repo, $path, $hash) {
  return $repo->run('show', "{$hash}:{$path}")->getOutputAsString();
}

function getTotalSize($repo) {
  $total = 0;
  $sizes = $repo->execute('count-objects', '-v');

  foreach ($sizes as $statistic) {
      if (preg_match('/size(?:-pack)?:\s+(\d+)/i', $statistic, $matches)) {
          $total += (int)$matches[1];
      }
  }

  // Git returns sizes in kB, but this function returns
  // in bytes for consistency with the rest of these APIs.
  return $total * 1024;
}

function formatTotalSize($repo) {
  return formatSize(getTotalSize($repo));
}

function formatSize($bytes) {
  $sizes = ['B', 'k', 'M', 'G', 'T'];
  $factor = floor((strlen($bytes) - 1) / 3);
  $size = $bytes / pow(1024, $factor);

  $format = (floor($size) == $size) ? "%d%s" : "%.1f%s";
  return sprintf($format, $size, $sizes[$factor]);
}

define('DEFAULT_DESCRIPTION', "Unnamed repository; edit this file 'description' to name the repository.\n");

function getDescription($repo) {
  $path = $repo->getRepositoryPath() . "/description";
  $description = rtrim(@file_get_contents($path));

  if($description and $description != DEFAULT_DESCRIPTION) {
    return ensure_suffix(htmlspecialchars($description), ".");
  } else {
    return "<span>No description.</span>";
  }
}

function isHEAD($repo, $branch) {
  return $branch == $repo->getCurrentBranchName();
}

function getContributors($repo, $branch = "HEAD") {
  $lines = $repo->execute('shortlog', '-sne', $branch);
  $collected = array_reduce($lines, fn($acc, $line) => collectContributorData($acc, $line), []);

  $trailers = $repo->execute('log', '--pretty=format:%(trailers:key=Co-authored-by,valueonly)', $branch);
  foreach(array_filter($trailers, fn($l) => trim($l) !== '') as $trailer) {
    $collected = collectContributorData($collected, "1  $trailer");
  }

  usort($collected, fn($a, $b) => $b['count'] - $a['count']);

  return array_values(array_filter($collected, fn($c) => !str_ends_with($c['email'] ?? '', '@users.noreply.github.com')));
}

function collectContributors($contributors) {
  $collected = array_reduce($contributors, fn($acc, $line) => collectContributorData($acc, $line), []);

  // Remove @users.noreply.github.com emailaddresses.
  return array_values(array_filter($collected, fn($c) => !str_ends_with($c['email'] ?? '', '@users.noreply.github.com')));
}

function collectContributorData($acc, $line) {
  preg_match('/^\s*(\d+)\s+(.+?)\s+(<[^>]+>)$/', trim($line), $m);
  [$count, $author, $email] = [$m[1], $m[2], extract_email($m[3])];

  $collected = [];
  $found = false;

  foreach($acc as $contributor) {
    if(($email && $contributor['email'] == $email) || $contributor['author'] == $author) {
      $contributor['count'] += $count;
      $found = true;
    }

    $collected[] = $contributor;
  }

  if(!$found) {
    $collected[] = [
      'count' => $count,
      'author' => $author,
      'email' => $email,  
    ];
  }

  return $collected;
}

define('COLLECT_FORMAT', ['--pretty=format:%H|%cd|%s|%an|%ae', '--date=iso-strict']);

function getLatestCommits($repo, $branch = "HEAD", $n = MAX_COMMITS) {
  $commits = $repo->execute('log', '-n' . $n , COLLECT_FORMAT , $branch);
  return collectCommits($commits);
}

function getAllCommits($repo, $branch = "HEAD") {
  $commits = $repo->execute('log', COLLECT_FORMAT, $branch);
  return collectCommits($commits);
}

function collectCommits($commits) {
  return array_map(fn($line) => collectCommitData($line), $commits);
}

function getCommit($repo, $hash) {
  $diff = $repo->execute('show', COLLECT_FORMAT, $hash);
  $metadata = collectCommitData(array_shift($diff));
  
  return ['diff' => join("\n", $diff), ...$metadata];
}

function collectCommitData($line) {
  [$hash, $datetime, $subject, $author, $email] = explode('|', $line, limit: 5);
  $datetime = \DateTimeImmutable::createFromFormat(\DateTime::ATOM, (string)$datetime);

  return [
    'hash' => $hash,
    'subject' => $subject,
    'author' => $author,
    'email' => $email,
    'datetime' => $datetime,
  ];
}

function getParent($repo, $hash) {
  return strtok(@rtrim($repo->execute('log', '-1', $hash, '--format=%P')[0]), " ");
}

function toShortHash($hash) {
  return substr($hash, 0, 7);
}

function getREADME($repo) {
  try {
    return rtrim(implode("\n", $repo->execute('show', $repo->getCurrentBranchName() . ':README.md')));
  } catch (\Throwable $e) {
    return false;
  } 
}

function isCommitHash($hash) {
  return preg_match('/^[0-9a-f]{40}$/i', $hash) === 1;
}

function commitURL(string $namespace, string $repo, string $hash): string {
  return GITZ_URL . '/~' . $namespace . '/' . $repo . '/commit/' . $hash;
}

function issueURL(string $namespace, string $project, string $number): string {
  return BUGZ_URL . '/~' . $namespace . '/' . $project . '/' . $number;
}

function renderBody(string $text): string {
  $converter = new \League\CommonMark\GithubFlavoredMarkdownConverter([
    'html_input' => 'escape',
    'allow_unsafe_links' => false,
  ]);

  $html = $converter->convert($text)->getContent();

  // ~ns/repo@hash -> commit link
  $html = preg_replace_callback(
    '/~?([a-zA-Z0-9_\-\.]+)\/([a-zA-Z0-9_\-\.]+)@([0-9a-f]{7,40})\b/',
    function($m) {
      $url = esc_attr(commitURL($m[1], $m[2], $m[3]));
      $label = esc_inner($m[1] . '/' . $m[2] . '@' . substr($m[3], 0, 7));
      return '<a href="' . $url . '">' . $label . '</a>';
    },
    $html
  );

  // ~ns/project#N -> issue link
  $html = preg_replace_callback(
    '/~?([a-zA-Z0-9_\-\.]+)\/([a-zA-Z0-9_\-\.]+)#(\d+)/',
    function($m) {
      $url = esc_attr(issueURL($m[1], $m[2], $m[3]));
      $label = esc_inner($m[1] . '/' . $m[2] . '#' . $m[3]);
      $api = esc_attr(BUGZ_URL . '/api/issue?' . http_build_query([
        'repo' => $m[1], 'project' => $m[2], 'number' => $m[3],
      ]));
      return '<span class="issue-ref" data-api="' . $api . '">'
        . '<a href="' . $url . '">' . $label . '</a></span>';
    },
    $html
  );

  return $html;
}

function fmtMode($mode) {
  $mod = str_pad(decoct(octdec($mode)), 6, '0', STR_PAD_LEFT);

  $p_str = '';
  $t_char = '';

  $t_char = match(substr($mod, 0, 2)) {
    '10' => '-',      // Regular file
    '04' => 'd',      // Directory
    '12' => 'l',      // Symbolic link
    default => '?',   // Unknown
  };

  foreach (str_split(substr($mod, -3)) as $digit) {
    $digit = intval($digit);
    $p_str .= ($digit & 4) ? 'r' : '-';
    $p_str .= ($digit & 2) ? 'w' : '-';
    $p_str .= ($digit & 1) ? 'x' : '-';
  }

  return $t_char . $p_str;
}

function hasTLD($str) {
  $TLDs = [".nl", ".com", ".org", ".eu"];
  $matches = array_filter($TLDs, fn ($tld) => str_ends_with($str, $tld));
  return !empty($matches);
}

function getHomepageURL($repo, $repo_name) {
  $path = $repo->getRepositoryPath() . "/homepage-url";
  $url = @rtrim(file_get_contents($path));
  return $url ?: (hasTLD($repo_name) ? "//{$repo_name}" : false);
}

function getDocumentationURL($repo) {
  $path = $repo->getRepositoryPath() . "/documentation-url";
  $url = @rtrim(file_get_contents($path));
  return $url ?: false;
}

function getIssuesURL($repo) {
  $path = $repo->getRepositoryPath() . "/issues-url";
  $url = @rtrim(file_get_contents($path));
  return $url ?: false;
}

function getPackageURL($repo) {
  $path = $repo->getRepositoryPath() . "/package-url";
  $url = @rtrim(file_get_contents($path));
  return $url ?: false;
}

function getDemoURL($repo) {
  $path = $repo->getRepositoryPath() . "/demo-url";
  $url = @rtrim(file_get_contents($path));
  return $url ?: false;
}

function getLoginURL($repo) {
  $path = $repo->getRepositoryPath() . "/login-url";
  $url = @rtrim(file_get_contents($path));
  return $url ?: false;
}
