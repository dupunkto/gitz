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

  return array_map(fn($repo) => $repo['name'], $repositories);
}

function repoExists($path) {
  return file_exists(path_join($path, 'git-daemon-export-ok'));
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
  $contributors = $repo->execute('shortlog', '-sne', $branch);
  return collectContributors($contributors);
}

function collectContributors($contributors) {
  return array_reduce($contributors, fn($acc, $line) => collectContributorData($acc, $line), []);
}

function collectContributorData($acc, $line) {
  [$count, $author, $email] = preg_split('/\s+/', trim($line));
  $email = extract_email($email);

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
