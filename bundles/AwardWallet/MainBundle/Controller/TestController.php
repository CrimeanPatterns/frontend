<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    private LoggerInterface $logger;
    private RequestStack $requestStack;
    private Connection $connection;

    public function __construct(LoggerInterface $logger, RequestStack $requestStack, Connection $connection)
    {
        $this->logger = $logger;
        $this->requestStack = $requestStack;
        $this->connection = $connection;
    }

    /**
     * Functional tests info app/tests/functional/LockoutCest.php.
     *
     * @Route("/test/client-info", name="aw_test_client_info")
     * @return JsonResponse
     */
    public function testClientInfoAction()
    {
        $request = $this->requestStack->getCurrentRequest();

        return new JsonResponse([
            'host_ip' => gethostbyname($request->getHost()),
            'client_ip' => $request->getClientIp(),
        ]);
    }

    /**
     * @Route("/test/centrifugal", name="aw_test_centrifugal")
     * @Security("is_granted('ROLE_USER')")
     * @Template("@AwardWalletMain/Test/testCentrifugal.html.twig")
     */
    public function testCentrifugalAction(AwTokenStorageInterface $tokenStorage)
    {
        return [
            'userId' => $tokenStorage->getToken()->getUser()->getUserid(),
            'time' => time(),
        ];
    }

    /**
     * @Route("/test/dietrace", name="aw_test_dietrace")
     * @Security("is_granted('ROLE_STAFF')")
     */
    public function testDieTraceAction()
    {
        $this->triggerDieTrace("123321", "Hello");
    }

    /**
     * @Route("/test/error_reporting", name="aw_test_error_reporting")
     * @Security("is_granted('ROLE_STAFF')")
     */
    public function testErrorReporting(Request $request)
    {
        $this->logger->pushProcessor(function (array $record) {
            $record['context']['testcontext'] = "jessica";

            return $record;
        });
        $this->logger->info("test info log", ["logcontext" => "123"]);

        switch ($request->query->get("case")) {
            case 'undefinedIndex':
                $____someArray = [];
                $res = $____someArray['undefindex'];

                break;

            case 'undefinedVar':
                $res = $undefvar;

                break;

            case 'undefinedFunction':
                $random = $request->query->get("random");

                if (!preg_match('#^\w{20}$#ims', $random) || function_exists('someMissingFunction' . $random)) {
                    throw new \Exception("Invalid request: $random");
                }
                $func = "someMissingFunction{$random}";
                $res = $func();

                break;

            case 'outOfMemory':
                $a = str_repeat("x", PHP_INT_MAX);

                break;

            case 'supressed':
                $____someArray = [];
                $res = @$____someArray['supindex'];

                $res = @$supvar;

                break;

            case 'exception':
                throw new \RuntimeException("Some test exception");

            case 'critical-exception':
                try {
                    $a = ['a' => 1];
                    $b = $a['b'];
                } catch (\Exception $e) {
                    $this->logger->critical('critical-exception', ['contextOne' => 'one', 'exception' => $e]);
                }

                break;

            case 'database':
                $random = $request->query->get("random");

                if (!\preg_match('/^[a-z0-9]{10}$/i', $random)) {
                    throw new \RuntimeException('Invalid random');
                }

                $this->connection->executeQuery("
                    select * 
                    from SomeTable{$random} str
                    where
                        str.Field1 = ? AND
                        str.Field2 = ? AND
                        str.Field3 = ? AND
                        str.Field4 = ?",
                    [
                        $random . 'Field1Value',
                        $random . 'Field2Value',
                        $random . 'Field3Value',
                        $random . 'Field4Value',
                    ]
                );

                break;
        }

        return new JsonResponse("No case specified");
    }

    private function triggerDieTrace($accountId, $message)
    {
        DieTrace("Some trace from controller");
    }
}
