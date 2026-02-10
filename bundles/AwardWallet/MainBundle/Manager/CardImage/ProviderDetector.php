<?php

namespace AwardWallet\MainBundle\Manager\CardImage;

use AwardWallet\MainBundle\Entity\CardImage;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Globals\GoogleVision\GoogleVisionResult;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\CardImage\RegexpHandler\RegexpCompilerInterface;
use AwardWallet\MainBundle\Manager\CardImage\RegexpHandler\RegexpHandler;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Psr\Log\LoggerInterface;

class ProviderDetector
{
    public const ALLOWED_STATES = [
        PROVIDER_ENABLED,
        PROVIDER_COLLECTING_ACCOUNTS,
        PROVIDER_CHECKING_OFF,
        PROVIDER_CHECKING_WITH_MAILBOX,
        PROVIDER_CHECKING_EXTENSION_ONLY,
        PROVIDER_RETAIL,
    ];

    public const PROVIDERS_FILTER = ['delta', 'mileageplus', 'rapidrewards'];

    public const LOGO_LOWER_SCORE = 0.25;

    public const PROVIDER_ACCOUNTS_COUNT_SORT_WINDOW = 1000;
    /**
     * @var ProviderRepository
     */
    private $providerRepository;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var RegexpHandler
     */
    private $regexpHandler;
    /**
     * @var RegexpCompilerInterface
     */
    private $regexpCompiler;

    public function __construct(
        RegexpHandler $regexpHandler,
        RegexpCompilerInterface $regexpCompiler,
        ProviderRepository $providerRepository,
        LoggerInterface $logger,
        Connection $connection
    ) {
        $this->regexpHandler = $regexpHandler;
        $this->regexpCompiler = $regexpCompiler;
        $this->providerRepository = $providerRepository;
        $this->logger = $logger;
        $this->connection = $connection;
    }

    /**
     * @return Provider|null
     */
    public function detectByCardImage(CardImage $cardImage, GoogleVisionResult $result)
    {
        if ($detectedProviderData = $this->detectByGoogleVisionResult($result)) {
            [$detectedProvider, $detectedText, $detectDebug] = $detectedProviderData;
            /** @var Provider $detectedProviderEntity */
            $detectedProviderEntity = $this->providerRepository->find($detectedProvider['ProviderID']);
            $cardImage->setDetectedProviderId($detectedProviderEntity);
            $cardImage->updateAwProviderDetect([
                'keywords' => $detectedProviderEntity->getKeywords(),
                'stopKeywords' => $detectedProviderEntity->getStopKeywords(),
                'text' => $detectedText,
                'debug' => $detectDebug,
            ]);

            return $detectedProviderEntity;
        }

        return null;
    }

    /**
     * @return array|null
     */
    public function detectByGoogleVisionResult(GoogleVisionResult $googleVision, ?Statement $providerStmt = null, bool $creditCardsOnly = false)
    {
        $startTime = (int) (microtime(true) * 1000);

        try {
            if (!$providerStmt) {
                $providerStmt = $this->connection->executeQuery(
                    "
                    SELECT   
                        p.ProviderID,
                        p.KeyWords,
                        p.StopKeyWords
                    FROM     Provider p
                    LEFT OUTER JOIN
                        (
                            SELECT 
                                ProviderID, 
                                COUNT(*) AS Votes
                            FROM ProviderVote
                            GROUP BY ProviderID
                        ) pv 
                        ON pv.ProviderID = p.ProviderID
                    WHERE
                        (
                            p.State IN (?) OR 
                            p.Code IN (?)
                        ) AND
                        " . ($creditCardsOnly ? "p.Kind = ?" : "p.Kind <> ?") . "
                    ORDER BY
                        FLOOR(p.Accounts / ?) * ? DESC, 
                        p.State DESC,
                        pv.Votes DESC",
                    [
                        self::ALLOWED_STATES,
                        self::PROVIDERS_FILTER,
                        PROVIDER_KIND_CREDITCARD,
                        self::PROVIDER_ACCOUNTS_COUNT_SORT_WINDOW,
                        self::PROVIDER_ACCOUNTS_COUNT_SORT_WINDOW,
                    ],
                    [
                        Connection::PARAM_INT_ARRAY,
                        Connection::PARAM_STR_ARRAY,
                        \PDO::PARAM_INT,
                        \PDO::PARAM_INT,
                        \PDO::PARAM_INT,
                        \PDO::PARAM_INT,
                    ]
                );
            }

            while ($provider = $providerStmt->fetch()) {
                if (StringUtils::isEmpty($compiledRegexp = $this->regexpCompiler->compile($provider['KeyWords'] ?? ''))) {
                    continue;
                }

                $compiledStopRegexp = $this->regexpCompiler->compile($provider['StopKeyWords'] ?? '');

                $matchResult = $this->regexpHandler->matchText(
                    $googleVision->text ?? '',
                    $compiledRegexp,
                    $compiledStopRegexp
                );

                if ($matchResult->isMatch()) {
                    return [$provider, $googleVision->text, $matchResult->getMatchData()];
                }

                if ($matchResult->isStopMatch()) {
                    continue;
                }

                $logoMatchData = [];

                foreach ($googleVision->logos as $googleVisionLogo) {
                    if ($googleVisionLogo->score < self::LOGO_LOWER_SCORE) {
                        break;
                    }

                    $matchResult = $this->regexpHandler->matchText($googleVisionLogo->text ?? '', $compiledRegexp, $compiledStopRegexp);

                    if ($matchResult->isMatch()) {
                        if (!$logoMatchData) {
                            $logoMatchData = [$provider, $googleVisionLogo->text, $matchResult->getMatchData()];
                        }

                        if (!isset($compiledStopRegexp)) {
                            return $logoMatchData;
                        }
                    }

                    if ($matchResult->isStopMatch()) {
                        continue 2;
                    }
                }

                if ($logoMatchData) {
                    return $logoMatchData;
                }
            }

            $provider = null;

            return null;
        } finally {
            $this->logger->warning('card image provider detect', [
                'timer' => ((int) (microtime(true) * 1000)) - $startTime,
                'success' => isset($provider),
            ]);

            if ($providerStmt) {
                $providerStmt->closeCursor();
            }
        }
    }
}
