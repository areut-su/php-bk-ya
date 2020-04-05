<?php

namespace areutBkYa;

use DateTime;
use Exception;

class YaDiskBackUp {
	const FILE_WEEK = 'week';
	const FILE_MONTH = 'month';
//	public $dumpTamplate = "mysqldump -u {{user}} -p{{password}} {{db_name}} | gzip > outputfile.sql.gz";
	public static $file_config = 'config_local.php';
	public $file_password_db = 'db.cnf';
//	public $dump_template = "mysqldump --user={{user}} --password='{{password}}' {{db_name}} > {{dumpName}}";
	public $dump_template = "mysqldump --defaults-extra-file={{file_password_db}} {{db_name}} | gzip > {{dumpName}} ";
	public $template_config = "echo -e '[mysqldump]\n user = {{user}}\n password = \"{{password}}\"\n' >{{file_password_db}}";
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
		$output     = [];
		$return_var = '';
		if ( file_exists( $this->file_password_db ) ) {
			echo " file $this->file_password_db  found" . PHP_EOL;
		} else {
			print_r( exec( $this->getDumpTamplateCnf(), $output, $return_var ) );

		}
		echo "Create dum start" . PHP_EOL;
		print_r( exec( $this->getDumpTamplateDb(), $output, $return_var ) );


		echo 'Dump Size:' . filesize( $this->dump_name );
		echo PHP_EOL;

		if ( ! file_exists( $this->dump_name ) || filesize( $this->dump_name ) < 500 ) {
//			print_r( $output );
			$this->rmLocalDump();
			die( 'Dump file name:' . $this->dump_name . ' not create or less 100b' . PHP_EOL );
		}
		echo "Dump Name:" . $this->dump_name . PHP_EOL;
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

	public function getDumpTamplateCnf(): string {
		$search  = [ 'user', 'password', 'file_password_db' ];
		$replace = [ $this->user, $this->password, $this->file_password_db ];

		return $this->getDumpTamplate( $search, $replace, $this->template_config );
	}

	/**
	 * @return string
	 */
	public function getDumpTamplate( $search, $replace, $template ): string {

		$search = array_map( function ( $name ) {
			return '{{' . $name . '}}';
		}, $search );

		return str_replace( $search, $replace, $template );

	}

	public function getDumpTamplateDb(): string {
		$search  = [ 'db_name', 'file_password_db', 'dumpName' ];
		$replace = [ $this->db_name, $this->file_password_db, $this->dump_name ];

		return $this->getDumpTamplate( $search, $replace, $this->dump_template );
	}

	public function rmLocalDump() {
		exec( "rm $this->dump_name" );
		echo "rm $this->dump_name";
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
				echo "Err Rename: $fileOld <!!!!> $fileNew";
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