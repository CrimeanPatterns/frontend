# Модуль AIModel

## Описание
Модуль предоставляет унифицированный интерфейс для работы с различными провайдерами языковых моделей (OpenAI, DeepSeek, Claude). Обеспечивает отправку запросов, обработку ответов, пакетную обработку данных и автоматическое логирование всех взаимодействий с AI моделями.

## Основные возможности
- **Единый интерфейс** для работы с разными провайдерами AI
- **Пакетная обработка** больших объемов данных с автоматическим разбиением на батчи
- **Автоматический retry** при ошибках с экспоненциальной задержкой
- **Подсчет токенов и стоимости** запросов для каждого провайдера
- **Детальное логирование** всех операций
- **Обработка лимитов токенов** с автоматическим разделением батчей при превышении

## Структура модуля
```
AIModel/
├── AIModelService.php       # Главный сервис для работы с AI моделями
├── ProviderInterface.php    # Интерфейс провайдера
├── AbstractProvider.php     # Базовый класс провайдеров
├── RequestInterface.php     # Интерфейс запроса
├── AbstractRequest.php      # Базовый класс запросов
├── ResponseInterface.php    # Интерфейс ответа
├── AbstractResponse.php     # Базовый класс ответов
├── TokenCounter.php         # Утилита для подсчета токенов
├── BatchConfig.php          # Конфигурация пакетной обработки
├── BatchProcessingResult.php # Результат пакетной обработки
├── OpenAI/                  # Провайдер OpenAI
│   ├── Provider.php         # OpenAI провайдер
│   ├── Request.php          # OpenAI запрос
│   └── Response.php         # OpenAI ответ
├── Deepseek/                # Провайдер DeepSeek
│   ├── Provider.php         # DeepSeek провайдер
│   ├── Request.php          # DeepSeek запрос
│   └── Response.php         # DeepSeek ответ
├── Claude/                  # Провайдер Claude (Anthropic)
│   ├── Provider.php         # Claude провайдер
│   ├── Request.php          # Claude запрос
│   └── Response.php         # Claude ответ
└── Exception/               # Исключения модуля
    └── BatchProcessingException.php  # Исключение с поддержкой частичных результатов
```

## Использование

### Базовая отправка запроса
```php
// Получение сервиса через DI
/** @var AIModelService $aiService */
$aiService = $container->get(AIModelService::class);

// Простой запрос к OpenAI
$response = $aiService->sendPrompt(
    'Переведи этот текст на английский: Привет, мир!',
    'openai',
    [
        'model' => \AwardWallet\MainBundle\Service\AIModel\OpenAI\Request::MODEL_CHATGPT_4O_LATEST,
        'temperature' => 0.7
    ]
);

// Получение результата
$translatedText = $response->getContent();
$cost = $response->getCost();
$tokensUsed = $response->getPromptTokens() + $response->getCompletionTokens();
```

### Запрос к DeepSeek
```php
$response = $aiService->sendPrompt(
    'Объясни принцип работы нейронных сетей',
    'deepseek',
    [
        'model' => \AwardWallet\MainBundle\Service\AIModel\Deepseek\Request::MODEL_DEEPSEEK_CHAT,
        'temperature' => 0.5
    ]
);
```

### Запрос к Claude
```php
$response = $aiService->sendPrompt(
    'Напиши краткий обзор современных алгоритмов машинного обучения',
    'claude',
    [
        'model' => \AwardWallet\MainBundle\Service\AIModel\Claude\Request::MODEL_CLAUDE_SONNET_4,
        'temperature' => 0.3,
        'max_tokens' => 2048
    ]
);
```

### Работа с конкретным провайдером
```php
$claudeProvider = $aiService->getProvider('claude');

// Создание настроенного запроса с кэшированным системным сообщением
$request = $claudeProvider->createRequest('Анализируй этот отзыв о программе лояльности')
    ->withModel(\AwardWallet\MainBundle\Service\AIModel\Claude\Request::MODEL_CLAUDE_SONNET_4)
    ->withTemperature(0.3)
    ->withSystemMessage('Ты эксперт по программам лояльности авиакомпаний и отелей')
    ->withMaxTokens(2048);

$response = $claudeProvider->sendRequest($request);

// Проверка использования кэша
if ($response->getCachedTokens() > 0) {
    echo "Использовано кэшированных токенов: " . $response->getCachedTokens();
}
```

### Работа с кэшированием в Claude
```php
$claudeProvider = $aiService->getProvider('claude');

// Кэшированное системное сообщение (по умолчанию кэшируется)
$request = $claudeProvider->createRequest('Вопрос пользователя')
    ->withSystemMessage('Длинные инструкции для анализа...', true); // true = кэшировать

// Добавление дополнительных системных сообщений
$request = $request->addSystemMessage('Дополнительный контекст...', false); // false = не кэшировать

// JSON ответ (автоматически добавляет системное сообщение)
$request = $claudeProvider->createRequest('Анализируй и верни JSON')
    ->withJsonResponse(); // Добавляет инструкцию для JSON ответа
```

### Пакетная обработка данных
```php
// Подготовка данных для обработки
$data = [
    ['text' => 'Первый текст для анализа'],
    ['text' => 'Второй текст для анализа'],
    // ... много элементов
];

// Конфигурация пакетной обработки
$batchConfig = BatchConfig::create()
    ->withBatchSize(25)               // 25 элементов в батче
    ->withMaxTokensPerBatch(2500)     // Максимум токенов в батче
    ->withMaxRetries(5)               // Количество повторов при ошибке
    ->withRetryDelayBase(3);          // Базовая задержка между повторами

// Отправка пакетного запроса с автоматическим кэшированием системного сообщения
$result = $aiService->sendBatchJsonRequest(
    'Проанализируй каждый текст и верни JSON с результатом анализа',
    $data,
    'claude', // Claude автоматически кэширует системное сообщение
    $batchConfig,
    ['model' => \AwardWallet\MainBundle\Service\AIModel\Claude\Request::MODEL_CLAUDE_HAIKU_35]
);

// Получение результатов с информацией о кэшировании
$processedData = $result->getData();
$totalCost = $result->getCost(); // Учитывает скидки на кэшированные токены
$totalTokens = $result->getPromptTokens() + $result->getCompletionTokens();
$requestCount = $result->getRequestCount();

echo "Обработано запросов: {$requestCount}, стоимость: \${$totalCost}";
```

### Обработка ошибок
```php
use AwardWallet\MainBundle\Service\AIModel\Exception\BatchProcessingException;

try {
    $result = $aiService->sendBatchJsonRequest($systemMessage, $data, 'openai');
} catch (BatchProcessingException $e) {
    // Ошибка в процессе пакетной обработки
    echo "Ошибка пакетной обработки: " . $e->getMessage();
    $context = $e->getContext(); // Дополнительная информация об ошибке
    
    // Получение частичного результата, если обработка была частично завершена
    $partialResult = $e->getResult();
    
    if ($partialResult !== null) {
        $processedData = $partialResult->getData();
        $partialCost = $partialResult->getCost();
        echo "Частично обработано элементов: " . count($processedData);
        echo "Стоимость частичной обработки: $" . $partialCost;
    }
} catch (\Throwable $e) {
    // Любые другие ошибки (сетевые, валидации и т.д.)
    echo "Общая ошибка: " . $e->getMessage();
}
```

## Модели и тарифы

### OpenAI
```php
// Доступные модели (используйте константы)
use AwardWallet\MainBundle\Service\AIModel\OpenAI\Request as OpenAIRequest;

OpenAIRequest::MODEL_CHATGPT_4O_LATEST  // 'chatgpt-4o-latest'
OpenAIRequest::MODEL_CHATGPT_35_TURBO   // 'gpt-3.5-turbo-0125'

// Тарифы (за 1M токенов):
// chatgpt-4o-latest: $5 input, $15 output
// gpt-3.5-turbo-0125: $0.5 input, $1.5 output
```

### DeepSeek
```php
// Доступные модели (используйте константы)
use AwardWallet\MainBundle\Service\AIModel\Deepseek\Request as DeepSeekRequest;

DeepSeekRequest::MODEL_DEEPSEEK_CHAT    // 'deepseek-chat'

// Тарифы (за 1M токенов) с временными скидками:
// Обычное время: $0.27 input, $1.10 output
// Скидочное время (16:30-00:30 UTC): $0.135 input, $0.55 output
// Кэшированные токены: $0.07 обычное время, $0.035 скидочное время
```

### Claude (Anthropic)
```php
// Доступные модели (используйте константы)
use AwardWallet\MainBundle\Service\AIModel\Claude\Request as ClaudeRequest;

ClaudeRequest::MODEL_CLAUDE_SONNET_4    // 'claude-sonnet-4-20250514'
ClaudeRequest::MODEL_CLAUDE_OPUS_4      // 'claude-opus-4-20250514'
ClaudeRequest::MODEL_CLAUDE_HAIKU_35    // 'claude-3-5-haiku-20241022'
ClaudeRequest::MODEL_CLAUDE_SONNET_35   // 'claude-3-5-sonnet-20241022'

// Тарифы (за 1M токенов):
// claude-sonnet-4: $3 input, $15 output
// claude-opus-4: $15 input, $75 output
// claude-3-5-haiku: $0.8 input, $4 output
// claude-3-5-sonnet: $3 input, $15 output
```

## Логирование
Модуль автоматически логирует все операции:
- Начало и завершение запросов к AI
- HTTP статус коды ответов
- Количество использованных токенов
- Стоимость каждого запроса
- Детали ошибок с контекстом
- Прогресс пакетной обработки

## Кэширование промптов (Claude)

Claude поддерживает автоматическое кэширование промптов для значительной экономии токенов:

### Автоматическое кэширование
- **Системные сообщения** автоматически кэшируются при пакетной обработке
- **90% скидка** на кэшированные токены
- **Время жизни кэша**: ~5 минут
- **Создание кэша**: +25% к стоимости (окупается со 2-го запроса)

### Управление кэшированием
```php
// Кэшированное системное сообщение (по умолчанию)
$request->withSystemMessage('Инструкции...', true);

// Не кэшированное системное сообщение
$request->withSystemMessage('Инструкции...', false);

// Добавление нескольких системных сообщений
$request->withSystemMessage('Основные инструкции...', true)
        ->addSystemMessage('Дополнительный контекст...', false);
```

### Мониторинг кэширования
```php
$response = $provider->sendRequest($request);

// Проверка использования кэша
$cachedTokens = $response->getCachedTokens();
$cacheWriteTokens = $response->getCacheWriteTokens();

if ($cachedTokens > 0) {
    echo "Использовано кэшированных токенов: {$cachedTokens} (90% скидка)";
}

if ($cacheWriteTokens > 0) {
    echo "Записано в кэш: {$cacheWriteTokens} токенов (+25% к стоимости)";
}
```

## Подсчет токенов
`TokenCounter` реализует эвристические алгоритмы подсчета токенов:

### Для OpenAI (GPT):
- Среднее соотношение слово/токен: ~1.3
- Учитывается пунктуация и специальные символы
- Цифры считаются как отдельные токены
- Метод: `TokenCounter::countGptTokens($text)`

### Для DeepSeek:
- Базовое соотношение символ/токен: ~0.3
- Особая обработка для многоязычного контента
- Учет азиатских языков
- Метод: `TokenCounter::countDeepSeekTokens($text)`

### Для Claude:
- Многоязычный подсчет токенов с учетом типа символов
- Латинские символы: ~3.5 символа/токен
- Кириллица: ~2.8 символа/токен  
- CJK языки: ~1.5 символа/токен
- Арабский: ~2.5 символа/токен
- Метод: `TokenCounter::countClaudeTokens($text)`

## Расширение модуля

### Добавление нового провайдера
1. **Создайте директорию** провайдера в `AIModel/`
2. **Реализуйте классы**:
   ```php
   // NewProvider/Provider.php
   class Provider extends AbstractProvider
   {
       public function getName(): string { return 'newprovider'; }
       
       protected function doSendRequest(RequestInterface $request): ResponseInterface
       {
           // Логика отправки запроса
       }
       
       protected function getBatchOptions(string $systemMessage, array $additionalOptions): array
       {
           // Опции для пакетной обработки
       }
       
       protected function estimateTokens(string $item): int
       {
           // Подсчет токенов для провайдера
       }
       
       protected function checkResponse(ResponseInterface $response): void
       {
           // Проверка ответа на ошибки
       }
   }
   
   // NewProvider/Request.php
   class Request extends AbstractRequest
   {
       // Специфичные методы и константы модели
   }
   
   // NewProvider/Response.php  
   class Response extends AbstractResponse
   {
       // Специфичные методы для обработки ответа
   }
   ```

3. **Зарегистрируйте провайдер** в сервисном контейнере

### Конфигурация пакетной обработки
```php
$config = BatchConfig::create()
    ->withBatchSize(50)              // Размер батча (по умолчанию: 50)
    ->withMaxTokensPerBatch(3000)    // Лимит токенов на батч (по умолчанию: 3000)
    ->withMaxRetries(3)              // Количество повторов (по умолчанию: 3)
    ->withRetryDelayBase(2);         // Базовая задержка в секундах (по умолчанию: 2)
```

## Рекомендации по использованию

## Специфика провайдеров

### OpenAI
- **JSON режим**: Поддерживает `response_format: {"type": "json_object"}`
- **Системные сообщения**: Через `messages` с ролью `system`
- **Завершение**: `finish_reason` (`stop`, `length`, `content_filter`)

### DeepSeek  
- **Совместимость**: Использует OpenAI-совместимый API
- **JSON режим**: Поддерживает `response_format` как OpenAI
- **Скидочные часы**: 16:30-00:30 UTC (50% скидка)

### Claude
- **JSON режим**: Только через промпт-инжиниринг (`withJsonResponse()`)
- **Системные сообщения**: Отдельный параметр `system` (не в `messages`)
- **Завершение**: `stop_reason` (`end_turn`, `max_tokens`, `stop_sequence`, `tool_use`)
- **Кэширование**: Автоматическое кэширование системных сообщений в пакетах
- **Токены**: `input_tokens`/`output_tokens` + `cache_read_input_tokens`/`cache_creation_input_tokens`
- **Авторизация**: `x-api-key` заголовок (не `Authorization`)
- **Версия API**: `anthropic-version: 2023-06-01` заголовок

### Оптимизация затрат
- Используйте DeepSeek в скидочные часы (16:30-00:30 UTC)
- **Используйте Claude с кэшированием** для максимальной экономии при пакетной обработке
- Настраивайте размер батчей под лимиты токенов
- Кэшируйте результаты для повторяющихся запросов
- **Claude Haiku + кэширование** = самый экономичный вариант для простых задач

### Пакетная обработка
- Для больших объемов данных всегда используйте `sendBatchJsonRequest`
- Настройте `BatchConfig` под специфику ваших данных
- Обрабатывайте исключения для надежной работы

### Выбор провайдера
- **OpenAI**: Лучшее качество для сложных задач, широкая совместимость
- **DeepSeek**: Самые доступные цены, хорошо работает с кодом и многоязычным контентом
- **Claude**: Отличная обработка естественного языка, автоматическое кэширование, эффективен для аналитических задач

### Экономические рекомендации
- **Простые задачи**: Claude Haiku + кэширование
- **Массовая обработка**: Claude с кэшированными инструкциями  
- **Сложный анализ**: Claude Opus или OpenAI GPT-4
- **Бюджетные проекты**: DeepSeek в скидочные часы