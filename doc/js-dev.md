# Front-end scripts

## Node.js scripts

В файле `package.json` есть следующие скрипты:

- `dev` - для сборки проекта в режиме developer и следить за изменениями в файле;
- `build` - для сборки проекта в режиме production.

Что конкретно делает каждый скрипт можно узнать в файле `package.json` в поле "scripts".

Все скрипты из `package.json` могут быть вызваны с помощью `yarn`, например вызов скрипта `dev`: `yarn run dev`;

## Yarn

**Общие сведения**
Yarn — это менеджер пакетов для JavaScript, созданный для эффективного управления зависимостями в проектах. Он предоставляет быстрый и надежный способ установки и управления библиотеками и пакетами JavaScript.

**Основные скрипты Yarn**

- `yarn install` - устанавливает все зависимости, перечисленные в файле package.json;

- `yarn add название пакета` - устанавливает определенный пакет, добавляет зависимость в `package.json`;

- `yarn remove название пакета` - удаляет определенный пакет, убирает зависимость в `package.json`;

- `yarn upgrade` - используется для обновления пакетов до их последних версий на основе диапазонов версий, указанных в вашем файле `package.json`;

**Про обновление версий**
Yarn обновляет пакеты до ограниченных версий, указанные в `package.json`. Если указан конкретный диапазон версий (например, "^1.0.0"), `yarn upgrade` попытается обновить до последней версии в этом диапазоне.

Если ограничения версий позволяют, Yarn может обновиться до последней патч- или минорной версии, но он не обновит автоматически до новой мажорной версии, если явно этого не разрешили в ваших ограничениях версий.

Если нужно обновить мажорную версию пакета, может потребоваться изменить ограничение версии в `package.json`. Например, изменение "^1.0.0" на "^2.0.0" позволит Yarn обновиться до 2 мажорной версии.

## FAQ

#### Ошибка в сборке webpack. Error: Cannot find module.

Добавились новые пакеты в проект, которые не установлены.

```shell
Error: Cannot find module 'eslint-webpack-plugin'
Require stack:
- /www/awardwallet/webpack.config.cjs
- /www/awardwallet/node_modules/webpack-cli/lib/webpack-cli.js
- /www/awardwallet/node_modules/webpack-cli/lib/bootstrap.js
- /www/awardwallet/node_modules/webpack-cli/bin/cli.js
- /www/awardwallet/node_modules/webpack/bin/webpack.js
- /www/awardwallet/node_modules/@symfony/webpack-encore/bin/encore.js
```

**Решение**
Нужно запустить `yarn install`. После этого запустить сборку еще раз.

#### Ошибка в установленной версии Node.js

Версия Node.js должна соответствовать версии указной в `package.json`.

```shell
The engine "node" is incompatible with this module.
Expected version ">=18.0.0". Got "16.0.0"
```

**Решение**
Установить Node.js нужной версии.
