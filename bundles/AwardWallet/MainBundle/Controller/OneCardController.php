<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Repositories\OnecardRepository;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Service\Counter;
use AwardWallet\WidgetBundle\Widget\OnecardMenuWidget;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class OneCardController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_SITE_BUSINESS_AREA')")
     * @Route("/new-onecard", name="aw_one_card")
     * @Template("@AwardWalletMain/OneCard/index.html.twig")
     */
    public function indexAction(
        OnecardRepository $onecardRepository,
        AwTokenStorageInterface $tokenStorage,
        Counter $counter,
        OnecardMenuWidget $onecardMenuWidget
    ) {
        $cards = $onecardRepository->OneCardsCountByUser($tokenStorage->getBusinessUser()->getUserid());
        $accounts = $counter->getDetailsCountAccountsByUser($tokenStorage->getBusinessUser(), true, false);

        $result = [];

        foreach ($accounts as $account) {
            if ($account['Accounts'] > 0) {
                $result[] = [
                    'userId' => isset($account['UserID']) ? 0 : $account['UserAgentID'],
                    'username' => $account['UserName'],
                    'count' => $account['Accounts'],
                ];
            }
        }

        $onecardMenuWidget->setActiveItem(0);

        return [
            'cards' => $cards,
            'result' => $result,
        ];
    }
}
