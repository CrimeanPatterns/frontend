Стиль кода
==========

Придерживаемся PSR-1/PSR-2

Почитать можно здесь: https://www.php-fig.org/psr/psr-2/*
Чтобы PHPStorm правильно форматировал код нужна небольшая настройка: http://laraveldaily.com/how-to-configure-phpstorm-for-psr-2/

Комментарии в коде
------------------

Используйте phpdoc /** ... для документирования  методов и свойств. 
Если аргументы уже типизированы, то дублировать типы в phpdoc не рекомендуется. 

Запрещены автокомментарии на создание файла шторма и подобных, типа:
```php
/**
 * Created by PhpStorm.
 * User: VPupkin
 * Date: 01.09.15
 * Time: 12:46
 */
```
Все это можно увидеть в blame кому интересно.

Комментарии к таблицам и полям в базе данных
---------------------------------------------

- Таблицы и поля в них - каждое слово с большой буквы, например ItineraryOffer. 
- Название должно быть в единсвенном числе, то есть ItineraryOffers - неправильно.
- В таблице должен присутствовать первичный ключ, <Имя таблицы>ID, для этого примера ItineraryOfferID int not null auto_increment.
- Таблицы должны быть type=InnoDB, charset=utf8
- Обязательно foreign keys, если есть поля указывающие на ключи других таблиц

Тернарные операторы
-------------------

Тернарные операторы и операторы объединения с null не должны использоваться ни для чего кроме извлечения и выбора данных для присвоения.

Неправильно:
```php
$currentUser->isAdmin() ? $allowedOperations = $allOperations : $allowedOpeartions = $userOperations;
```
Правильно:
```php
$allowedOperations = $currentUser->isAdmin() ? $allOperations : $userOperations;
```
Неправильно:
```php
$isInitializedByUser ? $this->doUserUpdate() : $this->doBackgroundUpdate()
```
Правильно:
```php
if ($isInitializedByUser) {
    $this->doUserUpdate();
} else {
   $this->doBackgroundUpdate()
}
```

Избегать вложенности в тернарных операторах
--------------------------------------------

Неправильно:
```php
$variable = $condition1 ? $condition2 ? $option1 : $option2 : $option3;
```
Правильно:
```php
if ($condition1) {
    if ($condition2) {
        $variable = $option1;
    } else {
        $variable = $option2;
    }
} else {
    $variable = $option3;
}
```

Объекты должны отвечать за валидность своего состояния с момента создания
--------------------------------------------------------------------------

Все создаваемые объекты должны быть валидными всегда и сразу. Объект через конструктор должен получать все обязательные свойства и сам следить за валидностью своего состояния при мутациях.
Валидность: 

Неправильно:
```php
Class Entity
{
    /**
    * @var Owner
    */
    private $owner;

    /**
    * @var Account|null
    */
    private $account;

    /**
    * \DateTimeImmutable
    */
    private $created;

    public function __construct() 
    {
        //I don't actually construct anything
    }

    public function setOwner(Owner $owner): void
    {
        $this->owner = $owner;
    }

    public function setAccount(?Account $account): void
    {
        $this->account = $account;
    }

    public function setCreated(\DateTimeImmutable $created): void
    {
        $this->created = $created;
    }
}
```

Правильно:
```php
Class Entity
{
    /**
    * @var Owner
    */
    private $owner;

    /**
    * @var Account|null
    */
    private $account;

    /**
    * \DateTimeImmutable
    */
    private $created;

    public function __construct(Owner $owner, ?Account $account) 
    {
        $this->owner = $owner;
        $this->account = $account;
        $this->created = new \DateTimeImmutable();
    }
}
```

Использовать строгую типизацию в коде
--------------------------------------

https://secure.php.net/manual/en/functions.arguments.php#functions.arguments.type-declaration.strict 

Объявлять типы аргументов и возвращаемых значений у функций и методов

Неправильно:

```php
public function doSomething($object, $string);
```

```php
/**
 * @return mixed|string|int|object
 */
public function getSomething(Object $object, string $string);
```

Правильно:

```php
public function doSomething(Object $object, string $string): void;
```

```php
public function getSomething(Object $object, string $string): int;
```

Использовать минимально необходимую область видимости при объявлении методов и свойств
--------------------------------------------------------------------------------------

Не объявлять методы и свойства protected, если вы явно не подразумеваете их перекрытие в дочерних классах
Не объявлять public, если вы не предполагаете чтобы этот метод использовали снаружи.

Порядок методов в классах
--------------------------
- первым идет конструктор
- потом публичные методы
- потом protected / private

Контроль версий
----------------

- Названия веток не должны включать заглавные буквы.
- Коммитить надо как можно чаще (не реже чем раз в день) и как можно атомарнее (каждый коммит должен иметь какую-то законченную цель). 
- Сообщения к коммитам пишутся на английском языке.
- Сообщения начинаются с "refs #<task id>" (e.g. "refs #12345 implement a great feature and save the World").
- Учитывайте что после коммита в папку engine - эта папка на проде обновляется автоматически, сразу после коммита.

Использовать константы при обращении к элементам глобальных массивов (session, request->attributes)
----------------------
Было
```php
if ($request->attributes->has('LogonLongtimeDays')) {
    $session->set('ShowPoup', true);
}
```
Стало
```php
if ($request->attributes->has(self::ATTR_LOGON_LONGTIME_DAYS)) {
    $session->set(self::SESSION_KEY_SHOW_POPUP, true);
}
```
Проще потом искать и рефакторить, так как эти ключи обычно размазаны по всему проекту, и бывает совпадают с другими текстовками.