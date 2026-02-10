<?php

namespace AwardWallet\MainBundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class EmailTestPageController extends AbstractController
{
    public const DEFAULT_METHOD_PARSE_EMAIL = 'json/v2/parseEmail';
    public const DEFAULT_METHOD_GET_RESULT = 'json/v2/getResults';
    public const TIMEOUT = 90;
    public const DEFAULT_CREDENTIALS = "testemail:testemail";
    public const USER_DATA = '{"source": "test"}';

    private \HttpDriverInterface $driver;

    private LoggerInterface $logger;

    private KernelInterface $kernel;

    private string $login;

    private string $pass;

    private string $emailUrl;

    public function __construct($emailUrl, \HttpDriverInterface $driver, LoggerInterface $logger, KernelInterface $kernel)
    {
        $auth = explode(':', self::DEFAULT_CREDENTIALS, 2) + ['', ''];
        $this->login = $auth[0];
        $this->pass = $auth[1];
        $this->emailUrl = trim($emailUrl, '/') . '/';

        $this->driver = $driver;
        $this->logger = $logger;
        $this->kernel = $kernel;
    }

    /**
     * @Route("/emailTestParse", name="aw_emailTestParse", defaults={"_canonical"="aw_emailTestParse_locale", "_alternate"="aw_emailTestParse_locale"})
     * @Route("/{_locale}/emailTestParse", name="aw_emailTestParse_locale", requirements={"_locale" = "%route_locales%"}, defaults={"_locale"="en", "_canonical"="aw_emailTestParse_locale", "_alternate"="aw_emailTestParse_locale"})
     */
    public function indexAction()
    {
        $data = [
            'login' => $this->login,
            'pass' => $this->pass,
            'files' => $this->getSamples(),
        ];

        return $this->render('@AwardWalletMain/EmailTestPage/index.html.twig', $data);
    }

    /**
     * @Route("/emailTestParse/send", name="aw_emailTestParse_send", methods={"POST"})
     */
    public function sendAction(Request $request)
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('file');

        if ($file === null) {
            $sample = $request->request->get('sample');

            if (empty($sample)) {
                $data = [
                    'status' => 'error',
                    'errorText' => 'Bad request. Empty email',
                ];

                return new JsonResponse($data);
            }
            $sources = $this->getSamples(true);

            if (!isset($sources[$sample])) {
                $data = [
                    'status' => 'error',
                    'errorText' => 'Bad request. Empty sample',
                ];

                return new JsonResponse($data);
            }
            $content = $sources[$sample];
        } else {
            $content = UPLOAD_ERR_OK === $file->getError() ? file_get_contents($file->getRealPath()) : null;
        }

        $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
        $data = [
            'userData' => self::USER_DATA,
            'callbackUrl' => '',
            'email' => $content,
            'wait' => null,
            'returnEmail' => 'none',
        ];
        $contentCut = mb_substr($content, 0, 500);

        if ($contentCut !== $content) {
            $contentCut .= '...';
        }
        $dataCut = [
            'userData' => self::USER_DATA,
            'callbackUrl' => '',
            'email' => $contentCut,
            'returnEmail' => 'none',
        ];

        $status = 'success';
        $errorText = '';
        $response = $this->driver->request(new \HttpDriverRequest(
            $this->emailUrl . self::DEFAULT_METHOD_PARSE_EMAIL,
            'POST',
            json_encode($data, JSON_INVALID_UTF8_IGNORE),
            ['X-Authentication' => $request->request->get('login', '') . ':' . $request->request->get('pass', '')]
        ));

        if ($response->errorCode > 0) {
            $this->logger->critical('Email Test Page returned error', [
                'message' => $response->errorMessage,
            ]);
            $errorText = $response->errorMessage;
            $status = 'error';
        } elseif ($response->httpCode !== 200) {
            $errorText = "request failed. " . (($response->httpCode === 400) ? "" : "HTTP code {$response->httpCode}");
        }

        $result = @json_decode($response->body, true, 512, JSON_INVALID_UTF8_IGNORE);

        $data = [
            'response' => $result,
            'requestIds' => $result,
            'request' => $dataCut,
            'status' => $status,
            'errorText' => $errorText,
        ];

        return new JsonResponse($data);
    }

    /**
     * @Route("/emailTestParse/sendCheck", name="aw_emailTestParse_sendCheck", methods={"POST"})
     */
    public function sendCheckAction(Request $request)
    {
        $ids = $request->request->get('ids');

        if (!isset($ids) || !is_array($ids) || count($ids) == 0) {
            $this->logger->critical('Email Test Page on check request has no ids');

            return new JsonResponse('Something went wrong. Try again later or contact the support service.');
        }
        $start = time();

        while (time() < $start + self::TIMEOUT) {
            foreach ($ids as &$id) {
                $response = $this->driver->request(new \HttpDriverRequest(
                    $this->emailUrl . self::DEFAULT_METHOD_GET_RESULT . '/' . $id,
                    'GET',
                    null,
                    [
                        'X-Authentication' => $request->request->get('login', '') . ':' . $request->request->get('pass', ''),
                    ]
                ));
                $result = @json_decode($response->body, true);

                if ($result['status'] === 'review') {
                    if (count($ids) !== 1) {
                        unset($id);
                    } else {
                        return new JsonResponse($result);
                    }
                } elseif (!in_array($result['status'], ['queued'])) {
                    return new JsonResponse($result);
                }
            }
            sleep(3);
        }

        return new JsonResponse('Timeout. Try again later.');
    }

    private function getSamples(?bool $isSourceData = false): array
    {
        $files = glob($this->kernel->locateResource('@AwardWalletMainBundle/Resources/emailTestPage') . '/*.eml');
        $result = [];

        if ($isSourceData) {
            foreach ($files as $file) {
                $result[preg_replace('/\.eml$/ims', '', basename($file))] = file_get_contents($file);
            }

            return $result;
        }

        foreach ($files as $file) {
            $result[] = preg_replace('/\.eml$/ims', '', basename($file));
        }
        $files = [];

        foreach ($result as $file) {
            if ($file === 'composite') {
                $files[$file] = 'Combined (Flight + Hotel)';
            } elseif ($file === 'rental') {
                $files[$file] = 'Car Rental';
            } elseif ($file === 'reservation') {
                $files[$file] = 'Hotel';
            } else {
                $files[$file] = ucwords($file);
            }
        }
        asort($files);

        return $files;
    }
}
