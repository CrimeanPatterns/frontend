# browser extension

## Исходники

Исходники для всех браузеров общие, разные только скрипты сборки

- Обвязки для браузеров лежат в extension/compatible
- Общий код для всех браузеров в web/extension/main.js
- Так же используется web/extension/lib.js 

После изменений в коде увеличить номер версии в extension/compatible/manifest.json

## Обновление chrome

- Собрать 
```bash
grunt --gruntfile desktopGrunt.js build-chrome
```
- Перейти в 
v2:
https://chrome.google.com/webstore/developer/edit/lppkddfmnlpjbojooindbmcokchjgbib
v3:
https://chrome.google.com/webstore/developer/edit/elbkchakmaiinadjpnmdgpflpjogpgmb
https://chromewebstore.google.com/detail/awardwallet/elbkchakmaiinadjpnmdgpflpjogpgmb
- Нажать "Upload Updated Package", загрузить extension/build/chrome/packed.zip

# обновление edge

- Собрать 
```bash
grunt --gruntfile desktopGrunt.js build-edge
```
- Перейти в 
https://partner.microsoft.com/dashboard
под аккаунтом awllc@outlook.com
- Нажать Update напротив последнего Submission 
- Загрузить в раздел Packages extension/build/edge/packed/AwardWallet/edgeextension/edgeExtension.appx


## Обновление firefox

- Собрать 
```bash
grunt --gruntfile desktopGrunt.js build-firefox
```
- Перейти в
  https://addons.mozilla.org/en-US/developers/addon/awardwallet/edit
- залогиниться как sysadmin@awardwallet.com
- выбрать Upload a New Version
- загрузить extension/build/firefox/packed.zip
- закоммитить подписанный файл в web/extension/awardwallet-2.xx.xpi
- проверить что обновилась версия в extensionInstall.html.twig
- обновить прод 

# Сборка dev-версии, для работы на awardwallet.docker

```bash
grunt --gruntfile desktopGrunt.js build-chrome --dev
grunt --gruntfile desktopGrunt.js build-firefox --dev
```

Загрузите unpacked расширение в браузер соотвественно из extension/build/chrome или extension/build/firefox

# Установка dev-версии Safari экстеншена

- разархивировать /extension/dev/safari-awardwallet-dev.zip
- переместить AwardWallet.app в директорию Applications


# Инструкция для ревьюверов

You could test the extension by logging in to https://awardwallet.com with username extreview and password Prdrw5-35.

Click on "JetBlue Airways (trueBlue)" link, it should log you into the jetblue website in a new tab.