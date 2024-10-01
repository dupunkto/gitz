<?php
// My own lil' standard library :)

function validate_path($root, $path) {
  if(realpath($root) != $root) return false;
  $path = realpath($path);

  if(str_starts_with($path, $root)) {
    return $path;
  } else {
    return false;
  }
}

function ensure_suffix($str, $suffix) {
  return str_ends_with($str, $suffix) ? $str : $str . $suffix;
}

function dbg($thing) {
  var_dump($thing);
  return $thing;
}

function path_join() {
  $paths = [];
  foreach (func_get_args() as $arg) {
    if ($arg !== '') $paths[] = $arg;
  }

  return preg_replace("#/+#", "/", join("/", $paths));
}

function lighten($hex, $percent) {
  $hex = str_replace('#', '', $hex);
  $r = hexdec(substr($hex, 0, 2));
  $g = hexdec(substr($hex, 2, 2));
  $b = hexdec(substr($hex, 4, 2));

  $r = min(255, round($r + (255 - $r) * $percent));
  $g = min(255, round($g + (255 - $g) * $percent));
  $b = min(255, round($b + (255 - $b) * $percent));

  return sprintf("#%02x%02x%02x", $r, $g, $b);
}

function darken($hex, $percent) {
  $hex = str_replace('#', '', $hex);
  $r = hexdec(substr($hex, 0, 2));
  $g = hexdec(substr($hex, 2, 2));
  $b = hexdec(substr($hex, 4, 2));

  $r = max(0, round($r - $r * $percent));
  $g = max(0, round($g - $g * $percent));
  $b = max(0, round($b - $b * $percent));

  return sprintf("#%02x%02x%02x", $r, $g, $b);
}