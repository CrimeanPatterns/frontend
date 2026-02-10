<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\Common\Memcached\Noop;
use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Utils\ConcurrentArrayFactory;
use AwardWallet\MainBundle\Service\SocksMessaging\Client as SocksClient;
use Clock\ClockInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

use function Duration\minutes;

class MerchantPatternController
{
    /**
     * @Security("is_granted('ROLE_MANAGE_MERCHANT')")
     * @Route("/manager/merchant-pattern-save-progress", name="aw_manager_merchant_pattern_save_progress")
     * @Template("@AwardWalletMain/Manager/CreditCards/merchantPatternSaveProgress.html.twig")
     */
    public function reportAction(
        ConcurrentArrayFactory $concurrentArrayFactory,
        SocksClient $messaging
    ): array {
        $channelsList = $concurrentArrayFactory->create('merchant_pattern_save_progress', minutes(30));

        return [
            "channels" => $channelsList->all(),
            'centrifuge_config' => $messaging->getClientData(),
            'title' => "Merchant Pattern Rematch Progress",
        ];
    }

    /**
     * @Route("/manager/merchant-pattern/cancel-task",
     *     name="aw_manager_merchant_pattern_cancel_task",
     *     methods={"POST"},
     *     options={"expose"=true}
     * )
     * @JsonDecode
     * @Security("is_granted('CSRF') and is_granted('ROLE_MANAGE_MERCHANT')")
     */
    public function cancelTaskAction(
        Request $request,
        ConcurrentArrayFactory $concurrentArrayFactory,
        AwTokenStorageInterface $tokenStorage,
        ClockInterface $clock,
        SocksClient $messaging
    ): Response {
        $channelId = $request->request->get('channel');

        if (\strpos($channelId, '$user_mrchprm') !== 0) {
            throw new BadRequestHttpException();
        }

        $channelsList = $concurrentArrayFactory->create('merchant_pattern_save_progress', minutes(30));
        $user = $tokenStorage->getUser();
        $lastUpdated = $clock->current();
        $patch = [
            'state' => 'cancelled',
            'state_info' => 'cancelled by ' . $user->getLogin(),
            'last_updated' => $lastUpdated->format('Y-m-d H:i:s'),
            'last_updated_internal' => $lastUpdated,
        ];

        $channelsList->update(function (array $map) use ($channelId, $patch) {
            if (!isset($map[$channelId]) || ('finished' === $map[$channelId]['state'])) {
                return Noop::getInstance();
            }

            $map[$channelId] = \array_merge($map[$channelId], $patch);

            return $map;
        });
        $messaging->publish($channelId, \array_merge($patch, ['type' => 'log']));

        return new JsonResponse(['success' => true]);
    }
}
