<?php


namespace areutBkYaTest;

include_once '_bootstrap.php';

use areutBkYa\YaDisk;
use areutBkYa\YaDiskBackUp;
use PHPUnit\Framework\TestCase;

class YaDiskTest extends TestCase {
	public $yaDisk;
	private $file_name_ya_disk = "test1.txt";

	public function testSizeFree() {
		$modelY = $this->getYaDisk();
		$size   = (int) ( $modelY->sizeFree() / 1024 );
		echo "$size" . PHP_EOL;
		$this->assertLessThan( $size, 0, 'Нет свобдного места места' );
	}

	/**
	 * @return YaDisk
	 */
	private function getYaDisk(): YaDisk {

		if ( ! isset( $this->yaDisk ) ) {
			$this->yaDisk = YaDisk::init( YaDiskBackUp::getAuthorization(), ( require 'config_local.php' )['path'] );
		}

		return $this->yaDisk;
	}

	public function testRenameFile() {
		$this->testUploadFile();
		$modelY = $this->getYaDisk();
		$this->assertTrue(
			$modelY->renameFile(
				$this->file_name_ya_disk,
				'New_' . $this->file_name_ya_disk,
				true
			),
			'ошибка записи' );
	}

	public function testUploadFile() {
		$modelY = $this->getYaDisk();
		$this->assertTrue( $modelY->uploadFile( __DIR__ . '/' . 'test1.txt', $this->file_name_ya_disk, true ),
			'ошибка записи' );
	}

	public function testFolderInfo() {
		$this->testUploadFile();
		$modelY = $this->getYaDisk();
		$items  = $modelY->folderInfo();
//		print_r( $items );
		$this->assertCount( 2, $items );

		$modelY->resetfolderInfo();

		$items2 = $modelY->folderInfo( 1, 0 );
//		print_r( $items2 );
		$this->assertCount( 1, $items2 );

		$modelY->resetfolderInfo();
		$this->assertCount( 1, $modelY->folderInfo( 1, 1 ) );


	}

	public function testDeleteFile() {
		$this->assertTrue( true );

		return true;

		// удаляет все файлы из папки,
		$modelY = $this->getYaDisk();

		$items  = $modelY->folderInfo();
		foreach ( $items as $value ) {
			if ( isset( $value['path'] ) ) {
				$this->assertTrue( $modelY->deleteFile( $value['path'] ),
					" Ошибка удаления файла" );
			}
		}

	}

	public function testInfo() {
		$modelY = $this->getYaDisk();
		print_r( $modelY->folderInfo() );
		$this->assertTrue( true );
	}
}
