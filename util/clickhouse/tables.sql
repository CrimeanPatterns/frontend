DROP TABLE IF EXISTS AccountHistory;
CREATE TABLE AccountHistory
(
    UUID String,
    AccountID UInt32,
    SubAccountID Nullable(UInt32),
    CreditCardID Nullable(UInt32),
    PostingDate Date,
    Miles Nullable(Decimal(64, 4)),
    Amount Nullable(Decimal(16, 4)),
    Multiplier Nullable(Decimal(3, 1)),
    MerchantID Nullable(UInt32),
    ShoppingCategoryID Nullable(UInt32)
)
ENGINE = MergeTree()
PARTITION BY toYear(PostingDate)
PRIMARY KEY UUID
ORDER BY UUID;

DROP TABLE IF EXISTS DetectedCards;
CREATE TABLE DetectedCards
(
    ID UInt32,
    AccountID UInt32,
    CreditCardID Nullable(UInt32),
    Code String
)
ENGINE = MergeTree()
PRIMARY KEY ID
ORDER BY ID;

DROP TABLE IF EXISTS SubAccount;
CREATE TABLE SubAccount
(
    SubAccountID UInt32,
    AccountID UInt32,
    CreditCardID Nullable(UInt32),
    Code String
)
ENGINE = MergeTree()
PRIMARY KEY SubAccountID
ORDER BY SubAccountID;

DROP TABLE IF EXISTS Account;
CREATE TABLE Account
(
    AccountID UInt32,
    ProviderID Nullable(UInt32),
    UserID UInt32,
    SuccessCheckDate Nullable(DateTime)
)
ENGINE = MergeTree()
PRIMARY KEY AccountID
ORDER BY AccountID;

DROP TABLE IF EXISTS CreditCardShoppingCategoryGroup;
CREATE TABLE CreditCardShoppingCategoryGroup
(
    CreditCardShoppingCategoryGroupID UInt32,
    CreditCardID UInt32,
    ShoppingCategoryGroupID Nullable(UInt32),
    Multiplier Decimal(4, 2),
    StartDate Nullable(Date),
    EndDate Nullable(Date)
)
ENGINE = MergeTree()
PRIMARY KEY CreditCardShoppingCategoryGroupID
ORDER BY CreditCardShoppingCategoryGroupID;

DROP TABLE IF EXISTS ShoppingCategory;
CREATE TABLE ShoppingCategory
(
    ShoppingCategoryID UInt32,
    ShoppingCategoryGroupID Nullable(UInt32)
)
ENGINE = MergeTree()
PRIMARY KEY ShoppingCategoryID
ORDER BY ShoppingCategoryID;

DROP TABLE IF EXISTS SubAccount;
CREATE TABLE SubAccount
(
    SubAccountID UInt32,
    AccountID UInt32,
    CreditCardID Nullable(UInt32),
    Code String
)
ENGINE = MergeTree()
PRIMARY KEY SubAccountID
ORDER BY SubAccountID;
