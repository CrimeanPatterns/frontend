- **Локальный запуск**
	- **frontend**
		- обновить `engine` в `engine` (от корня)
		- прогнать миграции
		  ```bash
		   php app/console doctrine:migrations:migrate  -vv
		  ```
		- сделать `./install-vendors.sh`
			- мб понадобится
			  ```bash
			  eval `ssh-agent -s`;
			  ssh-add ~/.ssh/id_rsa;
			  ```
     	- в файле `app/config/local_dev.yml` (создать, если отсутствует) в секции `services` добавить сервис `AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface`:
    	  ```yaml
          imports:
            - { resource: config_dev.yml }

          services:
              AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface:
                  public: true
                  alias: 'AwardWallet\MainBundle\Globals\Updater\Engine\Wsdl'
    		```
       		- выполнить 
              ```bash
               php app/console cache:clear
              ```
		- включить опцию `Extension V3 Parser Enabled` у нужного провайдера тут http://awardwallet.docker/manager/list.php?Schema=Provider
			- можно запросом
			  ```bash
			   docker compose exec mysql mysql -vv -D awardwallet -e 'update `Provider` set IsExtensionV3ParserEnabled = 1 where Code = "british"'
			  ```
		- добавить роль для юзера: http://awardwallet.docker/manager/list.php?Schema=UserAdmin -> Edit -> `staff:extension_v3_tester`, сделать логаут\логин
		- запустить воркеры обработки коллбеков от `loyalty` и асинхронных тасков для апдейтера
		  ```bash
		  php app/console rabbitmq:consumer -w loyalty_callback_processor -vv --no-ansi;
		  php app/console rabbitmq:consumer -w async_processor_2 -vv;
		  ```
	- **loyalty**
		- установить по доке https://github.com/AwardWallet/loyalty
		- обновить контейеры (если уже развернуто)
		  ```bash
		  docker volume rm loyalty_mysql-data
		  docker compose pull
		  ```
		- обновить `engine` в `src/AppBundle/Engine` (от корня)
		- переключиться на `22822-extension-v3`
		- сделать `composer install`
			- мб понадобится
			  ```bash
			  eval `ssh-agent -s`;
			  ssh-add ~/.ssh/id_rsa;
			  ```
		- Включить возможность проверки через Extension V3 у провайдера (напр. `british`)
		  ```bash
		  docker compose exec mysql mysql -vv -D loyalty -e 'update `Provider` set IsExtensionV3ParserEnabled = 1 where Code = "british"'
		  ```
		- запустить воркеры проверки и отправки коллбеков обратно на `frontend`
		  ```bash
		  php bin/console aw:check-worker -vv -p awardwallet;
		  php bin/console rabbitmq:consumer -w send_callback -vv;
		  ```
