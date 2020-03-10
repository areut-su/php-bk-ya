<?php

use areutBkYa\YaDiskBackUp;

require_once __DIR__ . "/lib/autoload.php";


class Week {

	private static $config_file = 'config_local.php';

	public static function start() {

		$model  = self::getModel();
		$result = $model->makeDump();
		print_r( $result );
		$result = $model->renameFiles( $model::getCountWeek(), $model::FILE_WEEK );
		$result = $result && $model->uploadDumps( true );
		echo 'result:' . (bool) $result;

	}


	private static function getModel() {
		YaDiskBackUp::$file_config = __DIR__ . '/' . self::$config_file . '';

		return YaDiskBackUp::init();
	}

}

Week::start();
