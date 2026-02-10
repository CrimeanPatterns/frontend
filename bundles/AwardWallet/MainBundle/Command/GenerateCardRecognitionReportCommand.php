<?php

namespace AwardWallet\MainBundle\Command
{
    use AwardWallet\MainBundle\Command\CardRecognitionReport\AccountDetectResult;
    use AwardWallet\MainBundle\Command\CardRecognitionReport\CardImage;
    use AwardWallet\MainBundle\Command\CardRecognitionReport\OcrResult;
    use AwardWallet\MainBundle\Command\CardRecognitionReport\ProviderDetectResult;
    use AwardWallet\MainBundle\Entity\Provider;
    use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
    use AwardWallet\MainBundle\FrameworkExtension\Command;
    use AwardWallet\MainBundle\Globals\StringUtils;
    use Doctrine\DBAL\Connection;
    use Doctrine\ORM\EntityManagerInterface;
    use Symfony\Component\Console\Input\InputArgument;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Output\OutputInterface;
    use Symfony\Component\Finder\Finder;
    use Symfony\Component\Finder\SplFileInfo;

    class GenerateCardRecognitionReportCommand extends Command
    {
        protected static $defaultName = 'aw:card-recognition:report';

        /**
         * @var string
         */
        protected $htmlOutputDir;
        private EntityManagerInterface $entityManager;

        public function __construct(
            EntityManagerInterface $entityManager
        ) {
            parent::__construct();
            $this->entityManager = $entityManager;
        }

        protected function configure()
        {
            $this
                ->addArgument('cardimages-json', InputArgument::REQUIRED, 'json(json_condensed) from db')
                ->addArgument('working-dir', InputArgument::REQUIRED, 'dir containing ocr_output/, img_*/ dirs')
                ->addOption('max-top', 't', InputOption::VALUE_REQUIRED, 'provider should exists in top N results', 1)
                ->addOption('providers', 'p', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'provider scope');
        }

        protected function execute(InputInterface $input, OutputInterface $output): int
        {
            /** @var ProviderRepository $repository */
            $repository = $this->entityManager->getRepository(Provider::class);
            /** @var Connection $connection */
            $connection = $this->entityManager->getConnection();
            /** @var CardImage[] $cardimages */
            //        $cardimages = [];
            $cardimagesByProvider = [];
            $maxTop = (int) $input->getOption('max-top');
            $workingDir = $input->getArgument('working-dir');
            $json = json_decode(file_get_contents($input->getArgument('cardimages-json')), true);

            $this->htmlOutputDir = "{$workingDir}/html_output";
            @mkdir("{$this->htmlOutputDir}/img", 0777, true);

            foreach (array_slice($json, isset($json[0][0]) ? 1 : 0) as $cardimageTmpArray) {
                $cardImage = new CardImage(...array_values($cardimageTmpArray));

                if (
                    isset($cardImage->ProviderID)
                    && (PROVIDER_KIND_CREDITCARD !== (int) $cardImage->LoyaltyKind)
                ) {
                    $cardimagesByProvider[$cardImage->ProviderID][$cardImage->AccountID][$cardImage->CardImageID] = $cardImage;
                }
            }

            uksort(
                $cardimagesByProvider,
                function ($providerIdA, $providerIdB) use ($cardimagesByProvider) {
                    return count($cardimagesByProvider[$providerIdB]) - count($cardimagesByProvider[$providerIdA]);
                }
            );

            $providerScope = $input->getOption('providers');
            $filterStmt = null;

            if ($providerScope) {
                $cardimagesByProvider = array_filter(
                    $cardimagesByProvider,
                    function ($v, $k) use ($providerScope) {
                        return in_array($k, $providerScope);
                    },
                    ARRAY_FILTER_USE_BOTH
                );
            }

            foreach ($cardimagesByProvider as $providerId => $accounts) {
                $processedCount = 0;
                /** @var ProviderDetectResult[] $providerDetectResultsByOcrParams */
                $providerDetectResultsByOcrParams = [];

                foreach ($accounts as $accountId => $cardImages) {
                    /** @var AccountDetectResult[] $accountDetectResultsByOcrParams */
                    $accountDetectResultsByOcrParams = [];

                    /** @var CardImage $cardImage */
                    foreach ($cardImages as $cardImageId => $cardImage) {
                        $filesIterator = (new Finder())
                            ->files()
                            ->in("{$workingDir}/google_output")
//                            ->name("{$cardImageId}-source-downscale50.json")
                            ->name("{$cardImageId}-source-downscale25.json");
                        //                            ->name("{$cardImageId}-source-orig.json");

                        /** @var SplFileInfo $splFileInfo */
                        foreach ($filesIterator as $splFileInfo) {
                            if (!preg_match('/^(?<cardImageId>[\d]+)-(?<ocrParams>source-(?<source>[^\.]+))\.json$/ims', $splFileInfo->getFilename(), $matches)) {
                                throw new \RuntimeException("unknown filename pattern '" . $splFileInfo->getFilename() . "'");
                            }

                            $ocrResult = new OcrResult(
                                "{$workingDir}/img_{$matches['source']}/{$cardImage->StorageKey}",
                                $splFileInfo->getPathname()
                            );

                            if (!isset($accountDetectResultsByOcrParams[$matches['ocrParams']])) {
                                $accountDetectResultsByOcrParams[$matches['ocrParams']] = new AccountDetectResult([$ocrResult], false);
                            } else {
                                $accountDetectResultsByOcrParams[$matches['ocrParams']]->ocrResults[] = $ocrResult;
                            }

                            //                            $mixedParams = "source-orig+grayscale";
                            //
                            //                            if (!isset($accountDetectResultsByOcrParams[$mixedParams])) {
                            //                                $accountDetectResultsByOcrParams[$mixedParams] = new AccountDetectResult([$ocrResult], false);
                            //                            } else {
                            //                                $accountDetectResultsByOcrParams[$mixedParams]->ocrResults[] = $ocrResult;
                            //                            }
                        }
                    }

                    foreach ($accountDetectResultsByOcrParams as $ocrParams => $accountDetectResult) {
                        $ocrContent = '';

                        foreach ($accountDetectResult->ocrResults as $ocrResult) {
                            $googleOcrResult = @json_decode(file_get_contents($ocrResult->ocrFile), true);

                            if (!is_array($googleOcrResult)) {
                                throw new \RuntimeException("invalid json data {$ocrResult->ocrFile}");
                            }

                            $ocrContent .= ' ' . $this->googleResultToText($googleOcrResult);
                        }

                        if (!isset($providerDetectResultsByOcrParams[$ocrParams])) {
                            $providerDetectResultsByOcrParams[$ocrParams] = new ProviderDetectResult();
                        }

                        if (!StringUtils::isEmpty(trim($ocrContent))) {
                            $searchResults = array_slice($repository->searchProviderByText($ocrContent, null, null, $maxTop), 0, $maxTop);

                            if (++$processedCount % 50 === 0) {
                                $output->writeln("{$processedCount} searches performed for provider {$providerId}");
                            }

                            $accountDetectResult->suggestions = array_map(
                                function ($searchResult) {
                                    return $searchResult['Code'];
                                },
                                $searchResults
                            );

                            foreach ($searchResults as $searchResult) {
                                if ($searchResult['ProviderID'] === (string) $providerId) {
                                    $accountDetectResult->matched = true;

                                    break;
                                }
                            }
                        }

                        if ($accountDetectResult->matched) {
                            $providerDetectResultsByOcrParams[$ocrParams]->successResults[] = $accountDetectResult;
                        } else {
                            $providerDetectResultsByOcrParams[$ocrParams]->failResults[] = $accountDetectResult;
                        }
                    }
                }

                foreach ($providerDetectResultsByOcrParams as $ocrParams => $providerDetectResult) {
                    $providerDetectResult->successRate =
                        round(
                            count($providerDetectResult->successResults) /
                            (
                                count($providerDetectResult->successResults) +
                                count($providerDetectResult->failResults)
                            ) *
                            100,
                            2
                        );
                }

                uasort(
                    $providerDetectResultsByOcrParams,
                    function ($a, $b) {
                        return $b->successRate - $a->successRate;
                    }
                );
                $htmlTable = '
                <table border="1">
                    <thead>
                        <tr>
                            <td>ocr params</td>
                            <td>sucess rate (%)</td>
                            <td>examples</td>
                        </tr>
                    </thead>
                    <tbody>
            ';

                foreach ($providerDetectResultsByOcrParams as $ocrParams => $providerDetectResult) {
                    $sucessCount = count($providerDetectResult->successResults);
                    $totalCount = count($providerDetectResult->successResults) + count($providerDetectResult->failResults);
                    $successRate = "{$providerDetectResult->successRate} % ({$sucessCount}/{$totalCount})";

                    $successHtml = $this->getSamplesHtml($providerDetectResult->successResults, 3);
                    $failHtml = $this->getSamplesHtml($providerDetectResult->failResults, 3);

                    $htmlTable .= "
                    <tr>
                        <td valign='top'>{$ocrParams}</td>
                        <td valign='top'>{$successRate}</td>
                        <td valign='top'>
                            <h3>Success samples:</h3>
                            {$successHtml}
                            <br/>
                            <h3>Fail samples:</h3>
                            {$failHtml}
                        </td>
                    </tr>
                ";
                }

                $htmlTable .= '
                </tbody>
            </table>';

                /** @var Provider $provider */
                $provider = $repository->find($providerId);
                $displayName = $provider->getDisplayname();
                file_put_contents(
                    $providerOutputFile = "{$this->htmlOutputDir}/provider_" . $provider->getCode() . ".html",
                    "<html>
                    <head>
                        <title>{$displayName}Google Vision results</title>
                    </head>
                    <body>
                        <h1>{$displayName}Google Vision results</h1>
                        {$htmlTable}
                    </body>
                </html>
                "
                );
            }

            return 0;
        }

        protected function googleResultToText(array $googleOcrResult, $implodeLines = true)
        {
            $ocrContent = '';

            if (isset($googleOcrResult['logoAnnotations'][0]['description'])) {
                $ocrContent .= $googleOcrResult['logoAnnotations'][0]['description'] . "\n\n";
            }

            if (isset($googleOcrResult['webDetection']['pagesWithMatchingImages'][0]['url'])) {
                $ocrContent .= $googleOcrResult['webDetection']['pagesWithMatchingImages'][0]['url'] . "\n\n";
            }

            if (isset($googleOcrResult['webDetection']['fullMatchingImages'][0]['url'])) {
                $ocrContent .= $googleOcrResult['webDetection']['fullMatchingImages'][0]['url'] . "\n\n";
            }

            //            if (isset($googleOcrResult['textAnnotations'][0]['description'])) {
            //                $ocrContent .= $googleOcrResult['textAnnotations'][0]['description'] . "\n\n";
            //            }
            //
            //            if (isset($googleOcrResult['webDetection']['webEntities'][0]['description'])) {
            //                $ocrContent .= $googleOcrResult['webDetection']['webEntities'][0]['description'] . "\n\n";
            //            }

            return trim(
                $implodeLines ?
                    str_replace("\n", ' ', $ocrContent) :
                    $ocrContent
            );
        }

        /**
         * @param AccountDetectResult[] $samplesSource
         * @param int $size
         * @return string
         */
        protected function getSamplesHtml(array $samplesSource, $size)
        {
            //            shuffle($samplesSource);
            //            $samples     = array_slice($samplesSource, 0, $size);
            usort($samplesSource, function ($a, $b) { return strcmp($a->ocrResults[0]->imageFile, $b->ocrResults[0]->imageFile); });
            $samplesSource = array_slice($samplesSource, 0, 50);
            $samplesHtml = [];
            $samples = $samplesSource;

            /** @var AccountDetectResult $accountDetectResult */
            foreach ($samples as $sampleIdx => $accountDetectResult) {
                $detected = implode(', ', $accountDetectResult->suggestions);

                $sampleHtml = "
                Account sample {$sampleIdx}:<br/>
                Detected: {$detected}<br/>
                <table border='1'>
                <thead>
                    <tr>
                        <td>image</td>
                        <td>text</td>
                    </tr>
                </thead>
                <tbody>
            ";

                foreach ($accountDetectResult->ocrResults as $ocrResult) {
                    $googleOcrResult = @json_decode(file_get_contents($ocrResult->ocrFile), true);

                    if (!is_array($googleOcrResult)) {
                        throw new \RuntimeException("invalid json data {$ocrResult->ocrFile}");
                    }

                    $textContent = $this->googleResultToText($googleOcrResult, false);
                    $imageRelativePath = 'img/' . implode('_', array_slice(explode('/', $ocrResult->imageFile), -2, 2));
                    copy($ocrResult->imageFile, "{$this->htmlOutputDir}/{$imageRelativePath}");
                    $sampleHtml .= "
                    <tr>
                        <td valign='top'>
                            <a href='{$imageRelativePath}' target='_blank'>
                                <img 
                                    style='max-width:300px; height:auto;'
                                    src='{$imageRelativePath}'
                                />
                            </a>
                        </td>                        
                        <td valign='top'>
                            <textarea rows='18' cols='80'>{$textContent}</textarea>
                        </td>                        
                    </tr>
                ";
                }

                $sampleHtml .= '
                </tbody>
            </table>';

                $samplesHtml[] = $sampleHtml;
            }

            return implode('<br/><br/>', $samplesHtml);
        }
    }
}

namespace AwardWallet\MainBundle\Command\CardRecognitionReport
{
    class CardImage
    {
        public $CardImageID;
        public $UserID;
        public $AccountID;
        public $SubAccountID;
        public $ProviderCouponID;
        public $Kind;
        public $Width;
        public $Height;
        public $FileName;
        public $FileSize;
        public $Format;
        public $StorageKey;
        public $UploadDate;
        public $ProviderID;
        public $LoyaltyProgramName;
        public $LoyaltyKind;

        public function __construct($CardImageID, $UserID, $AccountID, $SubAccountID, $ProviderCouponID, $Kind, $Width, $Height, $FileName, $FileSize, $Format, $StorageKey, $UploadDate, $ProviderID, $LoyaltyProgramName, $LoyaltyKind)
        {
            $this->CardImageID = $CardImageID;
            $this->UserID = $UserID;
            $this->AccountID = $AccountID;
            $this->SubAccountID = $SubAccountID;
            $this->ProviderCouponID = $ProviderCouponID;
            $this->Kind = $Kind;
            $this->Width = $Width;
            $this->Height = $Height;
            $this->FileName = $FileName;
            $this->FileSize = $FileSize;
            $this->Format = $Format;
            $this->StorageKey = $StorageKey;
            $this->UploadDate = $UploadDate;
            $this->ProviderID = $ProviderID;
            $this->LoyaltyProgramName = $LoyaltyProgramName;
            $this->LoyaltyKind = $LoyaltyKind;
        }
    }

    class OcrResult
    {
        /**
         * @var string
         */
        public $imageFile;
        /**
         * @var string
         */
        public $ocrFile;

        /**
         * OcrResult constructor.
         *
         * @param string $imageFile
         * @param string $ocrFile
         */
        public function __construct($imageFile, $ocrFile)
        {
            $this->imageFile = $imageFile;
            $this->ocrFile = $ocrFile;
        }
    }

    class AccountDetectResult
    {
        /**
         * @var OcrResult[]
         */
        public $ocrResults = [];
        /**
         * @var bool
         */
        public $matched = false;
        /**
         * @var array
         */
        public $suggestions = [];

        /**
         * DetectResult constructor.
         *
         * @param OcrResult[] $ocrResults
         * @param bool $matched
         */
        public function __construct(array $ocrResults, $matched)
        {
            $this->ocrResults = $ocrResults;
            $this->matched = $matched;
        }
    }

    class ProviderDetectResult
    {
        /**
         * @var AccountDetectResult[]
         */
        public $failResults = [];
        /**
         * @var AccountDetectResult[]
         */
        public $successResults = [];
        /**
         * Percentage.
         *
         * @var float
         */
        public $successRate = 0;
    }
}
