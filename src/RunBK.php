<?php

namespace areutBkYa;

use areutBkYa\YaDiskBackUp;

class RunBK {

	private static $config_file = 'config_local.php';

	public static function startWeek( $config_file = null ) {

		$model = self::initModel( $config_file );
		echo "Create dump:" . PHP_EOL;
		$model->makeDump();
		echo "Rename:" . PHP_EOL;
		$model->renameFiles( $model::getCountWeek(), $model::FILE_WEEK );
		echo "Upload:" . PHP_EOL;
		$result = $model->uploadDumps( true );
		$model->rmLocalDump();
		echo 'File Upload:' . ( $result ? 'true' : 'false' ) . PHP_EOL;

	}

	private static function initModel( $config_file = null ) {
		self::$config_file = is_null( $config_file ) ? self::$config_file : $config_file;
//		echo getcwd() . PHP_EOL;

		$path        = getcwd();
		$path_config = $path . '/' . $config_file;
		if ( ! file_exists( $path_config ) ) {
			$path_config = __DIR__ . '/' . $config_file;
			if ( ! file_exists( $path_config ) ) {
				die( 'config_file not found' );
			}
		}
		YaDiskBackUp::$file_config = $path_config;

		return YaDiskBackUp::init();
	}

	public static function dump( $config_file = null ) {

		$model = self::initModel( $config_file );
		echo "Create dump:" . PHP_EOL;
		$model->makeDump();

	}

}

