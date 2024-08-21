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