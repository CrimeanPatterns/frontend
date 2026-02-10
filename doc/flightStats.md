# FlightStats API - Реализация

Логин на сайт FlightStats:

[PV](https://awardwallet.com/manager/passwordVault/get.php?ID=16473)


## Структура базы

### FlightInfo

```sql
create table FlightInfo(
	FlightInfoID int not null auto_increment,
	ProviderID int not null,
	FlightNumber varchar(20) not null,
	FlightDate datetime not null,
	CreateDate datetime not null,
	UpdateDate datetime not null comment 'Когда информацию достали из FlightStats API',
	Properties text comment 'Свойства, которые мы получили от FlightStats, массив PropertyCode=Value сериализованный в JSON',
	UpdatesCount int not null default 1 comment 'Сколько раз мы вызывали API для этого полета, для финансовой статистики',
	primary key(FlightID),
	foreign key(ProviderID) references Provider(ProviderID) on delete cascade,
	unique key(ProviderID, FlightNumber, FlightDate),
	index (FlightDate)
) engine=InnoDB comment='Кэшируемая информация из FlightStats API';

alter table TripSegment 
	add FlightInfoID int, 
	foreign key(FlightInfoID) references FlightInfo(FlightInfoID) on delete set null;
```

## Смешивание информации от WSDL и FlightStats

При получении ответа от WSDL, и записи его в базу - ищем, есть ли в таблице FlightInfo запись для этого полета. Если нет, или если она устарела - скачиваем информацию с FlightStats и записываем в FlightInfo. Далее при записи свойств перелета смешиваем данные из FlightInfo и от WSDL. Данные от FlightStats имеют приоритет.

## Точка смешивания

function SaveTrips, если ConfigValue(CONFIG_TRAVEL_PLANS), (это фронтенд), перед вызовом сохранением сегмента: AddTripSegment($tid, $ts), вызвать метод getFlightInfo сервиса описанного ниже.

## Поиск полета в FlightStats API

Для поиска нам нужны параметры:

1. IATA-Код провайдера (Provider.IATACode)
2. Номер полета (TripSegment.FlightNumber)
3. Дата вылета (TripSegment.DepDate)

Провайдера определяем следующим образом:

* Если поле TripSegment.AirlineName не пустое, то вызываем функцию getProviderByFuzzyName, чтобы по этому названию найти провайдера.
* Если пустое - используем Trip.ProviderID

В итоге, если:

* смогли найти провайдера
* у него есть код IATA
* есть номер полета

Вызываем API 

[https://developer.flightstats.com/api-docs/flightstatus/v2/flight](https://developer.flightstats.com/api-docs/flightstatus/v2/flight)

И пишем результат в FlightInfo. Если запись уже есть - обновляем Properties и UpdateDate, увеличиваем UpdatesCount.
Пустые ответы FlightStats писать как Properties = null. Нужно для статистики, сколько у нас отрицательных ответов, и для небоольшой оптимизации, чтобы не тратить время на смешивание информации, если мы видим что Properties = null.

Реализовать в виде сервиса, у которого можно вызвать метод:

```php
/**
search flight info by flight number
@return FlightInfo 
**/
public getFlightInfo($Provider provider, unixtime $flightDate, string $flightNumber)

/**
update existing flight info
@return FlightInfo 
**/
public updateFlightInfo(FlightInfo $flightInfo)
```

Должен вернуть сущность FlightInfo.

Либо пустой массив, если FlightStats ничего не вернул.

[Wiki свойств](https://redmine.awardwallet.com/projects/awwa/wiki/Standard_reservation_attributes_list#Air-Trip-Segment)

## Обновление информации по расписанию

Добавляем в semiHourlyJobs командду, которая найдет все FlightInfo, у которых FlightDate > now() and FlightDate < addate(now(), interval 3 hour) and UpdateDate > addate(now(), interval 3 hour) и обновит данные из FlightStats.

Если в результате обновления данные в таблице FlightInfo изменились (сравнить Properties до и после), то надо обновить все резервации с этим рейсом. Для этого ищем в базе все TripSegment c FlightInfoID равным этому, и обновляем полученную с FlightStats информацию. 

Сохранение обрамить в вызовы aw.diff.tracker, для рассылки писем об изменениях, по аналогии с AccountAuditor::save 

Использовать вызов метода updateFlightInfo вышеописанного сервиса

На начальном этапе не будут обновляться полеты, у которых FlightInfoID null, которые были записаны в базу ранее, но их число будет уменьшатся со временем, считаю допустимо.

## Рефакторинг getProviderByFuzzyName

getProviderByFuzzyName нужно вытащить из репозитория и оформить в виде отдельного сервиса, втащить в него как зависимость менеджер кэша, и закэшировать соответствия AirlineName => ProviderID. Кэшируем на пару часов для начала, в зависимостях указываем тэг Providers, чтобы при редактировании провайдеров - кэш инвалидировался. Отрицательный результат (нет соответствия) - тоже кэшируем.

## Очистка FlightInfo

Добавить в deleteOld.php запрос, удаляющий из FlightInfo записи, у которых FlightDate более чем на месяц в прошлом. Месяц - для того чтобы посчитать, сколько мы потратили денег за месяц.