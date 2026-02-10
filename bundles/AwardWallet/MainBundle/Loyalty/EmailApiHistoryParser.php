<?php

namespace AwardWallet\MainBundle\Loyalty;

use AwardWallet\Common\API\Email\V2\Loyalty\HistoryField as V2HistoryField;
use AwardWallet\Common\API\Email\V2\Loyalty\HistoryRow as V2HistoryRow;
use AwardWallet\Common\API\Email\V2\Loyalty\Property;
use AwardWallet\Common\API\Email\V2\ParseEmailResponse;
use AwardWallet\MainBundle\Email\Api;
use AwardWallet\MainBundle\Email\ApiException;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Globals\GlobalVariables;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\HistoryProcessor;
use AwardWallet\MainBundle\Loyalty\Resources\History;
use AwardWallet\MainBundle\Loyalty\Resources\HistoryField;
use AwardWallet\MainBundle\Loyalty\Resources\HistoryRow;
use Doctrine\ORM\EntityManager;
use JMS\Serializer\Serializer;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Router;

class EmailApiHistoryParser
{
    public const METHOD_PARSE_EMAIL = 'json/v2/parseEmail';

    public const SUPPORTED_PROVIDERS = [
        'delta' => ['html', 'pdf'],
        'rapidrewards' => ['pdf'],
        'mileageplus' => ['csv'],
    ];

    /**
     * @var Api
     */
    private $emailApi;
    /**
     * @var GlobalVariables
     */
    private $globalVariables;
    /**
     * @var Router
     */
    private $router;
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var HistoryProcessor
     */
    private $historyProcessor;

    /**
     * @var Serializer
     */
    private $serializer;
    private $emailApiAuth;

    public function __construct(
        Api $emailApi,
        GlobalVariables $globalVariables,
        Router $router,
        Logger $logger,
        EntityManager $em,
        HistoryProcessor $historyProcessor,
        Serializer $serializer,
        $emailApiAuth
    ) {
        $this->logger = $logger;
        $this->emailApi = $emailApi;
        $this->globalVariables = $globalVariables;
        $this->router = $router;
        $this->em = $em;
        $this->historyProcessor = $historyProcessor;
        $this->serializer = $serializer;
        $this->emailApiAuth = $emailApiAuth;
    }

    /**
     * @return bool
     */
    public function sendParseEmailRequest(Account $account, Request $request)
    {
        $user = $account->getUserid();
        /** @var UploadedFile $historyFile */
        $uploadedFile = $request->files->get('historyFile');

        /** @var \Swift_Message $email */
        $email = (new \Swift_Message())
            ->setSubject('email parse')
            ->setFrom($user->getLogin() . '@awardwallet.com')
            ->setTo('awardwallet@awardwallet.com');

        if ($uploadedFile->getClientOriginalExtension() == 'html') {
            $email->setBody(file_get_contents($uploadedFile->getRealPath()), 'text/html');
        } else {
            $attachment = new \Swift_Attachment(
                file_get_contents($uploadedFile->getRealPath()),
                $uploadedFile->getClientOriginalName(),
                'application/' . $uploadedFile->getClientOriginalExtension()
            );

            $email->setBody('Email Body')->attach($attachment);
        }

        $payload = [
            'userData' => json_encode(['accountId' => $account->getAccountid(), 'id' => uniqid()]),
            'callbackUrl' => $this->router->generate('aw_emailcallback_save', [], UrlGenerator::ABSOLUTE_URL),
            'email' => $email->toString(),
            'returnEmail' => 'all',
        ];
        $auth = sprintf('X-Authentication: %s', $this->emailApiAuth);

        try {
            $response = $this->emailApi->call(self::METHOD_PARSE_EMAIL, true, $payload, ["userId" => $user->getUserid()], false, null, [$auth]);
        } catch (ApiException $e) {
            $this->logger->log('error', "Email API returned an exception", [
                'message' => $e->getMessage(),
                'account' => $account->getAccountid(),
            ]);

            return false;
        }

        if ($response && $response['status'] != 'queued') {
            $this->logger->log('error', "Email API returned an error", [
                'message' => $response,
                'account' => $account->getAccountid(),
            ]);
        }

        return $response && $response['status'] == 'queued';
    }

    public function saveApiResponse(ParseEmailResponse $apiResponse)
    {
        $userData = json_decode($apiResponse->userData);
        $accountId = $userData->accountId;
        $this->historyProcessor->saveAccountHistory($accountId, $this->convertEmailParserCallback($apiResponse), true);
        $this->logger->log('info', "Received parsed account history from Email API", [
            'accountId' => $accountId,
        ]);
    }

    /**
     * @return bool
     */
    public function validateApiRequest(Account $account, Request $request)
    {
        /** @var UploadedFile $historyFile */
        $historyFile = $request->files->get('historyFile');
        $providerCode = $account->getProviderid() ? $account->getProviderid()->getCode() : null;

        $isValidRequest =
            ($historyFile instanceof UploadedFile)
            && $providerCode
            && array_key_exists($providerCode, self::SUPPORTED_PROVIDERS)
            && in_array($historyFile->getClientOriginalExtension(), self::SUPPORTED_PROVIDERS[$providerCode]);

        if (!$isValidRequest) {
            $this->logger->log('error', "Invalid API request", [
                'account' => $account->getAccountid(),
                'file' => $historyFile instanceof UploadedFile ? $historyFile->getClientOriginalName() : 'not found',
            ]);
        }

        return $isValidRequest;
    }

    /**
     * @return bool
     */
    public function validateApiResponse(ParseEmailResponse $apiResponse)
    {
        $accountRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class);
        $this->logger->log('info', "Received account history callback from Email API", ['data' => $apiResponse->userData]);

        if ($apiResponse->status != 'success') {
            return false;
        }

        $userData = json_decode($apiResponse->userData);

        if (!$userData) {
            return false;
        }

        $accountId = $userData->accountId;
        $account = $accountRep->find($accountId);

        $parsedLogin = null;

        /** @var Property $property */
        foreach ($apiResponse->loyaltyAccount->properties as $property) {
            if ($property->code === 'Login') {
                $parsedLogin = $property->value;
            }
        }

        $isValid =
            $account
            && (
                $apiResponse->providerCode == 'rapidrewards'
                || $apiResponse->providerCode == 'mileageplus'
                || strtolower($account->getLogin()) == $parsedLogin
            )
            && $account->getProviderid()->getCode() == $apiResponse->providerCode;

        if (!$isValid) {
            $this->logger->log('error', "Account not found or incorrect login/provider", ['data' => $apiResponse]);
        }

        return $isValid;
    }

    /**
     * @return History
     */
    public function convertEmailParserCallback(ParseEmailResponse $apiResponse)
    {
        $provider = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->findOneBy(['code' => $apiResponse->providerCode]);
        $columns = $this->globalVariables->getAccountChecker($provider)->GetHistoryColumns();

        return (new History())
            ->setRange(History::HISTORY_INCREMENTAL)
            ->setRows(
                array_map(
                    function (V2HistoryRow $row) use ($columns) {
                        return (new HistoryRow())->setFields(
                            array_reduce($row->fields, function ($fields, V2HistoryField $field) use ($columns) {
                                $name = $field->name;
                                $value = $field->value;

                                if (isset($columns[$name])) {
                                    if ($columns[$name] == 'PostingDate') {
                                        $value = date_create_from_format('Y-m-d\TH:i:s', $value)->getTimestamp();
                                    }
                                    $fields[] = new HistoryField($name, $value);
                                }

                                return $fields;
                            }, [])
                        );
                    },
                    $apiResponse->loyaltyAccount->history)
            );
    }
}
