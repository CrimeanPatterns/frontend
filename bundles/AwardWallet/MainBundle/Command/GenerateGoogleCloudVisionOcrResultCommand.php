<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\Entity\CardImage;
use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Manager\CardImage\CardImageManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateGoogleCloudVisionOcrResultCommand extends Command
{
    public const GOOGLE_API_ROUTE = 'https://vision.googleapis.com/v1/images:annotate?key=%s';

    /**
     * @see https://cloud.google.com/vision/docs/limits
     */
    public const IMAGE_MAX_SIZE = 4 * 1024 * 1024;
    public const REQUEST_MAX_SIZE = 8 * 1024 * 1024 - 1024;
    public const MAX_IMAGES_PER_REQUEST = 1;
    public const MAX_IMAGES_PER_SECOND = 8; // combined with max_requests per second as 1 request = 1 image
    protected static $defaultName = 'aw:generate:google-cloud-vsion:report';

    /**
     * @var OutputInterface
     */
    protected $output;
    /**
     * @var int
     */
    protected $counter;
    /**
     * @var Client
     */
    protected $client;
    /**
     * @var string
     */
    protected $googleApiKey;
    private Connection $connection;
    private EntityManagerInterface $entityManager;
    private CardImageManager $cardImageManager;

    public function __construct(
        Connection $connection,
        EntityManagerInterface $entityManager,
        CardImageManager $cardImageManager,
        $googleApiKey
    ) {
        parent::__construct();
        $this->connection = $connection;
        $this->entityManager = $entityManager;
        $this->cardImageManager = $cardImageManager;
        $this->googleApiKey = $googleApiKey;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        /** @var CardImage[][][] $cardimagesByProvider */
        $cardimagesByProvider = [];
        $stmt = $this->connection->executeQuery('
                select
                    ci.CardImageID,
                    ci.UserID,
                    coalesce(ci.AccountID, sa.AccountID) as AccountID,
                    ci.SubAccountID,
                    ci.ProviderCouponID,
                    ci.Kind,
                    ci.Width,
                    ci.Height,
                    ci.FileName,
                    ci.FileSize,
                    ci.Format,
                    ci.StorageKey,
                    ci.UploadDate,
                    a.ProviderID,
                    coalesce(a.ProgramName, pc.ProgramName) as LoyaltyProgramName,
                    coalesce(p.Kind, a.Kind, pc.Kind) as LoyaltyKind
                from CardImage ci
                    left join ProviderCoupon pc on pc.ProviderCouponID = ci.ProviderCouponID
                    left join SubAccount sa on ci.SubAccountID = sa.SubAccountID
                    left join Account a on a.AccountID = coalesce(ci.AccountID, sa.AccountID)
                    left join Provider p on a.ProviderID = p.ProviderID
                where 
                    coalesce(p.Kind, a.Kind, pc.Kind) <> ' . PROVIDER_KIND_CREDITCARD . ' 
                order by rand()
                limit 100;
            ');
        $cardImageRep = $this->entityManager->getRepository(CardImage::class);
        $cardImageManager = $this->cardImageManager;

        while ($cardImageRow = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (
                isset($cardImageRow['ProviderID'])
                && (PROVIDER_KIND_CREDITCARD !== (int) $cardImageRow['SubAccountID'])
                && ($cardImage = $cardImageRep->find($cardImageRow['CardImageID']))
            ) {
                $cardimagesByProvider[$cardImageRow['ProviderID']][$cardImageRow['AccountID'] . "-" . (int) $cardImageRow['SubAccountID']][$cardImageRow['CardImageID']] = $cardImage;
            }
        }

        $this->client = new Client();
        $requestsPerSecond = 0;
        /** @var Task[] $batch */
        $batch = [];
        $batchTotalSize = 0;
        $requests = [];
        $requestTemplate = [
            "features" => [
                [
                    "type" => "LOGO_DETECTION",
                ],
                [
                    "type" => "TEXT_DETECTION",
                ],
            ],
            "image" => [
                "content" => null,
            ],
        ];
        $this->counter = 0;

        foreach ($cardimagesByProvider as $provider => $loyaltyContainers) {
            foreach ($loyaltyContainers as $loyaltyContainerId => $loyaltyContainer) {
                foreach ($loyaltyContainer as $cardImageId => $cardImage) {
                    $content = $this->cardImageManager->getImageContent($cardImage);
                    $bigImage = imagecreatefromstring($content);
                    $scaledImage = imagescale($bigImage, 0.25 * $cardImage->getWidth(), 0.25 * $cardImage->getHeight());
                    ob_start();
                    imagejpeg($scaledImage);
                    $content = ob_get_contents();
                    ob_end_clean();

                    if (null === $content) {
                        continue;
                    }

                    if (
                        isset($tick)
                        && $requestsPerSecond >= self::MAX_IMAGES_PER_SECOND
                    ) {
                        $current = (int) (microtime(true) * (10 ** 3));

                        if ($current < $tick + 10 ** 3) {
                            usleep(($tick + (10 ** 3) - $current) * 10000);
                        }

                        $requestsPerSecond = 0;
                    }

                    $encodedImage = base64_encode($content);
                    $request = $requestTemplate;
                    $request['image']['content'] = $encodedImage;
                    $requests[] = $request;
                    $requestsPerSecond++;
                    $batchTotalSize += strlen($encodedImage);

                    if (
                        (count($requests) === self::MAX_IMAGES_PER_REQUEST)
                        || ($requestsPerSecond === self::MAX_IMAGES_PER_SECOND)
                    ) {
                        $tick = (int) (microtime(true) * (10 ** 3));
                        $this->processBatch($requests);
                        $output->writeln('Size: ' . strlen($content) . ' bytes, time: ' . (((int) (microtime(true) * (10 ** 3))) - $tick) . ' ms');
                        $requests = [];
                        $batchTotalSize = 0;
                    }
                }
            }
        }

        return 0;
    }

    protected function processBatch(array $requests)
    {
        if (!$requests) {
            return;
        }

        $response = $this->client->post(
            sprintf(self::GOOGLE_API_ROUTE, $this->googleApiKey),
            ['json' => [
                'requests' => $requests,
            ]]
        );
        $responseJson = @json_decode((string) $response->getBody(), true);

        if (
            (200 === $response->getStatusCode())
            && is_array($responseJson = @json_decode((string) $response->getBody(), true))
            && isset($responseJson['responses'])
        ) {
            foreach ($responseJson['responses'] as $idx => $visionResponse) {
                if (
                    !isset($visionResponse['logoAnnotations'])
                    && !isset($visionResponse['textAnnotations'])
                    && !isset($visionResponse['webDetection'])
                ) {
                    throw new \RuntimeException('unknown response format: ' . json_encode(['response' => $visionResponse], JSON_PRETTY_PRINT));
                }
            }
        } else {
            throw new \RuntimeException('unknown response format: ' . json_encode($responseJson, JSON_PRETTY_PRINT));
        }
    }
}
