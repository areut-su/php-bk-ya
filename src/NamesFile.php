<?php

namespace areutBkYa;

class NamesFile {

	public $extension = "sql.gz";
	private $file_name;
	private $path;

	public function __construct( $file_name, $path ) {
		$this->file_name = $file_name;
		$this->path      = $path;
	}


	public function getFileNameFull( $number, $type ) {
		return $this->getPath() . "/{$this->file_name}_{$type}_{$number}.{$this->extension}";
	}

	public function getPath() {
		return $this->path;
	}

	public function getFileName( $number, $type ) {
		return "{$this->file_name}_{$type}_{$number}.{$this->extension}";

	}


	/**
	 * @param $fullFuleName
	 *
	 * @return string
	 */
	public function fileNameFuncthionName( $fullFuleName ): string {
		if ( $fullFuleName === false ) {
			$filename = "getFileName";
		} else {
			$filename = "getFileNameFull";
		}

		return $filename;
	}

}