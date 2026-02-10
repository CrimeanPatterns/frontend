# Доступ к базам staging\dev-*\test-runner

## Настройка SSH
Редактируем `~/.ssh/config` (НЕ под docker)
```ssh-config
# Если есть доступ ssh.awardwallet.com
Host aw-ssh
    HostName ssh.awardwallet.com
    IdentitiesOnly yes
    User JDoe # your username
    IdentityFile ~/.ssh/JDoe_ssh.awardwallet.com.pem # path to your ssh key
    
# Если есть доступ к infra.awardwallet.com
Host aw-infra
    HostName infra.awardwallet.com
    IdentitiesOnly yes
    User JDoe # your username
    IdentityFile ~/.ssh/JDoe_ssh.awardwallet.com.pem # path to your ssh key
    
Host aw-test-runner
    HostName 192.168.4.161
    User JDoe
    IdentityFile ~/.ssh/JDoe_ssh.awardwallet.com.pem # path to your ssh key
    ProxyJump aw-ssh # or aw-infra
    
Host aw-staging
    HostName 192.168.4.24
    User JDoe
    IdentityFile ~/.ssh/JDoe_ssh.awardwallet.com.pem # path to your ssh key
    ProxyJump aw-ssh # or aw-infra
```

Дальнейшее подключение будет таким:
```shell
ssh aw-test-runner
ssh aw-staging
```

## База Test runner (frontend)

База данных для тест раннера, где бегут тесты https://jenkins.awardwallet.com/job/Frontend/job/tests/
```shell
ssh -L localhost:3310:0.0.0.0:3306 aw-test-runner 
```
Теперь на вашей локальной тачке открылся порт `3010`, который форвардит все на `3306` порт хоста test-runner, можно натравить на него любой клиент для mysql.

**Логин\пароль:** awardwallet\awardwallet

**База:** awardwallet

Конольный клиент:
```shell
mysql -h 127.0.0.1 -P 3310 -u awardwallet -p awardwallet -A
```
Ключ `-A` желателен, чтобы отключить загрузку клиентом (может быть долго) инфы для комплишна таблиц и полей.


## База Staging

База данных стейджинга, где бежит https://staging.awardwallet.com/
```shell
ssh -L localhost:3311:0.0.0.0:3306 aw-staging
```
Теперь на вашей локальной тачке открылся порт `3011` (разводим по разным портам `aw-test-runner` и `aw-staging`), который форвардит все на `3306` порт хоста `aw-staging`, можно натравить на него любой клиент для mysql.

**Логин\пароль:** awardwallet\awardwallet

**База:** awardwallet

Конольный клиент:
```shell
mysql -h 127.0.0.1 -P 3311 -u awardwallet -p awardwallet -A
```
Ключ `-A` желателен, чтобы отключить загрузку клиентом (может быть долго) инфы для комплишна таблиц и полей.


## Запуск локальной копии с базой на удаленном сервере

Создаем проброс портов с локальной тачки до удаленного сервера:

```shell
ssh -L localhost:3311:0.0.0.0:3306 aw-staging
```
или 
```shell
ssh -L localhost:3310:0.0.0.0:3306 aw-test-runner
```
или для linux (т.к. порт надо открыть на интерфейс)
```shell
ssh -L 172.17.0.1:3310:0.0.0.0:3306 aw-test-runner # aw-test-runner, порт доступен только с cетевого интерфейса docker
# или
ssh -L 0.0.0.0:3310:0.0.0.0:3306 aw-test-runner # aw-test-runner, порт доступен со всех сетевых интерфейсов (может быть опасно)
```
Останавливаем `docker`-сервисы
```shell
docker compose down
```
Редактируем `docker-compose-local.yml`:
```yaml
services:
    php:
        environment:
            - "MYSQL_HOST=host.docker.internal" # для Docker for Mac\Windows
#            - "MYSQL_HOST=172.17.0.1" # для linux укаазать ip хоста в подсети docker'а, обычно 172.17.0.1
            - "MYSQL_PORT=3310" # aw-test-runner
#            - "MYSQL_PORT=3311" # aw-staging
```
Запускаем `docker`-сервисы:
```shell
docker compose up
```



