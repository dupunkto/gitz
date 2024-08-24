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

function getParent($repo, $commit) {
  return strtok(@rtrim($repo->execute('log', '-1', $commit->getId(), '--format=%P')[0]), " ");
}

function getShortHash($commit) {
  return substr($commit->getId(), 0, 7);
}

function getRelativeDate($commit) {
  return \dates\timeAgo($commit->getDate());
}

function getREADME($repo) {
  try {
    return rtrim(implode("\n", $repo->execute('show', $repo->getCurrentBranchName() . ':README.md')));
  } catch (\Throwable $e) {
    return false;
  } 
}