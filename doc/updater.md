# Updater

## Extension

Проверка через экстеншн может проходить как через десктопный браузер, так и через мобильное приложение (версии >= 3.10).

### Свойства провайдера

Проверка будет работать, если выставлено одно из следуюзших значений параметра **State** (`Provider.State`):

Опция                                                               | Фоновая проверка<br/> на сервере  | Серверная проверка<br/> по запросу пользователя | Проверка через<br/> extension
---                                                                 |---                                |---                                              |---
Enabled (`PROVIDER_ENABLED`)                                        | ✔                                 | ✔                                               | ✔  
Checking off (`PROVIDER_CHECKING_OFF`)                              | ✘                                 | ✔                                               | ✔ 
Cheking only through extension (`PROVIDER_CHECKING_EXTENSION_ONLY`) | ✘                                 | ✘                                               | ✔ 

и включена опция **Can check** (`Provider.CanCheck`).

<br/>
> На число `Usr.LogonCount` влияют число входов через логин-формы и срабатывание логики, связанной с RememberMe кукой. 

#### Десктопный браузер

Должна быть включена опция **Check using Browser extension** (`Provider.CheckInBrowser`), должно стоять **Yes** (`CHECK_IN_MIXED`). Т.е. экстеншн для провайдера уже протестирован и работает в десктопных браузерах. 

#### Мобильное приложение

Должна быть включена опция **Check In Mobile Browser** (`Provider.CheckInMobileBrowser`). Т.е. экстеншн для провайдера уже протестирован и работает на поддерживаемых мобильных платформах.

#### Как работает сессия апдейтера `AwardWallet\MainBundle\Updater\UpdaterSession` и её плагины.

На каждый тик `UpdaterSession::tick()` происходит перебор всех плагинов в сессии (см. *Порядок плагинов*): https://github.com/AwardWallet/frontend/blob/155386b9920a59b8654222e51ca9f55f66ee5a9f/bundles/AwardWallet/MainBundle/Updater/UpdaterSession.php#L267-L272, на каждой итерации выбираем только те аккаунты, у которых активный плагин `AccountState::getActivePlugin()` совпадает с текущим плагином, далее отобранные аккаунты передаются в тик плагина `PluginInterface::tick()`.
Какой аккаунт окажется в `AccountState::getActivePlugin()` на очередной итерации зависит от того, какие плагины и в каком порядке были добавлены на старте через `pushPlugin()` (https://github.com/AwardWallet/frontend/blob/0cb81c2bf007671dcb9ae99fce00f793115782eb/bundles/AwardWallet/MainBundle/Updater/UpdaterSession.php#L552-L576), в процессе тика внутри плагинов через `popPlugin()` \ `pushPlugin()`

##### Порядок плагинов.

Порядок ("базовый") описан тут: https://github.com/AwardWallet/frontend/blob/0cb81c2bf007671dcb9ae99fce00f793115782eb/bundles/AwardWallet/MainBundle/Updater/UpdaterSession.php#L48-L60
При создании нового плагина важно точно понимать в какое место среди других плагинов его нужно поместить.
Плагины добавляются в `AccountState` через `pushPlugin()` при добавлении аккаунтов в сессию (на старте, после предоставления локального пароля, после ответа на секретный вопрос, при повторных кругах проверки по провайдер-группе), важно чтобы относительный порядок (т.е. могут быть пропуски из "лишних" плагинов, как например при проверке по провайдер-группе) добавления плагинов в `AccountState` совпадал с "базовым" порядком. 
Если инвертировать порядок плагинов, или добавить плагины только через инжект автовайрингу через `!tagged` без дополнительной сортировки, то можно получить отсутствие какого-либо прогресса за тик, т.к. активный плагин не будет меняться (или поменяется только один раз), и сработает условие выхода из-за отсутствия прогресса: https://github.com/AwardWallet/frontend/blob/155386b9920a59b8654222e51ca9f55f66ee5a9f/bundles/AwardWallet/MainBundle/Updater/UpdaterSession.php#L276-L280 

`AccountState::getContextValue()` \ `AccountState::setContextValue()` работает как стейт доступный только текущему плагину, 
а `AccountState::getSharedValue()` \ `AccountState::setSharedValue()` как общий стейт и доступен для всех плагинов.

##### Некоторые неочевидные моменты при работе с плагинами.

Пока для `AccountState` не сделали `popPlugin()` (удаление последнего плагина) или `UpdaterSession::removeAccount()` (полное аккаунта из сессии),
то аккаунт будет держаться\крутиться\висеть на одном плагине (`AccountState::getActivePlugin()`) бесконечно, и не будет попадать в другие плагины.

```php
use AwardWallet\MainBundle\Updater\Plugin\PluginInterface;
use AwardWallet\MainBundle\Updater\Plugin\MasterInterface;
use AwardWallet\MainBundle\Updater\AccountState;

class SomePlugin implements PluginInterface
{
    /**
     * @param list<AccountState> $accountStates
     */
    public function tick(MasterInterface $master, array $accountStates): void
    {
        // в $accountStates попадут только те аккаунты, у которых активный плагин совпадает с текущим плагином 
        // на итерации перебора плагинов в UpdaterSession::tick()
        foreach ($accountStates as $accountState) {
            // do some work
        }
    }
}
```

Если в плагине сохранять какой-то стейт через `AccountState::setContextValue()` а затем сделать `popPlugin()`, 
то этот плагин не сможет получить доступ к сохраненному стейту. Даже если аккаунт вернётся обратно в изначальный плагин при проверке через `GroupCheckPlugin`,
то изначальный стейт плагина будет перезаписан внутри `GroupCheckPlugin` во время `pushPlugin(), и старые данные будут потеряны: https://github.com/AwardWallet/frontend/blob/0cb81c2bf007671dcb9ae99fce00f793115782eb/bundles/AwardWallet/MainBundle/Updater/Plugin/GroupCheckPlugin.php#L107-L110
Сработает только, если сохранить стейт через `AccountState::setSharedValue()` и при условии групповой проверки.
