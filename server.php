<?php

/**
 * This is a simple router middleware to allow Yeoman, PHP, and 
 * Livereload to work together in harmony.
 */

// Port for Live Reload to listen on.
$port = 35729;
$app_dir = 'src';
$tmp_dir = '.tmp';


/**
 * Output buffer callback.  Parse the Buffer and add a call to Livereload if needed
 */
$parse_buffer = function ($buffer) use ($port) {
    $content =  str_replace('</body>', "
        <!-- livereload script -->
        <script type=\"text/javascript\">document.write('<script src=\"http://'
        + (location.host || 'localhost').split(':')[0]
        + ':" . $port . "/livereload.js?snipver=1\" type=\"text/javascript\"><\\/script>')
        </script>
        </body>", $buffer);

    return $content;
};
/**
 * Return the mimetype based on file extention.
 *
 * Other methods that just don't work:
 *  - mime_content_type()
 *      - Depracated, returns the wrong type for css.
 *  - PECL: finfo::file
 *      - Returns the wrong type for css
 *  - mimetype -DM --database /usr/share/misc/magic $tmp
 *      - Not available on all systems
 *      - Returns text/plain for css.
 */
function get_mime_type ($filename) {
    $path = pathinfo($filename);

    switch ($path['extension']) {
    case 'css':
        return 'text/css';
    case 'js':
        return 'text/javascript';
    default:
        return 'text/plain';
    }
}


$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$uri = urldecode($uri);

$paths = require __DIR__ . "/$app_dir/bootstrap/paths.php";

$requested = $paths['public'] . $uri;
$tmp = __DIR__ . '/.tmp' . $uri;

// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.
if ($uri !== '/' and file_exists($requested)) {
	return false;
} else if (file_exists($tmp)  && !is_dir($tmp)) {
    header('Content-Type: ' . get_mime_type($tmp));
    $path = $tmp;
} else {
    $path =  $paths['public'] . '/index.php';
}

header('X-Loaded-File: ' . $path); 
ob_start($parse_buffer);
include $path;

