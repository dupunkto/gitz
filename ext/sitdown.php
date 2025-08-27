<?php

class Sitdown extends Parsedown {
  function __construct($repo, $hash, $base) {
    $this->repo = $repo;
    $this->hash = $hash;
    $this->base = $base;
  }

  function inlineImage($excerpt) {
      $image = parent::inlineImage($excerpt);

      if (!isset($image)) return null;

      $path = $image['element']['attributes']['src'];

      if(!is_url($path)) {
        $path = trim(path_join($this->base, $path), '/');
        $blob = \core\getBlob($this->repo, $path, $this->hash);
        $mime = \core\detectMimeType($this->repo, $path, $this->hash);

        $image['element']['attributes']['src'] =
          "data:{$mime};base64," . base64_encode($blob);
      }

      return $image;
  }
}