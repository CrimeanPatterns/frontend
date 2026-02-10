# Краткая инструкция

#### Почему не стоит использовать `AwardWallet\MainBundle\Worker\AsyncProcess\Process::execute`?

1. Неочевидный нейминг `Process::execute -> ExecutorInterface::execute`;
2. Использование в качестве хранилища данных Memcached, который не гарантирует сохранность данных на указанный срок;
3. Нерабочий механизм delay от 24 часов. Delay < 24 часов не гарантирует выполнение задачи.

### AwardWallet\MainBundle\Service\TaskScheduler\TaskInterface и AwardWallet\MainBundle\Service\TaskScheduler\Task

Убраны лишние свойства $method и $parameters. Автоматическая генерация $requestId. 

### AwardWallet\MainBundle\Service\TaskScheduler\ConsumerInterface

Замена `AwardWallet\MainBundle\Worker\AsyncProcess\ExecutorInterface` с методом `consume`, принимающим единственный аргумент - объект `TaskInterface`. Метод не должен возвращать никаких значений.


### AwardWallet\MainBundle\Service\TaskScheduler\Producer

Замена `AwardWallet\MainBundle\Worker\AsyncProcess\Process`. Метод `publish` принимает объект `TaskInterface`, задержку в секундах (если она нужна) а так же приоритет в очереди. Максимальная задержка 47 дней. 

### AwardWallet\MainBundle\Service\TaskScheduler\TaskNeedsRetryException

В своем Consumer можно выбрасывать это исключение, чтобы задача была помещена в очередь повторно с определенной задержкой.

### Запуск воркера в бакграунде

```bash
  php app/console rabbitmq:consumer -w task_scheduler -l 150 -vv;
```