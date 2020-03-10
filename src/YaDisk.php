<?php

namespace areutBkYa;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class YaDisk {
	public $debug = false;
	/**
	 * @var string
	 */
	private $authorization;
	/**
	 * папка с которой будем работь на яндекс диске
	 * "/Приложения/__Ваше_прлиоженеи_на_ЯД/"
	 * "disk:/Приложения/BK_olgatravel"
	 * @var string
	 */
	private $path;
	/**
	 * @var Client
	 */
	private $httpClien;
	private $yaDiskCash = [];

	/**
	 * @param $authorization - токен  для авторизации
	 * @param $path - путь к директории приложения
	 * @param string $base_url
	 *
	 * @return YaDisk
	 */
	static function init( $authorization, $path, $base_url = "https://cloud-api.yandex.net:443" ) {
		if ( empty( $headers ) ) {
			$headers = [
				"Accept"        => "*/*",
				"Cache-Control" => "no-cache",
				"Authorization" => "OAuth " . $authorization
			];
		}
		$model            = new self();
		$model->httpClien = new Client( [
				'base_uri' => $base_url,
				'headers'  => $headers,
			]
		);
		$model->setAuthorization( $authorization );
		$model->setPath( $path );

		return $model;
	}

	/**
	 * @param string $authorization
	 */
	public function setAuthorization( string $authorization ) {
		$this->authorization = $authorization;
	}

	/**
	 * свободное место в байтах
	 * @return int
	 */
	public function sizeFree() {

		return $this->sizeDisk() - $this->sizeUsed();
	}

	/**
	 *
	 * @return int в байтах
	 */
	public function sizeDisk() {
		$responseBody = $this->getDiskInfo();
		if ( isset( $responseBody['total_space'] ) ) {
			return (int) $responseBody['total_space'];
		}

		return 0;
	}

	/**
	 *  Получаем общую информацию о диске
	 *
	 * @param bool $update - true  - обнавляем данные из кэша
	 *
	 * @return array
	 */
	protected function getDiskInfo( $update = false ): array {

		if ( $update || ! isset( $this->yaDiskCash['diskInfo'] ) ) {
			try {
				$response = $this->getHttpClien()->request( 'GET', '/v1/disk', [
					'debug' => $this->debug,
				] );

			} catch ( RequestException $e ) {
				if ( $e->hasResponse() ) {
					echo "Ошибка получения информации о диске. Code:" . $e->getCode() . PHP_EOL;
					echo $e->getMessage() . PHP_EOL;
				}

				return [];
//				die( 'Disk info.  StatusCode' . $e->getCode() );


			}


			if ( $response->getStatusCode() === 200 ) {
				$respponse_array = json_decode( $response->getBody(), true );
				if ( ! empty( $respponse_array && is_array( $respponse_array ) ) ) {
					return $this->yaDiskCash['diskInfo'] = $respponse_array;
				}
			}


//			throw new Exception( 'Информация о диске не полученна' );


		}

		return $this->yaDiskCash['diskInfo'];

	}

	/**
	 * @return Client
	 */
	public function getHttpClien() {
		return $this->httpClien;
	}

	/**
	 * Занятоне место
	 * @return int
	 */
	public function sizeUsed() {
		$responseBody = $this->getDiskInfo();
		if ( isset( $responseBody['used_space'] ) ) {
			return (int) $responseBody['used_space'];
		}

		return 0;
	}

	/**
	 * @param $fileNameYaDiskOld -имя файла в папке
	 * @param $fileNameYaDiskNew -имя файла в папке
	 * @param bool $overwrite
	 *
	 * @return bool
	 */
	public function renameFile( $fileNameYaDiskOld, $fileNameYaDiskNew, $overwrite = false ) {
		try {

			$response = $this->getHttpClien()->request( 'POST', '/v1/disk/resources/move', [
				'query' => [

					"from"      => $this->getPath() . "/$fileNameYaDiskOld",
					"path"      => $this->getPath() . "/$fileNameYaDiskNew",
					"overwrite" => $overwrite,
					'debug'     => $this->debug,
				]
			] );
		} catch ( RequestException $e ) {
			if ( $e->hasResponse() ) {
				echo "Err Rename File. Code:" . $e->getCode() . PHP_EOL;
				if ( $this->debug ) {
					if ( $e->getCode() === 404 ) {
						echo "File not found" . PHP_EOL;
					} else {
						echo $e->getMessage() . PHP_EOL;
					}
				}
			}

			return false;
		}

		$status_code = $response->getStatusCode();
		if ( $status_code === 201 || $status_code === 202 ) {
			return true;
		}

		return false;

	}

	/**
	 * @return string
	 */
	public function getPath(): string {
		return $this->path;
	}

	/**
	 * @param string $path
	 */
	public function setPath( string $path ) {
		$this->path = $path;
	}

	/**
	 * @param $filePathLocal - полный путь к файлу на диске
	 * @param $fileNameYaDisk - название файла в папке  прилоения
	 * @param bool $overwrite
	 *
	 * @return bool
	 */
	public function uploadFile( $filePathLocal, $fileNameYaDisk, $overwrite = false ) {
		$href   = $this->getUploadHref( $fileNameYaDisk, $overwrite );
		$client = $this->getHttpClien();
//		$filename = __DIR__ . DIRECTORY_SEPARATOR . 'test1.txt';
//		$filename = $filePath . DIRECTORY_SEPARATOR . 'test1.txt';

		$handle = fopen( $filePathLocal, 'r' );
		if ( $handle ) {
			try {
				$response = $client->put( $href, [
					'body'            => $handle,
					'allow_redirects' => false,
//					'timeout'         => 10,
					'debug'           => $this->debug,
					'query'           => [
					]
				] );

			} catch ( RequestException $e ) {
				if ( $e->hasResponse() ) {
					echo "Err upload File. Code:" . $e->getCode() . PHP_EOL;
					echo $e->getMessage() . PHP_EOL;
				}

				return false;
			}
			if ( is_resource( $handle ) ) {
				fclose( $handle );
			}
		}
		if ( $response->getStatusCode() === 201 ) {
			return true;
		}

		return false;
	}

	/**
	 * @param $fileName
	 * @param bool $overwrite
	 *
	 * @return string
	 */
	protected function getUploadHref( $fileName, $overwrite = false ) {
		try {
			$response = $this->getHttpClien()->request( 'GET', 'v1/disk/resources/upload', [
				'query' => [
					'debug'     => $this->debug,
					"overwrite" => $overwrite,
					"path"      => $this->getPath() . "/$fileName"
				]
			] );
		} catch ( RequestException $e ) {
			if ( $e->hasResponse() ) {
				echo "Err Upload File. Code:" . $e->getCode() . PHP_EOL;
				echo $e->getMessage() . PHP_EOL;

				return '';
			}
		}
		if ( $response->getStatusCode() === 200 ) {
			$respponse_array = json_decode( $response->getBody(), true );
			if ( ! empty( $respponse_array['href'] ) ) {
				return $respponse_array['href'];
			}
		}
//			throw new Exception( 'Информация о диске не полученна' );
		die( 'Disk info.  StatusCode' . $response->getStatusCode() );
	}

	/**
	 * @param $fileNameFull
	 * @param bool $permanently - true удаление без корзины
	 *
	 * @return bool true - файл удален
	 */
	function deleteFile( $fileNameFull, $permanently = false ): bool {
		try {
			$response = $this->getHttpClien()->request( 'DELETE', 'v1/disk/resources', [
				'query' => [
					'debug'       => $this->debug,
					"permanently" => $permanently,
					"path"        => $fileNameFull
				]
			] );
		} catch ( RequestException $e ) {
			if ( $e->hasResponse() ) {
				echo "Err delete File. Code:" . $e->getCode() . PHP_EOL;
				echo $e->getMessage() . PHP_EOL;

				return false;
			}
		}
		$status_code = $response->getStatusCode();
		if ( $status_code === 202 || $status_code === 204 ) {
			return true;
		}

		return false;
	}

	/**
	 * ищет файл в папке на яндекс диске
	 *
	 * @param $fileName
	 *
	 * @return array
	 * ['path'=>, 'modified'=>]
	 */
	public function findFile( string $fileName ): array {
		foreach ( $this->folderInfo() as $index => $file ) {
			if ( strpos( $file['path'], $fileName ) ) {
				return $file;
			}
		}

		return [];
	}

	/**
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return array  []['path', 'modified',created]
	 */
	public function folderInfo( $limit = 256, $offset = 0 ) {
		if ( ! isset( $this->yaDiskCash['folderInfo'] ) ) {
			try {
				$response = $this->getHttpClien()->request( 'GET', '/v1/disk/resources', [
					'query' => [
						"path"   => $this->getPath(),
						'limit'  => $limit,
						'offset' => $offset,
						"fields" => '_embedded.items.path, _embedded.items.modified, _embedded.items.created',
						'debug'  => $this->debug,
					]
				] );
			} catch
			( RequestException $e ) {
				if ( $e->hasResponse() ) {
					echo "Err Folder Info. Code:" . $e->getCode() . PHP_EOL;
					echo $e->getMessage() . PHP_EOL;
				}

				return [];
			}
			$status_code = $response->getStatusCode();
			if ( $status_code === 200 ) {
				$respponse_array = json_decode( $response->getBody(), true );
				if ( ! empty( $respponse_array['_embedded']['items'] ) ) {
					$this->yaDiskCash['folderInfo'] = $respponse_array['_embedded']['items'];
				}
			}
		}

		return $this->yaDiskCash['folderInfo'];
	}

	/**
	 * return void
	 */
	public function resetfolderInfo() {
		$this->yaDiskCash = [];
	}

	/**
	 * @return string
	 */
	private function getAuthorization(): string {
		return $this->authorization;
	}


}