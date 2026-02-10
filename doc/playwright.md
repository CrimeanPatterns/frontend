Playwright
-----------

Структура папок
=================
- **tests**
    - **playwright** - рабочая папка, все команды выполняются отсюда
      - **tests** - папка с тестами
        - summary.**Cest.php** - php тест подготавливающий данные (базу) для playwright теста
        - summary.**data.json** - подготовленные данные, которые можно использовать внутри playwright теста, например логин пользователя
        - summary.**spec.ts** - playwright тест

Установка playwright
=====================
На хосте (не в контейнере):
```bash
cd tests/playwright/
npm install
npx playwright install
```

Подготовка данных для тестов
=============================
В контейнере php:
```bash
vendor/bin/codecept build
vendor/bin/codecept run tests/playwright
```
Тестовые данные будут сохранены в файлы вида tests/playwright/tests/summary.data.json

Запуск тестов, на хосте (не в контейнере):
===========================================
```bash
cd tests/playwright/
npx playwright test --headed --reporter dot
```
