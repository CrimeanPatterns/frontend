<?php

namespace AwardWallet\MainBundle\Controller\Manager\CreditCards;

use AwardWallet\MainBundle\Service\ElasticSearch\Client;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

class DoublePatternsController
{
    /**
     * @Security("is_granted('ROLE_MANAGE_CREDITCARD')")
     * @Route("/manager/credit-cards/double-patterns", name="aw_manage_double_patterns")
     */
    public function __invoke(Environment $twig, RouterInterface $router, Client $elasticClient, Request $request)
    {
        $extraQuery = $this->getExtraQuery($request);

        $rows = iterator_to_array($elasticClient->query(
            "message: cc_multiple_matches" . $extraQuery,
            new \DateTime("-1 day"),
            new \DateTime(),
            500,
            [
                'sort' => [
                    [
                        '@timestamp' => [
                            'order' => 'desc',
                            'unmapped_type' => 'boolean',
                        ],
                    ],
                ],
            ],
            500
        ));
        $rows = array_map(fn (array $row) => array_merge($row['context'], ["Time" => date("Y-m-d H:i:s", strtotime(substr($row['@timestamp'], 0, strlen('2023-12-06T07:46:11'))))]), $rows);

        return new Response($twig->render('@AwardWalletMain/Manager/CreditCards/doublePatterns.html.twig', ["rows" => $rows, "filter" => $extraQuery]));
    }

    private function getExtraQuery(Request $request): string
    {
        $patterns = [];

        for ($index = 1; $index <= 2; $index++) {
            $value = $request->query->get("pattern" . $index);

            if ($value === null) {
                continue;
            }

            $patterns[] .= "*\[" . $value . "\]*";
        }

        if (count($patterns) === 0) {
            return "";
        }

        return " AND context.MatchedPatterns: (" . implode(" OR ", $patterns) . ")";
    }
}
