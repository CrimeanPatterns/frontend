<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140609145828 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE `AbAccountProgram` COMMENT  ?;", ['Приложенные к букинг-запросу добавленные аккаунты'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbAccountProgram` MODIFY `RequestID` int(11) NOT NULL  COMMENT  ?;", ['Ссылка на букинг-запрос'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbAccountProgram` MODIFY `AccountID` int(11) NOT NULL  COMMENT  ?;", ['Ссылка на добавленный аккаунт'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbAccountProgram` MODIFY `Requested` tinyint(1) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Запрошен или нет аккаунт на шаринг букером'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbBookerInfo` COMMENT  ?;", ['Информация о букерах'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbBookerInfo` MODIFY `UserID` int(11) NOT NULL  COMMENT  ?;", ['Ссылка на aw-аккаунт букера'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbBookerInfo` MODIFY `Price` decimal(10,2) NOT NULL  COMMENT  ?;", ['Цена услуг букера за 1 пассажира'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbBookerInfo` MODIFY `PricingDetails` text DEFAULT NULL  COMMENT  ?;", ['Описание тарифов букера. Показывается, например, при создании букинг запроса'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbBookerInfo` MODIFY `ServiceName` varchar(120) NOT NULL  COMMENT  ?;", ['Наименование букера. Фирма. Вероятно будет удалено в пользу Usr.Company'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbBookerInfo` MODIFY `ServiceShortName` varchar(100) NOT NULL  COMMENT  ?;", ['Краткое наименование букера. Используется в качестве разного рода префиксов в URL'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbBookerInfo` MODIFY `Address` varchar(255) NOT NULL  COMMENT  ?;", ['Адрес букера. Возможно использование в качестве адреса для высылки чека'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbBookerInfo` MODIFY `ServiceURL` varchar(250) DEFAULT NULL  COMMENT  ?;", ['URL букера'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbBookerInfo` MODIFY `OutboundPercent` decimal(10,2) NOT NULL  COMMENT  ?;", ['Процент, который прилагается нам, если букинг запрос написал наш пользователь'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbBookerInfo` MODIFY `InboundPercent` decimal(10,2) NOT NULL  COMMENT  ?;", ['Процент, который прилагается нам, если букинг запрос написал пользователь, пришедший с сайта букера'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbBookerInfo` MODIFY `SmtpServer` varchar(250) DEFAULT NULL  COMMENT  ?;", ['Smtp сервер букера'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbBookerInfo` MODIFY `SmtpPort` int(11) DEFAULT NULL  COMMENT  ?;", ['Smtp порт букера'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbBookerInfo` MODIFY `SmtpUseSsl` tinyint(4) DEFAULT NULL  COMMENT  ?;", ['Использует ли сервер букера SSL'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbBookerInfo` MODIFY `SmtpUsername` varchar(250) DEFAULT NULL  COMMENT  ?;", ['Юзернейм'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbBookerInfo` MODIFY `SmtpPassword` varchar(250) DEFAULT NULL  COMMENT  ?;", ['Пароль'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbBookerInfo` MODIFY `SmtpError` varchar(250) DEFAULT NULL  COMMENT  ?;", ['Последняя ошибка SMTP. Служит для переключения с SMTP на наш сервер отправки, если по каким то причинам сервер букера отказывает отправлять письма'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbBookerInfo` MODIFY `SmtpErrorDate` datetime DEFAULT NULL  COMMENT  ?;", ['Дата последней ошибки SMTP. Служит для того, чтобы через опред.интеревал времени вернуть возможность отправки писем через сервер букера'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbBookerInfo` MODIFY `Greeting` text NOT NULL  COMMENT  ?;", ['Приветствие букера. Показывается при создании букинг запроса'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbBookerInfo` MODIFY `AutoReplyMessage` text NOT NULL  COMMENT  ?;", ['Автосообщение, которое автоматически вставляется в тред переписки после создания нового букинг запроса'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbBookerInfo` MODIFY `SiteAdID` int(11)  COMMENT  ?;", ['Ссылка на идентификатор, который используется для определения чей юзер составил букинг-запрос - наш или букера'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbCustomProgram` COMMENT  ?;", ['Приложенные к букинг запросу кастомные программы (не путать с кастомными aw-программами)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbCustomProgram` MODIFY `Name` varchar(255) NOT NULL  COMMENT  ?;", ['Наименование программы'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbCustomProgram` MODIFY `Owner` varchar(255) DEFAULT NULL  COMMENT  ?;", ['Собственник программы'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbCustomProgram` MODIFY `EliteStatus` varchar(255) DEFAULT NULL  COMMENT  ?;", ['Элитный статус программы'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbCustomProgram` MODIFY `Balance` decimal(15,2) DEFAULT NULL  COMMENT  ?;", ['Баланс по кастомной программе'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbCustomProgram` MODIFY `RequestID` int(11) NOT NULL  COMMENT  ?;", ['Ссылка на букинг запрос'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbCustomProgram` MODIFY `ProviderID` int(11)  COMMENT  ?;", ['Ссылка на провайдера. Ее создает сам букер сопоставляя вбитое наименование программы с реальной существующей у нас'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbCustomProgram` MODIFY `Requested` tinyint(1) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Запрошена ли программа на шаринг. Только после сопоставления с реальной программой. После юзер все равно добавляет реальный аккаунт и запись в этой таблице исчезает и переходит в AbAccountProgram'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbInvoice` COMMENT  ?;", ['Счет, выписываемый букером к юзеру'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbInvoice` MODIFY `Tickets` int(11) NOT NULL  COMMENT  ?;", ['Кол-во билетов, которые предоставил букер'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbInvoice` MODIFY `Price` decimal(10,2) NOT NULL  COMMENT  ?;", ['Стоимость 1 билета. Берется из AbBookerInfo.Price'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbInvoice` MODIFY `Discount` int(11) DEFAULT NULL  COMMENT  ?;", ['Скидка, если указана'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbInvoice` MODIFY `Taxes` decimal(10,2) DEFAULT NULL  COMMENT  ?;", ['Налоги авиакомпании'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbInvoice` MODIFY `Status` int(11) NOT NULL  COMMENT  ?;", ['Статус. Оплачен или нет'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbInvoice` MODIFY `PaymentType` tinyint(1) NOT NULL  COMMENT  ?;", ['Тип платежа: кредиткой или чеком'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbInvoice` MODIFY `MessageID` bigint(15) NOT NULL  COMMENT  ?;", ['Ссылка на сообщение. Счет выставляется как сообщение в треде переписки'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbInvoiceMiles` COMMENT  ?;", ['Прикладываемые к счету мили определенных программ (необходимы для букера для заказа билетов)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbInvoiceMiles` MODIFY `CustomName` varchar(255) NOT NULL  COMMENT  ?;", ['Название программы'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbInvoiceMiles` MODIFY `Balance` decimal(10,0) NOT NULL  COMMENT  ?;", ['Требуемый баланс'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbInvoiceMiles` MODIFY `InvoiceID` int(11) NOT NULL  COMMENT  ?;", ['Ссылка на счет'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbMessage` COMMENT  ?;", ['Сообщения в переписке между букером и юзером'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbMessage` MODIFY `CreateDate` datetime NOT NULL  COMMENT  ?;", ['Дата'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbMessage` MODIFY `Post` longtext DEFAULT NULL  COMMENT  ?;", ['Текст сообщения'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbMessage` MODIFY `Type` int(11) DEFAULT NULL  COMMENT  ?;", ['Тип сообщения (внутреннее или обычное, системное)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbMessage` MODIFY `Metadata` varchar(255) DEFAULT NULL  COMMENT  ?;", ['Необходимые данные для системных сообщений'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbMessage` MODIFY `RequestID` int(11) NOT NULL  COMMENT  ?;", ['Ссылка на букинг запрос'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbMessage` MODIFY `UserID` int(11)  COMMENT  ?;", ['Автор сообщения'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbPassenger` COMMENT  ?;", ['Списки пассажиров, которые прикладываются к букинг запросу'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbPassenger` MODIFY `FirstName` varchar(255) NOT NULL  COMMENT  ?;", ['Имя'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbPassenger` MODIFY `MiddleName` varchar(255) DEFAULT NULL  COMMENT  ?;", ['Отчество'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbPassenger` MODIFY `LastName` varchar(255) NOT NULL  COMMENT  ?;", ['Фамилия'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbPassenger` MODIFY `Birthday` datetime NOT NULL  COMMENT  ?;", ['Дата рождения'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbPassenger` MODIFY `Nationality` varchar(255) DEFAULT NULL  COMMENT  ?;", ['Гражданство'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbPassenger` MODIFY `RequestID` int(11) NOT NULL  COMMENT  ?;", ['Ссылка на букинг запрос'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbRequest` COMMENT  ?;", ['Букинг запрос'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbRequest` MODIFY `ContactName` varchar(255) NOT NULL  COMMENT  ?;", ['Контактное имя (обычно Имя и Фамилия из таблицы Usr)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbRequest` MODIFY `ContactEmail` varchar(255) NOT NULL  COMMENT  ?;", ['Контактный email'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbRequest` MODIFY `ContactPhone` varchar(255) NOT NULL  COMMENT  ?;", ['Контактный телефон (опционально)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbRequest` MODIFY `Status` tinyint(2) NOT NULL  COMMENT  ?;", ['Статус (открытый, проплаченный, отмененный, оставленный на будущее и т.д.)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbRequest` MODIFY `Notes` varchar(4000) DEFAULT NULL  COMMENT  ?;", ['Примечания к букинг запросу'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbRequest` MODIFY `CreateDate` datetime NOT NULL  COMMENT  ?;", ['Дата создания'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbRequest` MODIFY `LastUpdateDate` datetime NOT NULL  COMMENT  ?;", ['Дата последнего обновления запроса (редактирование, какая то активность в виде написания сообщения)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbRequest` MODIFY `RemindDate` datetime DEFAULT NULL  COMMENT  ?;", ['Дата, в которую букинг запрос перейдет в статус Открыт из статуса На будущее'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbRequest` MODIFY `CabinFirst` tinyint(1) NOT NULL  COMMENT  ?;", ['Первый класс (опция)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbRequest` MODIFY `CabinBusiness` tinyint(1) NOT NULL  COMMENT  ?;", ['Бизнес класс (опция)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbRequest` MODIFY `PriorSearchResults` varchar(4000) DEFAULT NULL  COMMENT  ?;", ['Пользователь отписывает (по желанию) обращался ли к другим букерам по своему запросу и что они требовали взамен'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbRequest` MODIFY `BookerUserID` int(11) NOT NULL  COMMENT  ?;", ['Букер, который обрабатывает данный букинг запрос'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbRequest` MODIFY `AssignedUserID` int(11)  COMMENT  ?;", ['На кого записан данный букинг запрос внутри букинг-бизнеса. Для внутренней организации букера.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbRequest` MODIFY `UserID` int(11)  COMMENT  ?;", ['Ссылка на автора букинг запроса'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbRequest` MODIFY `BookingTransactionID` int(11)  COMMENT  ?;", ['Ссылка на транзакцию. Транзакции создаются каждый месяц в результате взаиморасчетов между нами и букером'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbRequestMark` COMMENT  ?;", ['Прочитан ли букинг запрос, есть ли в нем новые сообщения'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbRequestMark` MODIFY `ReadDate` datetime NOT NULL  COMMENT  ?;", ['Последняя дата прочтения букинг запроса (когда в последний раз заходил на страницу просмотра запроса)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbRequestMark` MODIFY `UserID` int(11) NOT NULL  COMMENT  ?;", ['Пользователь'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbRequestMark` MODIFY `RequestID` int(11) NOT NULL  COMMENT  ?;", ['Ссылка на букинг запрос'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbRequestMark` MODIFY `IsRead` tinyint(4) NOT NULL DEFAULT '1'  COMMENT  ?;", ['Прочитан или нет запрос'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbSegment` COMMENT  ?;", ['Сегменты (перелеты), прикладываемые к букинг запросу. '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbSegment` MODIFY `DepDateFrom` datetime DEFAULT NULL  COMMENT  ?;", ['Дата вылета начальная'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbSegment` MODIFY `DepDateTo` datetime DEFAULT NULL  COMMENT  ?;", ['Дата вылета конечная'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbSegment` MODIFY `DepDateIdeal` datetime DEFAULT NULL  COMMENT  ?;", ['Дата вылета идеальная'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbSegment` MODIFY `Priority` int(11) NOT NULL  COMMENT  ?;", ['Порядок сортировки сегментов'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbSegment` MODIFY `RoundTrip` int(11) NOT NULL  COMMENT  ?;", ['Перелет туда-обратно?'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbSegment` MODIFY `RequestID` int(11) NOT NULL  COMMENT  ?;", ['Ссылка на букинг запрос'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbSegment` MODIFY `Dep` varchar(250)  COMMENT  ?;", ['Пункт отправления'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbSegment` MODIFY `Arr` varchar(250)  COMMENT  ?;", ['Пункт прибытия'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbTransaction` COMMENT  ?;", ['Транзакции букинга. Для взаиморасчетов между администрацией AW и букером. '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbTransaction` MODIFY `ProcessDate` datetime NOT NULL  COMMENT  ?;", ['Дата создания'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AbTransaction` MODIFY `Processed` int(11)  COMMENT  ?;", ['Обработано (да или нет)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` COMMENT  ?;", ['Информация об аккаунте'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `ProviderID` int(11)  COMMENT  ?;", [' ProviderID из тбалицы Provider'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `UserID` int(11) NOT NULL DEFAULT '0'  COMMENT  ?;", [' UserID из таблицы Usr'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `State` int(11) NOT NULL DEFAULT '0'  COMMENT  ?;", [' State провайдера (включена, выключена, в починке и т.д.)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `ErrorCode` int(11) DEFAULT NULL  COMMENT  ?;", [' Код, возвращаемый при проверке аккаунта'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `ErrorMessage` text DEFAULT NULL  COMMENT  ?;", [' Сообщение ошибки, возвращаемый при проверке аккаунта'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `Balance` float DEFAULT NULL  COMMENT  ?;", [' Баланс аккаунта (null, если нет)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `Login` varchar(80) NOT NULL DEFAULT ''  COMMENT  ?;", [' Логин'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `Pass` varchar(250) NOT NULL DEFAULT ''  COMMENT  ?;", [' Пароль'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `Login2` varchar(80) DEFAULT NULL  COMMENT  ?;", [' Логин 2'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `comment` text DEFAULT NULL  COMMENT  ?;", [' Комментарий к аккаунту (заполняется юзером)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `SavePassword` int(11) NOT NULL DEFAULT '1'  COMMENT  ?;", [' Где сохранен пароль (в базе - 1, в браузере - 2)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `ExpirationDate` datetime  COMMENT  ?;", ['Expiration Date'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `ExpirationAutoSet` int(11) NOT NULL DEFAULT '0'  COMMENT  ?;", [' Определяет, кем установлена Expiration Date (нами, юзером или попала из субакка)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `PassChangeDate` datetime DEFAULT NULL  COMMENT  ?;", [' Дата изменения пароля'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `InvalidPassCount` int(11) NOT NULL DEFAULT '0'  COMMENT  ?;", [' Количетсво попыток логина с неверными кренделями'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `LastChangeDate` datetime DEFAULT NULL  COMMENT  ?;", ['Дата последнего изменения баланса'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `ChangeCount` int(11) NOT NULL DEFAULT '0'  COMMENT  ?;", [' Количество изменений баланса'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `LastActivity` datetime DEFAULT NULL  COMMENT  ?;", [' Дата последней активности в аккаунте (собрана с сайта провайдера)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `SubAccounts` int(11) NOT NULL DEFAULT '0'  COMMENT  ?;", [' Количество субаккаунтвов'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `LastBalance` float DEFAULT NULL  COMMENT  ?;", [' Предыдущее значение баланса'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `SuccessCheckDate` datetime DEFAULT NULL  COMMENT  ?;", ['Дата последней успешной проверки аккаунта'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `Login3` varchar(40) DEFAULT NULL  COMMENT  ?;", [' Логин 3'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `Question` varchar(250) DEFAULT NULL  COMMENT  ?;", [' Секретный вопрос, заданный при проверке аккаунта'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `DontTrackExpiration` int(1) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Установлена ли юзером галочка, что поинты не протухают'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `CheckedBy` tinyint(4) DEFAULT NULL  COMMENT  ?;", ['Кем проверен акк (wsdl, user)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `Itineraries` int(11) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Количество резерваций в аккаунте'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `LastCheckItDate` datetime DEFAULT NULL  COMMENT  ?;", ['Дата последнего обновления резерваций'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Account` MODIFY `ErrorCount` int(11) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Количество ошибок при обновлении аккаунта (неверные кренделя, ошибки прова). Должен сбрасываться при успешной проверке - нужно для того, чтобы перестать проверять аккаунты с ошибками в бэкграунде при достижении определеноого порога'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AccountBalance` COMMENT  ?;", ['История изменения балансов по аккаунту, пишется только при изменении, нулевые и пустые значения не пишутся'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AccountBalance` MODIFY `UpdateDate` datetime NOT NULL  COMMENT  ?;", ['Дата изменения'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AccountBalance` MODIFY `Balance` float NOT NULL  COMMENT  ?;", ['Новый баланс'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AccountBalance` MODIFY `SubAccountID` int(11)  COMMENT  ?;", ['Указывает на подаккаунт у которого изменился баланс. Null если основной аккаунт'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AccountHistory` COMMENT  ?;", ['История транзакций (начисления, списания баллов), собирается из аккаунтов юзеров'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AccountHistory` MODIFY `AccountID` int(11) NOT NULL  COMMENT  ?;", [' AccountID из таблицы Account'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AccountHistory` MODIFY `PostingDate` datetime NOT NULL  COMMENT  ?;", [' Дата транзакции'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AccountHistory` MODIFY `Description` varchar(4000) NOT NULL DEFAULT ''  COMMENT  ?;", [' Описание транзакции'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AccountHistory` MODIFY `Miles` float DEFAULT NULL  COMMENT  ?;", [' Количество начисленных / списанных баллов'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AccountHistory` MODIFY `Info` mediumtext DEFAULT NULL  COMMENT  ?;", [' Сериализованные данные, содержающую другую полезную информацию о транзакции (Tier Points, Bonus и т.д.)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AccountProperty` COMMENT  ?;", ['Свойства аккаунта (субаккаунта)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AccountProperty` MODIFY `ProviderPropertyID` int(11) NOT NULL  COMMENT  ?;", [' ID свойства из таблицы ProviderProperty '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AccountProperty` MODIFY `AccountID` int(11) NOT NULL  COMMENT  ?;", [' AccountID, которому принаждлежит данное свойство (из таблицы Account)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AccountProperty` MODIFY `Val` varchar(20000) DEFAULT NULL  COMMENT  ?;", [' значение '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AccountProperty` MODIFY `SubAccountID` int(11)  COMMENT  ?;", [' ID субаккаунта из таблицы SubAccount '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Airline` COMMENT  ?;", ['Таблица авиалиний. Заполнялась скриптом util/update/updateAirlines.php, список берется с "$":http://www.flightradar24.com/data/airplanes/'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Airline` MODIFY `Code` varchar(2) DEFAULT NULL  COMMENT  ?;", ['2-символьный код'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Airline` MODIFY `ICAO` varchar(3) DEFAULT NULL  COMMENT  ?;", ['3-символьный код'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AllianceEliteLevel` COMMENT  ?;", ['Элитные уровни альянсов.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AllianceEliteLevel` MODIFY `AllianceID` int(11) NOT NULL  COMMENT  ?;", ['Ссылка на альянс'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AllianceEliteLevel` MODIFY `Rank` int(11) NOT NULL  COMMENT  ?;", ['Положение уровня в элитной программе.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `AllianceEliteLevel` MODIFY `Name` varchar(80) NOT NULL  COMMENT  ?;", ['Название элитного уровня'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Answer` COMMENT  ?;", ['Секретные вопросы (спрашиваются при проверке аккаунта)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Answer` MODIFY `AccountID` int(11) NOT NULL  COMMENT  ?;", [' AccountID из таблицы Account '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Answer` MODIFY `Question` varchar(250) NOT NULL  COMMENT  ?;", [' Секретный вопрос '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Answer` MODIFY `Answer` varchar(250) NOT NULL  COMMENT  ?;", [' Ответ на секретный вопрос'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `BonusConversion` COMMENT  ?;", ['Таблица с запроса на конвертацию AW-бонусов, полученных от рефералов, на мили авиакомпаний. Запросы отправляются через форму "$":https://awardwallet.com/agent/redeem.php. Инструкция по конвертации "AW Bonus Conversion Instructions":http://redmine.awardwallet.com/projects/awwa/wiki/AW_Bonus_Conversion_Instructions'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `BonusConversion` MODIFY `Airline` varchar(80) NOT NULL  COMMENT  ?;", ['Название авиакомпании, например (US Airways, Delta, United).'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `BonusConversion` MODIFY `Points` int(11) NOT NULL  COMMENT  ?;", ['Количество бонусов, которые надо обменять на мили.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `BonusConversion` MODIFY `Miles` int(11) NOT NULL  COMMENT  ?;", ['Количество миль, которые надо купить пользователю.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `BonusConversion` MODIFY `CreationDate` datetime NOT NULL  COMMENT  ?;", ['Дата создания запроса.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `BonusConversion` MODIFY `Processed` tinyint(4) NOT NULL  COMMENT  ?;", ['Обработан запрос или нет.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `BonusConversion` MODIFY `Cost` float DEFAULT NULL  COMMENT  ?;", ['Сколько заплатола AW, чтобы купить мили.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `BonusConversion` MODIFY `UserID` int(11) NOT NULL  COMMENT  ?;", ['Пользователь, который отправил запрос.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `BonusConversion` MODIFY `AccountID` int(11)  COMMENT  ?;", ['Аккаунт, в который покупаются мили.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Cart` MODIFY `IncomeTransactionID` int(11) DEFAULT NULL  COMMENT  ?;", ['Ссылка на транзацкию в таблицеIncomeTransaction, к которой привязана эта Cart'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Deal` COMMENT  ?;", ['Промоушены, показываются на странице "$":http://awardwallet.com/promos, и во вкладке Promotions поп-апа в списке аккаунтов'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Deal` MODIFY `ProviderID` int(11) NOT NULL  COMMENT  ?;", ['ссылка на провайдера'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Deal` MODIFY `Title` varchar(200) NOT NULL  COMMENT  ?;", ['Заголовок'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Deal` MODIFY `Description` text NOT NULL  COMMENT  ?;", ['Описание'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Deal` MODIFY `Link` varchar(2048) DEFAULT NULL  COMMENT  ?;", ['Ссылка на страницу регистрации для участия в промоушне'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Deal` MODIFY `DealsLink` varchar(2048) DEFAULT NULL  COMMENT  ?;", ['Ссылка на промоушн'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Deal` MODIFY `BeginDate` datetime DEFAULT NULL  COMMENT  ?;", ['Дата начала действия акции'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Deal` MODIFY `EndDate` datetime DEFAULT NULL  COMMENT  ?;", ['Дата окончания действия акции'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Deal` MODIFY `ButtonCaption` varchar(32) DEFAULT NULL  COMMENT  ?;", ['заголовок для кнопки, ведущей на страницу промоушна'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Deal` MODIFY `AutologinProviderID` int(11)  COMMENT  ?;", ['ссылка на провайдера, через которого произойдет автологин для последующего участия в промоушне'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Deal` MODIFY `CreateDate` datetime DEFAULT '0000-00-00 00:00:00'  COMMENT  ?;", ['дата создания промоушна'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Deal` MODIFY `AffiliateLink` varchar(2048) DEFAULT NULL  COMMENT  ?;", ['реферальная AW-ссылка на промоушн, с нее нам будет идти комиссия'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `DealMark` COMMENT  ?;", ['Активность пользователей в промоушнах. "$":http://awardwallet.com/promos'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `DealMark` MODIFY `UserID` int(11)  COMMENT  ?;", ['ссылка на пользователя'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `DealMark` MODIFY `DealID` int(11)  COMMENT  ?;", ['ссылка на промоушн'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `DealMark` MODIFY `Readed` tinyint(4) NOT NULL DEFAULT '0'  COMMENT  ?;", ['промоушн прочитан\\Mark as r\'ead'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `DealMark` MODIFY `Follow` tinyint(4) NOT NULL DEFAULT '0'  COMMENT  ?;", ['нажали кнопку Follow Up - появится флажок-топор рядом с промоушеном'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `DealMark` MODIFY `Applied` tinyint(4) NOT NULL DEFAULT '0'  COMMENT  ?;", ['кнопка Mark as applied'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `DealMark` MODIFY `Manual` tinyint(4) NOT NULL DEFAULT '0'  COMMENT  ?;", ['кнопка +1'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `EliteLevel` COMMENT  ?;", ['Таблица с уровнями элитных программ провайдеров.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `EliteLevel` MODIFY `ProviderID` int(11) NOT NULL  COMMENT  ?;", ['Ссылка на провайдера, к которому относится уровень.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `EliteLevel` MODIFY `Rank` int(11) NOT NULL  COMMENT  ?;", ['Положение уровня в элитной программе.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `EliteLevel` MODIFY `CustomerSupportPhone` varchar(70) DEFAULT NULL  COMMENT  ?;", ['Телефон поддержки клиентов.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `EliteLevel` MODIFY `AllianceEliteLevelID` int(11)  COMMENT  ?;", ['Ссылка на элитный уровень альянса, которому соответствует данный уровень.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `EliteLevel` MODIFY `Name` varchar(50) DEFAULT NULL  COMMENT  ?;", ['Название уровня.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `EliteLevel` MODIFY `Description` varchar(2000) DEFAULT NULL  COMMENT  ?;", ['Комментарий к элитному уровню. Показывается в тултипе около названия элитного уровня: список аккаунтов -> pop-up -> вкладка "Elite Levels".'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `EliteLevelProgress` COMMENT  ?;", ['Описания прогресс-баров для элитных уровней.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `EliteLevelProgress` MODIFY `ProviderPropertyID` int(11) NOT NULL  COMMENT  ?;", ['Ссылка на свойство провайдера, знаение свойства сравнивается с условиями элитной программы и строится график.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `EliteLevelProgress` MODIFY `StartDatePropertyID` int(11)  COMMENT  ?;", ['Ссылка на свойство, в котором хранится дата, с которой начинается обнуления(протухания) накопления для элитной программы.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `EliteLevelProgress` MODIFY `EndMonth` tinyint(4) DEFAULT NULL  COMMENT  ?;", ['Сколько месяцев и дней, начиная с StartDatePropertyID, пройдет до обнуления данных элитной программы у аккаунта.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `EliteLevelProgress` MODIFY `EndDay` tinyint(4) DEFAULT NULL  COMMENT  ?;", ['Сколько месяцев и дней, начиная с StartDatePropertyID, пройдет до обнуления данных элитной программы у аккаунта.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `EliteLevelProgress` MODIFY `Lifetime` tinyint(1) NOT NULL DEFAULT '0'  COMMENT  ?;", ['*True*: накопления и статус в элитной программе сохраняются пожизненно, график риусется(?) снизу отдельно.
*False*: накопления\\статус сбрасываются после определенной даты.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `EliteLevelProgress` MODIFY `ToNextLevel` tinyint(1) NOT NULL DEFAULT '0'  COMMENT  ?;", ['В каком виде представленны данные на странице провадера
*True*: относительная величина, количество миль\\бонусов до следующего уровня.
*False*: абсолютная величина, количество миль\\бонусов от начала.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `EliteLevelProgress` MODIFY `GroupIndex` int(11) DEFAULT NULL  COMMENT  ?;", ['Группировка свойств:
*NOT NULL*: группировка логическим AND, т.е. для достижения следующего уровня необходимо выполнение всех условий в группе с данным индексом,рисуется
*NULL*: группировка OR, необходимо выполнение хотя бы одного условия.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `EliteLevelProgress` MODIFY `StartLevelID` int(11)  COMMENT  ?;", ['Уровень с которого начинается отрисовка графика по свойству, по умолчанию с первого уровня из доступных.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `EliteLevelValue` COMMENT  ?;", ['Условия достижения элитных уровней.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `EliteLevelValue` MODIFY `EliteLevelProgressID` int(11) NOT NULL  COMMENT  ?;", ['Ссылка на прогресс-бар'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `EliteLevelValue` MODIFY `EliteLevelID` int(11) NOT NULL  COMMENT  ?;", ['Ссылка не элитный уровень'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `EliteLevelValue` MODIFY `Value` int(11) NOT NULL  COMMENT  ?;", ['Условия достижения данного элитного уровня'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ExtProperty` COMMENT  ?;", ['Доплнительные свойства поездок/заказов'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ExtProperty` MODIFY `SourceTable` char(1) NOT NULL  COMMENT  ?;", ['Контекст параметра является литералом
*T* - Trip
*R* - Reservation
*S* - TripSegment
*L* - Rental
*E* - Restaurant
*D* - Direction'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ExtProperty` MODIFY `SourceID` int(11) NOT NULL  COMMENT  ?;", ['Идентификатор в контексте'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ExtProperty` MODIFY `Name` varchar(80) NOT NULL  COMMENT  ?;", ['Название свойства/параметра'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ExtProperty` MODIFY `Value` varchar(4000) DEFAULT NULL  COMMENT  ?;", ['Значение (без изменений)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` COMMENT  ?;", ['Объединенная таблица для Trip и TripSegment для более гибкого управления травел планами'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `AccountID` int(11)  COMMENT  ?;", ['Идентификатор аккаунта в системе провайдера'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `RecordLocator` varchar(20) DEFAULT NULL  COMMENT  ?;", ['Уникальный номер сделанного заказа'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `TravelPlanID` int(11)  COMMENT  ?;", ['К какому травел плану относится'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `Hidden` smallint(6) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Удаленный или нет'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `Parsed` int(11) NOT NULL DEFAULT '0'  COMMENT  ?;", ['установлен в 1 если данные получены в результате парсинга резерваций на сайте провайдера'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `AirlineName` varchar(250) DEFAULT NULL  COMMENT  ?;", ['Название компании совершающей рейс'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `Notes` varchar(4000) DEFAULT NULL  COMMENT  ?;", ['Заметки'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `ProviderID` int(11) DEFAULT NULL  COMMENT  ?;", ['Идентификатор компании в таблице Provider'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `Moved` int(11) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Флаг для перемещенных вручную сегментов'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `UpdateDate` datetime DEFAULT NULL  COMMENT  ?;", ['Дата последнего автообновления'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `ConfFields` varchar(250) DEFAULT NULL  COMMENT  ?;", ['Данные подтверждения'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `Category` int(11) NOT NULL DEFAULT '1'  COMMENT  ?;", ['Категория поездки
*A* - Самолет,
*T* - Поезд,
*F* - Паром,
*C* - Круиз
*B* - Автобус'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `Direction` tinyint(4) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Для поездок туда и обратно 0- туда(default) 1- обратно'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `CreateDate` datetime NOT NULL  COMMENT  ?;", ['Дата создания строки'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `Cancelled` tinyint(2) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Отменено'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `UserAgentID` int(11)  COMMENT  ?;", ['Идентификатор агента для которого создан этот сегмент'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `Copied` tinyint(4) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Флаг скопированных вручную сегментов'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `Modified` tinyint(4) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Флаг измененных вручную данных сегмента'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `MailDate` datetime DEFAULT NULL  COMMENT  ?;", ['Дата рассылки'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `DepCode` varchar(10) DEFAULT NULL  COMMENT  ?;", ['Код места отправления'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `DepName` varchar(250) NOT NULL  COMMENT  ?;", ['Название места отправления'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `DepDate` datetime NOT NULL  COMMENT  ?;", ['Дата отправления'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `ArrCode` varchar(10)  COMMENT  ?;", ['Код места назначения'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `ArrName` varchar(250) NOT NULL  COMMENT  ?;", ['Название места назначения'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `ArrDate` datetime NOT NULL  COMMENT  ?;", ['Дата прибытия'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `FlightNumber` varchar(20) DEFAULT NULL  COMMENT  ?;", ['Номер рейса'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `DepGeoTagID` int(11)  COMMENT  ?;", ['GeoTag места отправления'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `ArrGeoTagID` int(11)  COMMENT  ?;", ['GeoTag места назначения'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `CheckinNotified` int(11) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Рассылка произведена'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Flights` MODIFY `ShareCode` varchar(32) DEFAULT NULL  COMMENT  ?;", ['Случайный набор символов для проверки расшаренного сегмента'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `IncomeTransaction` COMMENT  ?;", ['Транзакции платежей. Для расчетов с партнерами AW. Например, платежи от рефералов Carlson Wagon Travel'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `IncomeTransaction` MODIFY `Date` datetime NOT NULL  COMMENT  ?;", ['Дата создания.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `IncomeTransaction` MODIFY `Processed` tinyint(4) DEFAULT '0'  COMMENT  ?;", ['Обработано или нет.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `IncomeTransaction` MODIFY `Description` varchar(2000) NOT NULL DEFAULT ''  COMMENT  ?;", ['Описание\\Комментарий к транзакции.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Invites` COMMENT  ?;", ['Таблица со с инвайтами - пользователи приглашают знакомых на сайт. Используется для выдачи бесплатных купонов(за 5-х приглашенных), расчета AW-бонусов приглашающему в зависимости от платежей рефералов.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Invites` MODIFY `InviterID` int(11)  COMMENT  ?;", ['ссылка на приглащающего'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Invites` MODIFY `InviteeID` int(11)  COMMENT  ?;", ['ссылка на реферала. Значения:
*null* - пользователь удалился или еще не зарегистрировался по ссылке из письма'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Invites` MODIFY `Email` varchar(100) DEFAULT NULL  COMMENT  ?;", ['email реферала, заполнятся либо при отсылке инвайт-письма из левого меню на ящик друга, либо при регистрации нового пользователя по реферальной ссылке'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Invites` MODIFY `InviteDate` datetime DEFAULT NULL  COMMENT  ?;", ['Дата создания инвайта'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Invites` MODIFY `Code` varchar(10) DEFAULT NULL  COMMENT  ?;", ['код для уникальной ссылки в письме'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Invites` MODIFY `Approved` tinyint(4) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Значения:
*0*: приглашение из письма не было подтверждено.
*1*: пользователь зарегистрировался по ссылке(письмо\\реферальная)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `MailServer` COMMENT  ?;", ['Список известных поддерживаемых почтовых серверов'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `MailServer` MODIFY `Domain` varchar(64) NOT NULL  COMMENT  ?;", ['доменная часть почтового адреса (пр. gmail.com)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `MailServer` MODIFY `Server` varchar(64) NOT NULL  COMMENT  ?;", ['адрес самого сервера (пр. imap.googlemail.com) '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `MailServer` MODIFY `Port` int(11) NOT NULL  COMMENT  ?;", ['int, порт для соединения'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `MailServer` MODIFY `UseSsl` tinyint(4) DEFAULT NULL  COMMENT  ?;", ['boolean, использовать ли ssl'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `MailServer` MODIFY `Protocol` tinyint(4) DEFAULT NULL  COMMENT  ?;", ['int, почтовый протокол, значения в классе ImapDetector'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `MailServer` MODIFY `MxKeyWords` varchar(250) DEFAULT NULL  COMMENT  ?;", ['ключевые слова, которые содержатся в MX-записях для доменов этого сервера'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `MailServer` MODIFY `Connected` tinyint(4) DEFAULT NULL  COMMENT  ?;", ['boolean, поддерживается ли этот сервер'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Offer` COMMENT  ?;", ['Офферы, показываемые при посещении account/list.php'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Offer` MODIFY `Enabled` tinyint(4) NOT NULL  COMMENT  ?;", ['Включен/выключен. Выключенные офферы также не обновляют список пользователей по крону'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Offer` MODIFY `Name` varchar(250) NOT NULL  COMMENT  ?;", ['Название оффера для внутреннего пользования. Поскольку показывается также в отчёте для AA, следует писать что-то осмысленное'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Offer` MODIFY `Description` varchar(4000) DEFAULT NULL  COMMENT  ?;", ['Описание оффера для внутреннего пользования. Поскольку показывается также в отчёте для AA, следует писать что-то осмысленное'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Offer` MODIFY `Code` varchar(60) NOT NULL  COMMENT  ?;", ['Уникальный код оффера, используемый для идентификации в PHP'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Offer` MODIFY `CreationDate` datetime NOT NULL  COMMENT  ?;", ['Дата создания оффера'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Offer` MODIFY `PromotionCardID` int(10) unsigned DEFAULT '0'  COMMENT  ?;", ['Предполагалось использовать это поле для автоматической генерации офферов-попапов. Сейачс не используется. Ставить 0'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Offer` MODIFY `ApplyURL` varchar(250) NOT NULL  COMMENT  ?;", ['Ссылка, по которой пользователь переходит при согласи на оффер. Подставляется вместо переменной {ApplyURL} в твиге. Может быть пустой, если URL формируется динамически'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Offer` MODIFY `RemindMeDays` int(11) NOT NULL  COMMENT  ?;", ['Минимальный срок между показами этого оффера'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Offer` MODIFY `DisplayType` tinyint(4) DEFAULT '0'  COMMENT  ?;", ['Тип оффера - попап или отдельная страница. В данный момент используется только отдельная страница. Попап формально поддерживается'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Offer` MODIFY `ShowsCount` int(10) unsigned DEFAULT '0'  COMMENT  ?;", ['Общее число показов оффера, повышается при показе оффера пользователю'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Offer` MODIFY `Priority` int(11) DEFAULT '0'  COMMENT  ?;", ['Офферы с более высоким приоритетом показываются раньше офферов с более низким.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Offer` MODIFY `Kind` int(11) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Офферы группируются в типы, чтобы пользователь мог отказаться от показа всех офферов одного типа'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Offer` MODIFY `MaxShows` int(11) DEFAULT NULL  COMMENT  ?;", ['Максимальное число показов оффера пользователю'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `OfferImpersonate` COMMENT  ?;", ['Таблица сотрудников AwardWallet, которым не будет показываться оффер при имперсонейте. Скрипт "$":https://awardwallet.com/manager/disableOffer.php'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `OfferImpersonate` MODIFY `Login` varchar(30) NOT NULL  COMMENT  ?;", ['Логин сотрудника'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `OfferImpersonate` MODIFY `Disabled` int(11) DEFAULT '1'  COMMENT  ?;", ['Всегда равно 1. Установка в 0 не приведёт к показу оффера, для этого нужно удалить запись из таблицы'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `OfferKindRefused` COMMENT  ?;", ['Типы офферов, от показа которых отказался пользователь (см. Поле Offer.Kind)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `OfferKindRefused` MODIFY `OfferKind` int(11) DEFAULT NULL  COMMENT  ?;", ['Тип оффера, соответстувет полю Offer.Kind'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `OfferLog` COMMENT  ?;", ['Журнал показов/согласий/отказов офферов'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `OfferLog` MODIFY `Action` int(11) DEFAULT NULL  COMMENT  ?;", ['Действие. Null = показ, 0 - отказ от оффера, 1 - согласие на оффер, 2 - отказ от всех офферов данного Kind'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `OfferLog` MODIFY `ActionDate` datetime DEFAULT NULL  COMMENT  ?;", ['Время события'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `OfferUser` COMMENT  ?;", ['Таблица, определяющая, какие офферы каким пользователям показываются'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `OfferUser` MODIFY `CreationDate` datetime NOT NULL  COMMENT  ?;", ['Время создания записи'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `OfferUser` MODIFY `Manual` tinyint(4) NOT NULL DEFAULT '1'  COMMENT  ?;", ['При использовании функции Search Users и автоматическом обновлении все старые записи, не отвечающие новым требованиям оффера, стираются. Это не касается записей, у которых Manual = 1'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `OfferUser` MODIFY `Params` text DEFAULT NULL  COMMENT  ?;", ['Переменные оффера для конкретного пользователя. Формат: переменная=значение, разделённые переводом строки'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `OfferUser` MODIFY `Agreed` tinyint(4) DEFAULT NULL  COMMENT  ?;", ['1 - пользователь согласился на оффер, 0 - пользователь отказался от оффера, null - ни то, ни друое '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `OfferUser` MODIFY `ShowDate` datetime DEFAULT NULL  COMMENT  ?;", ['Время последнего показа оффера этому пользователю'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `OfferUser` MODIFY `ShowsCount` int(11) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Число состоявщихся показов оффера этому пользователю'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` COMMENT  ?;", ['Настройка провайдеров'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `Name` varchar(200) NOT NULL  COMMENT  ?;", ['Название компании, то что написано на самолете или на здании оттеля.
*Value*:
United'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `Code` varchar(20)  COMMENT  ?;", ['это то что будет уникально идентифицировать эту программу. Так будет названа папка и это слово будет в названии класса и еще много-много где. только маленькие буквы, без пробелов
*Value*:
united'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `Kind` tinyint(4) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Тип программы, т.е. в какую закладку эта программа попадет.
*Value*:
Airline'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `LoginCaption` varchar(80) NOT NULL DEFAULT ''  COMMENT  ?;", ['что требуется ввести для логина. В случае когда надо дать подсказку можно ввести Login (Mileage Plus # or email or screen name) тогда то что в скобках покажется в виде подсказки под полем и не ипортит форму длинным названием поля.
*Value*:
Mileage Plus #'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `DisplayName` varchar(100) DEFAULT ''  COMMENT  ?;", ['это название будет чаще всего показываться в интерфейсе. Например в списке програм она будет отбражена именно так.
*Value*:
United (Mileage Plus)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `ProgramName` varchar(80) NOT NULL DEFAULT ''  COMMENT  ?;", ['название бонусной программы
*Value*:
Milage Plus'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `PasswordCaption` varchar(80) DEFAULT NULL  COMMENT  ?;", ['что требуется в виде пароля, не обязятаельное поле
*Value*:
PIN'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `Site` varchar(80) NOT NULL DEFAULT ''  COMMENT  ?;", ['главная страница сайта
*Value*:
http://www.united.com'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `State` tinyint(4) NOT NULL DEFAULT '0'  COMMENT  ?;", ['если не надо чтобы народ уже начал добавлять программы то пока код полностью не готов надо чтобы порграмма была disabled. Если уже народ надобавлял програм и ее поменяли на disabled то программа полностью исчезнет с сайта. Т.е. ее нельзя будет добавить и уже добавленные программы (например с ошибками) тоже исчезнут из профилей людей. Потом поменяв на enabled все появится назад.
*Collecting requests* - копим запросы на добавление
*Collecting accounts* - собираем аккаунты, кренделя приходят на почту, при проверке выдается UE
*Beta users only* - ?
*In development* - ?
*Enabled* - включена, работает
*Fixing* - находится в починке
*Checking off* - нет проверки аккаунтов в бэкграунде
*Checking AWPlus only* - проверка аккаунтов в бэкграунде только для AW+
*Checking only through extension* - нет никакой проверки, кроме как через extension
*Disabled* - выключена
*Test* - ?
*WSDL Only* - ?
*Hidden provider(e-mail parsing)* - ? '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `Login2Caption` varchar(80) DEFAULT NULL  COMMENT  ?;", ['как правило пустое, но бывают программы у которые 2 поля логина как например у дельты
*Value*:
Last Name'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `CanCheck` int(11) NOT NULL DEFAULT '1'  COMMENT  ?;", ['возможна ли проверка данного провайдера
*Value*:
true/false'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `CanCheckBalance` int(11) NOT NULL DEFAULT '1'  COMMENT  ?;", ['если нет главного баланса, нужно в свойствах LP поставить false
*Value*:
true/false'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `CanCheckConfirmation` int(11) NOT NULL DEFAULT '0'  COMMENT  ?;", ['если возможна проверка резерваций по Confirmation Number, то нужно поставить true
*Value*:
true/false'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `CanCheckItinerary` int(11) NOT NULL DEFAULT '0'  COMMENT  ?;", ['если возможен парсинг резерваций, то нужно поставить true
*Value*:
true/false'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `ExpirationDateNote` text DEFAULT NULL  COMMENT  ?;", ['Описание логики вычисления Expiration Date (показывается пользователям)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `BalanceFormat` varchar(60) DEFAULT NULL  COMMENT  ?;", ['Форматирование баланса
*Allow Float* - true, если баланс дробное число
*Value*:
$%0.2f
function'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `ShortName` varchar(255) NOT NULL  COMMENT  ?;", ['в 99% случаев тоже самое что и Name но если Name больше 50 или 70 символов то как то надо сокращать.
*Value*:
United'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `ImageURL` varchar(512) DEFAULT NULL  COMMENT  ?;", ['аффилиат ссылка (image), срабатывающая при переходе пользователя на сайт провайдера'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `ClickURL` varchar(512) DEFAULT NULL  COMMENT  ?;", ['аффилиат ссылка (click), срабатывающая при переходе пользователя на сайт провайдера'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `WSDL` tinyint(4) NOT NULL DEFAULT '0'  COMMENT  ?;", ['если true, значит программа доступна для партнеров
*Value*:
true/false'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `CanCheckExpiration` tinyint(4) NOT NULL DEFAULT '0'  COMMENT  ?;", ['возможно ли проверить Expiration Date
*Expiration Always Known* - true, если всегда можно собрать Expiration Date
*Value*:
*No*
*Yes*
*Never expires*'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `ExpirationUnknownNote` varchar(2000) DEFAULT NULL  COMMENT  ?;", [' Текстовка, которая показывается в попапе при клике на "Unknown" в колонке Expiration. Если поле в базе пустое, то по умолчанию показывается текст: "At this point AwardWallet doesn’t know how to get your expiration date for this program..." если заполнено, то только тот текст, которым заполнено это поле'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `iPhoneAutoLogin` tinyint(4) NOT NULL DEFAULT '1'  COMMENT  ?;", ['Мобильный автологин
*Value*:
*Disabled* - в приложении будет "Go to site", кинет на LoginUrl из схемы Provider
*Server* - попытается сделать серверный автологин, надпись "Go to site" - т.к. серверный в мобильных очень плохо работает.
*Mobile extension* - надпись "Autologin", автологин через мобильный экстеншн. В случае какой-либо ошибки кинет на LoginUrl
*Desktop extension* - надпись "Autologin", автологин через десктопный экстеншн. В случае ошибки кинет на LoginUrl.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `Currency` int(11) NOT NULL DEFAULT '2'  COMMENT  ?;", ['в чем измеряется баланс
*Value*:
Miles, Points, Dollars'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `AllianceID` int(11)  COMMENT  ?;", ['если программа принадлежит к какому либо альянсу, то здесь можно его указать
*Value*:
Oneworld, SkyTeam, Star Alliance etc.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `Questions` tinyint(4) NOT NULL DEFAULT '0'  COMMENT  ?;", ['есть ли у программы секретные вопросы
*Value*:
true/false'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `Note` text DEFAULT NULL  COMMENT  ?;", ['текст показывается при отображении программы в списках Working Programs, Disabled, Considering to add'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `EliteLevelsCount` tinyint(4) DEFAULT NULL  COMMENT  ?;", ['Количество элитных уровней, выставляется без учета уровня №0. при отсутствии уровней выставляется "-1"'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `CanCheckCancelled` int(10) unsigned DEFAULT '0'  COMMENT  ?;", ['если возможно проверить, что резервация была отменена, то нужно поставить true
*Value*:
true/false'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `CheckInBrowser` tinyint(4) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Проверка через extension
*Value*:
*No*
*Yes, keep data in browser*
*Yes, copy data to database*'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `CanCheckHistory` tinyint(3) unsigned NOT NULL DEFAULT '0'  COMMENT  ?;", ['если возможен сбор истории для провайдера, то нужно поставить true
*Value*:
true/false'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `ExpirationAlwaysKnown` tinyint(4) NOT NULL DEFAULT '1'  COMMENT  ?;", ['true, если всегда можно собрать Expiration Date
*Value*:
*No*
*Yes*
*Never expires*'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `RequestsPerMinute` int(11) DEFAULT NULL  COMMENT  ?;", ['если существует вероятность того, что провайдер может заблокировать наши инстансы, то нужно выставить троттлинг для него (указать число запросов к сайту провайдера в минуту)
*Value*:
60'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `CanCheckNoItineraries` tinyint(3) unsigned NOT NULL DEFAULT '0'  COMMENT  ?;", ['если возможно проверить отстутсвие резеваций, то нужно поставить true
*Value*:
true/false'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `PlanEmail` varchar(120) DEFAULT NULL  COMMENT  ?;", [' для определения провайдера при парсинге писем.
Подробнее тут "Traxo email parsing":http://redmine.awardwallet.com/projects/awwa/wiki/Traxo_email_parsing#%D0%9F%D1%80%D0%BE%D0%B2%D0%B5%D1%80%D0%BA%D0%B0-%D0%BE%D0%BF%D1%80%D0%B5%D0%B4%D0%B5%D0%BB%D0%B5%D0%BD%D0%B8%D1%8F-%D0%BF%D1%80%D0%BE%D0%B2%D0%B0%D0%B9%D0%B4%D0%B5%D1%80%D0%B0
*Value*:
@(\\w+\\.)*delta\\.com'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `InternalNote` text DEFAULT NULL  COMMENT  ?;", ['Для внутренних заметок'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `CalcEliteLevelExpDate` tinyint(1) NOT NULL DEFAULT '0'  COMMENT  ?;", ['возможно ли рассчитать Expiration Date для элитного уровня'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `ItineraryAutologin` int(11) DEFAULT '0'  COMMENT  ?;", ['Автологин и редирект на страницу с нужной резерваций (с помощью extension). Обязательно для проверки программистом.
*Value*:
*Disabled* - отключен
*Account* - автологин в резервацию, которая была собрана с аккаунта
*Confirmation number* - автологин в резервацию, которая была собрана с помощью Confirmation number
*Account and confirmation number* - поддерживаются оба варианта'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Provider` MODIFY `Category` tinyint(4) DEFAULT '3'  COMMENT  ?;", ['Кейс #6873. Категория авиалинии для расчёта доли AA. По умолчанию 3. При добавлении новой программы следует ставить 3.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ProviderPartner` COMMENT  ?;", ['Список cashback-провайдеров и магазинов, которые они поддерживают'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ProviderPartner` MODIFY `ProviderID` int(11) NOT NULL  COMMENT  ?;", ['fk на провайдер-магазин'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ProviderPartner` MODIFY `PartnerID` int(11) NOT NULL  COMMENT  ?;", ['fk на провайдер-cashback'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ProviderPartner` MODIFY `Discount` varchar(50) NOT NULL  COMMENT  ?;", ['string, скидка, значение cashback'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ProviderPartner` MODIFY `Priority` int(11) NOT NULL DEFAULT '1'  COMMENT  ?;", ['int, порядок, в котором будет показываться список доступных cashback при первом автологине (низкий приоритет - первый в списке) '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ProviderPartner` MODIFY `SearchText` varchar(80) DEFAULT NULL  COMMENT  ?;", ['текст, по которому на сайте cashback ищется нужный провайдер'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ProviderPartner` MODIFY `UserData` varchar(50) DEFAULT NULL  COMMENT  ?;", ['любые данные, сейчас используется для обозначения, где в списке результатов поиска находится нужный провайдер'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ProviderProperty` COMMENT  ?;", ['Информация о собираемых свойствах провайдера'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ProviderProperty` MODIFY `ProviderID` int(11)  COMMENT  ?;", [' ProviderID из таблицы Provider '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ProviderProperty` MODIFY `Name` varchar(80) NOT NULL  COMMENT  ?;", [' Название свойства (показывается пользователям) '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ProviderProperty` MODIFY `Code` varchar(40) NOT NULL  COMMENT  ?;", [' Код свойства (используется в парсерах) '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ProviderProperty` MODIFY `SortIndex` int(11) NOT NULL  COMMENT  ?;", [' Порядок расположения свойства при показе информации '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ProviderProperty` MODIFY `Required` tinyint(4) NOT NULL DEFAULT '1'  COMMENT  ?;", [' Всегда ли должно присутствовать это свойство'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ProviderProperty` MODIFY `Kind` tinyint(4) DEFAULT NULL  COMMENT  ?;", [' Тип свойства (Name, Lifetime points, Expiring balance и т.д.)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ProviderProperty` MODIFY `Visible` tinyint(4) NOT NULL DEFAULT '1'  COMMENT  ?;", [' Видимость свойства (показывать его юезру или нет)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Redirect` COMMENT  ?;", ['Информация с названиями рекламный акций и ссылками на сайты рекламодателей'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Redirect` MODIFY `URL` varchar(1000) NOT NULL  COMMENT  ?;", [' Ссылка на сайт рекламодателя '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Redirect` MODIFY `Name` varchar(128) NOT NULL DEFAULT 'Noname'  COMMENT  ?;", [' Название рекламы '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ScanHistory` COMMENT  ?;", ['История спарсенных имейлов из ящиков юзеров'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ScanHistory` MODIFY `AccountID` int(11)  COMMENT  ?;", [' заполняется только если данными из этого письма был обновлен этот аккаунт '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ScanHistory` MODIFY `ParsedJson` text DEFAULT NULL  COMMENT  ?;", [' json спарсенных данных + subject письма '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ScanHistory` MODIFY `Processed` tinyint(4) NOT NULL DEFAULT '0'  COMMENT  ?;", [' когда ящик сканируется, сначала данные записываются с Processed = 0, и уже после скана проходимся по записям и ставим Processed = 1 '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ScanHistory` MODIFY `EmailToken` varchar(128) NOT NULL  COMMENT  ?;", [' уникальная фишка имейла, берется из заголовка message-id письма без <> '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ScanHistory` MODIFY `EmailDate` datetime NOT NULL  COMMENT  ?;", [' дата письма из заголовка Date '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `ScanHistory` MODIFY `ParsedType` tinyint(4) DEFAULT NULL  COMMENT  ?;", [' тип спарсенных данных (statement, itinerary, ...) '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TextEliteLevel` COMMENT  ?;", ['Ключевые слова для элитных уровней. С помощью ключевых слов ищется уровень соответствующий строке статуса, которая пришла из парсера'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TextEliteLevel` MODIFY `EliteLevelID` int(11) NOT NULL  COMMENT  ?;", ['Ссылка на элитный уровень'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TextEliteLevel` MODIFY `ValueText` varchar(250) NOT NULL  COMMENT  ?;", ['Ключевое слово\\строка'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TravelPlan` COMMENT  ?;", ['Таблица травел планов'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TravelPlan` MODIFY `Name` varchar(250) NOT NULL  COMMENT  ?;", ['Название плана'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TravelPlan` MODIFY `StartDate` datetime DEFAULT NULL  COMMENT  ?;", ['Дата начала плана'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TravelPlan` MODIFY `EndDate` datetime DEFAULT NULL  COMMENT  ?;", ['Дата окончания плана'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TravelPlan` MODIFY `PictureVer` int(11) DEFAULT NULL  COMMENT  ?;", ['Имя файла аватара для плана'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TravelPlan` MODIFY `PictureExt` varchar(5) DEFAULT NULL  COMMENT  ?;", ['Расширение файла аватара для плана'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TravelPlan` MODIFY `Code` varchar(20) DEFAULT NULL  COMMENT  ?;", ['Случайный набор букв для подтверждение доступа к расшареному плану'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TravelPlan` MODIFY `AutoUpdateDate` datetime DEFAULT NULL  COMMENT  ?;", ['Дата последнего автообновления'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TravelPlan` MODIFY `MailDate` datetime DEFAULT NULL  COMMENT  ?;", ['Дата отсылки напоминания'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TravelPlan` MODIFY `UserAgentID` int(11)  COMMENT  ?;", ['Если план создан для агента то указан его ID иначе null'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TravelPlan` MODIFY `Public` int(11) NOT NULL DEFAULT '1'  COMMENT  ?;", ['Доступ для просмотра любым пользователем'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TravelPlan` MODIFY `PlanGroupID` int(11)  COMMENT  ?;", ['Для объединенных в группу планов'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TravelPlan` MODIFY `CustomDates` tinyint(4) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Даты плана указаны вручную (для определения изменений при автообновлении)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TravelPlan` MODIFY `CustomName` tinyint(4) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Название плана изменено пользователем (для определения изменений при автообновлении)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TravelPlan` MODIFY `Hidden` tinyint(4) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Удаление плана если 0 - план показан 1 - план будет показан в разделе удаленных'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TravelPlan` MODIFY `CustomUserAgent` tinyint(4) NOT NULL DEFAULT '0'  COMMENT  ?;", ['ID агента указан вручную, пользователем (для определения изменений при автообновлении)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TravelPlanSection` COMMENT  ?;", ['Таблица связей между таблицами поездок/заказов и травел планами'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TravelPlanSection` MODIFY `SectionKind` char(1) DEFAULT NULL  COMMENT  ?;", ['Таблица донор '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TravelPlanSection` MODIFY `SectionID` int(11) DEFAULT NULL  COMMENT  ?;", ['Идентификатор строки таблицы донора'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TravelPlanShare` COMMENT  ?;", ['Таблица связей расшареных планов'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TravelPlanShare` MODIFY `TravelPlanID` int(11) NOT NULL  COMMENT  ?;", ['Идентификатор плана'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TravelPlanShare` MODIFY `UserAgentID` int(11) NOT NULL  COMMENT  ?;", ['Идентификатор агента для которого расшарен план'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Trip` COMMENT  ?;", ['Таблица поездок'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Trip` MODIFY `AccountID` int(11)  COMMENT  ?;", ['Идентификатор аккаунта в системе провайдера'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Trip` MODIFY `RecordLocator` varchar(100) DEFAULT NULL  COMMENT  ?;", ['Уникальный номер сделанного заказа'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Trip` MODIFY `TravelPlanID` int(11)  COMMENT  ?;", ['К какому травел плану относится'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Trip` MODIFY `Hidden` smallint(6) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Удален или нет'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Trip` MODIFY `Parsed` int(11) NOT NULL DEFAULT '0'  COMMENT  ?;", ['установлен в 1 если данные получены в результате парсинга резерваций на сайте провайдера'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Trip` MODIFY `AirlineName` varchar(250) DEFAULT NULL  COMMENT  ?;", ['Название компании совершающей рейс'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Trip` MODIFY `Notes` varchar(4000) DEFAULT NULL  COMMENT  ?;", ['Заметки'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Trip` MODIFY `ProviderID` int(11)  COMMENT  ?;", ['Идентификатор компании в таблице Provider'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Trip` MODIFY `Moved` int(11) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Флаг для перемещенных вручную сегментов'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Trip` MODIFY `UpdateDate` datetime DEFAULT NULL  COMMENT  ?;", ['Дата последнего автообновления'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Trip` MODIFY `ConfFields` varchar(250) DEFAULT NULL  COMMENT  ?;", ['Данные подтверждения'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Trip` MODIFY `Category` int(11) NOT NULL DEFAULT '1'  COMMENT  ?;", ['Категория поездки *A* - Самолет, *T* - Поезд, *F* - Паром, *C* - Круиз или *B* - Автобус'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Trip` MODIFY `Direction` tinyint(4) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Для поездок туда и обратно 0- туда(default) 1- обратно'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Trip` MODIFY `CreateDate` datetime NOT NULL  COMMENT  ?;", ['Дата создания строки'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Trip` MODIFY `Cancelled` tinyint(2) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Отменено'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Trip` MODIFY `UserAgentID` int(11)  COMMENT  ?;", ['Идентификатор агента для которого создана эта поездка'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Trip` MODIFY `Copied` tinyint(4) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Флаг скопированных вручную поездок'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Trip` MODIFY `Modified` tinyint(4) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Флаг измененных вручную данных поездки'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Trip` MODIFY `MailDate` datetime DEFAULT NULL  COMMENT  ?;", ['Дата рассылки'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TripSegment` MODIFY `TripID` int(11) NOT NULL  COMMENT  ?;", ['ID поездки к которой относится данный сегмент'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TripSegment` MODIFY `DepCode` varchar(10) DEFAULT NULL  COMMENT  ?;", ['Код места отправления'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TripSegment` MODIFY `DepName` varchar(250) NOT NULL  COMMENT  ?;", ['Название места отправления'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TripSegment` MODIFY `DepDate` datetime NOT NULL  COMMENT  ?;", ['Дата отправления'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TripSegment` MODIFY `ArrCode` varchar(10)  COMMENT  ?;", ['Код места назначения'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TripSegment` MODIFY `ArrName` varchar(250) NOT NULL  COMMENT  ?;", ['Название места назначения'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TripSegment` MODIFY `ArrDate` datetime NOT NULL  COMMENT  ?;", ['Дата прибытия'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TripSegment` MODIFY `AirlineName` varchar(250) DEFAULT NULL  COMMENT  ?;", ['Название компании совершающей рейс'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TripSegment` MODIFY `FlightNumber` varchar(20) DEFAULT NULL  COMMENT  ?;", ['Номер рейса'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TripSegment` MODIFY `DepGeoTagID` int(11)  COMMENT  ?;", ['GeoTag места отправления'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TripSegment` MODIFY `ArrGeoTagID` int(11)  COMMENT  ?;", ['GeoTag места назначения'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TripSegment` MODIFY `CheckinNotified` int(11) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Рассылка произведена'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TripSegment` MODIFY `TravelPlanID` int(11)  COMMENT  ?;", ['К какому травел плану относится'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `TripSegment` MODIFY `ShareCode` varchar(32) DEFAULT NULL  COMMENT  ?;", ['Случайный набор символов для проверки расшаренного сегмента'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserAgent` COMMENT  ?;", ['Таблица описания агентов пользователя'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserAgent` MODIFY `AgentID` int(11) NOT NULL  COMMENT  ?;", ['Идентификатор агента (*UserID* )'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserAgent` MODIFY `ClientID` int(11)  COMMENT  ?;", ['Идентификатор клиента (*UserID*) может быть пустым'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserAgent` MODIFY `FirstName` varchar(30) DEFAULT NULL  COMMENT  ?;", ['Имя клиента (при пустом *ClientID*)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserAgent` MODIFY `LastName` varchar(30) DEFAULT NULL  COMMENT  ?;", ['Фамилия клиента (при пустом *ClientID*)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserAgent` MODIFY `Email` varchar(80) DEFAULT NULL  COMMENT  ?;", ['Email клиента (при пустом *ClientID*)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserAgent` MODIFY `AccessLevel` int(11) NOT NULL  COMMENT  ?;", ['Уровень доступа к аккаунту (1-4) соответствует глобальным константам *ACCESS_READ*, *ACCESS_WRITE*, *ACCESS_ADMIN*'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserAgent` MODIFY `IsApproved` int(11) NOT NULL  COMMENT  ?;", ['Подтверждение указанных прав доступа(устанавливается после подтверждения клиентом)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserAgent` MODIFY `Notes` text DEFAULT NULL  COMMENT  ?;", ['Комментарий'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserAgent` MODIFY `ShareByDefault` int(11) NOT NULL DEFAULT '1'  COMMENT  ?;", ['Автоматический доступ к данным клиента'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserAgent` MODIFY `ShareCode` varchar(10) DEFAULT NULL  COMMENT  ?;", ['Случайный код для подтверждения'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserAgent` MODIFY `Source` char(1) NOT NULL DEFAULT 'A'  COMMENT  ?;", ['Литерал указывающий тип доступной информации *A* - _Балансы_ *T* - _Поездки_ * - _Оба типа_'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserAgent` MODIFY `TripShareByDefault` int(11) NOT NULL DEFAULT '0'  COMMENT  ?;", ['Автоматический доступ к данным поездок'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserAgent` MODIFY `ShareDate` datetime DEFAULT NULL  COMMENT  ?;", ['Дата создания записи'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserAgent` MODIFY `PictureVer` int(11) DEFAULT NULL  COMMENT  ?;", ['Имя файла аватарки'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserAgent` MODIFY `PictureExt` varchar(5) DEFAULT NULL  COMMENT  ?;", ['Расширение файла аватарки'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserAgent` MODIFY `ItineraryCalendarCode` varchar(32)  COMMENT  ?;", ['Код Google Calendar для доступа к календарю поездок'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserEmail` COMMENT  ?;", ['Почтовые аккаунты, которые пользователи добавили себе на сайте (/userMailbox/view)'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserEmail` MODIFY `Email` varchar(80) NOT NULL  COMMENT  ?;", ['логин'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserEmail` MODIFY `Password` varchar(4000) NOT NULL  COMMENT  ?;", ['зашифрованный пароль'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserEmail` MODIFY `Status` tinyint(1) NOT NULL  COMMENT  ?;", ['int, общее состояние добавленного ящика, значения - Useremail::STATUS_*'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserEmail` MODIFY `Connected` tinyint(4) NOT NULL DEFAULT '4'  COMMENT  ?;", [' int, состояние соединения непосредственно с самим ящиком, значения Useremail::MAILBOX_*'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserEmail` MODIFY `Added` datetime NOT NULL  COMMENT  ?;", ['дата добавления'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserEmail` MODIFY `UpdateDate` datetime DEFAULT NULL  COMMENT  ?;", ['дата последнего обновления'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserEmail` MODIFY `MailboxType` tinyint(4) NOT NULL DEFAULT '1'  COMMENT  ?;", [' int, тип ящика, Useremail::MAILBOX_* (да, нехорошо, но фантазия тут отказала) '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserEmail` MODIFY `ErrorMessage` varchar(255) DEFAULT NULL  COMMENT  ?;", [' текст ошибки '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserEmailToken` COMMENT  ?;", ['Токены для oauth аутентификации в пользовательские gmail/live ящики'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserEmailToken` MODIFY `Token` varchar(1000) DEFAULT NULL  COMMENT  ?;", ['string, токен'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserEmailToken` MODIFY `Added` datetime NOT NULL  COMMENT  ?;", ['дата добавления'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `UserEmailToken` MODIFY `TokenType` tinyint(4) NOT NULL DEFAULT '1'  COMMENT  ?;", [' тип токена (access, refresh) '], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Visit` COMMENT  ?;", ['Таблица посещений сайтов. Посещение регистрируется при логине.'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Visit` MODIFY `VisitDate` date NOT NULL  COMMENT  ?;", ['Дата посещений'], [\PDO::PARAM_STR]);
        $this->addSql("ALTER TABLE `Visit` MODIFY `Visits` int(11) DEFAULT '1'  COMMENT  ?;", ['Количество посещений в этот день'], [\PDO::PARAM_STR]);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
