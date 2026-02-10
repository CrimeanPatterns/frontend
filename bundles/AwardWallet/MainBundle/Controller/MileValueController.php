<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Service\MileValue\Form\Model\CustomSetModel;
use AwardWallet\MainBundle\Service\MileValue\MileValueCustom;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use AwardWallet\MainBundle\Service\MileValue\MileValueUserInfo;
use AwardWallet\MainBundle\Service\MileValue\UserPointValueService;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use AwardWallet\MainBundle\Timeline\Manager;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class MileValueController extends AbstractController
{
    private MileValueService $mileValueService;
    private TokenStorageInterface $tokenStorage;
    private AuthorizationCheckerInterface $authorizationChecker;
    private UserPointValueService $userPointValueService;

    public function __construct(
        MileValueService $mileValueService,
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        UserPointValueService $userPointValueService
    ) {
        $this->mileValueService = $mileValueService;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->userPointValueService = $userPointValueService;
    }

    /**
     * @Route(
     *     "/point-mile-values",
     *     methods={"GET"},
     *     name="aw_points_miles_values",
     *     defaults={"_canonical" = "aw_points_miles_values", "_alternate" = "aw_points_miles_values_locale", "_canonical_clean"="_locale"},
     *     options={"expose"=true}
     * )
     * @Route(
     *     "/{_locale}/point-mile-values",
     *     methods={"GET"},
     *     name="aw_points_miles_values_locale",
     *     requirements={"_locale"="%route_locales%"},
     *     defaults={"_locale"="en", "_canonical" = "aw_points_miles_values", "_alternate" = "aw_points_miles_values_locale", "_canonical_clean"="_locale"}
     * )
     * @Route("/point-mile-values/{providerName}", methods={"GET"}, name="aw_points_miles_values_provider", options={"expose"=true}, defaults={"_canonical"="aw_points_miles_values", "_canonical_clean"="providerName"})
     * @Route("/{_locale}/point-mile-values/{providerName}", methods={"GET"}, name="aw_points_miles_values_provider_locale", requirements={"_locale"="%route_locales%"}, defaults={"_locale"="en", "_canonical"="aw_points_miles_values", "_canonical_clean"="_locale,providerName"})
     * @Template("@AwardWalletMain/MileValue/index.html.twig")
     */
    public function indexAction(TranslatorInterface $translator, PageVisitLogger $pageVisitLogger, $providerName = null): array
    {
        $result = [
            'isAuth' => $this->authorizationChecker->isGranted('ROLE_USER'),
            'datas' => $this->mileValueService->getFlatDataList(),
            'providerName' => urldecode($providerName),
        ];

        if ($result['isAuth']) {
            $accountsUserSetValues = $this->userPointValueService->getAccountsUserSetValues($this->tokenStorage->getToken()->getUser());

            if (count($accountsUserSetValues) < 2) {
                $accountsUserSetValues = array_map(static function ($item) {
                    unset($item['Login']);

                    return $item;
                }, $accountsUserSetValues);
            }

            $result['datas'] = $this->userPointValueService->assignUserPointKinds(
                $result['datas'],
                $accountsUserSetValues
            );
        }
        $pageVisitLogger->log(PageVisitLogger::PAGE_POINT_MILE_VALUES);

        return $result;
    }

    /**
     * @Route("/point-mile-values/user-set", methods={"POST"}, name="aw_points_miles_userset", options={"expose"=true})
     * @Security("is_granted('ROLE_USER')")
     */
    public function setUserValues(
        Request $request,
        EntityManagerInterface $entityManager,
        MileValueUserInfo $mileValueUserInfo,
        UsrRepository $usrRepository,
        UserPointValueService $userPointValueService,
        TranslatorInterface $translator
    ): JsonResponse {
        $result = ['success' => false];
        $user = $this->tokenStorage->getToken()->getUser();

        if (!$this->authorizationChecker->isGranted('NOT_SITE_BUSINESS_AREA')) {
            $user = $usrRepository->getBusinessByUser($user);
        }

        $params = 'json' === $request->getContentType() ? json_decode($request->getContent(), true) : [];
        $source = $params['source'] ?? null;

        $providerId = (int) $request->get('providerId', $params['providerId'] ?? 0);
        $accountId = (int) $request->get('accountId', $params['accountId'] ?? 0);

        if (!empty($providerId)) {
            $provider = $entityManager->getRepository(Provider::class)->find($providerId);
        }

        if (!empty($accountId)) {
            $account = $entityManager->getRepository(Account::class)->find($accountId);
        }

        if (empty($account) && empty($provider)) {
            return new JsonResponse($result);
        }

        if (!empty($account) && !$this->authorizationChecker->isGranted('EDIT', $account)) {
            throw $this->createAccessDeniedException();
        }

        if (empty($provider) && isset($account) && $account instanceof Account) {
            $provider = $account->getProviderid();
        }
        $isCustomAccount = empty($provider);

        if (null === $params['value']) {
            $result['success'] = $isCustomAccount
                ? $userPointValueService->removeAccountUserPointValue($account)
                : $userPointValueService->removeProviderUserPointValue($user->getId(), $provider->getId());

            if (!$isCustomAccount) {
                // hack for KLM (Flying Blue) / Air France (Flying Blue)
                if (Provider::KLM_ID === $provider->getId()) {
                    $result['success'] = $userPointValueService->removeProviderUserPointValue($user->getId(), Provider::AIRFRANCE_ID);
                } elseif (Provider::AIRFRANCE_ID === $provider->getId()) {
                    $result['success'] = $userPointValueService->removeProviderUserPointValue($user->getId(), Provider::KLM_ID);
                }
            }
        } else {
            $value = (float) ($params['value'] ?? -1);

            if (!$isCustomAccount && !MileValueService::isValidValue($value)) {
                $result['error'] = $translator->trans(/** @Desc("On this page, you can assign a value of a single %provider% %providerCurrency% in US Cent. %break%The value you provided appears to be excessively high.") */ 'u-assign-value-high',
                    [
                        '%provider%' => $provider->getDisplayname(),
                        '%providerCurrency%' => $provider->getCurrency()->getName(),
                        '%break%' => ' ',
                    ]);

                return new JsonResponse($result);
            }

            if (MileValueService::isValidValue($value)) {
                $result['success'] = $isCustomAccount
                    ? $userPointValueService->setAccountUserPointValue($account, $value)
                    : $userPointValueService->setProviderUserPointValue($user->getId(), $provider->getId(), $value);

                if (!$isCustomAccount) {
                    // hack for KLM (Flying Blue) / Air France (Flying Blue)
                    if (Provider::KLM_ID === $provider->getId()) {
                        $result['success'] = $userPointValueService->setProviderUserPointValue($user->getId(), Provider::AIRFRANCE_ID, $value);
                    } elseif (Provider::AIRFRANCE_ID === $provider->getId()) {
                        $result['success'] = $userPointValueService->setProviderUserPointValue($user->getId(), Provider::KLM_ID, $value);
                    }
                }
            }
        }

        if ('cashEquivalent' === $source) {
            $result['MileValue'] = $mileValueUserInfo->fetchAccountInfo($account);
        } else {
            $result['datas'] = $this->userPointValueService->assignUserPointKinds(
                $this->mileValueService->getFlatDataList(),
                $this->userPointValueService->getAccountsUserSetValues($this->tokenStorage->getToken()->getUser())
            );

            $result['bankPointsShort'] = $this->mileValueService->getBankPointsShortData(true);
        }

        return new JsonResponse($result);
    }

    /**
     * @Route("/point-mile-values/timeline/customset", methods={"POST"}, name="aw_timeline_milevalue_customset", options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED')")
     */
    public function setCustomMileValueCost(
        Request $request,
        Manager $manager,
        MileValueService $mileValueService,
        AuthorizationCheckerInterface $authorizationChecker,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        MileValueCustom $mileValueCustom
    ): JsonResponse {
        if (!is_string($id = $request->request->get('id'))) {
            throw new \InvalidArgumentException();
        }
        $segment = $manager->getEntityByItCode($id);

        if (empty($segment)) {
            throw new \InvalidArgumentException('Invalid id value');
        }

        if (!$authorizationChecker->isGranted('EDIT', $segment->getTripid())) {
            throw new AccessDeniedException();
        }

        $model = $serializer->deserialize(json_encode($data = $request->request->all()), CustomSetModel::class, 'json');
        $errors = it($validator->validate($model))
            ->reindex(fn (ConstraintViolationInterface $violation) => $violation->getPropertyPath())
            ->map(fn (ConstraintViolationInterface $violation) => $violation->getMessage())
            ->toArrayWithKeys();

        if (empty($errors)) {
            $result = $mileValueCustom->setCustomValue($segment->getTripid()->getId(), (int) $data['customPick'], (float) $data['customValue']);
            $response['success'] = true;
            $response['data'] = $result;
        } else {
            $response['errors'] = $errors;
        }

        return new JsonResponse($response);
    }
}
