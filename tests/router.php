<?php
/** Router for the disposable loopback-only test fixture, never production. */
$path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
$file = realpath( '/var/www/html' . rawurldecode( $path ) );
if ( $file && 0 === strpos( $file, '/var/www/html/' ) && is_file( $file ) ) {
	return false;
}
require '/var/www/html/index.php';
