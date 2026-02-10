<?php

namespace AwardWallet\MainBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20131107115009 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        return;
        $this->addSql("ALTER TABLE `AbAccountProgram` COMMENT 'Приложенные к букинг-запросу добавленные аккаунты';");
        $table = $schema->getTable('AbAccountProgram');
        $table->getColumn('RequestID')->setComment('Ссылка на букинг-запрос');
        $table->getColumn('AccountID')->setComment('Ссылка на добавленный аккаунт');
        $table->getColumn('Requested')->setComment('Запрошен или нет аккаунт на шаринг букером');

        $this->addSql("ALTER TABLE `AbBookerInfo` COMMENT 'Информация о букерах';");
        $table = $schema->getTable('AbBookerInfo');
        $table->getColumn('UserID')->setComment('Ссылка на aw-аккаунт букера');
        $table->getColumn('Price')->setComment('Цена услуг букера за 1 пассажира');
        $table->getColumn('PricingDetails')->setComment('Описание тарифов букера. Показывается, например, при создании букинг запроса');
        $table->getColumn('ServiceName')->setComment('Наименование букера. Фирма. Вероятно будет удалено в пользу Usr.Company');
        $table->getColumn('ServiceShortName')->setComment('Краткое наименование букера. Используется в качестве разного рода префиксов в URL');
        $table->getColumn('Address')->setComment('Адрес букера. Возможно использование в качестве адреса для высылки чека');
        $table->getColumn('ServiceURL')->setComment('URL букера');
        $table->getColumn('OutboundPercent')->setComment('Процент, который прилагается нам, если букинг запрос написал наш пользователь');
        $table->getColumn('InboundPercent')->setComment('Процент, который прилагается нам, если букинг запрос написал пользователь, пришедший с сайта букера');
        $table->getColumn('SmtpServer')->setComment('Smtp сервер букера');
        $table->getColumn('SmtpPort')->setComment('Smtp порт букера');
        $table->getColumn('SmtpUseSsl')->setComment('Использует ли сервер букера SSL');
        $table->getColumn('SmtpUsername')->setComment('Юзернейм');
        $table->getColumn('SmtpPassword')->setComment('Пароль');
        $table->getColumn('SmtpError')->setComment('Последняя ошибка SMTP. Служит для переключения с SMTP на наш сервер отправки, если по каким то причинам сервер букера отказывает отправлять письма');
        $table->getColumn('SmtpErrorDate')->setComment('Дата последней ошибки SMTP. Служит для того, чтобы через опред.интеревал времени вернуть возможность отправки писем через сервер букера');
        $table->getColumn('Greeting')->setComment('Приветствие букера. Показывается при создании букинг запроса');
        $table->getColumn('AutoReplyMessage')->setComment('Автосообщение, которое автоматически вставляется в тред переписки после создания нового букинг запроса');
        $table->getColumn('SiteAdID')->setComment('Ссылка на идентификатор, который используется для определения чей юзер составил букинг-запрос - наш или букера');

        $this->addSql("ALTER TABLE `AbCustomProgram` COMMENT 'Приложенные к букинг запросу кастомные программы (не путать с кастомными aw-программами)';");
        $table = $schema->getTable('AbCustomProgram');
        $table->getColumn('Name')->setComment('Наименование программы');
        $table->getColumn('Owner')->setComment('Собственник программы');
        $table->getColumn('EliteStatus')->setComment('Элитный статус программы');
        $table->getColumn('Balance')->setComment('Баланс по кастомной программе');
        $table->getColumn('RequestID')->setComment('Ссылка на букинг запрос');
        $table->getColumn('ProviderID')->setComment('Ссылка на провайдера. Ее создает сам букер сопоставляя вбитое наименование программы с реальной существующей у нас');
        $table->getColumn('Requested')->setComment('Запрошена ли программа на шаринг. Только после сопоставления с реальной программой. После юзер все равно добавляет реальный аккаунт и запись в этой таблице исчезает и переходит в AbAccountProgram');

        $this->addSql("ALTER TABLE `AbInvoice` COMMENT 'Счет, выписываемый букером к юзеру';");
        $table = $schema->getTable('AbInvoice');
        $table->getColumn('Tickets')->setComment('Кол-во билетов, которые предоставил букер');
        $table->getColumn('Price')->setComment('Стоимость 1 билета. Берется из AbBookerInfo.Price');
        $table->getColumn('Discount')->setComment('Скидка, если указана');
        $table->getColumn('Taxes')->setComment('Налоги авиакомпании');
        $table->getColumn('Status')->setComment('Статус. Оплачен или нет');
        $table->getColumn('PaymentType')->setComment('Тип платежа: кредиткой или чеком');
        $table->getColumn('MessageID')->setComment('Ссылка на сообщение. Счет выставляется как сообщение в треде переписки');

        $this->addSql("ALTER TABLE `AbInvoiceMiles` COMMENT 'Прикладываемые к счету мили определенных программ (необходимы для букера для заказа билетов)';");
        $table = $schema->getTable('AbInvoiceMiles');
        $table->getColumn('CustomName')->setComment('Название программы');
        $table->getColumn('Balance')->setComment('Требуемый баланс');
        $table->getColumn('InvoiceID')->setComment('Ссылка на счет');

        $this->addSql("ALTER TABLE `AbMessage` COMMENT 'Сообщения в переписке между букером и юзером';");
        $table = $schema->getTable('AbMessage');
        $table->getColumn('CreateDate')->setComment('Дата');
        $table->getColumn('Post')->setComment('Текст сообщения');
        $table->getColumn('Type')->setComment('Тип сообщения (внутреннее или обычное, системное)');
        $table->getColumn('Metadata')->setComment('Необходимые данные для системных сообщений');
        $table->getColumn('RequestID')->setComment('Ссылка на букинг запрос');
        $table->getColumn('UserID')->setComment('Автор сообщения');

        $this->addSql("ALTER TABLE `AbPassenger` COMMENT 'Списки пассажиров, которые прикладываются к букинг запросу';");
        $table = $schema->getTable('AbPassenger');
        $table->getColumn('FirstName')->setComment('Имя');
        $table->getColumn('MiddleName')->setComment('Отчество');
        $table->getColumn('LastName')->setComment('Фамилия');
        $table->getColumn('Birthday')->setComment('Дата рождения');
        $table->getColumn('Nationality')->setComment('Гражданство');
        $table->getColumn('RequestID')->setComment('Ссылка на букинг запрос');

        $this->addSql("ALTER TABLE `AbRequest` COMMENT 'Букинг запрос';");
        $table = $schema->getTable('AbRequest');
        $table->getColumn('ContactName')->setComment('Контактное имя (обычно Имя и Фамилия из таблицы Usr)');
        $table->getColumn('ContactEmail')->setComment('Контактный email');
        $table->getColumn('ContactPhone')->setComment('Контактный телефон (опционально)');
        $table->getColumn('Status')->setComment('Статус (открытый, проплаченный, отмененный, оставленный на будущее и т.д.)');
        $table->getColumn('Notes')->setComment('Примечания к букинг запросу');
        $table->getColumn('CreateDate')->setComment('Дата создания');
        $table->getColumn('LastUpdateDate')->setComment('Дата последнего обновления запроса (редактирование, какая то активность в виде написания сообщения)');
        $table->getColumn('RemindDate')->setComment('Дата, в которую букинг запрос перейдет в статус Открыт из статуса На будущее');
        $table->getColumn('CabinFirst')->setComment('Первый класс (опция)');
        $table->getColumn('CabinBusiness')->setComment('Бизнес класс (опция)');
        $table->getColumn('PriorSearchResults')->setComment('Пользователь отписывает (по желанию) обращался ли к другим букерам по своему запросу и что они требовали взамен');
        $table->getColumn('BookerUserID')->setComment('Букер, который обрабатывает данный букинг запрос');
        $table->getColumn('AssignedUserID')->setComment('На кого записан данный букинг запрос внутри букинг-бизнеса. Для внутренней организации букера.');
        $table->getColumn('UserID')->setComment('Ссылка на автора букинг запроса');
        $table->getColumn('BookingTransactionID')->setComment('Ссылка на транзакцию. Транзакции создаются каждый месяц в результате взаиморасчетов между нами и букером');

        $this->addSql("ALTER TABLE `AbRequestMark` COMMENT 'Прочитан ли букинг запрос, есть ли в нем новые сообщения';");
        $table = $schema->getTable('AbRequestMark');
        $table->getColumn('ReadDate')->setComment('Последняя дата прочтения букинг запроса (когда в последний раз заходил на страницу просмотра запроса)');
        $table->getColumn('UserID')->setComment('Пользователь');
        $table->getColumn('RequestID')->setComment('Ссылка на букинг запрос');
        $table->getColumn('IsRead')->setComment('Прочитан или нет запрос');

        $this->addSql("ALTER TABLE `AbSegment` COMMENT 'Сегменты (перелеты), прикладываемые к букинг запросу. ';");
        $table = $schema->getTable('AbSegment');
        $table->getColumn('DepDateFrom')->setComment('Дата вылета начальная');
        $table->getColumn('DepDateTo')->setComment('Дата вылета конечная');
        $table->getColumn('DepDateIdeal')->setComment('Дата вылета идеальная');
        $table->getColumn('ArrDateFrom')->setComment('Дата прибытия начальная');
        $table->getColumn('ArrDateTo')->setComment('Дата прибытия конечная');
        $table->getColumn('ArrDateIdeal')->setComment('Дата прибытия идеальная');
        $table->getColumn('Priority')->setComment('Порядок сортировки сегментов');
        $table->getColumn('RoundTrip')->setComment('Перелет туда-обратно?');
        $table->getColumn('RequestID')->setComment('Ссылка на букинг запрос');
        $table->getColumn('Dep')->setComment('Пункт отправления');
        $table->getColumn('Arr')->setComment('Пункт прибытия');

        $this->addSql("ALTER TABLE `AbTransaction` COMMENT 'Транзакции букинга. Для взаиморасчетов между администрацией AW и букером. ';");
        $table = $schema->getTable('AbTransaction');
        $table->getColumn('ProcessDate')->setComment('Дата создания');
        $table->getColumn('Processed')->setComment('Обработано (да или нет)');

        $this->addSql("ALTER TABLE `Account` COMMENT 'Информация об аккаунте';");
        $table = $schema->getTable('Account');
        $table->getColumn('ProviderID')->setComment(' ProviderID из тбалицы Provider');
        $table->getColumn('UserID')->setComment(' UserID из таблицы Usr');
        $table->getColumn('State')->setComment(' State провайдера (включена, выключена, в починке и т.д.)');
        $table->getColumn('ErrorCode')->setComment(' Код, возвращаемый при проверке аккаунта');
        $table->getColumn('ErrorMessage')->setComment(' Сообщение ошибки, возвращаемый при проверке аккаунта');
        //$table->getColumn('Balance')->setComment(' Баланс аккаунта (null, если нет)');
        $table->getColumn('Login')->setComment(' Логин');
        $table->getColumn('Pass')->setComment(' Пароль');
        $table->getColumn('Login2')->setComment(' Логин 2');
        $table->getColumn('comment')->setComment(' Комментарий к аккаунту (заполняется юзером)');
        $table->getColumn('SavePassword')->setComment(' Где сохранен пароль (в базе - 1, в браузере - 2)');
        $table->getColumn('ExpirationDate')->setComment('Expiration Date');
        $table->getColumn('ExpirationAutoSet')->setComment(' Определяет, кем установлена Expiration Date (нами, юзером или попала из субакка)');
        $table->getColumn('PassChangeDate')->setComment(' Дата изменения пароля');
        $table->getColumn('InvalidPassCount')->setComment(' Количетсво попыток логина с неверными кренделями');
        $table->getColumn('LastChangeDate')->setComment('Дата последнего изменения баланса');
        $table->getColumn('ChangeCount')->setComment(' Количество изменений баланса');
        $table->getColumn('LastActivity')->setComment(' Дата последней активности в аккаунте (собрана с сайта провайдера)');
        $table->getColumn('SubAccounts')->setComment(' Количество субаккаунтвов');
        //$table->getColumn('LastBalance')->setComment(' Предыдущее значение баланса');
        $table->getColumn('SuccessCheckDate')->setComment('Дата последней успешной проверки аккаунта');
        $table->getColumn('Login3')->setComment(' Логин 3');
        $table->getColumn('Question')->setComment(' Секретный вопрос, заданный при проверке аккаунта');
        $table->getColumn('DontTrackExpiration')->setComment('Установлена ли юзером галочка, что поинты не протухают');
        $table->getColumn('CheckedBy')->setComment('Кем проверен акк (wsdl, user)');
        $table->getColumn('Itineraries')->setComment('Количество резерваций в аккаунте');
        $table->getColumn('LastCheckItDate')->setComment('Дата последнего обновления резерваций');
        $table->getColumn('ErrorCount')->setComment('Количество ошибок при обновлении аккаунта (неверные кренделя, ошибки прова). Должен сбрасываться при успешной проверке - нужно для того, чтобы перестать проверять аккаунты с ошибками в бэкграунде при достижении определеноого порога');

        $this->addSql("ALTER TABLE `AccountBalance` COMMENT 'История изменения балансов по аккаунту, пишется только при изменении, нулевые и пустые значения не пишутся';");
        $table = $schema->getTable('AccountBalance');
        $table->getColumn('UpdateDate')->setComment('Дата изменения');
        //$table->getColumn('Balance')->setComment('Новый баланс');
        $table->getColumn('SubAccountID')->setComment('Указывает на подаккаунт у которого изменился баланс. Null если основной аккаунт');

        $this->addSql("ALTER TABLE `AccountHistory` COMMENT 'История транзакций (начисления, списания баллов), собирается из аккаунтов юзеров';");
        $table = $schema->getTable('AccountHistory');
        $table->getColumn('AccountID')->setComment(' AccountID из таблицы Account');
        $table->getColumn('PostingDate')->setComment(' Дата транзакции');
        $table->getColumn('Description')->setComment(' Описание транзакции');
        //$table->getColumn('Miles')->setComment(' Количество начисленных / списанных баллов');
        //$table->getColumn('Info')->setComment(' Сериализованные данные, содержающую другую полезную информацию о транзакции (Tier Points, Bonus и т.д.)');

        $this->addSql("ALTER TABLE `AccountProperty` COMMENT 'Свойства аккаунта (субаккаунта)';");
        $table = $schema->getTable('AccountProperty');
        $table->getColumn('ProviderPropertyID')->setComment(' ID свойства из таблицы ProviderProperty ');
        $table->getColumn('AccountID')->setComment(' AccountID, которому принаждлежит данное свойство (из таблицы Account)');
        $table->getColumn('Val')->setComment(' значение ');
        $table->getColumn('SubAccountID')->setComment(' ID субаккаунта из таблицы SubAccount ');

        $this->addSql("ALTER TABLE `Airline` COMMENT 'Таблица авиалиний. Заполнялась скриптом util/update/updateAirlines.php, список берется с \"$\":http://www.flightradar24.com/data/airplanes/';");
        $table = $schema->getTable('Airline');
        $table->getColumn('Code')->setComment('2-символьный код');
        $table->getColumn('ICAO')->setComment('3-символьный код');

        $this->addSql("ALTER TABLE `AllianceEliteLevel` COMMENT 'Элитные уровни альянсов.';");
        $table = $schema->getTable('AllianceEliteLevel');
        $table->getColumn('AllianceID')->setComment('Ссылка на альянс');
        $table->getColumn('Rank')->setComment('Положение уровня в элитной программе.');
        $table->getColumn('Name')->setComment('Название элитного уровня');

        $this->addSql("ALTER TABLE `Answer` COMMENT 'Секретные вопросы (спрашиваются при проверке аккаунта)';");
        $table = $schema->getTable('Answer');
        $table->getColumn('AccountID')->setComment(' AccountID из таблицы Account ');
        $table->getColumn('Question')->setComment(' Секретный вопрос ');
        $table->getColumn('Answer')->setComment(' Ответ на секретный вопрос');

        $this->addSql("ALTER TABLE `BonusConversion` COMMENT 'Таблица с запроса на конвертацию AW-бонусов, полученных от рефералов, на мили авиакомпаний. Запросы отправляются через форму \"$\":https://awardwallet.com/agent/redeem.php. Инструкция по конвертации \"AW Bonus Conversion Instructions\":http://redmine.awardwallet.com/projects/awwa/wiki/AW_Bonus_Conversion_Instructions';");
        $table = $schema->getTable('BonusConversion');
        $table->getColumn('Airline')->setComment('Название авиакомпании, например (US Airways, Delta, United).');
        $table->getColumn('Points')->setComment('Количество бонусов, которые надо обменять на мили.');
        $table->getColumn('Miles')->setComment('Количество миль, которые надо купить пользователю.');
        $table->getColumn('CreationDate')->setComment('Дата создания запроса.');
        $table->getColumn('Processed')->setComment('Обработан запрос или нет.');
        //$table->getColumn('Cost')->setComment('Сколько заплатола AW, чтобы купить мили.');
        $table->getColumn('UserID')->setComment('Пользователь, который отправил запрос.');
        $table->getColumn('AccountID')->setComment('Аккаунт, в который покупаются мили.');

        $table = $schema->getTable('Cart');
        $table->getColumn('IncomeTransactionID')->setComment('Ссылка на транзацкию в таблицеIncomeTransaction, к которой привязана эта Cart');

        $this->addSql("ALTER TABLE `Deal` COMMENT 'Промоушены, показываются на странице \"$\":http://awardwallet.com/promos, и во вкладке Promotions поп-апа в списке аккаунтов';");
        $table = $schema->getTable('Deal');
        $table->getColumn('ProviderID')->setComment('ссылка на провайдера');
        $table->getColumn('Title')->setComment('Заголовок');
        $table->getColumn('Description')->setComment('Описание');
        $table->getColumn('Link')->setComment('Ссылка на страницу регистрации для участия в промоушне');
        $table->getColumn('DealsLink')->setComment('Ссылка на промоушн');
        $table->getColumn('BeginDate')->setComment('Дата начала действия акции');
        $table->getColumn('EndDate')->setComment('Дата окончания действия акции');
        $table->getColumn('ButtonCaption')->setComment('заголовок для кнопки, ведущей на страницу промоушна');
        $table->getColumn('AutologinProviderID')->setComment('ссылка на провайдера, через которого произойдет автологин для последующего участия в промоушне');
        $table->getColumn('CreateDate')->setComment('дата создания промоушна');
        $table->getColumn('AffiliateLink')->setComment('реферальная AW-ссылка на промоушн, с нее нам будет идти комиссия');

        $this->addSql("ALTER TABLE `DealMark` COMMENT 'Активность пользователей в промоушнах. \"$\":http://awardwallet.com/promos';");
        $table = $schema->getTable('DealMark');
        $table->getColumn('UserID')->setComment('ссылка на пользователя');
        $table->getColumn('DealID')->setComment('ссылка на промоушн');
        $table->getColumn('Readed')->setComment('промоушн прочитан\\Mark as read');
        $table->getColumn('Follow')->setComment('нажали кнопку Follow Up - появится флажок-топор рядом с промоушеном');
        $table->getColumn('Applied')->setComment('кнопка Mark as applied');
        $table->getColumn('Manual')->setComment('кнопка +1');

        $this->addSql("ALTER TABLE `EliteLevel` COMMENT 'Таблица с уровнями элитных программ провайдеров.';");
        $table = $schema->getTable('EliteLevel');
        $table->getColumn('ProviderID')->setComment('Ссылка на провайдера, к которому относится уровень.');
        $table->getColumn('Rank')->setComment('Положение уровня в элитной программе.');
        $table->getColumn('CustomerSupportPhone')->setComment('Телефон поддержки клиентов.');
        $table->getColumn('AllianceEliteLevelID')->setComment('Ссылка на элитный уровень альянса, которому соответствует данный уровень.');
        $table->getColumn('Name')->setComment('Название уровня.');
        $table->getColumn('Description')->setComment('Комментарий к элитному уровню. Показывается в тултипе около названия элитного уровня: список аккаунтов -> pop-up -> вкладка \"Elite Levels\".');

        $this->addSql("ALTER TABLE `EliteLevelProgress` COMMENT 'Описания прогресс-баров для элитных уровней.';");
        $table = $schema->getTable('EliteLevelProgress');
        $table->getColumn('ProviderPropertyID')->setComment('Ссылка на свойство провайдера, знаение свойства сравнивается с условиями элитной программы и строится график.');
        $table->getColumn('StartDatePropertyID')->setComment('Ссылка на свойство, в котором хранится дата, с которой начинается обнуления(протухания) накопления для элитной программы.');
        $table->getColumn('EndMonth')->setComment('Сколько месяцев и дней, начиная с StartDatePropertyID, пройдет до обнуления данных элитной программы у аккаунта.');
        $table->getColumn('EndDay')->setComment('Сколько месяцев и дней, начиная с StartDatePropertyID, пройдет до обнуления данных элитной программы у аккаунта.');
        $table->getColumn('Lifetime')->setComment('*True*: накопления и статус в элитной программе сохраняются пожизненно, график риусется(?) снизу отдельно.\n*False*: накопления\\статус сбрасываются после определенной даты.');
        $table->getColumn('ToNextLevel')->setComment('В каком виде представленны данные на странице провадера\n*True*: относительная величина, количество миль\\бонусов до следующего уровня.\n*False*: абсолютная величина, количество миль\\бонусов от начала.');
        $table->getColumn('GroupIndex')->setComment('Группировка свойств:\n*NOT NULL*: группировка логическим AND, т.е. для достижения следующего уровня необходимо выполнение всех условий в группе с данным индексом,рисуется\n*NULL*: группировка OR, необходимо выполнение хотя бы одного условия.');
        $table->getColumn('StartLevelID')->setComment('Уровень с которого начинается отрисовка графика по свойству, по умолчанию с первого уровня из доступных.');

        $this->addSql("ALTER TABLE `EliteLevelValue` COMMENT 'Условия достижения элитных уровней.';");
        $table = $schema->getTable('EliteLevelValue');
        $table->getColumn('EliteLevelProgressID')->setComment('Ссылка на прогресс-бар');
        $table->getColumn('EliteLevelID')->setComment('Ссылка не элитный уровень');
        $table->getColumn('Value')->setComment('Условия достижения данного элитного уровня');

        $this->addSql("ALTER TABLE `ExtProperty` COMMENT 'Доплнительные свойства поездок/заказов';");
        $table = $schema->getTable('ExtProperty');
        $table->getColumn('SourceTable')->setComment('Контекст параметра является литералом\n*T* - Trip\n*R* - Reservation\n*S* - TripSegment\n*L* - Rental\n*E* - Restaurant\n*D* - Direction');
        $table->getColumn('SourceID')->setComment('Идентификатор в контексте');
        $table->getColumn('Name')->setComment('Название свойства/параметра');
        $table->getColumn('Value')->setComment('Значение (без изменений)');

        $this->addSql("ALTER TABLE `Flights` COMMENT 'Объединенная таблица для Trip и TripSegment для более гибкого управления травел планами';");
        $table = $schema->getTable('Flights');
        $table->getColumn('AccountID')->setComment('Идентификатор аккаунта в системе провайдера');
        $table->getColumn('RecordLocator')->setComment('Уникальный номер сделанного заказа');
        $table->getColumn('TravelPlanID')->setComment('К какому травел плану относится');
        $table->getColumn('Hidden')->setComment('Удаленный или нет');
        $table->getColumn('Parsed')->setComment('установлен в 1 если данные получены в результате парсинга резерваций на сайте провайдера');
        $table->getColumn('AirlineName')->setComment('Название компании совершающей рейс');
        $table->getColumn('Notes')->setComment('Заметки');
        $table->getColumn('ProviderID')->setComment('Идентификатор компании в таблице Provider');
        $table->getColumn('Moved')->setComment('Флаг для перемещенных вручную сегментов');
        $table->getColumn('UpdateDate')->setComment('Дата последнего автообновления');
        $table->getColumn('ConfFields')->setComment('Данные подтверждения');
        $table->getColumn('Category')->setComment('Категория поездки\n*A* - Самолет,\n*T* - Поезд, \n*F* - Паром, \n*C* - Круиз \n*B* - Автобус');
        $table->getColumn('Direction')->setComment('Для поездок туда и обратно 0- туда(default) 1- обратно');
        $table->getColumn('CreateDate')->setComment('Дата создания строки');
        $table->getColumn('LastOfferDate')->setComment('Дата показа оффера');
        $table->getColumn('CouponCode1')->setComment('Код купона');
        $table->getColumn('CouponCode2')->setComment('Код купона');
        //$table->getColumn('SavingsAmount')->setComment('Сумма скидок');
        //$table->getColumn('SavingsConfirmed')->setComment('Подвержденные скидки');
        $table->getColumn('AllowChangeLessSavings')->setComment('Возможно использование меньшего количества скидок');
        $table->getColumn('Cancelled')->setComment('Отменено');
        $table->getColumn('UserAgentID')->setComment('Идентификатор агента для которого создан этот сегмент');
        $table->getColumn('Copied')->setComment('Флаг скопированных вручную сегментов');
        $table->getColumn('Modified')->setComment('Флаг измененных вручную данных сегмента');
        $table->getColumn('MailDate')->setComment('Дата рассылки');
        $table->getColumn('DepCode')->setComment('Код места отправления');
        $table->getColumn('DepName')->setComment('Название места отправления');
        $table->getColumn('DepDate')->setComment('Дата отправления');
        $table->getColumn('ArrCode')->setComment('Код места назначения');
        $table->getColumn('ArrName')->setComment('Название места назначения');
        $table->getColumn('ArrDate')->setComment('Дата прибытия');
        $table->getColumn('FlightNumber')->setComment('Номер рейса');
        $table->getColumn('DepGeoTagID')->setComment('GeoTag места отправления');
        $table->getColumn('ArrGeoTagID')->setComment('GeoTag места назначения');
        $table->getColumn('CheckinNotified')->setComment('Рассылка произведена');
        $table->getColumn('ShareCode')->setComment('Случайный набор символов для проверки расшаренного сегмента');

        $this->addSql("ALTER TABLE `IncomeTransaction` COMMENT 'Транзакции платежей. Для расчетов с партнерами AW. Например, платежи от рефералов Carlson Wagon Travel';");
        $table = $schema->getTable('IncomeTransaction');
        $table->getColumn('Date')->setComment('Дата создания.');
        $table->getColumn('Processed')->setComment('Обработано или нет.');
        $table->getColumn('Description')->setComment('Описание\\Комментарий к транзакции.');

        $this->addSql("ALTER TABLE `Invites` COMMENT 'Таблица со с инвайтами - пользователи приглашают знакомых на сайт. Используется для выдачи бесплатных купонов(за 5-х приглашенных), расчета AW-бонусов приглашающему в зависимости от платежей рефералов.';");
        $table = $schema->getTable('Invites');
        $table->getColumn('InviterID')->setComment('ссылка на приглащающего');
        $table->getColumn('InviteeID')->setComment('ссылка на реферала. Значения:\n*null* - пользователь удалился или еще не зарегистрировался по ссылке из письма');
        $table->getColumn('Email')->setComment('email реферала, заполнятся либо при отсылке инвайт-письма из левого меню на ящик друга, либо при регистрации нового пользователя по реферальной ссылке');
        $table->getColumn('InviteDate')->setComment('Дата создания инвайта');
        $table->getColumn('Code')->setComment('код для уникальной ссылки в письме');
        $table->getColumn('Approved')->setComment('Значения:\n*0*: приглашение из письма не было подтверждено.\n*1*: пользователь зарегистрировался по ссылке(письмо\\реферальная)');

        $this->addSql("ALTER TABLE `MailServer` COMMENT 'Список известных поддерживаемых почтовых серверов';");
        $table = $schema->getTable('MailServer');
        $table->getColumn('Domain')->setComment('доменная часть почтового адреса (пр. gmail.com)');
        $table->getColumn('Server')->setComment('адрес самого сервера (пр. imap.googlemail.com) ');
        $table->getColumn('Port')->setComment('int, порт для соединения');
        $table->getColumn('UseSsl')->setComment('boolean, использовать ли ssl');
        $table->getColumn('Protocol')->setComment('int, почтовый протокол, значения в классе ImapDetector');
        $table->getColumn('MxKeyWords')->setComment('ключевые слова, которые содержатся в MX-записях для доменов этого сервера');
        $table->getColumn('Connected')->setComment('boolean, поддерживается ли этот сервер');

        $this->addSql("ALTER TABLE `Offer` COMMENT 'Офферы, показываемые при посещении account/list.php';");
        $table = $schema->getTable('Offer');
        $table->getColumn('Enabled')->setComment('Включен/выключен. Выключенные офферы также не обновляют список пользователей по крону');
        $table->getColumn('Name')->setComment('Название оффера для внутреннего пользования. Поскольку показывается также в отчёте для AA, следует писать что-то осмысленное');
        $table->getColumn('Description')->setComment('Описание оффера для внутреннего пользования. Поскольку показывается также в отчёте для AA, следует писать что-то осмысленное');
        $table->getColumn('Code')->setComment('Уникальный код оффера, используемый для идентификации в PHP');
        $table->getColumn('CreationDate')->setComment('Дата создания оффера');
        $table->getColumn('PromotionCardID')->setComment('Предполагалось использовать это поле для автоматической генерации офферов-попапов. Сейачс не используется. Ставить 0');
        $table->getColumn('ApplyURL')->setComment('Ссылка, по которой пользователь переходит при согласи на оффер. Подставляется вместо переменной {ApplyURL} в твиге. Может быть пустой, если URL формируется динамически');
        $table->getColumn('RemindMeDays')->setComment('Минимальный срок между показами этого оффера');
        $table->getColumn('DisplayType')->setComment('Тип оффера - попап или отдельная страница. В данный момент используется только отдельная страница. Попап формально поддерживается');
        $table->getColumn('ShowsCount')->setComment('Общее число показов оффера, повышается при показе оффера пользователю');
        $table->getColumn('Priority')->setComment('Офферы с более высоким приоритетом показываются раньше офферов с более низким.');
        $table->getColumn('Kind')->setComment('Офферы группируются в типы, чтобы пользователь мог отказаться от показа всех офферов одного типа');
        $table->getColumn('MaxShows')->setComment('Максимальное число показов оффера пользователю');

        $this->addSql("ALTER TABLE `OfferImpersonate` COMMENT 'Таблица сотрудников AwardWallet, которым не будет показываться оффер при имперсонейте. Скрипт \"$\":https://awardwallet.com/manager/disableOffer.php';");
        $table = $schema->getTable('OfferImpersonate');
        $table->getColumn('Login')->setComment('Логин сотрудника');
        $table->getColumn('Disabled')->setComment('Всегда равно 1. Установка в 0 не приведёт к показу оффера, для этого нужно удалить запись из таблицы');

        $this->addSql("ALTER TABLE `OfferKindRefused` COMMENT 'Типы офферов, от показа которых отказался пользователь (см. Поле Offer.Kind)';");
        $table = $schema->getTable('OfferKindRefused');
        $table->getColumn('OfferKind')->setComment('Тип оффера, соответстувет полю Offer.Kind');

        $this->addSql("ALTER TABLE `OfferLog` COMMENT 'Журнал показов/согласий/отказов офферов';");
        $table = $schema->getTable('OfferLog');
        $table->getColumn('Action')->setComment('Действие. Null = показ, 0 - отказ от оффера, 1 - согласие на оффер, 2 - отказ от всех офферов данного Kind');
        $table->getColumn('ActionDate')->setComment('Время события');

        $this->addSql("ALTER TABLE `OfferUser` COMMENT 'Таблица, определяющая, какие офферы каким пользователям показываются';");
        $table = $schema->getTable('OfferUser');
        $table->getColumn('CreationDate')->setComment('Время создания записи');
        $table->getColumn('Manual')->setComment('При использовании функции Search Users и автоматическом обновлении все старые записи, не отвечающие новым требованиям оффера, стираются. Это не касается записей, у которых Manual = 1');
        $table->getColumn('Params')->setComment('Переменные оффера для конкретного пользователя. Формат: переменная=значение, разделённые переводом строки');
        $table->getColumn('Agreed')->setComment('1 - пользователь согласился на оффер, 0 - пользователь отказался от оффера, null - ни то, ни друое ');
        $table->getColumn('ShowDate')->setComment('Время последнего показа оффера этому пользователю');
        $table->getColumn('ShowsCount')->setComment('Число состоявщихся показов оффера этому пользователю');

        $this->addSql("ALTER TABLE `Provider` COMMENT 'Настройка провайдеров';");
        $table = $schema->getTable('Provider');
        $table->getColumn('Name')->setComment('Название компании, то что написано на самолете или на здании оттеля.\n*Value*:\nUnited');
        $table->getColumn('Code')->setComment('это то что будет уникально идентифицировать эту программу. Так будет названа папка и это слово будет в названии класса и еще много-много где. только маленькие буквы, без пробелов\n*Value*:\nunited');
        $table->getColumn('Kind')->setComment('Тип программы, т.е. в какую закладку эта программа попадет.\n*Value*:\nAirline');
        $table->getColumn('LoginCaption')->setComment('что требуется ввести для логина. В случае когда надо дать подсказку можно ввести Login (Mileage Plus # or email or screen name) тогда то что в скобках покажется в виде подсказки под полем и не ипортит форму длинным названием поля.\n*Value*:\nMileage Plus #');
        $table->getColumn('DisplayName')->setComment('это название будет чаще всего показываться в интерфейсе. Например в списке програм она будет отбражена именно так.\n*Value*:\nUnited (Mileage Plus)');
        $table->getColumn('ProgramName')->setComment('название бонусной программы\n*Value*:\nMilage Plus');
        $table->getColumn('PasswordCaption')->setComment('что требуется в виде пароля, не обязятаельное поле\n*Value*:\nPIN');
        $table->getColumn('Site')->setComment('главная страница сайта\n*Value*:\nhttp://www.united.com');
        $table->getColumn('State')->setComment('если не надо чтобы народ уже начал добавлять программы то пока код полностью не готов надо чтобы порграмма была disabled. Если уже народ надобавлял програм и ее поменяли на disabled то программа полностью исчезнет с сайта. Т.е. ее нельзя будет добавить и уже добавленные программы (например с ошибками) тоже исчезнут из профилей людей. Потом поменяв на enabled все появится назад.\n*Collecting requests* - копим запросы на добавление\n*Collecting accounts* - собираем аккаунты, кренделя приходят на почту, при проверке выдается UE\n*Beta users only* - ?\n*In development* - ?\n*Enabled* - включена, работает\n*Fixing* - находится в починке\n*Checking off* - нет проверки аккаунтов в бэкграунде\n*Checking AWPlus only* - проверка аккаунтов в бэкграунде только для AW+\n*Checking only through extension* - нет никакой проверки, кроме как через extension\n*Disabled* - выключена\n*Test* - ?\n*WSDL Only* - ?\n*Hidden provider(e-mail parsing)* - ? ');
        $table->getColumn('Login2Caption')->setComment('как правило пустое, но бывают программы у которые 2 поля логина как например у дельты\n*Value*:\nLast Name');
        $table->getColumn('CanCheck')->setComment('возможна ли проверка данного провайдера\n*Value*:\ntrue/false');
        $table->getColumn('CanCheckBalance')->setComment('если нет главного баланса, нужно в свойствах LP поставить false\n*Value*:\ntrue/false');
        $table->getColumn('CanCheckConfirmation')->setComment('если возможна проверка резерваций по Confirmation Number, то нужно поставить true\n*Value*:\ntrue/false');
        $table->getColumn('CanCheckItinerary')->setComment('если возможен парсинг резерваций, то нужно поставить true\n*Value*:\ntrue/false');
        $table->getColumn('ExpirationDateNote')->setComment('Описание логики вычисления Expiration Date (показывается пользователям)');
        $table->getColumn('BalanceFormat')->setComment('Форматирование баланса\n*Allow Float* - true, если баланс дробное число \n*Value*:\n$%0.2f\nfunction');
        $table->getColumn('ShortName')->setComment('в 99% случаев тоже самое что и Name но если Name больше 50 или 70 символов то как то надо сокращать.\n*Value*:\nUnited');
        $table->getColumn('ImageURL')->setComment('аффилиат ссылка (image), срабатывающая при переходе пользователя на сайт провайдера');
        $table->getColumn('ClickURL')->setComment('аффилиат ссылка (click), срабатывающая при переходе пользователя на сайт провайдера');
        $table->getColumn('WSDL')->setComment('если true, значит программа доступна для партнеров\n*Value*:\ntrue/false');
        $table->getColumn('CanCheckExpiration')->setComment('возможно ли проверить Expiration Date\n*Expiration Always Known* - true, если всегда можно собрать Expiration Date \n*Value*:\n*No*\n*Yes*\n*Never expires*');
        $table->getColumn('ExpirationUnknownNote')->setComment(' Текстовка, которая показывается в попапе при клике на \"Unknown\" в колонке Expiration. Если поле в базе пустое, то по умолчанию показывается текст: \"At this point AwardWallet doesn’t know how to get your expiration date for this program...\" если заполнено, то только тот текст, которым заполнено это поле');
        $table->getColumn('iPhoneAutoLogin')->setComment('Мобильный автологин\n*Value*:\n*Disabled* - в приложении будет \"Go to site\", кинет на LoginUrl из схемы Provider\n*Server* - попытается сделать серверный автологин, надпись \"Go to site\" - т.к. серверный в мобильных очень плохо работает.\n*Mobile extension* - надпись \"Autologin\", автологин через мобильный экстеншн. В случае какой-либо ошибки кинет на LoginUrl\n*Desktop extension* - надпись \"Autologin\", автологин через десктопный экстеншн. В случае ошибки кинет на LoginUrl.');
        $table->getColumn('Currency')->setComment('в чем измеряется баланс\n*Value*:\nMiles, Points, Dollars');
        $table->getColumn('AllianceID')->setComment('если программа принадлежит к какому либо альянсу, то здесь можно его указать\n*Value*:\nOneworld, SkyTeam, Star Alliance etc.');
        $table->getColumn('Questions')->setComment('есть ли у программы секретные вопросы\n*Value*:\ntrue/false');
        $table->getColumn('Note')->setComment('текст показывается при отображении программы в списках Working Programs, Disabled, Considering to add');
        $table->getColumn('EliteLevelsCount')->setComment('Количество элитных уровней, выставляется без учета уровня №0. при отсутствии уровней выставляется \"-1\"');
        $table->getColumn('CanCheckCancelled')->setComment('если возможно проверить, что резервация была отменена, то нужно поставить true\n*Value*:\ntrue/false');
        $table->getColumn('CheckInBrowser')->setComment('Проверка через extension\n*Value*:\n*No*\n*Yes, keep data in browser*\n*Yes, copy data to database*');
        $table->getColumn('CanCheckHistory')->setComment('если возможен сбор истории для провайдера, то нужно поставить true\n*Value*:\ntrue/false');
        $table->getColumn('ExpirationAlwaysKnown')->setComment('true, если всегда можно собрать Expiration Date\n*Value*:\n*No*\n*Yes*\n*Never expires*');
        $table->getColumn('RequestsPerMinute')->setComment('если существует вероятность того, что провайдер может заблокировать наши инстансы, то нужно выставить троттлинг для него (указать число запросов к сайту провайдера в минуту)\n*Value*:\n60');
        $table->getColumn('CanCheckNoItineraries')->setComment('если возможно проверить отстутсвие резеваций, то нужно поставить true\n*Value*:\ntrue/false');
        $table->getColumn('PlanEmail')->setComment(' для определения провайдера при парсинге писем.\nПодробнее тут \"Traxo email parsing\":http://redmine.awardwallet.com/projects/awwa/wiki/Traxo_email_parsing#%D0%9F%D1%80%D0%BE%D0%B2%D0%B5%D1%80%D0%BA%D0%B0-%D0%BE%D0%BF%D1%80%D0%B5%D0%B4%D0%B5%D0%BB%D0%B5%D0%BD%D0%B8%D1%8F-%D0%BF%D1%80%D0%BE%D0%B2%D0%B0%D0%B9%D0%B4%D0%B5%D1%80%D0%B0\n*Value*:\n@(\\w+\\.)*delta\\.com');
        $table->getColumn('InternalNote')->setComment('Для внутренних заметок');
        $table->getColumn('CalcEliteLevelExpDate')->setComment('возможно ли рассчитать Expiration Date для элитного уровня');
        $table->getColumn('ItineraryAutologin')->setComment('Автологин и редирект на страницу с нужной резерваций (с помощью extension). Обязательно для проверки программистом.\n*Value*:\n*Disabled* - отключен\n*Account* - автологин в резервацию, которая была собрана с аккаунта\n*Confirmation number* - автологин в резервацию, которая была собрана с помощью Confirmation number\n*Account and confirmation number* - поддерживаются оба варианта');
        $table->getColumn('Category')->setComment('Кейс #6873. Категория авиалинии для расчёта доли AA. По умолчанию 3. При добавлении новой программы следует ставить 3.');

        $this->addSql("ALTER TABLE `ProviderPartner` COMMENT 'Список cashback-провайдеров и магазинов, которые они поддерживают';");
        $table = $schema->getTable('ProviderPartner');
        $table->getColumn('ProviderID')->setComment('fk на провайдер-магазин');
        $table->getColumn('PartnerID')->setComment('fk на провайдер-cashback');
        $table->getColumn('Discount')->setComment('string, скидка, значение cashback');
        $table->getColumn('Priority')->setComment('int, порядок, в котором будет показываться список доступных cashback при первом автологине (низкий приоритет - первый в списке) ');
        $table->getColumn('SearchText')->setComment('текст, по которому на сайте cashback ищется нужный провайдер');
        $table->getColumn('UserData')->setComment('любые данные, сейчас используется для обозначения, где в списке результатов поиска находится нужный провайдер');

        $this->addSql("ALTER TABLE `ProviderProperty` COMMENT 'Информация о собираемых свойствах провайдера';");
        $table = $schema->getTable('ProviderProperty');
        $table->getColumn('ProviderID')->setComment(' ProviderID из таблицы Provider ');
        $table->getColumn('Name')->setComment(' Название свойства (показывается пользователям) ');
        $table->getColumn('Code')->setComment(' Код свойства (используется в парсерах) ');
        $table->getColumn('SortIndex')->setComment(' Порядок расположения свойства при показе информации ');
        $table->getColumn('Required')->setComment(' Всегда ли должно присутствовать это свойство');
        $table->getColumn('Kind')->setComment(' Тип свойства (Name, Lifetime points, Expiring balance и т.д.)');
        $table->getColumn('Visible')->setComment(' Видимость свойства (показывать его юезру или нет)');

        $this->addSql("ALTER TABLE `Redirect` COMMENT 'Информация с названиями рекламный акций и ссылками на сайты рекламодателей';");
        $table = $schema->getTable('Redirect');
        $table->getColumn('URL')->setComment(' Ссылка на сайт рекламодателя ');
        $table->getColumn('Name')->setComment(' Название рекламы ');

        $this->addSql("ALTER TABLE `TextEliteLevel` COMMENT 'Ключевые слова для элитных уровней. С помощью ключевых слов ищется уровень соответствующий строке статуса, которая пришла из парсера';");
        $table = $schema->getTable('TextEliteLevel');
        $table->getColumn('EliteLevelID')->setComment('Ссылка на элитный уровень');
        $table->getColumn('ValueText')->setComment('Ключевое слово\\строка');

        $this->addSql("ALTER TABLE `TravelPlan` COMMENT 'Таблица травел планов';");
        $table = $schema->getTable('TravelPlan');
        $table->getColumn('Name')->setComment('Название плана');
        $table->getColumn('StartDate')->setComment('Дата начала плана');
        $table->getColumn('EndDate')->setComment('Дата окончания плана');
        $table->getColumn('PictureVer')->setComment('Имя файла аватара для плана');
        $table->getColumn('PictureExt')->setComment('Расширение файла аватара для плана');
        $table->getColumn('Code')->setComment('Случайный набор букв для подтверждение доступа к расшареному плану');
        $table->getColumn('AutoUpdateDate')->setComment('Дата последнего автообновления');
        $table->getColumn('MailDate')->setComment('Дата отсылки напоминания');
        $table->getColumn('UserAgentID')->setComment('Если план создан для агента то указан его ID иначе null');
        $table->getColumn('Public')->setComment('Доступ для просмотра любым пользователем');
        $table->getColumn('PlanGroupID')->setComment('Для объединенных в группу планов');
        $table->getColumn('CustomDates')->setComment('Даты плана указаны вручную (для определения изменений при автообновлении)');
        $table->getColumn('CustomName')->setComment('Название плана изменено пользователем (для определения изменений при автообновлении)');
        $table->getColumn('Hidden')->setComment('Удаление плана если 0 - план показан 1 - план будет показан в разделе удаленных');
        $table->getColumn('CustomUserAgent')->setComment('ID агента указан вручную, пользователем (для определения изменений при автообновлении)');

        $this->addSql("ALTER TABLE `TravelPlanSection` COMMENT 'Таблица связей между таблицами поездок/заказов и травел планами';");
        $table = $schema->getTable('TravelPlanSection');
        $table->getColumn('SectionKind')->setComment('Таблица донор ');
        $table->getColumn('SectionID')->setComment('Идентификатор строки таблицы донора');

        $this->addSql("ALTER TABLE `TravelPlanShare` COMMENT 'Таблица связей расшареных планов';");
        $table = $schema->getTable('TravelPlanShare');
        $table->getColumn('TravelPlanID')->setComment('Идентификатор плана');
        $table->getColumn('UserAgentID')->setComment('Идентификатор агента для которого расшарен план');

        $this->addSql("ALTER TABLE `Trip` COMMENT 'Таблица поездок';");
        $table = $schema->getTable('Trip');
        $table->getColumn('AccountID')->setComment('Идентификатор аккаунта в системе провайдера');
        $table->getColumn('RecordLocator')->setComment('Уникальный номер сделанного заказа');
        $table->getColumn('TravelPlanID')->setComment('К какому травел плану относится');
        $table->getColumn('Hidden')->setComment('Удален или нет');
        $table->getColumn('Parsed')->setComment('установлен в 1 если данные получены в результате парсинга резерваций на сайте провайдера');
        $table->getColumn('AirlineName')->setComment('Название компании совершающей рейс');
        $table->getColumn('Notes')->setComment('Заметки');
        $table->getColumn('ProviderID')->setComment('Идентификатор компании в таблице Provider');
        $table->getColumn('Moved')->setComment('Флаг для перемещенных вручную сегментов');
        $table->getColumn('UpdateDate')->setComment('Дата последнего автообновления');
        $table->getColumn('ConfFields')->setComment('Данные подтверждения');
        $table->getColumn('Category')->setComment('Категория поездки *A* - Самолет, *T* - Поезд, *F* - Паром, *C* - Круиз или *B* - Автобус');
        $table->getColumn('Direction')->setComment('Для поездок туда и обратно 0- туда(default) 1- обратно');
        $table->getColumn('CreateDate')->setComment('Дата создания строки');
        $table->getColumn('LastOfferDate')->setComment('Дата показа оффера');
        $table->getColumn('CouponCode1')->setComment('Код купона');
        $table->getColumn('CouponCode2')->setComment('Код купона');
        //$table->getColumn('SavingsAmount')->setComment('Сумма скидок');
        //$table->getColumn('SavingsConfirmed')->setComment('Подвержденные скидки');
        $table->getColumn('AllowChangeLessSavings')->setComment('Возможно использование меньшего количества скидок');
        $table->getColumn('Cancelled')->setComment('Отменено');
        $table->getColumn('UserAgentID')->setComment('Идентификатор агента для которого создана эта поездка');
        $table->getColumn('Copied')->setComment('Флаг скопированных вручную поездок');
        $table->getColumn('Modified')->setComment('Флаг измененных вручную данных поездки');
        $table->getColumn('MailDate')->setComment('Дата рассылки');

        $table = $schema->getTable('TripSegment');
        $table->getColumn('TripID')->setComment('ID поездки к которой относится данный сегмент');
        $table->getColumn('DepCode')->setComment('Код места отправления');
        $table->getColumn('DepName')->setComment('Название места отправления');
        $table->getColumn('DepDate')->setComment('Дата отправления');
        $table->getColumn('ArrCode')->setComment('Код места назначения');
        $table->getColumn('ArrName')->setComment('Название места назначения');
        $table->getColumn('ArrDate')->setComment('Дата прибытия');
        $table->getColumn('AirlineName')->setComment('Название компании совершающей рейс');
        $table->getColumn('FlightNumber')->setComment('Номер рейса');
        $table->getColumn('DepGeoTagID')->setComment('GeoTag места отправления');
        $table->getColumn('ArrGeoTagID')->setComment('GeoTag места назначения');
        $table->getColumn('CheckinNotified')->setComment('Рассылка произведена');
        $table->getColumn('TravelPlanID')->setComment('К какому травел плану относится');
        $table->getColumn('ShareCode')->setComment('Случайный набор символов для проверки расшаренного сегмента');

        $this->addSql("ALTER TABLE `UserAgent` COMMENT 'Таблица описания агентов пользователя';");
        $table = $schema->getTable('UserAgent');
        $table->getColumn('AgentID')->setComment('Идентификатор агента (*UserID* )');
        $table->getColumn('ClientID')->setComment('Идентификатор клиента (*UserID*) может быть пустым');
        $table->getColumn('FirstName')->setComment('Имя клиента (при пустом *ClientID*)');
        $table->getColumn('LastName')->setComment('Фамилия клиента (при пустом *ClientID*)');
        $table->getColumn('Email')->setComment('Email клиента (при пустом *ClientID*)');
        $table->getColumn('AccessLevel')->setComment('Уровень доступа к аккаунту (1-4) соответствует глобальным константам *ACCESS_READ*, *ACCESS_WRITE*, *ACCESS_ADMIN*');
        $table->getColumn('IsApproved')->setComment('Подтверждение указанных прав доступа(устанавливается после подтверждения клиентом)');
        $table->getColumn('Notes')->setComment('Комментарий');
        $table->getColumn('ShareByDefault')->setComment('Автоматический доступ к данным клиента');
        $table->getColumn('ShareCode')->setComment('Случайный код для подтверждения');
        $table->getColumn('Source')->setComment('Литерал указывающий тип доступной информации *A* - _Балансы_ *T* - _Поездки_ * - _Оба типа_');
        $table->getColumn('TripShareByDefault')->setComment('Автоматический доступ к данным поездок');
        $table->getColumn('ShareDate')->setComment('Дата создания записи');
        $table->getColumn('PictureVer')->setComment('Имя файла аватарки');
        $table->getColumn('PictureExt')->setComment('Расширение файла аватарки');
        $table->getColumn('ItineraryCalendarCode')->setComment('Код Google Calendar для доступа к календарю поездок');

        $this->addSql("ALTER TABLE `UserEmail` COMMENT 'Почтовые аккаунты, которые пользователи добавили себе на сайте';");
        $table = $schema->getTable('UserEmail');
        $table->getColumn('Email')->setComment('логин');
        $table->getColumn('Password')->setComment('зашифрованный пароль');
        $table->getColumn('Status')->setComment('int, статус, значения в классе ImapDetector');
        $table->getColumn('Added')->setComment('дата добавления');
        $table->getColumn('UpdateDate')->setComment('дата последнего сканирования');
        $table->getColumn('UseGoogleOauth')->setComment('boolean, использовать gogole oauth');

        $this->addSql("ALTER TABLE `UserEmailAccountHistory` COMMENT 'История обновления аккаунтов из пользовательских почтовых ящиков';");
        $this->addSql("ALTER TABLE `UserEmailParseHistory` COMMENT 'История сканнирования пользовательских почтовых ящиков';");
        $table = $schema->getTable('UserEmailParseHistory');
        $table->getColumn('EmailToken')->setComment('token письма, формируется из уникальных полей имейла для его идентификации');
        $table->getColumn('EmailDate')->setComment('дата письма, вместе с EmailToken используется для идентификации письма');
        $table->getColumn('ParseDate')->setComment('дата обработки этого письма');

        $this->addSql("ALTER TABLE `UserEmailToken` COMMENT 'Токены для oauth аутентификации в пользовательские gmail ящики';");
        $table = $schema->getTable('UserEmailToken');
        $table->getColumn('Token')->setComment('string, токен');
        $table->getColumn('Added')->setComment('дата добавления');

        $this->addSql("ALTER TABLE `Visit` COMMENT 'Таблица посещений сайтов. Посещение регистрируется при логине.';");
        $table = $schema->getTable('Visit');
        $table->getColumn('VisitDate')->setComment('Дата посещений');
        $table->getColumn('Visits')->setComment('Количество посещений в этот день');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
