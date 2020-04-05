<?php

namespace areutBkYaTest;

require_once "../lib/autoload.php";

use areutBkYa\YaDiskBackUp;
use PHPUnit\Framework\TestCase;

class YaDiskBackUpTest extends TestCase {
	public function testUpdataPack() {
		$model = $this->getModel();

		for ( $i = 2; $i <= 4; $i ++ ) {
			$this->assertTrue( $model->uploadDumps( true ) );
			$this->assertNotNull( $model->renameFiles( $i, $model::FILE_WEEK ) );
			echo "-------------";
			echo PHP_EOL;
		}
		$this->assertTrue( $model->uploadDumps( true ) );
	}

	private function getModel() {

		YaDiskBackUp::$file_config = __DIR__ . '/config_local.php';
		$model                     = YaDiskBackUp::init();
		$model->setDumpName( __DIR__ . '/test1.txt' );

		return $model;
	}

	public function testUpdateWeekMonth() {
		$model = $this->getModel();
		$this->assertNotNull( $model->renameFiles( 2, $model::FILE_MONTH ) );
		$this->assertTrue( $model->renameFilesType( $model::FILE_WEEK, $model::FILE_MONTH ) );
		$this->assertNotNull( $model->renameFiles( 4, $model::FILE_WEEK ) );
		$this->assertTrue( $model->uploadDumps( true ) );
	}

}
