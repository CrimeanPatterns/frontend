# Тестирование

В контейнере запуск воркера:

```bash
php app/console rabbitmq:consumer -w task_scheduler -l 150 -vv --no-ansi
```

В ручную добавить перелет, до вылета которого остается 24 часа и 1 минута. Письмо/пуш отправятся минута в минуту. Для повторного тестирования можно отредактировать дату вылета предварительно обнулив TripSegment.CheckinNotificationDate

Автоматические тесты: `tests/unit/MainBundle/Service/FlightNotification/`