# php-bk-ya

Requires PHP 7.0+

## Docs
    Бэкап БД на яндекс диск. 
    
## Install
    composer.json
    {
      "minimum-stability": "dev",
      "type": "project",
      "repositories": [
        {
          "type": "vcs",
          "url": "https://github.com/areut-su/php-bk-ya.git"
        }
      ],
      "require": {
        "areut/php-bk-ya": "dev-master"
      }
      "require-dev": {
          "phpunit/phpunit": "~6.5.0"
       }
    }
 ====================================================================

    - получить токен:   
         https://yandex.ru/dev/disk/api/concepts/quickstart-docpage/
    - создать папку приложения на Ya диске. ??? возможно автоматически создаётся. 
    - создать файл config_local.php на основе файла config.php. Можно взяь из папки test
    - скопировать файл week.php и backup.php в корень. положить туда же файл config_local.php
    - Исправить week.php, указав верный путь к  файлу autoload.php.
    -  php -v - убедится, что версия больше 7.0
    - Запустить из консоли файл week.php: php week.php. 
    - дождаться выполнения скрипта.
    - убедится, что на Ya диске рабочий дамп.
     - добавить задачу в крон.
     
## Examples
        	
    см. папку test.

## Tests

    для работы тестов нужен файл config_local.php


## License
    MIT