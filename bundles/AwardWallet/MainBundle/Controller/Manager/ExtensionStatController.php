<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Loyalty\ApiCommunicatorException;
use Aws\S3\S3Client;
use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/manager/extension/stat")
 */
class ExtensionStatController extends AbstractController
{
    private ConnectionInterface $connection;
    private S3Client $s3Client;
    private ApiCommunicator $communicator;

    public function __construct(ConnectionInterface $connection, S3Client $s3Client, ApiCommunicator $communicator)
    {
        $this->connection = $connection;
        $this->s3Client = $s3Client;
        $this->communicator = $communicator;
    }

    /**
     * @Security("is_granted('ROLE_MANAGE_EXTENSIONSTAT')")
     * @Route("/details", name="aw_manager_extension_stat_details")
     */
    public function indexAction(Request $request)
    {
        // $this->logger = $this->get('logger');
        $id = (int) $request->query->get('providerId');
        $code = (int) $request->query->get('errorCode');
        $msg = (string) $request->query->get('msg');
        $platform = (string) $request->query->get('platform');
        $sql = "
SELECT a.UserID, es.AccountID, es.ErrorDate, p.Code
FROM ExtensionStat es, Provider p, Account a
WHERE
    es.ProviderID = p.ProviderID
    AND es.ProviderID=?
    AND es.ErrorCode=?
    AND es.Platform = ?
    AND (p.State >= " . PROVIDER_ENABLED . " OR p.State = " . PROVIDER_TEST . ")
    AND es.ErrorText=?
    AND es.AccountID=a.AccountID
ORDER BY es.ErrorDate DESC limit 10
";

        $result = $this->connection->executeQuery($sql, [$id, $code, $platform, $msg], [\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR]);
        $data = $result->fetchAll();
        $providerCode = null;

        foreach ($data as $i => $datum) {
            $data[$i]['log'] = $this->getLog($datum['Code'], $datum['AccountID'], strtotime($datum['ErrorDate']));

            if (empty($providerCode)) {
                $providerCode = $datum['Code'];
            }
        }

        if (empty($providerCode)) {
            $providerCode = $this->connection->executeQuery("select Code from Provider where ProviderID=?", [$id], [\PDO::PARAM_INT])->fetchColumn();
        }

        return $this->render('@AwardWalletMain/Manager/ExtensionStatDetails/index.html.twig', [
            'accounts' => $data,
            'errorMessage' => $msg,
            'providerCode' => $providerCode,
        ]);
    }

    private function getLog($providerCode, $accountId, int $approximateTime)
    {
        $files = [];

        // Logs from loyalty
        try {
            $response = $this->getLoyaltyCheckerLogs('awardwallet', $accountId, $providerCode);

            foreach ($response->getFiles() as $file) {
                $files[] = [
                    'date' => $this->prettyDate($file->getUpdatedate()),
                    'filename' => $file->getFilename(),
                    'bucket' => $response->getBucket(),
                ];
            }

            $extLogsFiles = $this->getExtensionAccountLogsFromS3($accountId);

            foreach ($extLogsFiles as $file) {
                $files[] = [
                    'date' => $this->prettyDate($file['LastModified']),
                    'filename' => $file['Key'],
                    'bucket' => 'awardwallet-logs',
                ];
            }

            usort($files, function ($log1, $log2) {
                return strtotime($log2["date"]) - strtotime($log1["date"]);
            });
        } catch (ApiCommunicatorException $e) {
            $files[] = [
                'date' => date('Y-m-d H:i:s'),
                'filename' => 'bla-bla.test',
                'bucket' => 'awardwallet-logs',
            ];
        }

        $log = [];

        if ((count($files) === 1) && (abs(strtotime($files[0]["date"]) - $approximateTime) < 180)) {
            $log[] = array_shift($files);
        } elseif (count($files) > 1) {
            foreach ($files as $file) {
                if (abs(strtotime($file["date"]) - $approximateTime) < 10) {
                    $log[] = $file;
                }
            }

            if (empty($log)) {
                foreach ($files as $file) {
                    if (abs(strtotime($file["date"]) - $approximateTime) < 180) {
                        $log[] = $file;
                    }
                }
            }
        }

        return (1 === count($log)) ? $log[0] : [];
    }

    private function prettyDate($date)
    {
        return str_replace(["T", "+00:00", ".000Z"], [" ", "", ""], $date);
    }

    private function getExtensionAccountLogsFromS3($accountId)
    {
        $iterator = $this->s3Client->getIterator('ListObjects',
            ['Bucket' => 'awardwallet-logs', 'Prefix' => "account-{$accountId}-"]);

        $result = [];

        foreach ($iterator as $object) {
            $result[] = $object;
        }

        return $result;
    }

    private function getLoyaltyCheckerLogs(
        $partner,
        $accountId,
        $providerCode = null,
        $login = null,
        $login2 = null,
        $login3 = null
    ) {
        $request = new \AwardWallet\MainBundle\Loyalty\Resources\AdminLogsRequest([
            'userData' => $accountId,
            'partner' => $partner,
            'provider' => $providerCode,
            'login' => $login,
            'login2' => $login2,
            'login3' => $login3,
            'method' => 'CheckAccount',
        ]);

        /** @var \AwardWallet\MainBundle\Loyalty\Resources\AdminLogsResponse $response */
        $responseAccount = $this->communicator->GetCheckerLogs($request);
        /** @var \AwardWallet\MainBundle\Loyalty\Resources\AdminLogsResponse $response */
        $responseConfirmation = $this->communicator->GetCheckerLogs($request->setMethod('CheckConfirmation'));

        $allFiles = array_merge(
            is_array($responseAccount->getFiles()) ? $responseAccount->getFiles() : [],
            is_array($responseConfirmation->getFiles()) ? $responseConfirmation->getFiles() : []
        );
        $responseAccount->setFiles($allFiles);

        return $responseAccount;
    }
}
