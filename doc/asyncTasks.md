# Асинхронное выполнение php-замыканий

При помощи библиотеки [Opis Closure](https://github.com/opis/closure), костылей и такой-то матери удалось 
добиться поддержки асинхронного исполнения php-замыканий в воркере `\AwardWallet\MainBundle\Worker\AsyncProcess\Worker`

## Принцип работы

### Сериализация

Замыкание в php - это объект типа `\Closure`. С помощью рефлексии библиотека извлекает php-код замыкания, сериализует параметры, захваченные через `use ($var1, ...)`, разворачивает в FQCN все импорты, подписывает получившиеся данные через `hash_hmac()`. К этим данным добавляются аргументы, все вместе это сериализуется и задача помещается в очередь на исполнение:

> Ограниченные возможности рефлексии функций позволяют получить только строку объявления замыкания в коде, но не позицию первого символа. 
> Поэтому сериализация будет работать только для тех функций, объявление которых является единственным в строке. В случае наличия нескольких
> замыканий в строке, будет примерно тоже, что и с [goto](https://imgs.xkcd.com/comics/goto.png)
>
> Старайтесь использовать только примитивные типы(числа, строки, массивы) для use-переменных.
> 
> Не пробрасывайте сервисы и сложные объекты в замыкание, для этого есть специальный механизм

### Десериализация и исполнение
Воркер получает сериализованную задачу, десериализует ее(в этот момент библиотека инклудит код замыкания с помощью кастомного `StreamWrapper`'а, получая объект типа `\Closure`), проверяет подпись и соответствие десериализованного кода с оригинальным. Далее замыканию через `\Closure::bindTo` восстанавливается scope-контекст (static) из места объявления.

Работает autowiring: из контейнера извлекается сервис по FQCN класса

```php
use AwardWallet\MainBundle\Worker\AsyncProcess\Callback\Parameter;
use AwardWallet\MainBundle\Worker\AsyncProcess\Callback\Service;

$task = new CallbackTask(
    function (
        HttpService $httpService, // автовайринг
        EntityManagerInterface $em, // автовайринг
        СallbackTaskExecutor $executor, // автовайринг
        /** @Service("monolog.logger.payment") */
        LoggerInterface $logger, // явное указание сервиса
        /** @Parameter("aw.some_service.api_key") */ // упадет, если не указать что это за параметр, т.к. попытается найти сервис по типу.
        string $apiKey
    ) use ($entityId) {
        $entity = $em->getRepository('SomeRepo')->find($entityId);
        $httpService->get('/some/endpoint', ['api' => $apiKey, 'name' => $entity->getName()]);
        
        if (someErrCondition(...)) {
            // откладываем выполнение задачи на 5 минут
            $executor->delayTask(5 * 60);
        } elseif (someRecurringErrCondition(...)) {
            // откладываем выполнение задачи с экспоненциальным увеличением времени
            $executor->expBackoffDelayTask(5);
        }
    }
);
```
Можно заинъектить зависимость `СallbackTaskExecutor` (см. пример выше) у которого есть следующие полезные методы:
```php
	...
	public function delayTask($seconds); // отложить выполнение задачи на $seconds секунд

	public function expBackoffDelayTask($maxExp, \Exception $lastException = null, $expBase = 2); 
	// откладывать выполнение задачи с возрастанием времени по экспоненте(показатель в пределах от 0 до $maxExp, основание - $expBase). 
	// В случае превышения лимитов времени бросится TaskRetriesExceededException c проставлением $lastException в цепочку предыдущих
	...
```


После приготовлений замыкание вызывается с прошедшими обработку аргументами. Всплывшие исключения ловятся и логируются в `critical` канал логгера.

> *В момент исполнения замыкание отвязано от `$this` контекста, в котором оно было объявлено!*

> *Будьте внимательны используя сервисы, зависимые от реквеста. В контексте воркера никаких реквестов нету!*


## Примеры использования

### Выстрелили и забыли

Таска сериализуется и отправится в очередь:
```php
    use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
    ...
    
    $container->get(Process::class)->execute(new CallbackTask(function (SomeService $someService, СallbackTaskExecutor $executor) use ($someContextVar) {
        $someService->someResourceHungryTask($someContextVar);
    }));
    ...
```

### Попробовали один раз, если не получилось - попробуем еще несколько

Таска выполнится один раз в том месте, где она была объявлена. В случае неудачи выполнится воркером 6 раз через 1, 2, 4, 8, 16, 32 секунды:
```php
	...
	$container->get('aw.async.executor.callback')->execute(new CallbackTask(function (SomeService $someService, СallbackTaskExecutor $executor) use ($some) {
		try {
			$someService->someExternalServiceCallingTask($some);
		} catch (SomeDomainException $e) {
			$executor->expBackoffDelayTask(5, $e);
		}
	}));
	...
```

### Пробуем до победного!

Таска выполнится один раз в том месте, где она была объявлена. В случае неудачи таска будет выполняться каждые 5 минут, пока не добежит:
```php
	...
	$container->get('aw.async.executor.callback')->execute(new CallbackTask(function (SomeService $someService, СallbackTaskExecutor $executor) use ($some) {
		try {
			$someService->someExternalServiceTask($some);
		} catch (SomeDomainException $e) {
			$executor->delay(5 * 60);
		}
	}));
	...
```

### Вариативность в выборе замыкания

Если нужна вариативность в выборе замыкания (т.е. без явной передачи замыкания в таску), то можно использовать аннотацию `@Autowire` для автоматического инъекта зависимостей в замыкание.

```php
    use AwardWallet\MainBundle\Worker\AsyncProcess\Callback\Autowire;
    use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
    ...
    if ($condition) {
        $closure = /** @Autowire */ function (SomeService $someService) use ($someContextVar) {
            $someService->someResourceHungryTask($someContextVar);
        };
    } else {
        $closure = /** @Autowire */ function (SomeService $someService) use ($someContextVar) {
            $someService->someOtherResourceHungryTask($someContextVar);
        };
    }
    
    $container->get(Process::class)->execute(new CallbackTask($closure));
    ...
```
