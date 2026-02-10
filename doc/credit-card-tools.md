# CREDIT CARD OPTIMIZATION TOOLS

## Общие принципы

Есть три инструмента для работы рекомендательной системы кредитных карт, опираясь на историю транзакций

- Аналитика расходов `Credit Card Spend Analysis` 
- Аналитика транзакций `Transaction Analyzer` 
- `Merchant Lookup Tool`, сложно сформулировать как его по-русски можно обозвать 

Все они сделаны с одной целью - рекомендовать пользователю самую оптимальную, с точки зрения зарабатывания миль, кредитную карту.

## Схема данных

https://drive.google.com/file/d/1J19QCkcBPUxsg3oTHw7YY_9YSS8q5mvS/view?usp=sharing


На нашей стороне есть статичная информация (схема кредитных карт https://awardwallet.com/manager/sonata/credit-card/list) о том 
какие карты мы можем офферить нашим пользователям, контролем этих данных занимается Эрик.
Здесь есть привязка `CreditCardID-ShoppingCategoryGroupID-Multiplier` (таблица `CreditCardShoppingCategoryGroup`) и 
`CreditCardID-MerchantGroupID-Multiplier` (таблица `CreditCardMerchantGroup`). Таким образом мы знаем какой мультипликатор
зарабатывает конкретная карта в категории или в конкретной группе мерчантов. 

Рекомендация (EarningPotential) формирует список карт, которыми полезно шопиться в конкретном мерчанте и сортирует полученный
список карт по максимальной эффективности на потраченный доллар, то есть берется mileValue по милям карты и умножается на мультипликатор,
известный из нашей схемы ``BankTransactionsAnalyser::findPotentialCards()``

Далее полученный список мы проверяем на достоверность и группируем их в 3 категории, описание каждой есть в тексте офера:
- Confirmed Cards
- Unconfirmed Cards
- Excluded Cards

Группируем с помощью отношения ``MerchantReport.ExpectedMultiplierTransactions``/``MerchantReport.Transactions``,
а также проверяем транзакции в кликхаус за последние 3 месяца, на основании чего принимаем решение в какую группу засунуть кредитку
``SpentAnalysisService::buildCardsListToOffer()``
