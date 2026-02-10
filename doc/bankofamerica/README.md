Bank of America API 
---------------------

CloudHSM certificate update instructions.

Certificates and keys stored in ~/Dropbox/AWSysAdmin/BankOfAmerica/CloudHSM/
Password from private key stored in AW SysAdmin KeePass, under "CloudHSM private key password"

Also you could find all these certs on the instance/image "cloudhsm", in folder /opt/aw-cloudhsm-certs

Загрузка нового ключа в hsm
---------------------------
https://docs.aws.amazon.com/cloudhsm/latest/userguide/ssl-offload-import-or-generate-private-key-and-certificate.html#ssl-offload-import-private-key

Ключ должен быть без пароля.

Обновление
----------
1. Восстановить кластер из бэкапа
2. Внутри восстановленного кластера запустить инстанс hsm
2. Запустить ec2 инстанс cloudhsm и залогиниться на него
3. Скопировать новые сертификаты на инстанс:
   ```shell
   rsync ~/Downloads/awardwallet.com\ 2/* 192.168.2.198:/opt/aw-cloudhsm-certs/2023/
   ```  
3. Проверить modulus ключа:
   ```shell
   openssl x509 -noout -modulus -in  certificate.pem
   ```
3. Выполнить на нем 
    ```shell
   sudo /opt/cloudhsm/bin/configure -a 192.168.2.71 # адрес нового HSM
   sudo service cloudhsm-client restart
   /opt/cloudhsm/bin/key_mgmt_util 
    ```
4. Внутри открывшегося промпта выполнить
    ```shell
   getHSMInfo
    ```
   и сохранить скриншот
5. Открыть пароли в админском кипассе по слову HSM, найти пароль CU
6. Залогиниться:
   ```shell
   loginHSM -u CU -s bofa -hpswd
   ```
7. Создать wrapping key для загрузки сертификата:
```shell
genSymKey -t 31 -s 24 -l tmpAES -id wrap01 -nex -sess
```
8. Импортировать private key используя созданный wrapping key:
```shell
importPrivateKey -f 2024_06_25_awardwallet_passless.key -l key2025 -w 1048584
```
7. Получить modulus сохраненного в HSM ключа:
   ```shell
   getAttribute -o 9 -a 512 -out /tmp/getAttribute2023.txt
   ```
   и сохрани скриншот запроса
8. Сделать скриншот ответа
   ```shell
   cat /tmp/getAttribute2023.txt
   ```
8. Поменять сертификат на cloudfront, проверить curl что новый сертификат применился.
9. Сравнить OBJ_ATTR_MODULUS с public key в информации о сертификате сайта:
```shell
openssl s_client -connect awardwallet.com:443 -servername awardwallet.com 2>/dev/null | openssl x509 -noout -modulus
```
9. Сделать бэкап hsm кластера
10. Удалить HSM
10. Остановить инстанс cloudhsm
   