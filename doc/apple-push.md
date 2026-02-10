## Генерация сертификата для отправки apple пушей (параметр push_notifications.ios.awardwallet_team.pem_path)

Выполните при получении письма:

Action Needed: Apple Push Services Certificate Expires in 30 Days
Your Apple Push Services Certificate will no longer be valid in 30 days. 

To generate a new certificate, sign in and visit Certificates, Identifiers & Profiles.

Certificate: Apple Push Services
Identifier: com.awardwallet.iphone
Team ID: J3M2LK2HFC 

## Обновление

https://developer.apple.com/account/resources/certificates/add

Certificates, Identifiers & Profiles 
    -> Create a New Certificate
    -> Apple Push Notification service SSL (Sandbox & Production) 
    -> J3M2LK2HFC.com.awardwallet.iphone

- Импортировать сертификат в Keychain Assistant
- Найти его там по com.awardwallet.iphone, "login" или "System" keychain, вкладка "Certificates"
- Экспортировать как .p12. Если экспорт в p12 недоступен - поискать сертификат именно поиском по "com.awardwallet.iphone", оттуда доступен.
- взять пароль для сертификата "aws ssm get-parameter --name /frontend/prod/ios_push_passphrase_2023_09_16 --with-decrypt" 
- Запустить util/certificates/create-apple-push-certificates.sh <.p12 file> внутри контейнера php.
- Указать пароль для сертификата
- Поменять push_notifications.ios.awardwallet_team.pem_path in parameters-prod.yml
- Поменять push_notifications.ios.awardwallet_team.pem_passphrase
- удалить лишний .p12 с s3
- выкатить воркер на бету, посмотреть что пуши идут
