<?php

namespace areutBkYa;

use DateTime;
use Exception;

class YaDiskBackUp {
	const FILE_WEEK = 'week';
	const FILE_MONTH = 'month';
//	public $dumpTamplate = "mysqldump -u {{user}} -p{{password}} {{db_name}} | gzip > outputfile.sql.gz";
	public static $file_config = 'config_local.php';
	public $dump_template = "mysqldump -u {{user}} -p{{password}} {{db_name}} > {{dumpName}}  2>&3 &";
	private $dump_name = "outputfile.sql.gz";
	private $db_name;
	private $user;
	private $password;
	/**
	 * @var YaDisk
	 */
	private $yaDisk;

	/**
	 * @var NamesFile
	 */
	private $fileNames;

	public static function init() {
		$m            = new self();
		$m->db_name   = self::getConfig( 'db_name' );
		$m->user      = self::getConfig( 'user' );
		$m->password  = self::getConfig( 'password' );
		$m->fileNames = new NamesFile( $m->db_name, YaDiskBackUp::getPath() );

		return $m;

	}

	/**
	 * читаем конфигурацию из файла
	 *
	 * @param null $name
	 *
	 * @return array|mixed|null
	 */
	public static function getConfig( $name = null ) {
		$config = require( self::$file_config );
		if ( is_array( $config ) ) {
			if ( isset( $name ) ) {
				if ( isset( $config[ $name ] ) ) {
					return $config[ $name ];
				} else {
					return null;
				}

			} else {
				return $config;
			}

		}

		return [];
	}

	public static function getPath() {
		return YaDiskBackUp::getConfig( 'path' );
	}

	public static function getCountMonth() {
		return YaDiskBackUp::getConfig( self::FILE_MONTH );
	}

	public function uploadDumps( bool $overwrite = true, string $fileName = null ) {
		$m        = $this->getYaDisk();
		$fileName = $fileName ?? ( $this->getFileNames()->getFileName( 1, self::FILE_WEEK ) );

		return $m->uploadFile( $this->dump_name, $fileName, $overwrite );
	}

	/**
	 * @return YaDisk
	 */
	public function getYaDisk(): YaDisk {
		if ( ! isset( $this->yaDisk ) ) {
			$this->yaDisk = YaDisk::init( YaDiskBackUp::getAuthorization(), YaDiskBackUp::getPath() );
		}

		return $this->yaDisk;
	}

	/**
	 * @return string
	 */
	public static function getAuthorization() {
		return YaDiskBackUp::getConfig( 'authorization' );
	}

	/**
	 * @return NamesFile
	 */
	public function getFileNames(): NamesFile {
		return $this->fileNames;
	}

	/**
	 * осздает дамп БД
	 * если ошибка файл дампа будет всерано создан
	 * @return array
	 */
	public function makeDump() {
		$output = [];
		exec( $this->getDumpTamplate(), $output, $return_var );

		if ( isset( $output ) ) {

			if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
				$output2 = [];
				foreach ( $output as $val ) {

					$output2[] = iconv( "CP866", "UTF-8", $val );
				}
			}

			return $output2;
		}

		return $output;
	}

	/**
	 * @return string
	 */
	public function getDumpTamplate(): string {
		$search  = array( 'db_name', 'user', 'password', 'dumpName' );
		$search  = array_map( function ( $name ) {
			return '{{' . $name . '}}';
		}, $search );
		$replace = array( $this->db_name, $this->user, $this->password, $this->dump_name );

		return str_replace( $search, $replace, $this->dump_template );

	}

	/**
	 * переименовываем файл ... 2 ->3,1->2.
	 *
	 * @param $count
	 * @param $type
	 *
	 * @param bool $fullFuleName
	 *
	 * @return int
	 */
	public function renameFiles( $count, $type, $fullFuleName = false ): int {
		$couner   = 0;
		$filename = $this->getFileNames()->fileNameFuncthionName( $fullFuleName );
		for ( $i = $count; $i > 1; $i -- ) {
			$fileOld = $this->getFileNames()->$filename( $i - 1, $type );
			$fileNew = $this->getFileNames()->$filename( $i, $type );

			if ( $this->getYaDisk()->renameFile( $fileOld, $fileNew, true ) ) {
				echo $fileOld;
				echo '------->';
				echo $fileNew;
				echo PHP_EOL;
				$couner ++;

			} else {
				echo "Файл не переименован $fileOld --> $fileNew";
				echo PHP_EOL;
			}

		}

		return $couner;

	}

	/**
	 * переименовываем файл week в mounth если разница в днях меньше чем
	 *
	 *
	 * @param string $file_week
	 * @param $file_month
	 * @param float $deltaDay
	 * @param bool $full_fule_name
	 *
	 * @return bool
	 */
	public function renameFilesType( string $file_week, string $file_month, bool $full_fule_name = false ) {
		$countWeek = self::getCountWeek();
		/** @var callback $filename */

		$filename = $this->getFileNames()->fileNameFuncthionName( $full_fule_name );

		$fileNameWeek  = $this->getFileNames()->$filename( $countWeek, $file_week );
		$fileNameMonth = $this->getFileNames()->$filename( 1, $file_month );

		return $this->getYaDisk()->renameFile( $fileNameWeek, $fileNameMonth, true );
	}

	public static function getCountWeek() {
		return YaDiskBackUp::getConfig( self::FILE_WEEK );
	}

	public function enableModificationWeekMonth( $fileMonth, $fileWeek ) {
		$enable_rename = false;
		if ( isset( $fileMonth['modified'] ) ) {
			try {
				$dateTimeMonth = new DateTime( $fileMonth['modified'] );
				if ( isset ( $fileWeek['modified'] ) ) {
					$dateTimeWeek = new DateTime( $fileWeek['modified'] );
				}
			} catch
			( Exception $e ) {
				echo $e->getMessage() . PHP_EOL;

				return false;
			}
		} else {
			$enable_rename = true;
		}

		return $enable_rename || ( $dateTimeMonth->format( 'U' ) - $dateTimeWeek->format( 'U' ) < self::getDeltaSec() );

	}

	public
	static function getDeltaSec() {
		return YaDiskBackUp::getConfig( 'delta' );
	}

	/**
	 * @return string
	 */
	public function getDumpName(): string {
		return $this->dump_name;
	}

	/**
	 * @param string $dump_name
	 */
	public function setDumpName( string $dump_name ) {
		$this->dump_name = $dump_name;
	}


}

