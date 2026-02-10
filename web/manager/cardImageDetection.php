<?php

use AwardWallet\MainBundle\Entity\CardImage;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Globals\GoogleVision\GoogleVisionResult;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\CardImage\Matcher\DebugMatcher;
use AwardWallet\MainBundle\Manager\CardImage\ProviderDetector;
use AwardWallet\MainBundle\Manager\CardImage\RegexpHandler\RegexpCompilerCached;
use AwardWallet\MainBundle\Manager\CardImage\RegexpHandler\RegexpHandler;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Output\StreamOutput;

use function AwardWallet\MainBundle\Globals\Utils\iter\explodeLazy;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtObj;

$schema = "cardImageDetection";

require "start.php";

require_once $sPath . "/kernel/siteFunctions.php";

require_once "$sPath/kernel/public.php";

require_once "$sPath/kernel/TForm.php";

require_once __DIR__ . "/reports/common.php";
$bSecuredPage = false;

$connectionUnbuffered = getSymfonyContainer()->get('doctrine.dbal.unbuffered_connection');
$connection = getSymfonyContainer()->get('database_connection');
$router = getSymfonyContainer()->get('router');
/** @var ProviderRepository $providerRepository */
$providerRepository = getRepository(\AwardWallet\MainBundle\Entity\Provider::class);
$googleVisionResponseConverter = getSymfonyContainer()->get('aw.google.vision_response_converter');
$providerDetector = new ProviderDetector(
    new RegexpHandler(new DebugMatcher()),
    new RegexpCompilerCached(),
    getRepository(\AwardWallet\MainBundle\Entity\Provider::class),
    new NullLogger(),
    $connectionUnbuffered
);
$logger = getSymfonyContainer()->get('logger');

class Statement extends \Doctrine\DBAL\Statement
{
    private $data;

    public function __construct(array $providers)
    {
        $this->data = $providers;
    }

    public function fetch($fetchMode = null, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
        $current = current($this->data);
        next($this->data);

        return $current;
    }

    public function closeCursor()
    {
        reset($this->data);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }
}

class DetectResult
{
    /**
     * @var GoogleVisionResult
     */
    public $googleVisionResult;
    /**
     * @var array
     */
    public $providerDetect;
    /**
     * @var array
     */
    public $barcodeDetect;
    /**
     * @var string
     */
    public $error;
}

class CardImageInfo
{
    public $UserID;
    public $AccountID;
    public $CardImageID;
    public $UUID;
    public $ComputerVisionResult;
    public $Kind;
    public $DetectedProviderID;
    public $UploadDate;
    public $DetectedProviderDisplayName;

    //    public function __construct($UserID, $AccountID, $CardImageID, $Width, $Height, $ComputerVisionResult, $Kind, $DetectedProviderID)
    //    {
    //        $this->UserID               = $UserID;
    //        $this->AccountID            = $AccountID;
    //        $this->CardImageID          = $CardImageID;
    //        $this->Width                = $Width;
    //        $this->Height               = $Height;
    //        $this->ComputerVisionResult = $ComputerVisionResult;
    //        $this->Kind                 = $Kind;
    //        $this->DetectedProviderID   = $DetectedProviderID;
    //    }

    public function format()
    {
        $this->UserID = (int) $this->UserID;
        $this->AccountID = (int) $this->AccountID;
        $this->CardImageID = (int) $this->CardImageID;
        $this->ComputerVisionResult = convertGoogleVisionResult($this->ComputerVisionResult ?? '');
        $this->Kind = (int) $this->Kind;
        $this->DetectedProviderID = $this->DetectedProviderID ? (int) $this->DetectedProviderID : null;
    }
}

class CardImageCondensed
{
    public $CardImageID;
    public $AccountID;
    public $Kind;
    public $ComputerVisionResult;

    /**
     * CardImageCondensed constructor.
     *
     * @param $AccountID
     * @param $Kind
     * @param $ComputerVisionResult
     */
    //    public function __construct($AccountID, $Kind, $ComputerVisionResult)
    //    {
    //        $this->AccountID            = $AccountID;
    //        $this->Kind                 = $Kind;
    //        $this->ComputerVisionResult = $ComputerVisionResult;
    //    }
}

function convertGoogleVisionResult(string $computerVisionResult)
{
    global $googleVisionResponseConverter;

    $detectResult = new DetectResult();

    if (StringUtils::isEmpty($computerVisionResult)) {
        $detectResult->error = 'Empty column data';

        return $detectResult;
    }

    if (!($decoded = @json_decode($computerVisionResult, true))) {
        if ([] === $decoded) {
            $detectResult->error = 'Empty json data';
        } elseif (json_last_error()) {
            $detectResult->error = 'JSON error: ' . json_last_error_msg();
        }

        return $detectResult;
    }

    if (!isset($decoded['googleVision'])) {
        $detectResult->error = 'empty google vision result';

        return null;
    }

    $detectResult->googleVisionResult = $googleVisionResponseConverter->convert($decoded['googleVision']);
    $detectResult->providerDetect = $decoded['aw_provider_detect'] ?? null;
    $detectResult->barcodeDetect = $decoded['aw_barcode_detect'] ?? null;

    return $detectResult;
}

function findProviderByAccountImages(array $accountImages, array $providers)
{
    global $providerDetector;

    /** @var CardImageCondensed $accountImage */
    foreach ($accountImages as $kind => $accountImage) {
        if (!isset($accountImage->ComputerVisionResult)) {
            continue;
        }

        /** @var DetectResult $detectResult */
        $detectResult = $accountImage->ComputerVisionResult;

        if (!$detectResult->googleVisionResult) {
            continue;
        }

        $detectData = $providerDetector->detectByGoogleVisionResult(
            $detectResult->googleVisionResult,
            new Statement($providers)
        );

        if ($detectData) {
            return $detectData;
        }
    }

    return null;
}

function highlighter($text, array $highlightMarkersData)
{
    global $logger;
    $highlightText = [];
    $highlightTextByClass = [];

    foreach ($highlightMarkersData as $highlightMarkerData) {
        if (
            isset($highlightMarkerData['Debug']['text'])
            && ($highlightMarkerData['Debug']['text'] === $text)
        ) {
            foreach ($highlightMarkerData['Debug']['debug']['matches'] as $match) {
                [$textMatch, $position] = $match;
                $highlightText[] = $textMatch;
                $highlightTextByClass[$highlightMarkerData['Class']][] = $textMatch;
            }
        }
    }

    $highlightText = array_unique($highlightText);

    foreach ($highlightText as $i => $highlight) {
        $highlightText[$i] = preg_quote($highlight, '#');
    }

    if ($highlightText) {
        $regexp = '#\b(' . implode('|', $highlightText) . ')\b#iu';

        $text = preg_replace_callback(
            $regexp,
            function ($match) use ($highlightTextByClass) {
                $classes = [];

                foreach ($highlightTextByClass as $class => $highlightText) {
                    if (in_array($match[0], $highlightText)) {
                        $classes[] = $class;
                    }
                }

                $multilineParts = \explode("\n", $match[0]);

                foreach ($multilineParts as $i => $multilinePart) {
                    $multilineParts[$i] = '<span class="highlightText ' . implode(' ', $classes) . '">' . $multilinePart . '</span>';
                }

                return implode("<br/>", $multilineParts);
            },
            $text
        );
    }

    return str_replace("\n", '<br/>', $text);
}

function formatKeywords(string $keywords): string
{
    return htmlspecialchars(
        it(explodeLazy(',', $keywords))
        ->mapByTrim()
        ->toCollection()
        ->sort()
        ->join(', ')
    );
}

function loadCardImages(array $cardImageIds, $savedProviderId, $detectedProviderId, array &$providers)
{
    global $connectionUnbuffered;
    $randomCardImagesStmt = $connectionUnbuffered->executeQuery("
        select 
            a.UserID,
            a.AccountID,
            ciAccount.CardImageID,
            ciAccount.UUID,
            ciAccount.ComputerVisionResult,
            ciAccount.Kind,
            ciAccount.DetectedProviderID,
            ciAccount.UploadDate,
            p.DisplayName as DetectedProviderDisplayName
        from CardImage ci 
        join Account a on 
            ci.AccountID = a.AccountID
        join CardImage ciAccount on
            a.AccountID = ciAccount.AccountID
        left join Provider p on 
            ciAccount.DetectedProviderID = p.ProviderID
        where 
            ci.CardImageID in (?)
        order by 
            a.AccountID,
            ciAccount.Kind",
        [$cardImageIds],
        [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]
    );
    $randomCardImagesStmt->setFetchMode(\PDO::FETCH_CLASS, CardImageInfo::class);

    /** @var CardImageInfo $randomCardImage */
    while ($randomCardImage = $randomCardImagesStmt->fetch()) {
        $randomCardImage->format();
        $providers[$savedProviderId]['DetectedProviders'][$detectedProviderId]['CardImages'][$randomCardImage->AccountID][$randomCardImage->CardImageID] = $randomCardImage;
    }

    $randomCardImagesStmt->closeCursor();
}

function getTodayButtonsSpecial()
{
    return "<input type='button' onclick=\"navigateDay(-1)\" value='&lt;'>
	<input type='button' onclick=\"showToday()\" value='Today'>
	<input type='button' onclick=\"navigateDay(1)\" value='&gt;'>";
}

function modifyDateParam(string $sqlDate, string $modify): string
{
    $date = new \DateTime($sqlDate);
    $date->modify($modify);

    return $date->format('n/j/Y');
}

$fields = [
    "StartDate" => [
        "Type" => "date",
        "Value" => date(DATE_FORMAT, mktime(0, 0, 0, date("m"), date("d") - 3, date("Y"))),
    ],
    "EndDate" => [
        "Type" => "date",
        "Value" => date(DATE_FORMAT, mktime(0, 0, 0, date("m"), date("d") + 1, date("Y"))),
    ],
    "Button" => [
        "Type" => "html",
        "Caption" => "",
        "HTML" => getTodayButtonsSpecial(),
    ],
    "CardImageLimit" => [
        "Type" => 'integer',
        'Caption' => 'Accounts per detected provider',
        'Value' => '10',
        'Size' => '8',
    ],
];

$objForm = new class($fields) extends TForm {
    public function ButtonsHTML()
    {
        $html = parent::ButtonsHTML();
        $html .= '
        <button 
            type="submit" 
            class="btn-blue" 
            name="submitButtonTrigger" 
            onclick="var form = document.forms[\'editor_form\']; if( CheckForm( form ) ) { form.submitButton.value=\'detect\'; return true; } else return false;"
        >
            Detect
        </button>
        <input type="hidden" id="accountToExport" name="accountToExport" value="0" />
        <button type="button" onclick="changeAll(true)">Expand all</button>
        <button type="button" onclick="changeAll(false)">Collapse all</button>
        ';

        return $html;
    }

    public function FormatHTML($sHTML, $bExistsRequired)
    {
        // add custom form fields in table below
        return preg_replace('#</form>\s*$#ims', '', parent::FormatHTML($sHTML, $bExistsRequired));
    }
};

function drawTopHeader()
{
    require __DIR__ . "/reports/paymentsCommon.php";
    drawHeader("Card image detection");

    echo "<h2>Card image detection</h2><br>";
}

$totals = [];
$objForm->SubmitButtonCaption = "Show stats";
$objForm->SubmitOnce = false;

if (
    ($objForm->IsPost && $objForm->Check())
    || (!empty($_GET) && $objForm->Check($_GET))
) {
    $reDetectProviders = [];
    $objForm->CalcSQLValues();

    foreach ($_POST['providers'] ?? [] as $savedProviderId => $needsRedetect) {
        if ($needsRedetect) {
            $reDetectProviders[] = $savedProviderId;
        }
    }

    if (
        isset($_POST['submitButton'])
        && ('export' === $_POST['submitButton'])
        && StringUtils::isNotEmpty($_POST['accountToExport'])
    ) {
        $accountToExport = (string) $_POST['accountToExport'];
        header('Content-Type: application/json');
        header("Content-Disposition: attachment; filename=card_image_export_{$accountToExport}_" . date('Y_m_d_H_i_s') . ".json");
        getSymfonyContainer()->get('aw.card_image.exporter')->export(
            new StreamOutput(fopen('php://output', 'wb')),
            [$accountToExport]
        );

        exit;
    }

    drawTopHeader();
    echo $objForm->HTML();
    $detectMode = isset($_POST['submitButton']) && ($_POST['submitButton'] === 'detect');

    /** @var \Doctrine\DBAL\Connection $connection */
    $connection = getSymfonyContainer()->get('doctrine')->getConnection();
    $cardImageLimit = (int) $objForm->Fields['CardImageLimit']['Value'];
    $providers = [];
    $accounts = [];

    if (isset($_GET['ProviderID'])) {
        $providers = it(explodeLazy(',', $_GET['ProviderID']))->filterNotEmpty()->mapToInt()->toArray();
    } elseif (isset($_GET['AccountID'])) {
        $accounts = it(explodeLazy(',', $_GET['AccountID']))->filterNotEmpty()->mapToInt()->toArray();
    }

    $randomCardImagesStmt = $connection->executeQuery('SET @@session.group_concat_max_len = 1000000');
    $queryStats = $connection->executeQuery(
        "select
            providerStat.ProviderID as SavedProviderID,
            providerSaved.DisplayName as SavedProviderDisplayName,
            providerSaved.Code as SavedProviderCode,
            providerSaved.KeyWords as SavedProviderKeyWords,
            providerSaved.StopKeyWords as SavedProviderStopKeyWords,
            providerSaved.Accounts as SavedProviderAccounts,

            providerStat.DetectedProviderID,
            providerDetected.DisplayName as DetectedProviderDisplayName,
            providerDetected.Code as DetectedProviderCode,
            providerDetected.KeyWords as DetectedProviderKeyWords,
            providerDetected.StopKeyWords as DetectedProviderStopKeyWords,
            providerDetected.Accounts as DetectedProviderAccounts,

            count(if(providerStat.ProviderID = providerStat.DetectedProviderID, 1, null)) as SuccessCount,
            count(providerStat.AccountID) as TotalCount,
            
            group_concat(providerStat.CardImageID separator ',') as CardImageIDs
        from (
            select
                cardImageStat.AccountID,
                cardImageStat.ProviderID,
                
                ifnull(
                    ciMin.DetectedProviderID, 
                    ciMax.DetectedProviderID
                ) as DetectedProviderID,
                
                if(
                    ciMin.DetectedProviderID is not null,
                    ciMin.CardImageID,
                    ciMax.CardImageID
                ) as CardImageID
            from
                (
                    select
                        ci.AccountID,
                        ci.ProviderID,
                        max(ci.CardImageID) as MaxCardImageID,
                        min(ci.CardImageID) as MinCardImageID
                    from CardImage ci
                        join Provider p on p.ProviderID = ci.ProviderID
                    where
                        (ci.UploadDate between ? and ?) and
                        p.Kind <> ? and 
                        ci.ComputerVisionResult is not null and
                        ci.ComputerVisionResult <> '' and
                        ci.SubAccountID is null and " .
                        (
                            $providers ?
                                'ci.ProviderID in (?)' :
                                (
                                    $accounts ?
                                        'ci.AccountID in (?)' :
                                        'ci.ProviderID is not null'
                                )
                        ) .
                    " group by ci.AccountID, ci.ProviderID
                ) cardImageStat
                join CardImage ciMin on
                    ciMin.CardImageID = cardImageStat.MinCardImageID
                join CardImage ciMax on
                    ciMax.CardImageID = cardImageStat.MaxCardImageID
        ) providerStat
        left join Provider providerSaved on
            providerStat.ProviderID = providerSaved.ProviderID
        left join Provider providerDetected on
            providerStat.DetectedProviderID = providerDetected.ProviderID
        group by
            SavedProviderID,
            SavedProviderDisplayName,
            SavedProviderCode,
            SavedProviderKeyWords,
            SavedProviderStopKeyWords,
            SavedProviderAccounts,
            providerStat.DetectedProviderID,
            DetectedProviderDisplayName,
            DetectedProviderCode,
            DetectedProviderKeyWords,
            DetectedProviderStopKeyWords,
            DetectedProviderAccounts
            ",
        array_merge(
            [
                trim($objForm->Fields["StartDate"]["SQLValue"], "'"),
                trim($objForm->Fields["EndDate"]["SQLValue"], "'"),
                PROVIDER_KIND_CREDITCARD,
            ],
            $providers ?
                [$providers] :
                (
                    $accounts ?
                        [$accounts] :
                        []
                )
        ),
        array_merge(
            [
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                \PDO::PARAM_INT,
            ],
            ($providers || $accounts) ? [Doctrine\DBAL\Connection::PARAM_INT_ARRAY] : []
        )
    );
    $providers = [];

    while ($row = $queryStats->fetch(\PDO::FETCH_ASSOC)) {
        if (!isset($providers[$row['SavedProviderID']]['DetectedProviders'])) {
            $providers[$row['SavedProviderID']] = array_intersect_key(
                $row,
                [
                    'SavedProviderDisplayName' => null,
                    'SavedProviderCode' => null,
                    'SavedProviderKeyWords' => null,
                    'SavedProviderStopKeyWords' => null,
                    'SavedProviderAccounts' => null,
                ]
            );
        }

        $providers[$row['SavedProviderID']]['DetectedProviders'][$row['DetectedProviderID']] = $row;

        foreach (['TotalCount', 'SuccessCount'] as $countKey) {
            if (!isset($providers[$row['SavedProviderID']][$countKey])) {
                $providers[$row['SavedProviderID']][$countKey] = $row[$countKey];
            } else {
                $providers[$row['SavedProviderID']][$countKey] += $row[$countKey];
            }
        }
    }

    $queryStats->closeCursor();

    // providers with most absolute count of failed detects should be at the top
    uasort($providers, function ($a, $b) {
        return
            ($b['TotalCount'] - $b['SuccessCount'])
            -
            ($a['TotalCount'] - $a['SuccessCount']);
    });

    foreach ($providers as $savedProviderId => $savedProviderData) {
        $totals['stat']['total'] = ($totals['stat']['total'] ?? 0) + $savedProviderData['TotalCount'];
        $totals['stat']['success'] = ($totals['stat']['success'] ?? 0) + $savedProviderData['SuccessCount'];

        $providers[$savedProviderId] = $savedProviderData;
        $reDetectStats = []; // detec mode
        $providerInRedetectMode = in_array($savedProviderId, $reDetectProviders);
        $expandProvider = ($providerInRedetectMode || $accounts || (count($providers) === 1));

        foreach ($savedProviderData['DetectedProviders'] as $detectedProviderId => $detectedProviderData) {
            if ($detectMode && $providerInRedetectMode) {
                $cardImageIds = \explode(',', $detectedProviderData['CardImageIDs']);

                $providerStatementData = [
                    [
                        'ProviderID' => $savedProviderId,
                        'KeyWords' => $savedProviderData['SavedProviderKeyWords'],
                        'StopKeyWords' => $savedProviderData['SavedProviderStopKeyWords'],
                        'Accounts' => $savedProviderData['SavedProviderAccounts'],
                        'Kind' => PROVIDER_KIND_AIRLINE,
                    ],
                ];

                if (
                    $detectedProviderId
                    && ($savedProviderId != $detectedProviderId)
                ) {
                    $providerStatementData[] = [
                        'ProviderID' => $detectedProviderId,
                        'KeyWords' => $detectedProviderData['DetectedProviderKeyWords'],
                        'StopKeyWords' => $detectedProviderData['DetectedProviderStopKeyWords'],
                        'Accounts' => $detectedProviderData['DetectedProviderAccounts'],
                        'Kind' => PROVIDER_KIND_AIRLINE,
                    ];
                }

                usort($providerStatementData, function ($a, $b) { return $b['Accounts'] - $a['Accounts']; });

                foreach (array_chunk($cardImageIds, 50) as $cardImageIdsChunk) {
                    $chunkedCardImagesStmt = $connectionUnbuffered->executeQuery("
                        select
                            ciAccount.CardImageID,
                            a.AccountID,
                            ciAccount.Kind,
                            ciAccount.ComputerVisionResult
                        from CardImage ci 
                        join Account a on 
                            ci.AccountID = a.AccountID
                        join CardImage ciAccount on
                            a.AccountID = ciAccount.AccountID
                        where 
                            ci.CardImageID in (?)
                        order by 
                            a.AccountID,
                            ciAccount.Kind",
                        [$cardImageIdsChunk],
                        [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]
                    );

                    foreach (
                        stmtObj($chunkedCardImagesStmt, CardImageCondensed::class)
                        ->onEach(function (CardImageCondensed $cardImage) {
                            $cardImage->ComputerVisionResult = convertGoogleVisionResult($cardImage->ComputerVisionResult ?? '');
                        })
                        ->reindexByField('Kind')
                            ->groupAdjacentByFieldWithKeys('AccountID') as $accountCardImagesBuffer
                    ) {
                        if ($reDetectedProviderData = findProviderByAccountImages($accountCardImagesBuffer, $providerStatementData)) {
                            $reDetectedProviderId = $reDetectedProviderData[0]['ProviderID'];
                        } else {
                            $reDetectedProviderId = '';
                        }

                        $reDetectStats[$reDetectedProviderId][] = (int) current($accountCardImagesBuffer)->CardImageID;
                    }

                    $chunkedCardImagesStmt->closeCursor();
                }
            }
        }

        if ($detectMode && $providerInRedetectMode) {
            foreach ($reDetectStats as $detectedProviderId => $detectedCardImages) {
                if (isset($savedProviderData['DetectedProviders'][$detectedProviderId])) {
                    $savedProviderData['DetectedProviders'][$detectedProviderId]['CardImageIDs'] = implode(',', $detectedCardImages);
                } else {
                    $savedProviderData['DetectedProviders'][$detectedProviderId] = [
                        'SavedProviderID' => $savedProviderId,
                        'SavedProviderDisplayName' => $savedProviderData['SavedProviderDisplayName'],
                        'SavedProviderCode' => $savedProviderData['SavedProviderCode'],
                        'SavedProviderKeyWords' => $savedProviderData['SavedProviderKeyWords'],
                        'SavedProviderStopKeyWords' => $savedProviderData['SavedProviderStopKeyWords'],
                        'SavedProviderAccounts' => $savedProviderData['SavedProviderAccounts'],
                        'DetectedProviderID' => $detectedProviderId ?: null,
                        'DetectedProviderDisplayName' => $detectedProviderId ? $savedProviderData['SavedProviderDisplayName'] : null,
                        'DetectedProviderCode' => $detectedProviderId ? $savedProviderData['SavedProviderCode'] : null,
                        'DetectedProviderKeyWords' => $detectedProviderId ? $savedProviderData['SavedProviderKeyWords'] : null,
                        'DetectedProviderStopKeyWords' => $detectedProviderId ? $savedProviderData['SavedProviderStopKeyWords'] : null,
                        'DetectedProviderAccounts' => $detectedProviderId ? $savedProviderData['SavedProviderAccounts'] : null,
                        'SuccessCount' => 0,
                        'TotalCount' => 0,
                        'CardImageIDs' => implode(',', $detectedCardImages),
                    ];
                }
            }

            foreach (array_diff(
                array_keys($savedProviderData['DetectedProviders']),
                array_keys($reDetectStats)
            ) as $eliminatedProviderId) {
                $savedProviderData['DetectedProviders'][$eliminatedProviderId]['CardImageIDs'] = '';
            }
        }

        // topmost
        uasort($savedProviderData['DetectedProviders'], function ($a, $b) {
            return
                $b['TotalCount']
                -
                $a['TotalCount'];
        });

        // commit changes
        $providers[$savedProviderId]['DetectedProviders'] = $savedProviderData['DetectedProviders'];

        foreach ($savedProviderData['DetectedProviders'] as $detectedProviderId => $detectedProviderData) {
            $cardImageIds = \explode(',', $detectedProviderData['CardImageIDs']);

            if (count($cardImageIds) > $cardImageLimit + 1) {
                array_pop($cardImageIds);
                $cardImageIdsShuffled = $cardImageIds;
                shuffle($cardImageIdsShuffled);
            } else {
                $cardImageIdsShuffled = $cardImageIds;
            }

            loadCardImages(
                array_slice($cardImageIdsShuffled, 0, $cardImageLimit * 2),
                $savedProviderId,
                $detectedProviderId,
                $providers
            );

            $providers[$savedProviderId]['DetectedProviders'][$detectedProviderId]['CardImages']
                =
                array_slice(
                    $providers[$savedProviderId]['DetectedProviders'][$detectedProviderId]['CardImages'] ?? [],
                    0,
                    $cardImageLimit
                );
        }

        if ($detectMode) {
            $successCount = 0;
            $totalCount = 0;

            foreach ($reDetectStats as $reDetectProviderId => $reDetectProviderImages) {
                $reDetectProviderScore = count($reDetectProviderImages);

                if ($reDetectProviderId == $savedProviderId) {
                    $successCount += $reDetectProviderScore;
                }

                $totalCount += $reDetectProviderScore;
                $reDetectStats[$reDetectProviderId] = $reDetectProviderScore;
            }

            $providers[$savedProviderId]['ReDetectStats'] = [
                'Providers' => $reDetectStats,
                'TotalCount' => $totalCount,
                'SuccessCount' => $successCount,
            ];

            $totals['redetect']['total'] =
                ($totals['redetect']['total'] ?? 0) +
                ($providerInRedetectMode ? $totalCount : $savedProviderData['TotalCount']);

            $totals['redetect']['success'] =
                ($totals['redetect']['success'] ?? 0) +
                ($providerInRedetectMode ? $successCount : $savedProviderData['SuccessCount']);
        }
    }

    ?>
    <style>
    @import url('https://fonts.googleapis.com/css?family=PT+Mono');

    .tech-font {
        font-family: 'PT Mono', monospace;
    }

    table pre {
        margin: 0px;
    }
    /*subtables*/
    table.level1, table.level2, table.level3, table.level4 {
        border-collapse: collapse;
    }
    table.level1 {
        width:100%;
        /*margin-right:200px;*/
    }
    table.level2, table.level3, table.level4 {
    }
    table.level1 th {
        background:#ddd;
        padding:3px 6px;
        border:1px solid #ccc;
        font-size: 16px;
    }
    table.level1 td {
        padding:3px 0px;
        border:1px solid #ccc;
        text-align:center;
    }
    table.level2 th {
        font-size: 12px;
    }
    table.level3 th {
        font-size: 10px;
    }
    table.level4 th {
        font-size: 10px;
    }
    div.tableContainerHidden {
        padding: 0;
        display: none;
    }
    div.tableContainer {
        padding: 0;
    }
    td.empty {
        padding: 0 !important;
    }
    td.nonEmpty {
        padding: 3px 0 0 0;
    }
    table.level1 td.counter {
        vertical-align: top;
    }
    table.level1 .counter {
        width: 120px;
    }
    table.level2 .counter {
        width: 200px;
    }
    .highlightYellow {
        background-color: yellow;
    }
    .highlightText {
        display: inline-block;
    }
    .highlightUnderlineGreen {
        border-bottom: 3px solid green;
    }

    td.matches {
        text-align: left !important;
        vertical-align: top;
    }
    td.keywords {
        padding: 0;
    }
    </style>
    <script type='text/javascript'>
        function showChild(path, display) {
            if (typeof display === 'undefined') {
                var container = $('div.tableContainerHidden#' + path);
                var isVisibleBefore = container.is(':visible');
                container.toggle();

                if (!isVisibleBefore) {
                    showScrolled(getHeight() + 400);
                }

                $('div.tableContainerHidden#' + path + ' input').each(function () {
                    $(this).val($(this).val() === '1' ? '0' : '1');
                });

            } else {
                $('div.tableContainerHidden#' + path).toggle(display);
                $('div.tableContainerHidden#' + path + ' input').each(function () {
                    $(this).val(display ? '1' : '0');
                });

                if (display) {
                    showScrolled(getHeight() + 400);
                }
            }
        }

        function changeAll(display) {
            $('div.tableContainerHidden[id]:not([data-detected-provider-id])').each(function () {
                showChild($(this).attr('id'), display);
            });
        }

        var lastKnownScrollPosition = 0;
        var ticking = false;

        function showScrolled(bottomBoundary) {
            var list = document.querySelectorAll("div.tableContainerHidden[id*='_'][style*='display: block'] .proxy-img[data-backgroundimage]");

            for (var i = 0; i < list.length; i++) {
                var elem = list[i];

                // isNotVisible
                if (!(elem.offsetWidth || elem.offsetHeight || elem.getClientRects().length)) {
                    continue;
                }

                if (elem.getBoundingClientRect().top > bottomBoundary) {
                    return;
                }

                showImage(elem);
            }
        }

        function getHeight() {
            return window.innerHeight || document.body.clientHeight;
        }

        function showImage(el) {
            var image = el.dataset.backgroundimage;

            if (!image) {
                return;
            }

            delete el.dataset.backgroundimage;
            el.style.backgroundImage = image;
        }

        window.onload = function () {
            window.addEventListener('scroll', function () {
                lastKnownScrollPosition = window.scrollY;

                if (!ticking) {
                    window.requestAnimationFrame(function () {
                        showScrolled(lastKnownScrollPosition + getHeight() + 400);
                        ticking = false;
                    });
                }

                ticking = true;
            });

            document.querySelectorAll('.proxy-img').forEach(function (img) {
                img.addEventListener('mouseover', function (e) {
                    showImage(e.target);
                });
            });
        }

    </script>

    <?php if (isset($totals['stat']) && $totals['stat']['total'] > 0) { ?>
        <br/>
        <b>Before:</b> <?php echo $totals['stat']['success']; ?> / <?php echo $totals['stat']['total']; ?> (<?php echo round(($totals['stat']['success'] / $totals['stat']['total']) * 100, 2); ?>%)
        <br/>
    <?php } ?>

    <?php if (
        $detectMode
        && isset($totals['redetect'])
        && ($totals['redetect']['total'] > 0)
    ) { ?>
        <b>After:</b> <?php echo $totals['redetect']['success']; ?> / <?php echo $totals['redetect']['total']; ?> (<?php echo round(($totals['redetect']['success'] / $totals['redetect']['total']) * 100, 2); ?>%)
        <br/>
    <?php } ?>

    <br/>

    <table class='level1'>
        <tr>
            <th class="counter">Stat</th>
            <th>Provider</th>
        </tr>
        <?php
        foreach ($providers as $savedProviderId => $savedProviderData) {
            $providerInRedetectMode = $detectMode && in_array($savedProviderId, $reDetectProviders);
            ?>
            <tr>
      			<td class="counter">
                    <a class='smallOpen' href="javascript:showChild('<?php echo $savedProviderId; ?>')">
                        <?php echo $savedProviderData['SuccessCount']; ?>
                        / <?php echo $savedProviderData['TotalCount']; ?>
                        (<?php echo round($savedProviderData['SuccessCount'] / $savedProviderData['TotalCount'] * 100, 2); ?>
                        %)
                    </a>
                    <?php if ($providerInRedetectMode) { ?>
                        <br/>
                        <b>New:</b> <?php echo $savedProviderData['ReDetectStats']['SuccessCount']; ?> / <?php echo $savedProviderData['TotalCount']; ?>
                        (<?php echo round($savedProviderData['ReDetectStats']['SuccessCount'] / $savedProviderData['TotalCount'] * 100, 2); ?>%)
                    <?php } ?>
                    <br/>
                    <br/>
                    <a target="_blank"
                       href="?ProviderID=<?php echo urlencode($savedProviderId); ?>&StartDate=<?php echo urlencode($_GET['StartDate'] ?? $_POST['StartDate']); ?>&EndDate=<?php echo urlencode($_GET['EndDate'] ?? $_POST['EndDate']); ?>&CardImageLimit=<?php echo urlencode($_GET['CardImageLimit'] ?? $_POST['CardImageLimit']); ?>&submitButton=detect">permalink</a>
                </td>
                <td class="tableContainerHidden">
                    <div style="text-align: left">
                        <b>Name:</b> <a href="/manager/edit.php?ID=<?php echo $savedProviderId; ?>&Schema=Provider"
                                        target="_blank">
                            <?php echo htmlspecialchars($savedProviderData['SavedProviderDisplayName']); ?>
                        </a><br/>
                        <b>KeyWords:</b> <?php echo formatKeywords($savedProviderData['SavedProviderKeyWords'] ?? ''); ?>
                        <br/>
                        <b>StopKeyWords:</b> <?php echo formatKeywords($savedProviderData['SavedProviderStopKeyWords'] ?? ''); ?>
                        <br/>
                        <b>Popularity (accounts):</b> <?php echo $savedProviderData['SavedProviderAccounts']; ?><br/>
                    </div>
                    <div id='<?php echo $savedProviderId; ?>' class="tableContainerHidden"
                         style="<?php echo $expandProvider ? 'display: block;' : ''; ?>">
                        <input type="hidden" name="providers[<?php echo $savedProviderId; ?>]"
                               value="<?php echo intval($expandProvider); ?>"/>
                        <table class="level2">
                            <tr>
                                <th class="counter">Detected</th>
                                <th></th>
                            </tr>
                            <?php

                            foreach ($savedProviderData['DetectedProviders'] as $detectedProviderId => $detectedProviderData) {
                                ?>
                                <tr>
                                    <td class="counter <?php echo $detectedProviderId == $savedProviderId ? 'highlightYellow' : ''; ?>"
                                        data-detected-provider-id="<?php echo $detectedProviderId; ?>">
									    <b>Count:</b>
                                        <?php if ($detectedProviderData['CardImages']) { ?>
                                            <a class='smallOpen'
                                               href='javascript:showChild("<?php echo $savedProviderId . '_' . $detectedProviderId; ?>")'><?php echo $detectedProviderData['TotalCount']; ?></a>
                                        <?php } else {
                                            echo $detectedProviderData['TotalCount'];
                                        } ?>

                                        <br/>
                                        <?php if ($providerInRedetectMode) { ?>
                                            <b>New Count:</b>
                                            <?php
                                                $new = (int) ($savedProviderData['ReDetectStats']['Providers'][$detectedProviderId] ?? 0);
                                            $old = (int) $detectedProviderData['TotalCount'];
                                            $sign =
                                                $new > $old ?
                                                    '+' :
                                                    (
                                                        $new < $old ?
                                                            '-' :
                                                            ''
                                                    );

                                            if ('' === $sign) {
                                                echo "{$new} (same)";
                                            } else {
                                                echo "{$new} ({$sign}" . abs($new - $old) . ')';
                                            }
                                            ?>
                                            <br/>
                                        <?php } ?>
                                        <br/>
                                    </td>
									<td class="empty">
                                        <div style="text-align: left">
                                            <b>Name:</b>
                                            <?php if (StringUtils::isNotEmpty($name = htmlspecialchars($detectedProviderData['DetectedProviderDisplayName']))) {
                                                ?>
                                                <a href="/manager/edit.php?ID=<?php echo $detectedProviderId; ?>&Schema=Provider"
                                                   target="_blank">
                                                    <?php echo $name; ?>
                                                    </a>
                                                <?php
                                            } else {
                                                echo 'undetected';
                                            }
                                ?>
                                            <br/>
                                            <?php if (StringUtils::isNotEmpty($name)) { ?>
                                                <b>KeyWords:</b>
                                                <br/> <?php echo formatKeywords($detectedProviderData['DetectedProviderKeyWords'] ?? ''); ?>
                                                <br/>
                                                <b>StopKeyWords:</b>
                                                <br/> <?php echo formatKeywords($detectedProviderData['DetectedProviderStopKeyWords'] ?? ''); ?>
                                                <br/>
                                                <b>Popularity
                                                    (accounts):</b> <?php echo $detectedProviderData['DetectedProviderAccounts'] ?? ''; ?>
                                                <br/>
                                            <?php } ?>
                                            <br/>
                                        </div>
                                        <div id="<?php echo $savedProviderId . '_' . $detectedProviderId; ?>"
                                             data-detected-provider-id="<?php echo $detectedProviderId; ?>"
                                             class="tableContainerHidden" <?php echo $accounts ? 'style="display: block;"' : ''; ?>>
                                            <?php if ($detectedProviderData['CardImages']) { ?>
                                                <table class='level3'>
                                                    <tr>
                                                        <th style="width: 60px;">AccountID</th>
                                                        <th></th>
                                                    </tr>
                                                    <?php
                                        /** @var CardImageInfo[] $accountCardImages */
                                        foreach ($detectedProviderData['CardImages'] as $accountId => $accountCardImages) {
                                            $firstImage = current($accountCardImages);
                                            ?>
                                                            <tr>
                                                                <td>
                                                                    <a
                                                                        title="view by sharing code"
                                                                        href="/manager/impersonate?UserID=<?php echo $firstImage->UserID; ?>&Goto=<?php echo urlencode("/account/list#/?account={$firstImage->AccountID}"); ?>"
                                                                        target="_blank"
                                                                    ><?php echo $firstImage->AccountID; ?></a>
                                                                    <br/>
                                                                    <br/>
                                                                    <button
                                                                        type="submit"
                                                                        class="btn-blue"
                                                                        name="submitButtonTrigger"
                                                                        onclick="
                                                                            var form = document.forms['editor_form'];
                                                                            $('#accountToExport').val('<?php echo $firstImage->UUID; ?>');

                                                                            if( CheckForm( form ) ) {
                                                                                form.submitButton.value='export';
                                                                                return true;
                                                                            } else {
                                                                                return false;
                                                                            }
                                                                        "
                                                                    >
                                                                        Export
                                                                    </button>
                                                                    <br/>
                                                                    <br/>
                                                                    <a target="_blank"
                                                                       href="?AccountID=<?php echo urlencode($firstImage->AccountID); ?>&StartDate=<?php echo modifyDateParam(it($accountCardImages)->UploadDate->min(), '-1 day'); ?>&EndDate=<?php echo modifyDateParam(it($accountCardImages)->UploadDate->max(), '+1 day'); ?>&CardImageLimit=<?php echo urlencode($_GET['CardImageLimit'] ?? $_POST['CardImageLimit']); ?>&submitButton=detect">permalink</a>
                                                                </td>
                                                                <td class="empty">
                                                                    <div class="tableContainer">
                                                                    <table class="level3">
                                                                        <tr>
                                                                            <th style="width: 30px;">Side</th>
                                                                            <th style="width: 330px;">Image</th>
                                                                            <th style="width: 210px;">Recognition</th>
                                                                            <th style="width: 210px;">Keywords</th>
                                                                        </tr>
                                                                        <?php
                                                            foreach ($accountCardImages as $cardImageId => $cardImage) {
                                                                /** @var DetectResult $detectResult */
                                                                $detectResult = $cardImage->ComputerVisionResult;
                                                                $proxyUrl = $router->generate('aw_card_image_download_staff_proxy', ['cardImageUUID' => $cardImage->UUID]);
                                                                ?>
                                                                                <tr>
                                                                                    <td class="<?php
                                                                        if (
                                                                            isset($cardImage->DetectedProviderID)
                                                                            && ($cardImage->DetectedProviderID == $detectedProviderId)
                                                                        ) {
                                                                            echo 'highlightYellow';
                                                                        }
                                                                ?>">
                                                                                        <?php echo CardImage::KIND_BACK == $cardImage->Kind ? 'Back' : 'Front'; ?>
                                                                                    </td>
                                                                                    <td>
                                                                                        <a href="<?php echo $proxyUrl; ?>"
                                                                                           target="_blank">
                                                                                            <div
                                                                                                class="proxy-img"
                                                                                                style="
                                                                                                   width: 320px;
                                                                                                   height: 180px;
                                                                                                   background-size: 320px 180px;
                                                                                                "
                                                                                                data-backgroundimage="url('<?php echo $proxyUrl; ?>')"
                                                                                            >
                                                                                            </div>
                                                                                        </a>
                                                                                    </td>
                                                                                    <td class="matches">
                                                                                        <?php
                                                                    $highlightData = [];

                                                                if ($detectResult && $detectResult->providerDetect) {
                                                                    $highlightData = [
                                                                        [
                                                                            'Class' => 'highlightYellow',
                                                                            'Debug' => $detectResult->providerDetect,
                                                                            'Label' => 'Detected',
                                                                        ],
                                                                    ];
                                                                }

                                                                if ($providerInRedetectMode) {
                                                                    $reDetectedProviderData = findProviderByAccountImages(
                                                                        [CardImage::KIND_FRONT => $cardImage],
                                                                        [
                                                                            [
                                                                                'ProviderID' => $detectedProviderId,
                                                                                'KeyWords' => $detectedProviderData['DetectedProviderKeyWords'],
                                                                                'StopKeyWords' => $detectedProviderData['DetectedProviderStopKeyWords'],
                                                                                'Accounts' => $detectedProviderData['DetectedProviderAccounts'],
                                                                                'Kind' => PROVIDER_KIND_AIRLINE,
                                                                            ],
                                                                            [
                                                                                'ProviderID' => $savedProviderId,
                                                                                'KeyWords' => $savedProviderData['SavedProviderKeyWords'],
                                                                                'StopKeyWords' => $savedProviderData['SavedProviderStopKeyWords'],
                                                                                'Accounts' => $savedProviderData['SavedProviderAccounts'],
                                                                                'Kind' => PROVIDER_KIND_AIRLINE,
                                                                            ],
                                                                        ]
                                                                    );

                                                                    if ($reDetectedProviderData) {
                                                                        [$reDetectedProvider, $text, $debug] = $reDetectedProviderData;

                                                                        $highlightData[] = [
                                                                            'Class' => 'highlightUnderlineGreen',
                                                                            'Debug' => [
                                                                                'keywords' => $reDetectedProvider['KeyWords'],
                                                                                'text' => $text,
                                                                                'debug' => $debug,
                                                                            ],
                                                                            'Label' => 'Re-detected',
                                                                        ];
                                                                    }
                                                                }

                                                                if (isset($detectResult->googleVisionResult->text)) {
                                                                    echo "<b>Text:</b><pre class='tech-font'>" . highlighter(
                                                                        $detectResult->googleVisionResult->text,
                                                                        $highlightData
                                                                    ) . '</pre>';
                                                                }

                                                                foreach ($logos = $detectResult->googleVisionResult->logos ?? [] as $i => $logoAnnotation) {
                                                                    if ($logoAnnotation->score >= ProviderDetector::LOGO_LOWER_SCORE) {
                                                                        echo "<b>Logo " . ($i + 1) . " (" . round($logoAnnotation->score, 2) . "):</b><pre class='tech-font'>" . highlighter(
                                                                            $logoAnnotation->text,
                                                                            $highlightData
                                                                        ) . '</pre>';
                                                                    } else {
                                                                        echo "<b>Logo " . ($i + 1) . " (" . round($logoAnnotation->score, 2) . "):</b><pre class='tech-font'>" . htmlspecialchars($logoAnnotation->text) . '</pre>';
                                                                    }
                                                                }

                                                                if ($logos) {
                                                                    echo '<br/>';
                                                                }

                                                                if (isset($detectResult->barcodeDetect)) {
                                                                    echo "<b>Barcode:</b><pre class='tech-font'>" . htmlspecialchars($detectResult->barcodeDetect['text'] ?? '') . "</pre>\n";
                                                                    echo "<b>Barcode type:</b><pre class='tech-font'>" . htmlspecialchars($detectResult->barcodeDetect['format'] ?? '') . "</pre><br/>\n";
                                                                }

                                                                if (isset($cardImage->DetectedProviderID)) {
                                                                    echo "
                                                                                                    <b>Detected provider:</b>
                                                                                                    <a href=\"/manager/edit.php?ID={$cardImage->DetectedProviderID}&Schema=Provider\" target=\"_blank\">
                                                                                                        {$cardImage->DetectedProviderDisplayName} (id: {$cardImage->DetectedProviderID})
                                                                                                    </a>
                                                                                                    <br/>";
                                                                }

                                                                if (isset($cardImage->UploadDate)) {
                                                                    echo "<b>Upload date:</b> {$cardImage->UploadDate} (UTC)<br/>";
                                                                }

                                                                if (isset($detectResult->error)) {
                                                                    echo '<span style="background-color: #f04662; color: white">Detect error: ' . $detectResult->error . '</span><br/>';
                                                                }

                                                                if (isset($detectResult->providerDetect['savedProviderKeywords'])) {
                                                                    $highlightData[] = [
                                                                        'Label' => 'Saved',
                                                                        'Debug' => [
                                                                            'keywords' => $detectResult->providerDetect['savedProviderKeywords'],
                                                                        ],
                                                                    ];
                                                                }

                                                                if (isset($detectResult->providerDetect['savedProviderStopKeywords'])) {
                                                                    $highlightData[] = [
                                                                        'Label' => 'Saved Stop',
                                                                        'Debug' => [
                                                                            'keywords' => $detectResult->providerDetect['savedProviderStopKeywords'],
                                                                        ],
                                                                    ];
                                                                }

                                                                ?>
                                                                                    </td>
                                                                                    <td class="matches">
                                                                                        <?php
                                                                if ($highlightData) {
                                                                    foreach ($highlightData as $highlightDatum) {
                                                                        if (!isset($highlightDatum['Debug']['keywords'])) {
                                                                            continue;
                                                                        }

                                                                        $keywords = formatKeywords($highlightDatum['Debug']['keywords']);

                                                                        if (isset($highlightDatum['Debug']['debug']['matches'])) {
                                                                            $matchedWords = array_column($highlightDatum['Debug']['debug']['matches'], 0);

                                                                            foreach ($matchedWords as $i => $matchedWord) {
                                                                                $matchedWords[$i] = preg_quote($matchedWord, '#');
                                                                            }

                                                                            $class = $highlightDatum['Class'];

                                                                            if ($matchedWords) {
                                                                                $keywords = preg_replace_callback(
                                                                                    '#(^|,\s*)\b(' . implode('|', $matchedWords) . ')\b(,|$)#ius',
                                                                                    function ($match) use ($class) {
                                                                                        return "{$match[1]}<span class=\"highlightText {$class}\">{$match[2]}</span>{$match[3]}";
                                                                                    },
                                                                                    $keywords
                                                                                );
                                                                            }
                                                                        }

                                                                        echo "<b>{$highlightDatum['Label']}:</b><br/>{$keywords}<br/>";
                                                                    }
                                                                }

                                                                ?>
                                                                                    </td>
                                                                                </tr>
                                                                        <?php } ?>
                                                                    </table>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        <?php
                                        }
                                                ?>
                                                </table>
                                            <?php } ?>
                                        </div>
                                        <?php echo $accounts ? "<script>showScrolled(getHeight() + 400);</script>" : ""; ?>
                                    </td>
                                    <?php
                            }
            ?>
						</table>
                    </div>
                </td>
            </tr>
            <?php
        }
    ?>
    </table>
    </form>
    <?php
} else {
    drawTopHeader();
    echo $objForm->HTML();
}

drawFooter();
