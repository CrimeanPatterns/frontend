# Credit Card Jobs

### CompleteTransactionsCommand

``bundles/AwardWallet/MainBundle/Command/CreditCards/CompleteTransactionsCommand.php``

Команда пробегает по записям таблицы ``AccountHistory``, детектит категорию, мерчанта и заполняет поля для них (``MerchantID``, ``ShoppingCategoryID``).  
Дополнение этих полей происходит также налету во время сохранения истории подаккаунта, ежедневный прогон этой команды больше для подстраховки.
Также раз в неделю проходит полный рекомплит всех транзаций, запуск с параметром ``--update``, в Jenkins отдельная джоба ``complete-transactions-weekly-full``

### FillCardsCommand

``bundles/AwardWallet/MainBundle/Command/CreditCards/FillCardsCommand.php``

Бежит по таблице ``SubAccount`` и заполняет поле ``CreditCardID`` с помощью матчинга в схему кредитных карт ``CreditCard`` по полю ``Patterns``.
Также механизм матчинга реализован налету на уровне сохранения субаккаунта

### ReportBuilderCommand

``bundles/AwardWallet/MainBundle/Command/CreditCards/ReportBuilderCommand.php``

Команда построения основной аналитики по мерчантам. 
Собирает и подсчитывает все возможные срезы Merchant - CreditCard - Category, делает выводы ожидаемые ли мультипликаторы по транзакциям и обновляет общее число транзакций по каждому Merchant 
Вся аналитика строится на запросах в clickhouse

### FillMerchantCategoryCommand

``bundles/AwardWallet/MainBundle/Command/CreditCards/FillMerchantCategoryCommand.php``

Самая страдальная и костыльная команда, которая занимается самым важном во всей это "истории". 
Принимает решение и проставляет категорию для конкретного мерчанта. На основании этого решения строятся наши тулза во frontend, в которых мы делаем рекомендательные офферы об использовании той или иной кредитки в конкретном магазине

### MerchantCleanerCommand

``bundles/AwardWallet/MainBundle/Command/CreditCards/MerchantCleanerCommand.php``

Команда чистки таблицы ``Merchant``. Поскольку мы постоянному улучшаем логику детекта мерчантов и Таня описывает их с помощью регулярок, то возникла необходимость удаления мерчантов, но которых нет транзакций, поскольку транзакции схлопнулись на более понятный мерчант

### ReportUnknownCardsCommand

``bundles/AwardWallet/MainBundle/Command/CreditCards/ReportUnknownCardsCommand.php``

Небольшая команда-отчет, сечасй почти не используем, но иногда помогает.
Выводит stdout список банковский субаккаунтов, которые мы не смогли сматчить ни в одну из кредиток, существующих у нас в схеме.
Эрик обычно по нему пробегал иногда и делал выводы по добавлению карт или исправлению регулярок. 
В Jenkins не добавлял ее, прогонял консольно на staging