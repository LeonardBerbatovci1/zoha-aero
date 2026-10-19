<?php
/**
 * Fallback entry point. Visitors normally get the static index.html directly.
 * After a fresh GitHub deploy the generated pages do not exist yet: this builds them
 * once from the saved content (or the defaults) and then serves the home page.
 */
require __DIR__ . '/cms/lib.php';

if (!is_file(__DIR__ . '/index.html')) {
    publish(load_content());
}

if (is_file(__DIR__ . '/index.html')) {
    readfile(__DIR__ . '/index.html');
} else {
    http_response_code(500);
    echo 'The website could not be built. Please make sure the website folder is writable.';
}
