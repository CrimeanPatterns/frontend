<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/manager/fs/stats")
 */
class FlightStatsUsageController extends AbstractController
{
    /**
     * @Route("/", name="aw_manager_fs_stats")
     * @Security("is_granted('ROLE_MANAGE_FSUSAGE')")
     */
    public function indexAction()
    {
        $links = [
            ['(Email) All partners', 'https://loyalty-kibana.awardwallet.com/app/kibana#/visualize/edit/%5BFS%5D-Email-FlightStats-Usage?_g=(refreshInterval:(display:Off,pause:!f,value:0),time:(from:now-90d,mode:quick,to:now))'],
            ['(Email) All partners (with api name)', 'https://loyalty-kibana.awardwallet.com/app/kibana#/visualize/edit/%5BFS%5D-Email-FlightStats-Usage-(With-API-name)?_g=(refreshInterval:(display:Off,pause:!f,value:0),time:(from:now-90d,mode:quick,to:now))'],
            ['(Loyalty) All partners', 'https://loyalty-kibana.awardwallet.com/app/kibana#/visualize/edit/%5BFS%5D-Loyalty-FlightStats-Usage?_g=(refreshInterval:(display:Off,pause:!f,value:0),time:(from:now-90d,mode:quick,to:now))'],
            ['(Loyalty) All partners (with api name)', 'https://loyalty-kibana.awardwallet.com/app/kibana#/visualize/edit/%5BFS%5D-Loyalty-FlightStats-Usage-(With-API-name)?_g=(refreshInterval:(display:Off,pause:!f,value:0),time:(from:now-90d,mode:quick,to:now))'],
            ['Frontend', 'https://loyalty-kibana.awardwallet.com/app/kibana#/visualize/edit/%5BFS%5D-Frontend-FlightStats-Usage?_g=(refreshInterval:(display:Off,pause:!f,value:0),time:(from:now-90d,mode:quick,to:now))'],
            ['Frontend (with api name)', 'https://loyalty-kibana.awardwallet.com/app/kibana#/visualize/edit/%5BFS%5D-Frontend-FlightStats-Usage-(With-API-name)?_g=(refreshInterval:(display:Off,pause:!f,value:0),time:(from:now-90d,mode:quick,to:now))'],
            ['Total', 'https://loyalty-kibana.awardwallet.com/app/kibana#/visualize/edit/%5BFS%5D-FS-Usage-By-API?_g=(refreshInterval:(display:Off,pause:!f,value:0),time:(from:now-60d,mode:quick,to:now))'],
            ['Comparison', 'https://loyalty-kibana.awardwallet.com/app/kibana#/visualize/edit/%5BFS%5D-FS-Usage-Comparison?_g=(refreshInterval:(display:Off,pause:!f,value:0),time:(from:now-14d,mode:relative,to:now))'],
        ];

        return $this->render('@AwardWalletMain/Manager/FlightStatsUsage/index.html.twig', ['links' => $links]);
    }
}
