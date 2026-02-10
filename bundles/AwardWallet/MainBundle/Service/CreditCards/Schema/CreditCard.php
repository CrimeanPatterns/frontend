<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Schema;

use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Tags;
use AwardWallet\MainBundle\Service\CreditCards\CreditCardPatternsSource;
use AwardWallet\MainBundle\Service\CreditCards\DoublePatternsValidator;
use AwardWallet\MainBundle\Service\CreditCards\PatternsParser;
use Doctrine\DBAL\Connection;

class CreditCard extends \TBaseSchema
{
    private Connection $connection;
    private CreditCardCategoryList $creditCardCategoryList;
    private ProviderOptions $providerOptions;
    private CacheManager $cacheManager;
    private PatternsParser $patternsParser;
    private CreditCardPatternsSource $patternsSource;
    private DoublePatternsValidator $doublePatternsValidator;

    public function __construct(
        Connection $connection,
        CreditCardCategoryList $creditCardCategoryList,
        ProviderOptions $providerOptions,
        CacheManager $cacheManager,
        PatternsParser $patternsParser,
        CreditCardPatternsSource $patternsSource,
        DoublePatternsValidator $doublePatternsValidator
    ) {
        $this->connection = $connection;
        $this->providerOptions = $providerOptions;

        parent::__construct();

        unset($this->Fields['PointValue']);

        $this->Fields['IsBusiness']['Caption'] = 'Type';
        $this->Fields['IsBusiness']['Type'] = 'integer';
        $this->Fields['IsBusiness']['Options'] = [0 => 'Personal', 1 => 'Business'];
        $this->Fields['ExcludeCardsId']['Caption'] = 'Exclude Cards';
        $this->Fields['SortIndex']['Sort'] = 'VisibleInList DESC, SortIndex ASC';
        $this->Fields['DisplayNameFormat']['Note'] = 'Display name for account list. Params available to bind: {number_ending}';
        $this->Fields['IsCashBackOnly']['Caption'] = 'Cashback only';
        $this->Fields['IsCashBackOnly']['Note'] = 'Is cashback card (earns 1¢ per $)';
        $this->Fields['CashBackType']['Options'] = [
            '' => null,
            \AwardWallet\MainBundle\Entity\CreditCard::CASHBACK_TYPE_USD => 'USD',
            \AwardWallet\MainBundle\Entity\CreditCard::CASHBACK_TYPE_POINT => 'Point',
        ];
        $this->Fields['CobrandProviderID']['Options'] =
            ["" => ""]
            + $this->connection->executeQuery(
                "select distinct p.ProviderID, p.DisplayName 
                    from Provider p
                    join ProviderMileValue pmv on p.ProviderID = pmv.ProviderID 
                    order by p.DisplayName")->fetchAllKeyValue();
        $this->Fields['CobrandProviderID']['Caption'] = "Loyalty Currency";
        $this->Fields['Patterns']['InputType'] = 'textarea';
        $this->Fields['Patterns']['Caption'] = 'Detected Cards Patterns';
        $this->Fields['Patterns']['Note'] = 'Regular expressions should be written with # as #somepattern#ims';
        $this->Fields['Patterns']['HTML'] = true;
        $this->Fields['HistoryPatterns']['InputType'] = 'textarea';
        $this->Fields['ClickURL']['Caption'] = 'Blog ClickURL';
        $this->Fields['Description']['InputType'] = 'textarea';
        $this->Fields['QsCreditCardID']['Options'] = ['' => ''] + $this->connection->executeQuery("
            select QsCreditCardID, concat_ws(' ', CardName, '[Slug:', Slug, ']', (CASE WHEN IsManual = 1 THEN '(manual)' ELSE '' END))
            from QsCreditCard
            where IsHidden = 0
            order by CardName asc
        ")->fetchAll(\PDO::FETCH_KEY_PAIR);
        $this->Fields['CobrandSubAccPatterns']['Caption'] = 'Cobrand SubAccounts Patterns';
        $this->Fields['CobrandSubAccPatterns']['InputType'] = 'textarea';
        $this->Fields["ProviderID"]["Caption"] = "Issuing Bank";
        $this->Fields['IsApiReady']['Caption'] = 'API Ready';
        $this->Fields['IsApiReady']['Note'] = 'If unchecked this card will not show up in the AwardWallet <a href="https://jenkins.awardwallet.com/job/Frontend/job/Cron/job/update-credit-cards-api" target="cc" title="Command to update credit card state in json">Credit Cards API</a>';
        $this->Fields['IsVisibleInAll']['Caption'] = 'Visible in All Cards';
        $this->Fields['IsVisibleInAll']['Note'] = 'Blog: best cards';
        $this->Fields['IsVisibleInBest']['Caption'] = 'Visible in Best Offers';
        $this->Fields['IsVisibleInBest']['Note'] = 'Blog: best cards';
        $this->Fields['QsAffiliate']['Options'] = [
            \AwardWallet\MainBundle\Entity\CreditCard::QS_AFFILIATE_NONE => 'Non-Affiliate',
            \AwardWallet\MainBundle\Entity\CreditCard::QS_AFFILIATE_DIRECT => 'Direct',
            \AwardWallet\MainBundle\Entity\CreditCard::QS_AFFILIATE_CARDRATINGS => 'CardRatings',
        ];
        $this->Fields['RankIndex']['Caption'] = 'All Cards Rank';
        $this->Fields['RankIndex']['Note'] = 'Sorting for page /blog/credit-cards/';

        $this->Fields["BonusEarning"] = [
            'Type' => 'string',
            'Database' => false,
            'Caption' => 'Bonus Earning',
        ];

        $fieldOrder = array_flip([
            'CreditCardID', 'ProviderID', 'CobrandProviderID', 'Name', 'CardFullName', 'DisplayNameFormat', 'QsCreditCardID',
            'IsBusiness', 'IsBankTransferable', 'IsNonAffiliateDisclosure', 'VisibleInList', 'VisibleOnLanding', 'IsVisibleInAll', 'IsVisibleInBest', 'IsCashBackOnly', 'CashBackType', 'IsDiscontinued', 'IsApiReady', 'IsOfferPriorityPass', 'ForeignTransactionFee',
            'Patterns', 'HistoryPatterns', 'CobrandSubAccPatterns',
            'ClickURL', 'DirectClickURL',
            'Description', 'PointName', 'MatchingOrder',
            'BonusEarning', 'ExcludeCardsId', 'SortIndex',
        ]);
        uksort($this->Fields, fn ($a, $b) => ($fieldOrder[$a] ?? 100) <=> ($fieldOrder[$b] ?? 100));

        if ('POST' === $_SERVER['REQUEST_METHOD']) {
            $this->extendSave();
        }

        foreach ($this->Fields as $name => &$field) {
            $field["FilterField"] = "CreditCard.{$name}";
        }
        unset($field);

        $this->creditCardCategoryList = $creditCardCategoryList;
        $this->cacheManager = $cacheManager;
        $this->patternsParser = $patternsParser;
        $this->patternsSource = $patternsSource;
        $this->doublePatternsValidator = $doublePatternsValidator;

        $this->Fields['IsBankTransferable']['Caption'] = 'Transferable to bank currency';
        $this->Fields['IsBankTransferable']['Note'] = 'Select this checkbox for cashback cards that allow transferring earned cashback to the bank rewards currency (e.g., Ultimate Rewards, Capital One Miles), provided the user holds a card that earns that currency';

        // synchronizes from https://awardwallet.com/blog/wp-admin/admin.php?page=NAC
        $this->Fields['IsNonAffiliateDisclosure']['InputAttributes'] = 'readonly disabled';
        $this->Fields['IsNonAffiliateDisclosure']['Database'] =
        $this->Fields['IsNonAffiliateDisclosure']['Required'] = false;
        $this->Fields['IsNonAffiliateDisclosure']['Note'] = 'Show label "Card offer not available through this site."';
    }

    public function TuneList(&$list)
    {
        /* @var $list TBaseList */
        parent::TuneList($list);

        $list->SQL = 'SELECT 
            *, 
            (select count(*) from CreditCardBonusLimit where CreditCardBonusLimit.CreditCardID = CreditCard.CreditCardID) as BonusLimits 
        FROM ' . $this->TableName . " where 1 = 1 [Filters]";

        $list->KeyField = $this->KeyField;
        $list->ShowExport = false;
        $list->ShowImport = false;
        $list->DefaultSort = 'SortIndex';
    }

    public function GetFormFields()
    {
        $result = parent::GetFormFields();

        $picManager = new \TPictureFieldManager();
        $picManager->Dir = "/images/uploaded/creditcard";
        $picManager->thumbHeight = 150;
        $picManager->thumbWidth = 83;
        $picManager->KeepOriginal = true;
        $picManager->CreateMedium = true;
        $picManager->previewSize = "original";
        //        $picManager->ShowUploadButton = false;

        $result['Picture'] = [
            "Type" => "string",
            "Manager" => $picManager,
            "Database" => false,
        ];

        if ((int) ($_GET['ID'] ?? 0) === 0) {
            $result['BonusEarning']['HTML'] = 'You should save the new card first, then you could edit categories for it';
        } else {
            $result['BonusEarning']['HTML'] = $this->creditCardCategoryList->getCategoriesList((int) ($_GET['ID'] ?? 0)) . "<br/>" .
                "<a href=\"list.php?Schema=CreditCardShoppingCategoryGroup&CreditCardID=" . (int) ($_GET['ID'] ?? 0) . "\" target='_blank'>Category Groups</a>
            <br/>
            <a href=\"list.php?Schema=CreditCardMerchantGroup&CreditCardID=" . (int) ($_GET['ID'] ?? 0) . "\" target='_blank'>Merchant Groups</a>";
        }
        $result['BonusEarning']['InputType'] = 'html';
        $qsFeeInfo = $this->connection->fetchAllKeyValue("select QsCreditCardID, ForeignTransactionFee from QsCreditCard");
        $result['ForeignTransactionFee']['Note'] = '...';
        $result['Scripts'] = [
            'Type' => 'html',
            'HTML' => "
            

<script type='application/javascript'>
    var qsFeeInfo = " . json_encode($qsFeeInfo) . ";

    function onCardChange()
    {
        const qsCardCode = document.getElementById('fldQsCreditCardID').value;
        const hint = document.getElementById('fldForeignTransactionFeeHint');
        hint.innerHTML = qsCardCode ? qsFeeInfo[qsCardCode] : 'Select a QS Card to show a fee hint';
    }
    
    $(document).ready(onCardChange);
    $('#fldQsCreditCardID').on('change', onCardChange);
</script>
",
        ];

        return $result;
    }

    public function ShowForm()
    {
        $this->Fields['Text'] = [
            'Type' => 'string',
            'InputType' => 'textarea',
        ];

        parent::ShowForm();

        if (!empty($_GET['ID'])) {
            $card = getSymfonyContainer()->get('doctrine')->getManager()->getRepository(\AwardWallet\MainBundle\Entity\CreditCard::class)->find((int) $_GET['ID']);
        }

        if (false !== strpos($_SERVER['REQUEST_URI'], '/edit.php') && !empty($card)) {
            $this->extendEditPage($card);
        }
    }

    public function TuneForm(\TBaseForm $form)
    {
        parent::TuneForm($form);

        if ($form->IsPost && array_key_exists("ForceSave", $_POST)) {
            $this->addForceSaveField($form);
        }

        $form->OnCheck = function () use ($form) {
            $newPatterns = $this->patternsParser->parse($form->Fields["Patterns"]["Value"] ?? '');

            foreach ($newPatterns as $pattern) {
                if (substr($pattern, 0, 1) === "#") {
                    try {
                        preg_match($pattern, "blah");
                    } catch (\Exception $exception) {
                        return "Invalid pattern '$pattern': " . $exception->getMessage();
                    }
                }
            }

            $allPatterns = $this->patternsSource->getPatterns();
            $allPatterns[$form->Fields["ProviderID"]["Value"]][$form->ID] = ["Patterns" => $newPatterns];

            $doubles = $this->doublePatternsValidator->validate($allPatterns, $form->ID);

            if (count($doubles) > 0 && !array_key_exists("ForceSave", $form->Fields)) {
                $this->addForceSaveField($form);
            }

            if (count($doubles) > 0 && array_key_exists("ForceSave", $form->Fields) && $form->Fields["ForceSave"]["Value"]) {
                $doubles = [];
            }

            if (count($doubles) > 0) {
                return implode("<br/>\n", $doubles) . "<br/><br/>You could check the 'force save' check box below to save ignoring duplicates";
            }

            return null;
        };

        $form->OnSave = function () {
            $this->cacheManager->invalidateTags([Tags::TAG_CREDIT_CARDS_INFO]);
        };
    }

    public function GetListFields()
    {
        $result = parent::GetListFields();

        $result = array_diff_key($result, array_flip([
            'DisplayNameFormat', 'Patterns', 'HistoryPatterns',
            'CobrandSubAccPatterns', 'MatchingOrder', 'ClickURL',
            'CardFullName', 'DirectClickURL', 'Text', 'PointName',
            'CobrandProviderID', 'QsCreditCardID', 'Description', 'RankIndex', 'SuccessCheckDate',
            'IsBankTransferable',
        ]));

        return $result;
    }

    protected function guessFieldOptions(string $field, array $fieldInfo): ?array
    {
        if ($field === 'ProviderID') {
            return $this->providerOptions->getOptions();
        }

        return parent::guessFieldOptions($field, $fieldInfo);
    }

    private function addForceSaveField(\TBaseForm $form)
    {
        ArrayInsert($form->Fields, "ProviderID", false, [
            "ForceSave" => [
                "Type" => "boolean",
                "Database" => false,
                "Caption" => "Force save ignoring duplicates",
            ],
        ]);
        $form->CompleteField("ForceSave", $form->Fields["ForceSave"]);
    }

    private function extendEditPage($card)
    {
        $groupCardsHtml = '<div id="excludedCardsList">';

        if (null !== $card->getExcludeCardsId()) {
            $excludeCards = $this->connection->fetchAllAssociative('SELECT CreditCardID, CardFullName, Name FROM CreditCard WHERE CreditCardID IN (' . implode(',', $card->getExcludeCardsId()) . ') ORDER BY SortIndex ASC');

            foreach ($excludeCards as $excludeCard) {
                $name = empty($excludeCard['CardFullName']) ? $excludeCard['Name'] : $excludeCard['CardFullName'];
                $groupCardsHtml .= '<div><a class="exclude-card" href="">&times;</a><span class="exist-excard">' . $name . '</span><input type="hidden" name="_excludeCardsId[]" value="' . $excludeCard['CreditCardID'] . '"></div>';
            }
        }
        $groupCardsHtml .= '</div><p><a href="#add-excludecard">Add Card</a></p>';

        $providerCards = [];
        $listCards = $this->connection->fetchAllAssociative('SELECT c.CreditCardID, c.ProviderID, c.CardFullName, c.Name, p.Name as ProviderName FROM CreditCard c JOIN Provider p ON (p.ProviderID = c.ProviderID) ORDER BY p.Name ASC, c.Name ASC');

        foreach ($listCards as $listCard) {
            array_key_exists($listCard['ProviderID'], $providerCards) ? null : $providerCards[$listCard['ProviderID']] = ['name' => $listCard['ProviderName'], 'list' => []];
            $providerCards[$listCard['ProviderID']]['list'][] = '<option value="' . $listCard['CreditCardID'] . '">' . $listCard['Name'] . '</option>';
        }

        $listCardsHtml = '';

        foreach ($providerCards as $pCard) {
            $listCardsHtml .= '<optgroup label="' . str_replace('"', '', $pCard['name']) . '">';
            $listCardsHtml .= implode('', $pCard['list']);
            $listCardsHtml .= '</optgroup>';
        }

        echo <<< HTML
        <link rel="stylesheet" href="/bundles/sonatacore/vendor/select2-bootstrap-css/select2-bootstrap.min.css">
        <script src="/bundles/sonatacore/vendor/select2/select2.min.js"></script>

        <style>
        #trCashBackType,
        #trCreditCardID, #trPreview, #fldExcludeCardsId {display: none;}
        #excludedCardsList {padding-bottom: 1rem;}
        #excludedCardsList div {padding: 3px 0;}
        .exclude-card {position:relative;top:0px;font-weight: bold !important;font-size: 14px;margin-right: 10px;text-decoration: none !important;color: red !important;}
        .exclude-card:hover + span {text-decoration: line-through;}
        .select2-container .select2-choice {height: 30px;}
        </style>
        <script type="text/javascript">
        $("#fldExcludeCardsId").after(`{$groupCardsHtml}`);
        $('a[href="#add-excludecard"]').click(function() {
            $('#excludedCardsList').append(`<div><a class="exclude-card" href="">&times;</a><select name="_excludeCardsId[]" class="exist-newcard" style="min-width:340px"><option></option>{$listCardsHtml}</select></div>`);
            $('select.exist-newcard:visible', '#excludedCardsList').select2({placeholder: 'Select Credit Card'});
            return false;
        });
        $('#excludedCardsList')
            .on('click', 'a.exclude-card', function(e){
                e.preventDefault();
                $(this).parent().remove();
            });
        $('#fldIsCashBackOnly').change(function(){
            $('#trCashBackType')[$(this).is(':checked') ? 'show' : 'hide']();
        }).trigger('change');
        </script>
HTML;
    }

    private function extendSave()
    {
        $doctrine = getSymfonyContainer()->get('doctrine');

        if (!empty($_GET['ID'])) {
            $card = $doctrine->getManager()->getRepository(\AwardWallet\MainBundle\Entity\CreditCard::class)->find((int) $_GET['ID']);
        }

        if (!empty($card)) {
            $card->setExcludeCardsId($_POST['_excludeCardsId'] ?? null);
            $doctrine->getManager()->persist($card);
            $doctrine->getManager()->flush();
        }
    }
}
