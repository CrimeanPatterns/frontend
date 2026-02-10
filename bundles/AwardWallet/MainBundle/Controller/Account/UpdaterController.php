<?php

namespace AwardWallet\MainBundle\Controller\Account;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Form\Extension\JsonFormExtension\JsonRequestHandler;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\Mapper;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Globals\FormDehydrator;
use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Manager\AccountManager;
use AwardWallet\MainBundle\Manager\LocalPasswordsManager;
use AwardWallet\MainBundle\Updater\Option;
use AwardWallet\MainBundle\Updater\Options\ClientPlatform;
use AwardWallet\MainBundle\Updater\RequestHandler;
use AwardWallet\MainBundle\Updater\UpdaterSession;
use AwardWallet\MainBundle\Updater\UpdaterStateException;
use AwardWallet\MobileBundle\Form\Type;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/updater")
 */
class UpdaterController extends AbstractController
{
    private UpdaterSession $updaterSessionDesktop;
    private AccountListManager $accountListManager;
    private OptionsFactory $optionsFactory;
    private Mapper $mapper;
    private FormDehydrator $formDehydrator;

    public function __construct(
        UpdaterSession $updaterSessionDesktop,
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory,
        Mapper $mapper,
        FormDehydrator $formDehydrator
    ) {
        $this->updaterSessionDesktop = $updaterSessionDesktop;
        $this->accountListManager = $accountListManager;
        $this->optionsFactory = $optionsFactory;
        $this->mapper = $mapper;
        $this->formDehydrator = $formDehydrator;
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @Route("/start", name="aw_account_updater_start", methods={"POST"}, options={"expose"=true})
     * @return JsonResponse
     */
    public function startAction(Request $request, AwTokenStorageInterface $tokenStorage)
    {
        $requestData = JsonRequestHandler::parse($request);

        if (!(isset($requestData['accounts']) && is_array($requestData['accounts']) && !empty($requestData['accounts']))) {
            throw $this->createNotFoundException();
        }

        if (empty($requestData['startKey'])) {
            throw $this->createNotFoundException();
        }

        $options = [];
        $options[Option::BROWSER_SUPPORTED] = !empty($requestData['supportedBrowser']);
        $options[Option::EXTENSION_INSTALLED] = !empty($requestData['extensionAvailable']);
        $options[Option::CHECK_TRIPS] = !empty($requestData['trips']);
        $options[Option::SOURCE] = $requestData['source'];
        $options[Option::CLIENT_PLATFORM] = ClientPlatform::DESKTOP;

        $options[Option::EXTRA] = [
            'add' => $tokenStorage->getToken()->getUser()->getItineraryadddate(),
            'update' => $tokenStorage->getToken()->getUser()->getItineraryupdatedate(),
        ];
        $options[Option::PLATFORM] = UpdaterEngineInterface::SOURCE_DESKTOP;

        try {
            $ret = $this->updaterSessionDesktop->startLockSafe(
                $tokenStorage->getBusinessUser(),
                intval($requestData['startKey']),
                $requestData['accounts'],
                $options
            );
        } catch (UpdaterStateException $e) {
            throw new BadRequestHttpException();
        } catch (LockConflictedException $e) {
            throw new TooManyRequestsHttpException(5);
        }

        $response = new JsonResponse($ret);
        $response->setEncodingOptions($response->getEncodingOptions() | JSON_PRETTY_PRINT);

        return $response;
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @Route("/getEvents/{key}/{eventIndex}",
     *      name = "aw_account_updater_progress",
     *      methods={"GET", "POST"},
     *      requirements = {
     *          "key": "[a-z]+",
     *          "eventIndex" : "\d+"
     *      },
     *      options={"expose"=true}
     * )
     * @param string $key
     * @param int $eventIndex
     * @return JsonResponse
     */
    public function progressAction($key, $eventIndex, Request $request, RequestHandler $requestHandler)
    {
        $result = $requestHandler
            ->setUpdater($this->updaterSessionDesktop)
            ->handleProgress($request, $key, $eventIndex);

        $response = new JsonResponse($result);

        $response->setEncodingOptions($response->getEncodingOptions() | JSON_PRETTY_PRINT);

        return $response;
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @Route("/question/{key}/{accountId}",
     *      name="aw_account_updater_security_answer",
     *      methods={"GET", "POST"},
     *      requirements={
     *          "key": "[a-z]+",
     *          "accountId": "a\d+"
     *      }, options={"expose"=true}
     * )
     * @JsonDecode
     * @return JsonResponse
     */
    public function securityQuestionAction(Request $request, $accountId, $key, AccountManager $accountManager)
    {
        $accountId = intval(preg_replace('/[^0-9]/', '', $accountId));
        $account = $this->accountListManager
            ->getAccount(
                $this->optionsFactory
                    ->createDefaultOptions()
                    ->set(Options::OPTION_FORMATTER, $this->mapper),
                $accountId
            );

        if (!$account) {
            throw $this->createNotFoundException('Account not found');
        }

        // Form
        $pattern = [
            'answer' => '',
            'question' => $account['Question'],
        ];

        /** @var \Symfony\Component\Form\Form */
        $form = $this->createForm(Type\AnswerQuestionType::class, $pattern);
        $request->request->replace([$form->getName() => $request->request->all()]);

        $result = [
            'accountId' => $accountId,
            'DisplayName' => $account['DisplayName'],
        ];

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $formData = $form->getData();

                if (!empty($formData['question']) && !empty($formData['answer'])) {
                    try {
                        $account = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($accountId);

                        if (!$account) {
                            throw $this->createNotFoundException('Account not found');
                        }

                        $accountManager->answerSecurityQuestion($account, $formData['question'], $formData['answer']);
                        // add accounts on client!
                        // $this->get('aw.updater2')->add($key, [$accountId]);
                        // TODO: fix nonsense exceptions
                    } catch (\UnexpectedValueException $e) {
                        throw $this->createNotFoundException('Update session has expired or does not exist');
                    } catch (\InvalidArgumentException $e) {
                        throw $this->createNotFoundException('Account info was not found in update session');
                    }
                    $result['success'] = true;

                    return new JsonResponse($result);
                } else {
                    $result['error'] = 'Empty form data';
                }
            } else {
                $result['error'] = 'Invalid form data';
            }
        }

        return new JsonResponse(array_merge(
            $result,
            ['formData' => $this->formDehydrator->dehydrateForm($form)]
        ));
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/password/{key}/{accountId}",
     *      name="aw_account_updater_local_password",
     *      methods={"GET", "POST"},
     *      requirements={
     *          "key": "[a-z]+",
     *          "accountId": "a\d+"
     *      }, options={"expose"=true}
     * )
     * @JsonDecode
     * @return JsonResponse
     */
    public function localPasswordAction(Request $request, $key, $accountId, LocalPasswordsManager $localPasswordsManager)
    {
        $accountId = intval(preg_replace('/[^0-9]/', '', $accountId));
        $account = $this->accountListManager
            ->getAccount(
                $this->optionsFactory
                    ->createDefaultOptions()
                    ->set(Options::OPTION_FORMATTER, $this->mapper),
                $accountId
            );

        if (!$account) {
            throw $this->createNotFoundException('Account not found');
        }

        $form = $this->createForm(Type\LocalPasswordType::class);
        $request->request->replace([$form->getName() => $request->request->all()]);

        $result = [
            'accountId' => $accountId,
            'DisplayName' => $account['DisplayName'],
        ];

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $localPasswordsManager->setPassword($accountId, $form['password']->getData());
                // add accounts with local password on client!
                // $this->get('aw.updater2')->add($key, [$accountId]);
                $result['success'] = true;

                return new JsonResponse($result);
            }
        }

        return new JsonResponse(array_merge(
            $result,
            ['formData' => $this->formDehydrator->dehydrateForm($form)]
        ));
    }
}
