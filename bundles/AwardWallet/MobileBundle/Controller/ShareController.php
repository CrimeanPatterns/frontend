<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\MobileMapper;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\ProgramShareManager;
use AwardWallet\MainBundle\Service\LegacyUrlGenerator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @Route("/connections/share")
 */
class ShareController extends AbstractController
{
    use JsonTrait;

    public function __construct(
        LocalizeService $localizeService
    ) {
        $localizeService->setRegionalSettings();
    }

    /**
     * @Security("is_granted('CSRF') and is_granted('NOT_USER_IMPERSONATED')")
     * @Route("/{code}", name="awm_share_all", methods={"POST"})
     */
    public function shareAllAction(
        string $code,
        ProgramShareManager $programShareManager,
        MobileMapper $mobileMapper,
        LegacyUrlGenerator $legacyUrlGenerator
    ): JsonResponse {
        $user = $this->getUser();
        $decoded = $programShareManager->decodeShareAllCode($code);

        if (empty($decoded)) {
            throw $this->createNotFoundException();
        }

        /** @var Useragent $userAgent */
        [$userAgent, $type] = $decoded;

        if (!$this->isGranted('CONNECTION_APPROVED', $userAgent)) {
            throw $this->createNotFoundException();
        }

        /** @var Useragent $connection */
        /** @var Usr $agent */
        [$agent, $_, $usrAccounts] = $programShareManager->apiSharingShareAll(
            $user,
            $userAgent,
            $type,
            (new Options())
                ->set(Options::OPTION_FORMATTER, $mobileMapper)
        );
        $accountsIter = it($usrAccounts);

        if ('full' === $type) {
            [$accountsWithLocalPasswords, $accountsWithoutLocalPasswords] =
                $accountsIter
                ->partition(function (array $usrAccount) {
                    return
                        ('Account' === $usrAccount['TableName'])
                        && (SAVE_PASSWORD_LOCALLY == $usrAccount['SavePassword']);
                });
        } else {
            $accountsWithoutLocalPasswords = $accountsIter;
            $accountsWithLocalPasswords = it([]);
        }

        $accountMapper = function (array $account) {
            return [
                'program' => $account['DisplayName'],
                'user' => $account['UserName'],
                'balance' => $account['Balance'] ?? '-',
            ];
        };

        return $this->jsonResponse([
            'connectionId' => $userAgent->getUseragentid(),
            'agent' => $agent->getFullName(),
            'accountsWithLocalPasswords' => $accountsWithLocalPasswords
                ->map($accountMapper)
                ->toArray(),
            'accountsWithoutLocalPasswords' => $accountsWithoutLocalPasswords
                ->map($accountMapper)
                ->toArray(),
            'fullAccess' => ('full' === $type),
            'avatar' => StringUtils::isNotEmpty($avatarSrc = $agent->getAvatarLink('small')) ?
                    $legacyUrlGenerator->generateAbsoluteUrl($avatarSrc) :
                    null,
        ]);
    }

    /**
     * @Security("is_granted('CSRF') and is_granted('NOT_USER_IMPERSONATED')")
     * @Route("/deny-all/{userAgentId}", name="awm_share_deny_all", requirements={"userAgentId"="\d+"}, methods={"POST"})
     * @ParamConverter("userAgent", class="AwardWalletMainBundle:Useragent", options={"id" = "userAgentId"})
     */
    public function denyAllAction(Useragent $userAgent, AwTokenStorageInterface $awTokenStorage, ProgramShareManager $programShareManager): JsonResponse
    {
        $user = $awTokenStorage->getBusinessUser();
        $userId = $user->getId();

        if ($userId != $userAgent->getAgentid()->getId() && $userId != $userAgent->getClientid()->getId()) {
            throw $this->createNotFoundException();
        }

        if ($this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class)->findBy([
            'clientid' => [
                $userAgent->getAgentid()->getId(),
                $userAgent->getClientid()->getId(),
            ],
            'agentid' => $awTokenStorage->getBusinessUser()->getId(),
            'isapproved' => true,
            'accesslevel' => [
                UseragentRepository::ACCESS_ADMIN,
                UseragentRepository::ACCESS_BOOKING_MANAGER,
            ],
        ])) {
            throw $this->createNotFoundException();
        }

        $programShareManager->apiSharingDenyAll($user, $userAgent);

        return $this->successJsonResponse();
    }
}
