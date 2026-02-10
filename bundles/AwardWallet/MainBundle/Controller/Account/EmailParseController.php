<?php

namespace AwardWallet\MainBundle\Controller\Account;

use AwardWallet\MainBundle\Email\Api;
use AwardWallet\MainBundle\Email\ApiException;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Loyalty\EmailApiHistoryParser;
use AwardWallet\MainBundle\Service\SocksMessaging\Client as SocksClient;
use phpcent\Client as PhpcentClient;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class EmailParseController extends AbstractController
{
    public const CHANNEL_TEMPLATE = 'AccountEmailParse_%d_%s';

    private LoggerInterface $logger;
    private RouterInterface $router;
    private Api $emailApi;
    private string $emailApiAuth;
    private PhpcentClient $client;

    public function __construct(
        LoggerInterface $logger,
        RouterInterface $router,
        Api $emailApi,
        string $emailApiAuth,
        string $url,
        string $secret
    ) {
        $this->logger = $logger;
        $this->router = $router;
        $this->emailApi = $emailApi;
        $this->emailApiAuth = $emailApiAuth;
        $this->client = new PhpcentClient($url);
        $this->client->setSecret($secret);
    }

    /**
     * @Route("/email-parse/{accountId}", methods={"POST"}, name="aw_account_email_parse", options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id"="accountId"})
     * @return JsonResponse
     * @throws ApiException
     */
    public function emailParseAction(Request $request, Account $account, SocksClient $socksClient)
    {
        $user = $this->getUser();
        /** @var UploadedFile $file */
        $file = $request->files->get('content');
        $content = UPLOAD_ERR_OK === $file->getError() ? file_get_contents($file->getRealPath()) : null;

        if (empty($content)) {
            return new JsonResponse(['success' => false]);
        }

        $emailLogin = $user->getLogin()
            . (empty($account->getLogin()) ? '' : '.' . str_replace('@', '_--_', $account->getLogin()));

        /** @var \Swift_Message $email */
        $email = (new \Swift_Message())
            ->setSubject('email parse')
            ->setFrom($emailLogin . '@awardwallet.com')
            ->setTo('awardwallet@awardwallet.com')
            ->setBody($content, 'text/html');

        $postData = [
            'userData' => json_encode([
                'accountId' => $account->getAccountid(),
                'accountBalance' => $account->getBalance(),
                'notifyToChannel' => true,
            ]),
            'callbackUrl' => $this->router->generate('aw_emailcallback_save', [], UrlGenerator::ABSOLUTE_URL),
            'email' => $email->toString(),
            'returnEmail' => 'all',
        ];

        try {
            $response = $this->emailApi->call(
                EmailApiHistoryParser::METHOD_PARSE_EMAIL,
                true,
                $postData,
                ['userId' => $user->getUserid()],
                false,
                null,
                ['X-Authentication: ' . $this->emailApiAuth]
            );
        } catch (ApiException $e) {
            $this->logger->critical('Email API returned an exception', [
                'message' => $e->getMessage(),
                'account' => $account->getAccountid(),
            ]);

            return new JsonResponse(['success' => false, 'status' => 'error']);
        }

        if ($response && 'queued' !== $response['status']) {
            $this->logger->critical('Email API returned status: ' . $response['status'], [
                'message' => $response,
                'account' => $account->getAccountid(),
            ]);

            return new JsonResponse(['success' => false, 'status' => 'error']);
        }

        $requestId = $response['requestIds'][0];
        $accountRequestKey = sprintf(self::CHANNEL_TEMPLATE, $account->getAccountid(), $requestId);
        $socksClient->publish($accountRequestKey, [
            'status' => null,
        ]);

        return new JsonResponse([
            'success' => true,
            'requestId' => $requestId,
            'centrifugeOptions' => [
                'url' => $this->client->getHost(),
                'authEndpoint' => $this->router->generate('aw_socks_auth', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'user' => (string) $user->getUserid(),
                'timestamp' => (string) $timestamp = time(),
                'token' => $this->client->generateClientToken($user->getUserid(), $timestamp),
            ],
        ]);
    }
}
