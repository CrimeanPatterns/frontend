<?php

namespace AwardWallet\MainBundle\Controller\Manager\MileValue;

use AwardWallet\MainBundle\Service\MileValue\FoundPrices;
use AwardWallet\MainBundle\Service\MileValue\PriceSource\ResultRoute;
use AwardWallet\MainBundle\Service\MileValue\PriceWithInfo;
use AwardWallet\MainBundle\Service\MileValue\TimeDiff;
use Doctrine\DBAL\Connection;
use JMS\Serializer\SerializerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class PriceHistoryController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_MANAGE_MILEVALUE')")
     * @Route("/manager/milevalue/prices/{tripId}", requirements={"tripId": "^\d+$"}, name="aw_manager_milevalue_prices")
     */
    public function showAction(Connection $connection, int $tripId, SerializerInterface $serializer, Environment $twig, Request $request)
    {
        $json = $connection->executeQuery("select FoundPrices from MileValue where TripID = ?", [$tripId])->fetchColumn();

        if (!preg_match('#^V2:#ims', $json)) {
            return new Response("Invalid data: " . $json);
        }
        $json = substr($json, 3);

        /** @var FoundPrices $foundPrice */
        $foundPrice = $serializer->deserialize($json, FoundPrices::class, 'json');
        /** @var PriceWithInfo[] $priceInfos */
        $priceInfos = $foundPrice->priceInfos;

        $csv = $request->query->has('csv');

        $rows = it($priceInfos)
            ->map(function (PriceWithInfo $priceWithInfo) use ($csv) {
                $row = [
                    "Price" => $priceWithInfo->price->price,
                    "From" => reset($priceWithInfo->price->routes)->depCode,
                    "To" => end($priceWithInfo->price->routes)->arrCode,
                    "Routes" => it($priceWithInfo->price->routes)
                        ->map(function (ResultRoute $route) {
                            return
                                $route->airline . $route->flightNumber . " " . $route->depCode . "-" . $route->arrCode
                                . " " . date("Y-m-d H:i", $route->depDate) . "-" . date("Y-m-d H:i", $route->arrDate)
                                . " " . $route->flightNumber;
                        })
                        ->joinToString(", "),
                    "DurationInMinutes" => round($priceWithInfo->duration / 60),
                    "Duration" => TimeDiff::format($priceWithInfo->duration),
                ];

                if ($csv) {
                    $row['URL'] = $priceWithInfo->price->bookingURL;
                } else {
                    $row['Price'] = "<a target='_blank' href='{$priceWithInfo->price->bookingURL}'>{$row['Price']}</a>";
                }

                return $row;
            })
            ->toArray()
        ;

        if (count($rows) === 0) {
            return new Response('No price history found');
        }

        if ($csv) {
            $file = fopen("php://memory", "wb+");
            fputcsv($file, array_keys($rows[0]));

            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
            $length = ftell($file);
            fseek($file, 0);
            $content = fread($file, $length);
            fclose($file);

            return new Response(
                $content,
                200,
                [
                    "Content-type" => "text/csv; charset=utf-8",
                    "Content-Disposition" => "attachment; filename=prices-{$tripId}.csv",
                ]
            );
        }

        return new Response($twig->render('@AwardWalletMain/Manager/MileValue/priceHistory.html.twig', [
            'columns' => array_keys($rows[0]),
            'rows' => $rows,
            'tripId' => $tripId,
        ]));
    }
}
