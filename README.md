Назначение
==========

***YOU MUST UNPACK***
- ***web/assets/common/vendors/vendors.zip***
- ***vendors/vendors.zip***
- ***vendors/vendors2.zip***
- ***node_modules/node_modules.zip***
- ***node_modules/node_modules2.zip***
- ***node_modules/node_modules3.zip***
- ***app/config/config.zip***

Сайт awardwallet.com 

Установка Docker
================

1. Установите docker: https://www.docker.com  
2. Установите docker-compose если вы на linux: https://docs.docker.com/compose/install/
3. Создайте Github Access Token тут: https://github.com/settings/tokens/new?scopes=read:packages,repo&description=frontend

   Увеличьте или уберите время жизни токена.

   Сгенерированный токен понадобится на следующих шагах. Запишите его.
3. Настройте доступ в awardwallet github: 
https://redmine.awardwallet.com/projects/awwa/wiki/Migrating_to_git#Настройка-доступа-к-github
4. Скачайте проект и сабмодули, находясь в папке проектов Awardwallet:
```bash
git clone --recursive https://github.com/AwardWallet/frontend.git 
cd frontend
git submodule foreach "git checkout master && git pull" 
```
5. Авторизуйтесь на docker.awardwallet.com (ваш пароль придет в письме, в зашифрованном виде):
```bash
docker login docker.awardwallet.com
Username: VPupkin
Password: 
Login Succeeded
```
6. Настройка `.env`

Узнайте свой локальный user id, командой:
```bash
id -u $USER
```
Создайте файл .env вида
```bash
LOCAL_USER_ID=<ваш user id>
```
Смотрите в .env.example что должно получиться 
7. Создайте сеть для взаимодействия между проектами awardwallet 
```bash
docker network create awardwallet
```
8. Запустите docker-compose
```bash
docker compose up
```
Это запустит все нужные сервисы для работы с сайтом. Пока не закрывайте это окно, здесь мы сможем
увидеть логи если что то пойдет не так.

**(Только для ARM)**
если в логах базы появляются такие ошибки:
```
mysqld: File './binlog.index' not found (OS errno 13 - Permission denied)
```
то выполните:
```bash
docker compose run -T --rm mysql chown -R mysql:mysql /var/lib/mysql
```
и перезапустите контейнеры (`Ctrl+C`), `docker compose up`

9. Настройте среду внутри контейнера
Откройте новое окно терминала, перейдите в папку где лежат исходники, выполните:
```bash
docker compose exec php console
```
Вы должны увидеть запрос командной строки вида:
```bash
user@frontend:/www/awardwallet$
```
10. Залогиньтесь в npm
```bash
npm login --scope=@awardwallet --registry=https://npm.pkg.github.com
Username: <Ваше имя пользователя GitHub в нижнем регистре>
Password: <ваш GitHub Access Token>
Email: (this IS public) <ваш @awardwallet.com email>
Logged in as vsilantyev to scope @awardwallet on https://npm.pkg.github.com/.
```
11. Установите зависимости
```bash
./install-vendors.sh
```
12. Прогоните тест
```bash
user@frontend:/www/awardwallet$ vendor/bin/codecept build
user@frontend:/www/awardwallet$ vendor/bin/codecept run tests/unit/Timeline/PhoneBookFactoryTest.php

```
13. Настройте сайт в браузере

Вам понадобится 
https://github.com/codekitchen/dinghy-http-proxy

Если контейнер http-proxy уже был ранее создан, то сначала удаляем старый контейнер:
```text
docker stop http-proxy
docker rm http-proxy
```

Запускаем dinghy (это вариант для mac):
```bash
docker run -d --restart=always -v /var/run/docker.sock:/tmp/docker.sock:ro -v ~/docker-share/dinghy-certs:/etc/nginx/certs -p 80:80 -p 443:443 -p 19322:19322/udp -e DOMAIN_TLD='#' -e DNS_IP=127.0.0.1 -e CONTAINER_NAME=http-proxy --name http-proxy codekitchen/dinghy-http-proxy
```
прописать в /etc/hosts:
```text
127.0.0.1	beta.ra.local
127.0.0.1	awardwallet.docker
127.0.0.1	comet.awardwallet.docker
127.0.0.1	analytics.google.com
127.0.0.1	blog.awardwallet.docker
127.0.0.1	loyalty.docker
127.0.0.1	mail.loyalty.docker
```

Сайт будет доступен по http://awardwallet.docker

Docker FAQ
----------

У меня мак и все так медленно!
==============================

Docker for Mac 17.06+
----------
Добавляем конфиг `docker-compose-local.yml`(он в .gitignored) перекрываем опции монтирования:
```yaml
version: '2.4'

services:
  php:
    volumes:
      - ./:/www/awardwallet:cached
      - ./app/logs:/var/log/www/awardwallet:cached
```
Подключаем конфиг в `.env` (если такая строка уже есть, то добавляем в конец):
```
COMPOSE_FILE=docker-compose.yml:docker-compose-local.yml
```
Перезапускаем
```
$ docker compose down
$ docker compose up -d
```

Мне нужен localhost:8081, mysql, vnc, imap-почта
==================================================

Добавляем в `docker-compose-local.yml`, (смотри выше):
```yaml
services:
  nginx:
    ports:
      - "8081:80"
  mysql:
    ports:
      - "3306:3306"
  selenium:
    ports:
      - "5900:5900"
  mail:
    ports:
      - "143:143"
```

Настройка xdebug
=================

Для docker for mac ничего настраивать не надо.

Для других систем:

```bash
sudo vim /etc/php/7.1/mods-available/php_aw_debug.ini 
```

В этом файле вам надо поменять параметр xdebug.remote_host, прописать туда свой ip в локальной сети.

После этого выйти из контейнера и сделать
```bash
docker compose restart php
```
Эти изменения придется делать при смене ip, и при обновлении контейнеров.
Для docker под windows есть специальное имя хоста: docker.for.win.localhost (не проверено, отпишите кто попробует), для linux наверно что то аналогичное.

В консоли xdebug по умолчанию отключен, чтобы включить, используйте алиас xdebug, пример:
```bash
xdebug ../vendon/bin/codecept run tests/unit/SomeTest.php
```

Переключение prod\dev режимов
=============================
По-умолчанию локальная копия работает в dev-режиме, переключение происходит посредством куки SITE_STATE.

Prod-режим работает через https, для [dinghy-http-proxy](https://github.com/AwardWallet/frontend/blob/master/README.md#Можно-мне-awardwalletdev-вместо-localhost8081-) нужно сгенерировать сертификаты:
```
cd ~/docker-share/dinghy-certs
openssl req -x509 -newkey rsa:2048 -keyout awardwallet.docker.key \
-out awardwallet.docker.crt -days 365 -nodes \
-subj "/C=US/ST=Oregon/L=Portland/O=Company Name/OU=Org/CN=awardwallet.docker" \
-config <(cat /etc/ssl/openssl.cnf <(printf "[SAN]\nsubjectAltName=DNS:awardwallet.docker")) \
-reqexts SAN -extensions SAN
docker restart http-proxy
```

Сгенерированный `awardwallet.docker.crt` добавляем в keychain и делаем ему trust.

Перезапускаем контейнеры(делаем из папки frontend-проект):
```
docker compose down
docker compose up
```

Добавляем два букмарклета:

prod
```javascript:(function() {document.cookie='SITE_STATE='+'prod'+';path=/;';})()```

dev
```javascript:(function() {document.cookie='SITE_STATE='+'dev'+';path=/;';})()```

Для Google Chrome после переключения prod -> dev придется очистить кеш: Clear Browsing Data -> Cached Images and files, иначе будет бесконечный редирект(скоро поправим).

Как обновить базу?
==================

```bash
docker compose down
docker compose rm -v mysql mysql-data
docker volume rm frontend_mysql-data-8
docker compose pull mysql-data
```

Запускайте снова.
```bash
docker compose up -d
```

Не забудьте прогнать 
```bash
docker compose exec php console
php app/console doctrine:migrations:migrate
```

Если ничего не работает, то прогнать:

```bash
rm app/config/parameters.yml
./install-vendors.sh
```

В случае, если при попытке получить свежий контейнер с базой возникает ошибка вида:
```bash
ERROR: Error: image mysql-data/awardwallet:latest not found
```
необходимо заново авторизоваться при помощи команды docker login docker.awardwallet.com


Как подключиться к acceptance селениуму через VNC?
==================================================
Это селениум для тестов, для парсинга нужно ставить отдельно, смотри ниже.
 
Установите себе VNC полноценный клиент или расширение для Chrome https://chrome.google.com/webstore/detail/chrome-remote-desktop/gbchcmhmhahfdphkhkmpfmihenigjmpp

подключиться можно по следующему адресу
```bash
localhost:5900
```
Должен быть docker-compose-local.yml, с настроенными портами, смотри выше.

Селениум для парсинга (кроме Apple Silicon)
=====================

Для парсинга программ с использованием селениум установите:
https://github.com/AwardWallet/selenium-monitor

Удаленный селениум для всех (в т.ч. для Apple Silicon)
=====================

Нужно пробросить порты. Скрипт в репозитории парсербокса https://github.com/AwardWallet/parserbox-web/blob/master/connect-remote-selenium.sh . SELENIUM_HOST не менять. Скрипт сработает если нужные ssh ключи для ssh.awardwallet.com прописаны в ~/.ssh/config. Для проверки можно зайти на ssh.awardwallet.com и посмотреть, что все ок.

parameters.yml:
```yaml
selenium_host: host.docker.internal
```

local_dev.yml: добавляем в services:
```yaml
aw.selenium_mac_node_finder: '@AwardWallet\WebdriverClient\SingleNodeFinder'

AwardWallet\WebdriverClient\SingleNodeFinder:
  arguments:
    $nodeAddress: 'host.docker.internal'
```

Веб интерфейс для подключения к селениумам 100-ой версии и выше: http://localhost:43765/



web-консоль для RabbitMQ
========================
Добавляем в `docker-compose-local.yml`:
``` 
  rabbitmq:
    ports:
      - "15672:15672"
```
открываем в браузере `http://localhost:15672` логин\пароль: `guest:guest`

настройка локальной кибаны
===========================
Добавляем в `.env`: 
COMPOSE_FILE=docker-compose.yml:docker-compose-local.yml:docker-compose-kibana.yml
