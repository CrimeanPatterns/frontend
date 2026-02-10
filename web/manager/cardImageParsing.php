<?php

use AwardWallet\CardImageParser\CardImageParserInterface;
use AwardWallet\CardImageParser\CardImageParserLoader;
use AwardWallet\CardImageParser\CardRecognitionResult;
use AwardWallet\CardImageParser\CreditCardDetection\CreditCardDetectionResult;
use AwardWallet\CardImageParser\CreditCardDetection\CreditCardDetectorInterface;
use AwardWallet\CardImageParser\DOMConverter\Node;
use AwardWallet\CardImageParser\ImageRecognitionResult;
use AwardWallet\MainBundle\Entity\CardImage;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Globals\StringUtils;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Output\StreamOutput;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

$schema = "cardImageParsing";

require "start.php";

require_once $sPath . "/kernel/siteFunctions.php";

require_once "$sPath/kernel/public.php";

require_once "$sPath/kernel/TForm.php";

require_once __DIR__ . "/reports/common.php";
$bSecuredPage = false;

$router = getSymfonyContainer()->get('router');
/** @var ProviderRepository $providerRepository */
$providerRepository = getRepository(\AwardWallet\MainBundle\Entity\Provider::class);
$domConverter = getSymfonyContainer()->get('aw.card_iamge.dom_converter');
$parserLoader = getSymfonyContainer()->get(CardImageParserLoader::class);
$router = getSymfonyContainer()->get('router');
$logger = getSymfonyContainer()->get('logger');
$cardImageParserLoader = new CardImageParserLoader(
    new NullLogger(),
    __DIR__ . '/../../app'
);

class ProviderPartialWithCode extends Provider
{
    public function __construct(string $code)
    {
        $this->code = $code;
    }
}

function modifyDateParam(string $sqlDate, string $modify): string
{
    $date = new \DateTime($sqlDate);
    $date->modify($modify);

    return $date->format('n/j/Y');
}

/**
 * @return CardImageParserInterface|CreditCardDetectorInterface|null
 */
function loadParser(string $providerCode)
{
    global $parserLoader, $logger;

    if (!($parser = $parserLoader->loadParser($providerCode))) {
        throw new \InvalidArgumentException('Could not load parser');
    }

    if ($parser instanceof LoggerAwareInterface) {
        $parser->setLogger($logger);
    }

    return $parser;
}

function reportProviders(TForm $form, array $reParsedProviders = [])
{
    $container = getSymfonyContainer();
    $isProdMode = $container->get('kernel')->getEnvironment() === 'prod';
    $unbufferedConnection = $container->get('doctrine.dbal.unbuffered_connection');
    $unbufferedConnection->executeQuery('SET @@session.group_concat_max_len = 1000000');
    $providers = [];
    $accounts = [];

    if (isset($_GET['ProviderID'])) {
        $providers = it(explode(',', $_GET['ProviderID']))->mapToInt()->filterNotEmpty()->toArray();
    } elseif (isset($_GET['AccountID'])) {
        $accounts = it(explode(',', $_GET['AccountID']))->mapToInt()->filterNotEmpty()->toArray();
    }

    $providersStmt = $unbufferedConnection->executeQuery("
        select 
            accountStat.ProviderID,
            p.DisplayName,
            p.Code,
            p.Accounts,
            GROUP_CONCAT(accountStat.AccountID separator ',') as AccountIDs 
        from (
            select
                ci.AccountID,
                ci.ProviderID
            from CardImage ci
            where
                (ci.UploadDate between ? and ?) and
                ci.ComputerVisionResult is not null and
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
        ) accountStat
        join Provider p on 
            accountStat.ProviderID = p.ProviderID
        where 
            p.Kind <> ? and
            (
                p.CanDetectCreditCards = 1 or
                p.CanParseCardImages = 1
            )
        group by accountStat.ProviderID, p.DisplayName, p.Code, p.Accounts",
        array_merge(
            [
                trim($form->Fields["StartDate"]["SQLValue"], "'"),
                trim($form->Fields["EndDate"]["SQLValue"], "'"),
            ],
            $providers ?
                [$providers] :
                (
                    $accounts ?
                        [$accounts] :
                        []
                ),
            [PROVIDER_KIND_CREDITCARD]
        ),
        array_merge(
            [
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
            ],
            ($providers || $accounts) ? [Doctrine\DBAL\Connection::PARAM_INT_ARRAY] : [],
            [\PDO::PARAM_INT]
        )
    );
    $providers = [];

    foreach (stmtAssoc($providersStmt) as $row) {
        $row['AccountIDs'] = explode(',', $row['AccountIDs']);
        $providers[$row['ProviderID']] = $row;
    }

    $limit = $form->Fields['CardImageLimit']['Value'];
    $reParseMode = isset($_POST['submitButton']) && ($_POST['submitButton'] === 'parse');
    echo "<table class='level1'>
            <thead>
                <th class='counter'>Stat</th>
                <th>Provider</th>
            </thead>
            <tbody>";

    foreach ($providers as $providerId => $providerData) {
        $providerInReparseMode = $reParseMode && in_array($providerData['ProviderID'], $reParsedProviders);
        $providerExpanded = $providerInReparseMode || $accounts || (count($providers) === 1);
        $accountIds = $providerData['AccountIDs'];

        if (count($accountIds) > $limit + 1) {
            array_pop($accountIds);
            $accountIdsShuffled = $accountIds;
            shuffle($accountIdsShuffled);
        } else {
            $accountIdsShuffled = $accountIds;
        }

        if (!($parser = loadParser($providerData['Code']))) {
            continue;
        }

        $providerData['SupportedProperties'] = $parser instanceof CardImageParserInterface ? ([get_class($parser), 'getSupportedProperties'])() : [];
        $supportedPropertiesCount = count($providerData['SupportedProperties']);

        $accountIdsStmt = $unbufferedConnection->executeQuery("
            select
                a.AccountID,
                ci.CardImageID,
                ci.Kind,
                ci.Width,
                ci.Height,
                ci.ComputerVisionResult,
                ci.CCDetected,
                ci.CCDetectorVersion
            from Account a
            join CardImage ci on
                a.AccountID = ci.AccountID
            where
                a.AccountID in (?) and
                ci.CCDetected = 0
            order by a.AccountID",
            [$accountIds],
            [Connection::PARAM_INT_ARRAY]
        );

        $maxReparsedScore = 0;
        $reParseScore = 0;
        $maxPossibleSavedSuccessScore = 0;
        $successScore = 0;

        foreach (
            stmtAssoc($accountIdsStmt)
            ->reindexByColumn('Kind')
            ->groupAdjacentByColumnWithKeys('AccountID') as $accountBuffer
        ) {
            $firstImage = current($accountBuffer);
            $computerVisionResult = @json_decode($firstImage['ComputerVisionResult'], true);

            if (isset(
                $computerVisionResult['aw_parsing']['result'],
                $computerVisionResult['aw_parsing']['supported_properties']
            )) {
                foreach ($computerVisionResult['aw_parsing']['supported_properties'] as $supportedProperty) {
                    if (isset($computerVisionResult['aw_parsing']['result'][$supportedProperty])) {
                        $successScore++;
                    }
                }

                $maxPossibleSavedSuccessScore += count($computerVisionResult['aw_parsing']['supported_properties']);
            }

            if ($reParseMode) {
                [$ccDetectionResult, $reParsedProperties] = parseAccountImages($providerData['Code'], $accountBuffer);

                /** @var CreditCardDetectionResult $ccDetectionResult */
                if (!($ccDetectionResult && $ccDetectionResult->isDetected())) {
                    foreach ($providerData['SupportedProperties'] as $supportedProperty) {
                        if (isset($reParsedProperties[$supportedProperty])) {
                            $reParseScore++;
                        }
                    }

                    $maxReparsedScore += $supportedPropertiesCount;
                }
            }
        }

        echo "
            <tr 
                class='providerRow' 
                data-unhappiness='" . ($maxPossibleSavedSuccessScore - $successScore) . "' 
                data-provider-id='{$providerData['ProviderID']}'
            >
                <td class='counter'>
                    <a class='smallOpen' href=\"javascript:showChild('{$providerData['ProviderID']}')\">
                        {$successScore} / {$maxPossibleSavedSuccessScore}
                        (" . round($maxPossibleSavedSuccessScore ? $successScore / $maxPossibleSavedSuccessScore * 100 : 0, 2) . "%)
                    </a>
                         
                     " .
                    ($providerInReparseMode ?
                        "   
                        <br/>
                        <b>New:</b> {$reParseScore} / {$maxReparsedScore}
                        (" . round($maxReparsedScore ? $reParseScore / $maxReparsedScore * 100 : 0, 2) . "%)" :
                        ""
                    ) . "
                    <br/>
                    <br/>
                    <a 
                        target='_blank' 
                        href='?ProviderID=" . urlencode($providerId) .
                            "&StartDate=" . urlencode($_GET['StartDate'] ?? $_POST['StartDate']) .
                            "&EndDate=" . urlencode($_GET['EndDate'] ?? $_POST['EndDate']) .
                            "&CardImageLimit=" . urlencode($_GET['CardImageLimit'] ?? $_POST['CardImageLimit']) .
                            "&submitButton=submit'
                    >
                        permalink
                    </a>
                </td>
                
               <td>
                    <div style='text-align: left'>
                        <b>Name:</b> <a href='/manager/edit.php?ID={$providerData['ProviderID']}&Schema=Provider' target='_blank'>
                            " . htmlspecialchars($providerData['DisplayName']) . "
                        </a><br/>
                        <b>Popularity (accounts):</b> {$providerData['Accounts']}<br/>
                    </div>
                    <div class='tableContainerHidden' id='{$providerData['ProviderID']}' style='" . ($providerExpanded ? 'display: block;' : '') . "'>
                    <input 
                        type='hidden' 
                        name='providers[{$providerData['ProviderID']}]' 
                        value='" . intval($providerExpanded) . "' 
                    />
                    <table class='level2'>
                        <tr>
                            <th style=\"width: 60px;\">AccountID</th>
                            <th></th>
                        </tr>
                                                    
       ";

        $shuffledAccountsStmt = $unbufferedConnection->executeQuery("
            select
                a.AccountID,
                a.UserID,
                ci.CardImageID,
                ci.UUID,
                ci.Kind,
                ci.Width,
                ci.Height,
                ci.ComputerVisionResult,
                ci.UploadDate,
                ci.CCDetected,
                ci.CCDetectorVersion
            from Account a
            join CardImage ci on
                a.AccountID = ci.AccountID
            where
                a.AccountID in (?)
            order by a.AccountID
        ",
            [$accountIdsShuffled],
            [Connection::PARAM_INT_ARRAY]
        );

        foreach (
            stmtAssoc($shuffledAccountsStmt)
            ->reindexByColumn('Kind')
            ->groupAdjacentByColumnWithKeys('AccountID') as $accountImages
        ) {
            printAccountImages($providerData['Code'], $accountImages, $providerInReparseMode);
        }

        echo "</table>" .
            (($providerInReparseMode || $accounts) ? '<script type="module">showScrolled(getHeight() + 400);</script>' : '') .
        "</div>";
    }

    echo "</tbody></table>";
}

function printAccountImages(string $providerCode, array $accountBuffer, bool $reParseMode = false)
{
    global $router, $parserLoader;
    $firstImage = current($accountBuffer);

    echo "<tr>
        <td>
            <a
                href='/manager/impersonate?UserID={$firstImage['UserID']}&Goto=" . urlencode("/account/list#/?account={$firstImage['AccountID']}") . "'
                target='_blank'
            >{$firstImage['AccountID']}</a>
            <br/>
            <br/>
            <button 
                type=\"submit\" 
                class=\"btn-blue\" 
                name=\"submitButtonTrigger\" 
                onclick=\"
                    var form = document.forms['editor_form'];
                    $('#accountToExport').val('{$firstImage['UUID']}');
                     
                    if( CheckForm( form ) ) { 
                        form.submitButton.value='export'; 
                        return true; 
                    } else { 
                        return false;
                    }
                \"
            >
                Export
            </button>
            <br/>
            <br/>
            <a 
                target='_blank' 
                href='?AccountID=" . urlencode($firstImage['AccountID']) .
                    "&StartDate=" . modifyDateParam(it($accountBuffer)['UploadDate']->min(), '-1 day') .
                    "&EndDate=" . modifyDateParam(it($accountBuffer)['UploadDate']->max(), '+1 day') .
                    "&CardImageLimit=" . urlencode($_GET['CardImageLimit'] ?? $_POST['CardImageLimit']) .
                    "&submitButton=submit'
            >
                permalink
            </a>
        </td>
        <td class='matches'>
            <div class='tableContainer'>
                <table class='level4'>
                    <tr>
                        <th style='width: 30px;'>Side</th>
                        <th style='width: 330px;'>Image</th>
                        <th style='width: 210px;'>Text</th>
                        <th>DOM</th>
                    </tr>";

    if ($reParseMode) {
        /** @var CardRecognitionResult $parserInputData */
        /** @var CreditCardDetectionResult $ccDetectionResult */
        [$ccDetectionResult, $parsedProperties] = parseAccountImages($providerCode, $accountBuffer);
    }

    foreach ($accountBuffer as $kind => $cardImageData) {
        $cardImageUrl = $router->generate('aw_card_image_download_staff_proxy', ['cardImageUUID' => $cardImageData['UUID']]);
        $computerVisionResult = @json_decode($cardImageData['ComputerVisionResult'], true);
        $cardImageData['ComputerVisionResult'] = $computerVisionResult;

        $imgWidth = (int) $cardImageData['Width'];
        $imgHeight = (int) $cardImageData['Height'];
        $logoAnnotations = [];

        foreach ($computerVisionResult['googleVision']['logoAnnotations'] ?? [] as $logoAnnotation) {
            if (!isset(
                $logoAnnotation['description'],
                $logoAnnotation['score']
            )) {
                continue;
            }

            $node = Node::createFromAnnotation($logoAnnotation, $imgWidth, $imgHeight);

            if (!$node) {
                continue;
            }

            $logoAnnotations[] = [
                $node->left,
                $node->top,
                $node->width,
                $node->height,
                $node->text,
                (int) ($logoAnnotation['score'] * 100),
            ];
        }

        $textAnnotations = [];

        foreach (array_slice($computerVisionResult['googleVision']['textAnnotations'] ?? [], 1) as $textAnnotation) {
            if (!isset($textAnnotation['description'])) {
                continue;
            }

            $node = Node::createFromAnnotation($textAnnotation, $imgWidth, $imgHeight);

            if (!$node) {
                continue;
            }

            $textAnnotations[] = [
                $node->left * $imgWidth / 100,
                $node->top * $imgHeight / 100,
                $node->width * $imgWidth / 100,
                $node->height * $imgHeight / 100,
                $node->text,
            ];
        }

        $ccData = [];

        if (
            (1 != $cardImageData['CCDetected'])
            && isset($ccDetectionResult) && $ccDetectionResult->isDetected()
        ) {
            $ccData = (CardImage::KIND_BACK == $kind) ? $ccDetectionResult->getBack() : $ccDetectionResult->getFront();
        }

        echo "
            <tr id='card-side-{$cardImageData['CardImageID']}' class='card-side'>
                <td>
                    " . (CardImage::KIND_BACK == $kind ? 'Back' : 'Front') . "
                </td>
                <td>
                    <a href='{$cardImageUrl}' target='_blank'>
                        <div
                            class='proxy-img'
                            style=\"
                                width: 320px;
                                height: 180px;
                                background-size: 320px 180px;
                                position: relative;
                            \"
                            data-backgroundimage=\"url('{$cardImageUrl}')\"
                            data-width=\"{$cardImageData['Width']}\"
                            data-height=\"{$cardImageData['Height']}\"
                        >
                            <canvas width='320' height='180' style='position: absolute; top: 0; left: 0' class='cc-highlight' id='canvas-cc-highlight-{$cardImageData['CardImageID']}' data-cc-rects=" . htmlspecialchars(json_encode($ccData)) . ">
                            </canvas>
                            <canvas width='320' height='180' style='position: absolute; top: 0; left: 0' class='node-highlight' id='canvas-node-highlight-{$cardImageData['CardImageID']}'>
                            </canvas>
                        </div>
                    </a>
                </td>
                <td class='matches'>
                    <pre class='tech-font'>" . htmlspecialchars($computerVisionResult['googleVision']['textAnnotations'][0]['description'] ?? '') . "</pre>
                    " . (isset($cardImageData['UploadDate']) ? "<br/><b>Upload date:</b> {$cardImageData['UploadDate']} (UTC)<br/>" : "") . "
                </td>
                <script type='module'>
                    window.showCCRects(document.getElementById('canvas-cc-highlight-{$cardImageData['CardImageID']}'));                            
                </script>
                <td class='matches' style='white-space: nowrap'>" .
                    (
                        $textAnnotations ?
                            "<button type='button' class='slider-down'>-</button>
                            <input type='range' min='0' max='100' value='5' step='1' class='slider'>
                            <button type='button' class='slider-up'>+</button><br/>
                            <b>maxYDev:</b> <span class='slider-value'>5</span><br/>
                            <span class='stat'>&nbsp;</span>
                            <br/>
                            <br/>
                            <div class='tech-font dom' data-text-annotations=\"" . htmlspecialchars(json_encode($textAnnotations)) . "\"></div>
                            <script type='module'>
                                window.initSlider(
                                    document.getElementById('card-side-{$cardImageData['CardImageID']}'),
                                    {$cardImageData['Width']}, 
                                    {$cardImageData['Height']},
                                    5
                                );
                            </script>" : ''
                    ) .
                    "<div class='logo-container' data-logo-annotations=\"" . htmlspecialchars(json_encode($logoAnnotations)) . "\">" .
                    "</div>
                </td>
            </tr>
            ";
    }

    echo "
                </table>";

    if (isset($computerVisionResult['aw_parsing']['result'])) {
        echo "
            <br/>
            <b>Parsed:</b><br/>
            <table class='level3'>
                <tr>
                    <th>
                        Name
                    </th>
                    <th>
                        Value
                    </th>
                </tr>
        ";

        $supportedProperties = $computerVisionResult['aw_parsing']['supported_properties'];
        ksort($supportedProperties);

        foreach ($supportedProperties as $propertyName) {
            echo "
                <tr style='" . ('' === trim($computerVisionResult['aw_parsing']['result'][$propertyName] ?? '') ? "background-color: #ffb3b2" : '') . "'>
                    <td>
                        {$propertyName}
                    </td>
                    <td>
                        <pre class='tech-font'>" . htmlspecialchars($computerVisionResult['aw_parsing']['result'][$propertyName] ?? '<None>') . "</pre>
                    </td>
                </tr>
            ";
        }
        echo "</table>";
    }

    if ($reParseMode) {
        echo "
            <br/>
            <b>Re-Parsed:</b>
            <br/>";

        if (
            ($ccDetectionResult && $ccDetectionResult->isDetected())
            || (1 == $firstImage['CCDetected'])
        ) {
            echo "<div>No properties parsing for credit cards</div>";
        } else {
            echo "
                <table class='level3'>
                    <tr>
                        <th>
                            Name
                        </th>
                        <th>
                            Value
                        </th>
                    </tr>
            ";

            if (
                ($parser = $parserLoader->loadParser($providerCode))
                && ($parser instanceof CardImageParserInterface)
            ) {
                $supportedProperties = ([get_class($parser), 'getSupportedProperties'])();
            } else {
                $supportedProperties = [];
            }

            ksort($supportedProperties);

            foreach ($supportedProperties as $propertyName) {
                echo "
                <tr style='" . ('' === trim($parsedProperties[$propertyName] ?? '') ? "background-color: #ffb3b2" : '') . "'>
                    <td>
                        {$propertyName}
                    </td>
                    <td>
                        <pre class='tech-font'>" . htmlspecialchars($parsedProperties[$propertyName] ?? '<None>') . "</pre>
                    </td>
                </tr>
            ";
            }

            echo "</table>";
        }
    }
    echo "
            </div>
        </td>  
    ";
}

function parseAccountImages(string $providerCode, array $accountBuffer): array
{
    $ccDetectionResult = null;
    $parsedProperties = null;

    if (
        ($recognitionResult = prepareCardRecognitionResult($accountBuffer))
        && ($parser = loadParser($providerCode))
        && ($parser instanceof CreditCardDetectorInterface)
    ) {
        $ccDetectionResult = $parser->detect($recognitionResult);
    }

    if (
        isset($ccDetectionResult)
        && $ccDetectionResult->isDetected()
    ) {
        return [$ccDetectionResult, $parsedProperties];
    }

    if (
        ($recognitionResult = prepareCardRecognitionResult($accountBuffer))
        && ($parser = loadParser($providerCode))
        && ($parser instanceof CardImageParserInterface)
    ) {
        $parsedProperties = $parser->parseImages($recognitionResult);
    }

    return [$ccDetectionResult, $parsedProperties];
}

/**
 * @return CardRecognitionResult|null
 */
function prepareCardRecognitionResult(array $accountBuffer)
{
    $cardImageResultConstructorArgs = [];

    foreach ([CardImage::KIND_FRONT, CardImage::KIND_BACK] as $kind) {
        $cardImageResultConstructorArgs[] = isset($accountBuffer[$kind]) ?
            prepareImageRecognitionResult($accountBuffer[$kind]) :
            null;
    }

    if (!array_filter($cardImageResultConstructorArgs)) {
        return null;
    }

    return new CardRecognitionResult(...$cardImageResultConstructorArgs);
}

function prepareImageRecognitionResult(array $cardImageData)
{
    global $domConverter;

    if (
        ($computerVisionResult = is_array($cardImageData['ComputerVisionResult']) ?
            $cardImageData['ComputerVisionResult'] :
            @json_decode($cardImageData['ComputerVisionResult'], true)
        )
        && isset($computerVisionResult['googleVision'])
        && ($googleResponse = $computerVisionResult['googleVision'])
    ) {
        $width = $cardImageData['Width'];
        $height = $cardImageData['Height'];

        return new ImageRecognitionResult(
            $googleResponse['textAnnotations'][0]['description'] ?? '',
            function (int $maxYDeviation) use ($googleResponse, $domConverter, $width, $height) {
                return $domConverter->convert(
                    $googleResponse['textAnnotations'] ?? [],
                    $googleResponse['logoAnnotations'] ?? [],
                    $width,
                    $height,
                    $maxYDeviation
                );
            }
        );
    } else {
        return null;
    }
}

function getTodayButtonsSpecial()
{
    return "<input type='button' onclick=\"navigateDay(-1)\" value='&lt;'>
	<input type='button' onclick=\"showToday()\" value='Today'>
	<input type='button' onclick=\"navigateDay(1)\" value='&gt;'>";
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
            onclick="var form = document.forms[\'editor_form\']; if( CheckForm( form ) ) { form.submitButton.value=\'parse\'; return true; } else return false;"
        >
            Parse
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
    drawHeader("Card image parsing");

    echo "<h2>Card image parsing</h2><br>";
}

$totals = [];
$objForm->SubmitButtonCaption = "Show stats";
$objForm->SubmitOnce = false;

if (
    ($objForm->IsPost && $objForm->Check())
    || (!empty($_GET) && $objForm->Check($_GET))
) {
    $reParsedProviders = [];
    $objForm->CalcSQLValues();

    foreach ($_POST['providers'] ?? [] as $savedProviderId => $needsRedetect) {
        if ($needsRedetect) {
            $reParsedProviders[] = $savedProviderId;
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
    $parseMode = isset($_POST['submitButton']) && ($_POST['submitButton'] === 'parse');

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


        .slider {
            -webkit-appearance: none;
            width: 300px;
            height: 5px;
            border-radius: 5px;
            background: #d3d3d3;
            outline: none;
            opacity: 0.7;
            -webkit-transition: .2s;
            transition: opacity .2s;
        }

        .slider:hover {
            opacity: 1;
        }

        .slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: #4CAF50;
            cursor: pointer;
        }

        .slider::-moz-range-thumb {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: #4CAF50;
            cursor: pointer;
        }
        </style>
        <script type="module">
            <?php echo file_get_contents(getSymfonyContainer()->getParameter('kernel.root_dir') . '/../web/assets/common/vendors/card-image-parser-js/dist/domOperations.js'); ?>

            window.showChild = function (path, display) {
                if (typeof display === 'undefined') {
                    $('div.tableContainerHidden#' + path).toggle();
                    $('div.tableContainerHidden#' + path + ' input[type=hidden]').each(function () {
                        var newDisplay = $(this).val() === '1' ? '0' : '1';
                        $(this).val(newDisplay);

                        if ('1' === newDisplay) {
                            window.showScrolled(window.getHeight() + 400);
                        }
                    });

                } else {
                    $('div.tableContainerHidden#' + path).toggle(display);
                    $('div.tableContainerHidden#' + path + ' input[type=hidden]').each(function () {
                        $(this).val(display ? '1' : '0');
                    });

                    if (display) {
                        window.showScrolled(window.getHeight() + 400);
                    }
                }
            }

            window.changeAll = function (display) {
                $('div.tableContainerHidden[id]:not([data-detected-provider-id])').each(function () {
                   window.showChild($(this).attr('id'), display);
                });
            }

            $(document).on('ready', function () {
                var rowDock = $('.level1');
                var rows = rowDock.find('.providerRow');
                rows.detach();
                rows
                    .sort(function (a, b) { return $(b).data('unhappiness') - $(a).data('unhappiness'); })
                    .appendTo(rowDock);
            });

            var lastKnownScrollPosition = 0;
            var ticking = false;

            window.showScrolled = function (bottomBoundary) {
                const tableContainers = document.querySelectorAll("div.tableContainerHidden[id][style*='display: block']");

                for (const tableContainer of tableContainers) {
                    const sides = tableContainer.querySelectorAll('.card-side');

                    for (const side of sides) {
                        const elem = side.querySelector('.proxy-img[data-backgroundimage]');

                        if (!elem) {
                            continue;
                        }

                        // isNotVisible
                        if (!(elem.offsetWidth || elem.offsetHeight || elem.getClientRects().length)) {
                            continue;
                        }

                        if (elem.getBoundingClientRect().top > bottomBoundary) {
                            return;
                        }

                        DOMUtils.showImage(
                            elem,
                            side.querySelector('.logo-container'),
                            side.querySelector('canvas.node-highlight'),
                            side.querySelector('.stat'),
                            elem.dataset.width,
                            elem.dataset.height
                        );
                    }
                }
            }

            window.getHeight = function() {
                return window.innerHeight || document.body.clientHeight;
            }

            window.onload = function () {
                window.addEventListener('scroll', function () {
                    lastKnownScrollPosition = window.scrollY;

                    if (!ticking) {
                        window.requestAnimationFrame(function () {
                            window.showScrolled(lastKnownScrollPosition + window.getHeight() + 400);
                            ticking = false;
                        });
                    }

                    ticking = true;
                });

                document.querySelectorAll('.card-side').forEach(cardSide => {
                    const img = cardSide.querySelector('.proxy-img');
                    img.addEventListener('mouseover', e => {
                        DOMUtils.showImage(
                            img,
                            cardSide.querySelector('.logo-container'),
                            cardSide.querySelector('canvas'),
                            cardSide.querySelector('.stat'),
                            img.dataset.width,
                            img.dataset.height
                        )
                    });
                });
            };

            window.initSlider = DOMUtils.initSlider.bind(DOMUtils);
            window.showCCRects = DOMUtils.showCCRects.bind(DOMUtils);
        </script>
    <?php
    reportProviders($objForm, $reParsedProviders);
} else {
    drawTopHeader();
    echo $objForm->HTML();
}

drawFooter();
