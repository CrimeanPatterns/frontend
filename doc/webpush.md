# Safari

## Дебаг

1. Запустить ngrok

```bash
ngrok http --host-header rewrite awardwallet.docker:80
```

2. Добавить хост xxx.ngrok.io в parameters.yml как host. Внимание, некоторые ссылки все равно могут вести на awardwallet.docker.

3. Если нестандартный website push id, прописать его в параметр webpush.id

4. Зафорсить https режим: добавить
    ```yaml
    parameters:
        requires_channel: https
    ```
    в app/config/local_dev.yml
 
5. Скопируйте сертификат указанный в webpush.safari_cert_path с прода, пропишите его в parameters.yml. Так же скопируйте webpush.safari_cert_pass. 
5. Сделайте тоже самое для параметров push_notifications.mac.path и push_notifications.mac.pem_passphrase 
5. Пересобрать кэш
6.  Подпишитесь на пуши на странице /account/list - Safari должен спросить разрешения на подлписку
7. Отправьте тестовый пуш:
    ```bash
    app/console rabbitmq:consumer -l 150 -w -m 0 push_notification_sender_ios -vv 
    ```
 
## Генерация сертификата для отправки web пушей (параметр webpush.safari_cert_path) 

Когда получаем письмо 

Your Website Push Certificate will no longer be valid in 30 days

Certificate: Website Push
Identifier: web.com.awardwallet
Team ID: J3M2LK2HFC

https://developer.apple.com/account/resources/certificates/add

Certificates, Identifiers & Profiles -> Create a new certificate -> Services -> Website Push ID Certificate -> J3M2LK2HFC.web.com.awardwallet

- Импортировать сертификат в Keychain Assistant
- Экспортировать как .p12 (экспорт доступен из вкладки Certificates). Иногда чтобы экспорт стал доступен - надо удалить сертификат
  из Keychain Assistant и импортировать снова. Запишите в папку data/, чтобы сертификат был читаем из контейнера.
- Запустить в контейнере util/certificates/create-apple-push-certificates.sh <.p12 file>
- Указать пароль для скрипта отсюда https://us-east-1.console.aws.amazon.com/systems-manager/parameters/frontend/prod/webpush_safari_cert_pass/description?region=us-east-1&tab=Table#list_parameter_filters=Name:Contains:webpush_safari_cert_pass
- В конце выполнения скрипты напишет команды копирования (aws ..) - надо выполнить их.
- Поменять param webpush.safari_cert_path in parameters-prod.yml
- Поменять param push_notifications.mac.pem_path

Можно потестировать на одном пользователе (2110 по умолчанию), указав новый сертификат 
в параметре webpush.safari_beta_cert_path:

https://github.com/AwardWallet/frontend/blob/833531409a851fd4eecdec4c022c365178ba8e9a/bundles/AwardWallet/MainBundle/Service/WebPush/SafariPackageBuilder.php#L78-L78

Внимание, проверить какой issuer у нового сертификата, (Get Info в Keychain Assistant)
и скачать соответсвующий сертификат из раздела Intermediate Certificates отсюда:

https://developer.apple.com/account/resources/certificates/add

Поправить код, чтобы он использовал этот intermediate:

https://github.com/AwardWallet/frontend/blob/833531409a851fd4eecdec4c022c365178ba8e9a/bundles/AwardWallet/MainBundle/Service/WebPush/SafariPackageBuilder.php#L76-L76

Проверить что отправка работает по логам:
https://kibana.awardwallet.com/app/discover#/?_g=(filters:!(),refreshInterval:(pause:!t,value:0),time:(from:now-15m,to:now))&_a=(columns:!(_source),filters:!(),index:f7bcf3e0-1a67-11e9-8067-9bee5e3ddf43,interval:auto,query:(language:kuery,query:'message:%22safari%20push%20successfully%20sent%22'),sort:!())
и отправкой тестового пуша из Profile > Notifications

## Генерация сертификата для отправки ios пушей 

Смотри apple-push.md

# Chrome

1. получить dev-ключ к гугл апи
2. google api key прописываем в parameters.yml: push_notifications.android.gcm.api_key
3.  достаешь из базы прода нужные тебе идшники устройств
4. добавляешь их локально
5. запускаешь воркеры, которые шлют
6. триггеришь событие посылки

