## Использование

Каждый класс представляет отдельную таблицу в БД.
Для каждого класса есть свой сохранятор в модуле
DbBuilder с методом `make{Entity}`.
Возможно каскадное сохранение сущностей.

```php
use AwardWallet\Tests\Modules\DbBuilder\User;

$userId = $this->dbBuilder->makeUser($user = new User());
$userId = $user->getId();
```
Данный код создает юзера и предлагает нескоько вариантов получения его ID.
<hr>

```php
use AwardWallet\Tests\Modules\DbBuilder\Account;
use AwardWallet\Tests\Modules\DbBuilder\AccountProperty;
use AwardWallet\Tests\Modules\DbBuilder\Provider;
use AwardWallet\Tests\Modules\DbBuilder\ProviderProperty;
use AwardWallet\Tests\Modules\DbBuilder\SubAccount;
use AwardWallet\Tests\Modules\DbBuilder\User;

$accountId = $this->dbBuilder->makeAccount(
    new Account(
        new User(null, true),
        new Provider('Test Provider', [
            'Kind' => PROVIDER_KIND_HOTEL,
        ]),
        [
            new AccountProperty($pp = new ProviderProperty('status'), 'Gold'),
        ],
        [],
        [
            new SubAccount('sub1', 100, [
                new AccountProperty($pp, 'Silver')
            ]),
        ]
    )
);
```
Создается аккаунт с юзером, провайдером отелем, свойством и субаккаунтом.
<hr>

```php
use AwardWallet\Tests\Modules\DbBuilder\Account;
use AwardWallet\Tests\Modules\DbBuilder\Provider;
use AwardWallet\Tests\Modules\DbBuilder\Rental;
use AwardWallet\Tests\Modules\DbBuilder\TravelPlan;
use AwardWallet\Tests\Modules\DbBuilder\User;

$rentalId = $this->dbBuilder->makeRental(
    (new Rental(
        '001',
        'Moskow',
        new \DateTime('+1 day'),
        'Moskow',
        new \DateTime('+4 day'),
        $user = new User()
    ))
        ->setAccount(
            new Account(
                $user,
                new Provider('Test Provider')
            )
        )
        ->setTravelPlan(
            new TravelPlan(
                'New Travel Plan',
                new \DateTime('+1 day'),
                new \DateTime('+4 day'),
                $user
            )
        )
);
```
Rental, собранный с аккаунта, внутри травел плана
<hr>

Любая сущность максимально гибка в настройке за счет $fields, которые передаеются в конструкторе.

Уже созданную сущность можно передавать в make{Entity} метод повторно. Новая сущность создаваться не будет,
т.к. в fields уже прописаны первичные ключи. Однако, если до повторного вызова
make{Entity} тестируемый код вносил изменения в базу, то они будут перезаписаны, т.к. никакого механизма
синхронизации нет.

## Добавление новых сущностей

Для этого нужно создать сущность одноименную в виде класса и метод make{Entity} в DbBuilder.

При создании сущности есть несколько правил. Рассмотрим на конкретном примере:

```php
namespace AwardWallet\Tests\Modules\DbBuilder;

class UserAgent extends AbstractDbEntity
{
    private ?User $agent;

    private ?User $client;

    public function __construct(?User $agent = null, ?User $client = null, array $fields = [])
    {
        parent::__construct(array_merge([
            'IsApproved' => 1,
        ], $fields));

        $this->agent = $agent;
        $this->client = $client;
    }

    public function getAgent(): ?User
    {
        return $this->agent;
    }

    public function setAgent(?User $agent): self
    {
        $this->agent = $agent;

        return $this;
    }

    public function getClient(): ?User
    {
        return $this->client;
    }

    public function setClient(?User $client): self
    {
        $this->client = $client;

        return $this;
    }

    public static function familyMember(
        User $user,
        ?string $fn = null,
        ?string $ln = null,
        ?string $email = null
    ): self {
        return new self(
            $user,
            null,
            [
                'FirstName' => $fn,
                'LastName' => $ln,
                'Email' => $email,
                'IsApproved' => 1,
            ]
        );
    }
}
```

1) Все сущности наследуются от `AbstractDbEntity`.
2) Все свойства сущности представляют собой внешние связи, т.е. поле, 
   куда будет прописан ID связанной сущности.
3) Все свойства сущности опциональны. Для того, чтобы можно было ID прописывать через $fields.
4) Геттеры/сеттеры для опциональных свойств.
5) Сеттеры должны возвращать self.
6) Можно создавать фабричные методы как в примере.

<hr>

```php
class TravelPlan extends AbstractDbEntity implements OwnableInterface
{
    /**
     * @var User|UserAgent|null
     */
    private $user;

    public function __construct(
        string $name,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        $user = null,
        array $fields = []
    ) {
        parent::__construct(array_merge($fields, [
            'Name' => $name,
            'StartDate' => $start->format('Y-m-d H:i:s'),
            'EndDate' => $end->format('Y-m-d H:i:s'),
        ]));

        $this->user = $user;
    }

    /**
     * @return User|UserAgent|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User|UserAgent|null $user
     */
    public function setUser($user): self
    {
        $this->user = $user;

        return $this;
    }
}
```

В этом примере показаны:
1) наиболее актуальные поля для сущности вынесены из $fields в отдельные аргументы, которые имеют приоритет при мерже.
2) $user представляет собой свойство, которое может содержать как User так и UserAgent (член семьи).
Это свойство актуально для сущностей, в которых есть поля UserID и UserAgentID.
Сохранение данного свойства автоматизировано и показано ниже.
   
<hr>

```php
namespace AwardWallet\Tests\Modules\DbBuilder;

class AccountProperty extends AbstractDbEntity
{
    private ?ProviderProperty $providerProperty;

    public function __construct(?ProviderProperty $providerProperty, string $value, array $fields = [])
    {
        parent::__construct(array_merge($fields, ['Val' => $value]));

        $this->providerProperty = $providerProperty;
    }

    public function getProviderProperty(): ?ProviderProperty
    {
        return $this->providerProperty;
    }

    public function setProviderProperty(?ProviderProperty $providerProperty): self
    {
        $this->providerProperty = $providerProperty;

        return $this;
    }

    public static function createByCode(string $code, string $value, array $fields = []): self
    {
        return new self(
            new ProviderProperty($code),
            $value,
            $fields
        );
    }
}
```

В этом примере демонстрируется фабричный метод, который ни в коем разе
не заменяет базовый конструктор, когда потребуется создать у провайдера
одно свойство и несколько свойств аккаунта для разных субаккаунтов.

##### Создание метода make{Entity}

```php
class DbBuilder extends Module
{
    public function makeProvider(Provider $provider): int
    {
        if (!$provider->startMaking()) {
            return $provider->getId();
        }

        $providerId = $this->make($provider);

        if (count($props = $provider->getProperties()) > 0) {
            foreach ($props as $prop) {
                if (!$prop->isMakeable()) {
                    continue;
                }

                $this->makeProviderProperty($prop->extendFields(['ProviderID' => $providerId]));
            }
        }

        if (($pointValue = $provider->getUserPointValue()) && $pointValue->isMakeable()) {
            $this->makeUserPointValue($pointValue->extendFields(['ProviderID' => $providerId]));
        }

        $provider->finishMaking();

        return $providerId;
    }
}
```

Всегда перед непосредственным созданием нужно вызывать `startMaking` у сущности для установки state объекта в processing для защиты от бесконечной рекурсии. Так же перед вызовом любых методов make{Entity} нужно проверять, что сущность находится в нужном состоянии. 
Т.к. у сущности Provider нет свойств, которые бы прописывали в
Provider.{Entity}ID, то сначала вызывается базовый метод сохранения -
$this->make($provider), который сохраняет/обновляет сущность и возвращает первичный ключ.
Теперь когда есть ProviderID, его надо прописывать в связанные сущности.
В зависимости от сущности вызывается нужный метод make{Entity} а в
передаваемую сущность устанавливается ProviderID.

`Все методы make{Entity} должны возвращать первичный ключ`

<hr>

```php
class DbBuilder extends Module
{
    public function makeProviderProperty(ProviderProperty $providerProperty): int
    {
        if (!$providerProperty->startMaking()) {
            return $providerProperty->getId();
        }

        return $providerProperty->finishMaking(fn () => $this->make($providerProperty));
    }
}
```

Максимально простой сохранятор, т.к. нет внешних связей в свойствах.
**Внимание!** Не обязательно создавать свойства в сущности ко всем внешним связям.
Это только для удобства. Например, в указанном примере нет свойства, содержащего Provider.
Передавая ProviderProperty на сохранение не будет создан провайдер.
Но если вам потребуется создать провайдер через свойство, то можно добавить. Код изменится незначительно:

```php
class DbBuilder extends Module
{
    public function makeProviderProperty(ProviderProperty $providerProperty): int
    {
        if (!$providerProperty->startMaking()) {
            return $providerProperty->getId();
        }
        
        if (($provider = $providerProperty->getProvider()) && $provider->isMakeable()) {
            $providerProperty->extendFields(['ProviderID' => $this->makeProvider($provider)]);
        }
        
        return $providerProperty->finishMaking(fn () => $this->make($providerProperty));
    }
}
```
<hr>

```php
class DbBuilder extends Module
{
    public function makeProviderCoupon(ProviderCoupon $providerCoupon): int
    {
        if (!$providerCoupon->startMaking()) {
            return $providerCoupon->getId();
        }
        
        $this->makeOwner($providerCoupon);

        if (($currency = $providerCoupon->getCurrency()) && $currency->isMakeable()) {
            $providerCoupon->extendFields(['CurrencyID' => $this->makeCurrency($currency)]);
        }

        return $providerCoupon->finishMaking(fn () => $this->make($providerCoupon));
    }
}
```

Тут показано как сохранять свойство $user для сущностей, имеющих UserID и UserAgentID.
Будут созданы юзер и член семьи, если потребуется.

<hr>

```php
class DbBuilder extends Module
{
    public function makeAccount(Account $account): int
    {
        if (!$account->startMaking()) {
            return $account->getId();
        }
    
        $this->makeOwner($account);

        if (($provider = $account->getProvider()) && $provider->isMakeable()) {
            $account->extendFields(['ProviderID' => $this->makeProvider($provider)]);
        }

        if (($currency = $account->getCurrency()) && $currency->isMakeable()) {
            $account->extendFields(['CurrencyID' => $this->makeCurrency($currency)]);
        }

        $accountId = $this->make($account);
        $providerId = $account->getFields()['ProviderID'] ?? null;

        if (count($props = $account->getProperties()) > 0 && !is_null($providerId)) {
            foreach ($props as $prop) {
                $prop->getProviderProperty()->extendFields(['ProviderID' => $providerId]);

                if ($prop->isMakeable()) {
                    $this->makeAccountProperty($prop->extendFields(['AccountID' => $accountId]));
                }
            }
        }

        if (count($subAccounts = $account->getSubAccounts()) > 0) {
            foreach ($subAccounts as $subAccount) {
                if (!$subAccount->isMakeable()) {
                    continue;
                }

                $this->makeSubAccount(
                    $subAccount->extendFields(['AccountID' => $accountId])
                );
            }
        }

        if (count($its = $account->getItineraries()) > 0) {
            foreach ($its as $it) {
                if (!$it->isMakeable()) {
                    continue;
                }

                $this->makeItinerary($it->extendFields(['AccountID' => $accountId]));
            }
        }

        if (($accountShare = $account->getAccountShare()) && $accountShare->isMakeable()) {
            $this->makeAccountShare(
                $accountShare->extendFields([
                    'AccountID' => $accountId,
                ])
            );
        }

        $account->finishMaking();

        return $accountId;
    }
}
```

Комплексный пример. Сначала идет сохранение сущностей, где не трубется AccountID.
Затем непосредственное сохранение самого аккаунта для получения AccountID.

## UPD 20.09.2024

`AwardWallet\Tests\Modules\DbBuilder\AbstractDbEntity::getId` возвращает не только int, но и любой другой тип первичного ключа. В том числе и составной первичный ключ. В таком случае будет возвращен массив значений полей, составляющих первичный ключ.

`AwardWallet\Tests\Modules\DbBuilder\AbstractDbEntity::getPrimaryKey` может возвращать не только строку, но и массив строк. Массив строк названий полей представляет собой составной первичный ключ.

Методы make{Entity} могут возвращать не только целочисленный тип, но и строки, массивы и т.д. в зависимости от первичного ключа сущности.
