<?php

namespace AwardWallet\MainBundle\Controller\Sonata;

use AwardWallet\MainBundle\Entity\EmailTemplate;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\MailerCollection;
use AwardWallet\MainBundle\Service\EmailTemplate\AbstractDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\DataProviderLoader;
use AwardWallet\MainBundle\Service\EmailTemplate\Event\Events;
use AwardWallet\MainBundle\Service\EmailTemplate\Event\SendEvent;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;
use AwardWallet\MainBundle\Worker\AsyncProcess\Callback\CallbackTask;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use Doctrine\ORM\EntityManagerInterface;
use Sonata\AdminBundle\Controller\CRUDController;
use Sonata\AdminBundle\Exception\ModelManagerException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function AwardWallet\MainBundle\Globals\Utils\f\propertyPathEq;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class EmailTemplateAdminController extends CRUDController
{
    private $sended;
    private Process $process;
    private DataProviderLoader $dataProviderLoader;
    private EntityManagerInterface $entityManager;
    private Mailer $mailer;
    private MailerCollection $mailerCollection;
    private RequestStack $requestStack;

    public function __construct(
        Process $process,
        DataProviderLoader $dataProviderLoader,
        EntityManagerInterface $entityManager,
        Mailer $mailer,
        MailerCollection $mailerCollection,
        RequestStack $requestStack
    ) {
        $this->process = $process;
        $this->dataProviderLoader = $dataProviderLoader;
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->mailerCollection = $mailerCollection;
        $this->requestStack = $requestStack;
    }

    public function duplicateAction()
    {
        /** @var EmailTemplate $object */
        $object = $this->admin->getSubject();

        if (!$object) {
            throw new NotFoundHttpException('Email template not found');
        }

        $duplicate = new EmailTemplate();

        /** @var \ReflectionProperty $reflectionProperty */
        foreach (
            it((new \ReflectionClass(EmailTemplate::class))->getProperties())
            ->filterNot(propertyPathEq('name', 'emailTemplateID')) as $reflectionProperty
        ) {
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($duplicate, $reflectionProperty->getValue($object));
            $reflectionProperty->setAccessible(false);
        }

        $duplicate->setCode('copy_of_' . $duplicate->getCode());

        try {
            $this->admin->create($duplicate);
        } catch (ModelManagerException $e) {
            $this->addFlash('sonata_flash_error', 'Failed to create duplicate!');

            return new RedirectResponse($this->admin->generateObjectUrl('edit', $object));
        }

        $this->addFlash('sonata_flash_success', 'Duplicate created!');

        return new RedirectResponse($this->admin->generateObjectUrl('edit', $duplicate));
    }

    public function sendAction(Request $request)
    {
        $this->handleRequest($request, 'send');
        $messages = [];

        if (isset($this->sended)) {
            $success = true;
            $object = $this->admin->getSubject();
            $messages[] = sprintf("Emails were sent successfully: %s", $this->sended);
            $messages[] = $this->admin->getTranslator()->trans(
                'flash_edit_success',
                ['%name%' => $this->escapeHtml($this->admin->toString($object))],
                'SonataAdminBundle'
            );
            $this->sended = null;
        } else {
            $success = false;
            $messages[] = "User has not been found";
        }

        return (new JsonResponse())->setData([
            'data' => $this->getRequestData($request),
            'success' => $success,
            'messages' => $messages,
        ]);
    }

    public function showEmailAction(Request $request)
    {
        /** @var \Swift_Message $message */
        $message = $this->handleRequest($request, 'show');

        if (is_string($message)) {
            return (new JsonResponse())->setData([
                'success' => false,
                'message' => $message,
            ]);
        }

        return (new JsonResponse())->setData([
            'success' => true,
            'subject' => $message->getSubject(),
            'preview' => str_replace("\\n", "", $message->getBody()),
        ]);
    }

    public function statsAction(Request $request)
    {
        return new JsonResponse($this->handleRequest($request, 'stats'));
    }

    public function searchUserAction(Request $request)
    {
        return new JsonResponse($this->handleRequest($request, 'searchUser'));
    }

    /**
     * @return AbstractDataProvider
     */
    public static function prepareDataProvider(DataProviderLoader $dataProviderLoader, EmailTemplate $emailTemplate, array $requestData)
    {
        $dataProvider = $dataProviderLoader->getDataProviderByEmailTemplate($emailTemplate);
        // Query Options
        $queryOptions = $dataProvider->getQueryOptions();
        $fixturesUserIds = [];

        if ($requestData['fixture']) {
            $fixturesUserIds = $dataProvider->addFixtures();
        }
        [$exclusionEmails, $exclusionProviders] = DataProviderLoader::expandExclusions($emailTemplate);
        array_walk($queryOptions, function ($option) use ($emailTemplate, $requestData, $fixturesUserIds, $exclusionEmails, $exclusionProviders) {
            /** @var Options $option */
            $option->userId = array_merge($option->userId, $requestData['users'], $fixturesUserIds);
            $option->exclusionDataProviders = $exclusionProviders;
            $option->hasNotEmails = $exclusionEmails;
            $option->excludedCreditCards = $emailTemplate->getExcludedCreditCards();
        });

        $dataProvider->setQueryOptions($queryOptions);

        $mailerOptions = $dataProvider->getOptions();
        $mailerOptions = array_merge($mailerOptions, [
            Mailer::OPTION_SKIP_DONOTSEND => true,
            Mailer::OPTION_SKIP_STAT => true,
        ]);
        $dataProvider->setOptions($mailerOptions);

        return $dataProvider;
    }

    protected function handleRequest(Request $request, $action)
    {
        self::createImageFolder();
        /** @var Usr $user */
        $user = $this->getUser();

        if (!$request->isMethod('POST')) {
            throw $this->createNotFoundException();
        }
        $id = $request->get($this->admin->getIdParameter());
        /** @var EmailTemplate $object */
        $object = $this->admin->getObject($id);

        if (!$object) {
            throw $this->createNotFoundException(sprintf('unable to find the object with id : %s', $id));
        }
        $this->admin->checkAccess($action, $object);
        $requestData = $this->getRequestData($request);

        $dataProvider = self::prepareDataProvider(
            $this->dataProviderLoader,
            $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\EmailTemplate::class)->find($object->getId()),
            $requestData
        );
        $dataProvider->setOptions(array_merge($dataProvider->getOptions(), [Mailer::OPTION_TRANSACTIONAL => true]));
        $templateId = $object->getId();

        if ($action == 'show') {
            if (!$dataProvider->next(false)) {
                $result = "User has not been found";
            } else {
                $result = $dataProvider->getMessage($this->mailer);
            }

            if ($requestData['fixture']) {
                $dataProvider->deleteFixtures();
            }

            return $result;
        } elseif ('stats' === $action) {
            return $this->process->execute(new CallbackTask(
                function (
                    DataProviderLoader $dataProviderLoader,
                    EntityManagerInterface $entityManager
                ) use ($templateId, $requestData) {
                    $dataProvider = EmailTemplateAdminController::prepareDataProvider(
                        $dataProviderLoader,
                        $emailTemplate = $entityManager->getRepository(\AwardWallet\MainBundle\Entity\EmailTemplate::class)->find($templateId),
                        $requestData
                    );
                    $queryOptions = $dataProvider->getQueryOptions();
                    [$exclusionEmails, $exclusionProviders] = DataProviderLoader::expandExclusions($emailTemplate);

                    \array_walk($queryOptions, function ($option) use ($emailTemplate, $exclusionEmails, $exclusionProviders) {
                        /** @var Options $option */
                        $option->userId = [];
                        $option->exclusionDataProviders = $exclusionProviders;
                        $option->hasNotEmails = $exclusionEmails;
                        $option->excludedCreditCards = $emailTemplate->getExcludedCreditCards();
                    });
                    $dataProvider->setQueryOptions($queryOptions);

                    return [
                        'count' => $dataProvider->getQuery()->getCount(),
                        'code' => $dataProvider->getEmailTemplate()->getCode() . '-' . DataProviderLoader::getCodeByClass(\get_class($dataProvider)),
                        'dataProvider' => $dataProvider->getEmailTemplate()->getDataProvider(),
                        'success' => true,
                        'exampleUsers' =>
                            it($dataProvider)
                            ->take(5)
                            ->column('UserID')
                            ->toArray(),
                    ];
                },
                [],
                $request->get('requestId') . '_stats' . \hash('sha256', \serialize($requestData))
            ));
        } elseif ('searchUser' === $action) {
            return $this->process->execute(new CallbackTask(
                function (
                    DataProviderLoader $dataProviderLoader,
                    EntityManagerInterface $entityManager
                ) use ($requestData, $templateId) {
                    $dataProvider = self::prepareDataProvider(
                        $dataProviderLoader,
                        $entityManager->getRepository(\AwardWallet\MainBundle\Entity\EmailTemplate::class)->find($templateId),
                        (function () use ($requestData) {
                            $requestData['users'] = [];

                            return $requestData;
                        })()
                    );

                    $queryOptions = $dataProvider->getQueryOptions();
                    $searchMaxCount = 1;

                    foreach ($queryOptions as $option) {
                        /** @var Options $option */
                        $option->notUserId =
                            it($option->notUserId)
                            ->chain($requestData['users'])
                            ->collect()
                            ->unique()
                            ->values()
                            ->toArray();
                        $option->limit = ($notUserCount = \count($requestData['users'])) > 0 ? $notUserCount : 1;
                        $searchMaxCount += $option->limit;
                    }

                    $users =
                        it($dataProvider)
                        ->take($searchMaxCount)
                        ->column('UserID')
                        ->toArray();

                    return $users ?
                        [
                            'success' => true,
                            'users' => $users,
                        ] :
                        [
                            'success' => false,
                            'message' => 'User has not been found',
                        ];
                },
                [],
                $request->get('requestId') . '_searchuser' . \hash('sha256', \serialize($requestData))
            ));
        } else {
            // send
            if ($user && $user->getId() == 20096 && $object->getType() === EmailTemplate::TYPE_OTHER) {
                throw $this->createAccessDeniedException();
            }

            $editRequest = $request->duplicate(null, [
                'email_template' => $requestData['form'],
                'btn_update_and_edit' => '',
            ]);
            $this->requestStack->pop();
            $this->requestStack->push($editRequest);
            $this->editAction($request);

            $ctrl = $this;
            $to = null;
            $sentCount = 0;
            $dataProvider->getDispatcher()->addListener(Events::EVENT_PRE_SEND, function (SendEvent $event) use ($requestData, $user, &$to, &$sentCount) {
                $message = $event->getMessage();
                $to = isset($requestData['emails']) && sizeof($requestData['emails']) ? $requestData['emails'] : $user->getEmail();
                $sentCount++;

                $message->setTo(isset($requestData['emails']) && sizeof($requestData['emails']) ? $requestData['emails'] : $user->getEmail())
                    ->setBcc([])
                    ->setCc([]);
            });

            $dataProvider->setForceTransactional(true);
            $this->mailerCollection->setDataProvider($dataProvider);
            $this->mailerCollection->setLimit($requestData['limit'] ?? 5);
            $this->mailerCollection->send(false, true);

            $this->sended = "{$sentCount} email(s) to " . it(\is_array($to) ? $to : [$to])->joinToString(', ');

            if ($requestData['fixture']) {
                $dataProvider->deleteFixtures();
            }

            return null;
        }
    }

    protected function getRequestData(Request $request)
    {
        $result = [
            'users' => [],
            'emails' => [],
            'fixture' => false,
            'limit' => 1,
            'form' => null,
        ];

        $data = $request->request->all();

        // form
        if (isset($data['form'])) {
            $result['form'] = $data['form'];
        }

        // users
        if (isset($data['users']) && is_string($data['users'])) {
            if ($users = $this->getUsers($data['users'])) {
                $result['users'] = $users;
            }
        }

        // emails
        if (isset($data['emails']) && is_string($data['emails'])) {
            foreach (explode("\n", trim($data['emails'])) as $row) {
                $row = trim($row);

                if (empty($row)) {
                    continue;
                }

                if (filter_var($row, FILTER_VALIDATE_EMAIL)) {
                    $result['emails'][] = $row;
                }
            }
        }
        // fixture
        $result['fixture'] = isset($data['fixture']) && $data['fixture'] == 'true';

        // limit
        if (isset($data['limit']) && is_numeric($data['limit'])) {
            $result['limit'] = intval($data['limit']);
        }

        if (count($result['users']) > 50) {
            $result['users'] = array_slice($result['users'], 0, 50);
        }

        if (count($result['emails']) > 50) {
            $result['emails'] = array_slice($result['emails'], 0, 50);
        }

        if ($result['limit'] > 20) {
            $result['limit'] = 20;
        }

        if ($result['limit'] <= 0) {
            $result['limit'] = 1;
        }

        return $result;
    }

    private static function createImageFolder(): void
    {
        $imagesPath = 'images/uploaded/emails';
        $fs = new Filesystem();

        if (!$fs->exists($imagesPath)) {
            $fs->mkdir($imagesPath, 0755);
        }
    }

    private function getUsers($str)
    {
        $str = trim($str);

        if (empty($str)) {
            return [];
        }

        $result = [];
        $userRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);

        foreach (explode("\n", $str) as $row) {
            $row = trim($row);

            if (empty($row)) {
                continue;
            }

            if (is_numeric($row)) {
                $result[] = intval($row);

                continue;
            } elseif (filter_var($row, FILTER_VALIDATE_EMAIL)) {
                $user = $userRep->findOneByEmail($row);
            } else {
                $user = $userRep->findOneByLogin($row);
            }

            if ($user) {
                $result[] = $user->getId();
            } else {
                $result[] = 0;
            }
        }

        return array_unique($result);
    }
}
