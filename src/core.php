<?php

namespace core;

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/router.php";
require_once __DIR__ . "/dates.php";
require_once __DIR__ . "/utils.php";

function handleDumbClone($repo, $query) {
  $repo_path = $repo->getRepositoryPath();
  $query_path = path_join($repo_path, $query);
  $request_path = validate_path($repo_path, $query_path);

  if($request_path == false) {
    http_response_code(403);
    exit;
  }

  if(!is_file($request_path)) {
    http_response_code(404);
    exit;
  }

  header('Content-Type: application/octet-stream');
  readfile($request_path);
  exit;
}

function generateGraph($git, $year) {
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
  $baseColor = '#7426e2';

  switch(@$_GET['c']) {
    case 'dark':
      $colorScale = [
        "#131618",
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

function listRepositories($git, $namespace) {
  $repositories = [];
  $scan_path = path_join(SCAN_PATH, $namespace);

  if (!is_dir($scan_path)) return $repositories;

  foreach (scandir($scan_path) as $child) {
    if (in_array($child, [".", ".."])) continue;

    $path = path_join($scan_path, $child);
    if (repoExists($path)) {
      $repo = $git->open($path);

      if(HOUSEKEEPING) {
        $repo->execute('gc', '--auto');
        $repo->execute('update-server-info');
      }

      $commits = $repo->execute('log', '--reverse', '--format=%cI');
      $created = strtotime(@$commits[0]);

      $repositories[] = [
        'name' => $child,
        'created' => $created,
      ];
    }
  }

  usort($repositories, function($a, $b) {
    return $b['created'] - $a['created'];
  });

  return array_map(fn($repo) => $repo['name'], $repositories);
}

function repoExists($path) {
  return file_exists(path_join($path, 'git-daemon-export-ok'));
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
    default => "github.com",
  };
}

function getTotalCommits($repo) {
  $count = $repo->execute('rev-list', '--count', 'HEAD');
  return (!empty($count) && isset($count[0])) ? (int)$count[0] : 0;
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

function getLatestCommits($repo) {
  $collected = [];
  $commits = $repo->execute('log', '-n' . MAX_COMMITS, '--pretty=format:%H|%cd|%s', '--date=iso-strict');

  foreach ($commits as $line) {
    list($hash, $datetime, $subject) = explode('|', $line, limit: 3);
    $datetime = \DateTimeImmutable::createFromFormat(\DateTime::ATOM, (string)$datetime);

    $collected[] = [
      'hash' => $hash,
      'subject' => $subject,
      'datetime' => $datetime,
    ];
  }

  return $collected;
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