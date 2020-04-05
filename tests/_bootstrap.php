<?php

if ( file_exists( __DIR__ . "/../lib/autoload.php" ) ) {
	include_once __DIR__ . "/../lib/autoload.php";

} elseif ( file_exists( __DIR__ . "/../../autoload.php" ) ) {
	include_once __DIR__ . "/../lib/autoload.php";
}
