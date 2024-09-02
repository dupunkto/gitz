<?php
// Helper functions for dealing with my amazingly slow homepage.
// Uses a so-called "stale-while-revalidate" caching strategy.

namespace cache;

define('CACHE', "cache.html");
define('TMP', "/tmp/cache.html");

define('ENTRYPOINT', __DIR__ . "/index.php");

function capture($closure) {
    ob_start();
    $closure();
    return ob_get_clean();
}

function generate() {
    $cached = capture(fn() => include ENTRYPOINT);

    // We're writing to TMP first, and then renaming
    // atomically to CACHE, to prevent race condition
    // where we'd be serving partials files.
    file_put_contents(TMP, $cached);
    rename(TMP, CACHE);

    return $cached;
}

function serve() {
    if(file_exists(CACHE)) {
        readfile(CACHE);

        // Trigger background process to invalidate cached copy.
        if(!file_exists(TMP)) exec("php cache.php > /dev/null 2>&1 &");
        exit;
    } else {
        echo generate();
        exit;
    }
}

// If this file is called directly (in the case of cache invalidation),
// we generate a new cached copy. Otherwise, we serve a regular page,
// either from cache, or generate it on the fly.

match(php_sapi_name()) {
    'cli' => generate(),
    default => serve(),
};
