DROP TABLE IF EXISTS AccountHistory;
CREATE TABLE AccountHistory
(
    `UUID` char(36) NOT NULL DEFAULT '' COMMENT '(DC2Type:guid)',
    `AccountID` int(11) NOT NULL COMMENT ' AccountID из таблицы Account',
    `SubAccountID` int(11) DEFAULT NULL COMMENT 'Связь с субаккаунтом для формирования его истории',
    `PostingDate` datetime NOT NULL COMMENT ' Дата транзакции',
    `Miles` float DEFAULT NULL COMMENT ' Количество начисленных / списанных баллов',
    `Amount` decimal(10,2) DEFAULT NULL COMMENT 'Потраченная сумма в валюте',
    `MerchantID` int(11) DEFAULT NULL,
    `ShoppingCategoryID` int(11) DEFAULT NULL,
    PRIMARY KEY (`UUID`),
    KEY `fkSubAccount` (`SubAccountID`),
    KEY `Account` (`AccountID`)
)
ENGINE = InnoDB;

DROP TABLE IF EXISTS DetectedCards;
CREATE TABLE DetectedCards
(
    ID int not null,
    AccountID int not null,
    CreditCardID int,
    Code varchar(250),
    primary key(ID),
    key (AccountID),
    key (CreditCardID)
)
ENGINE = InnoDB;

DROP TABLE IF EXISTS SubAccount;
CREATE TABLE SubAccount
(
    SubAccountID int not null,
    AccountID int not null,
    CreditCardID int,
    Code varchar(250),
    key (AccountID),
    key (CreditCardID)
)
ENGINE = InnoDB;

DROP TABLE IF EXISTS CreditCardShoppingCategoryGroup;
CREATE TABLE CreditCardShoppingCategoryGroup
(
    CreditCardShoppingCategoryGroupID int not null,
    CreditCardID int not null,
    ShoppingCategoryGroupID int,
    Multiplier Decimal(8, 1),
    StartDate date,
    primary key(CreditCardShoppingCategoryGroupID),
    key (CreditCardID),
    key (ShoppingCategoryGroupID)
)
ENGINE = InnoDB;

DROP TABLE IF EXISTS ShoppingCategory;
CREATE TABLE ShoppingCategory
(
    ShoppingCategoryID int not null,
    ShoppingCategoryGroupID int,
    primary key(ShoppingCategoryID),
    key (ShoppingCategoryGroupID)
)
ENGINE = InnoDB;

DROP TABLE IF EXISTS SubAccount;
CREATE TABLE SubAccount
(
    SubAccountID int not null,
    AccountID int not null,
    CreditCardID int,
    Code varchar(250),
    primary key(SubAccountID),
    key (CreditCardID)
)
ENGINE = InnoDB;
