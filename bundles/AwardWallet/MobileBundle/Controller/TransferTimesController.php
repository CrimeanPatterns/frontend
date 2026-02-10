<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Entity\BalanceWatch;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\MobileFormatter\Desanitizer;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\TransferTimes;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @Route("/mile-transfers")
 */
class TransferTimesController extends AbstractController
{
    use JsonTrait;

    private Desanitizer $desanitizer;
    private TranslatorInterface $translator;
    private TransferTimes $transferTimes;
    private ApiVersioningService $apiVersioning;

    public function __construct(
        Desanitizer $desanitizer,
        TranslatorInterface $translator,
        TransferTimes $transferTimes,
        ApiVersioningService $apiVersioning,
        LocalizeService $localizeService
    ) {
        $localizeService->setRegionalSettings();
        $this->desanitizer = $desanitizer;
        $this->translator = $translator;
        $this->transferTimes = $transferTimes;
        $this->apiVersioning = $apiVersioning;
    }

    /**
     * @Route("/data/{source}",
     *     name="awm_mile_transfers_data",
     *     methods={"POST"},
     *     requirements={"source" = "transfer|purchase"}
     * )
     * @Security("is_granted('CSRF')")
     */
    public function dataAction(Request $request, string $source): JsonResponse
    {
        $desanitizer = fn ($value) => $this->desanitizer->tryDesanitize($value, Desanitizer::TAGS | Desanitizer::CHARS);

        $rowFormatter = function (array $row) use ($desanitizer) {
            $data = [
                'program' => $desanitizer($row['ProviderName']),
                'duration' => $row['TransferDuration'],
            ];

            $tip = null;

            if (StringUtils::isNotEmpty($row['TransferRatio'] ?? '')) {
                $tip = $row['TransferRatio'];
            }

            if (isset($tip) && StringUtils::isNotEmpty($row['MinimumTransfer'] ?? '')) {
                $tip .= ' (' . $this->translator->trans(/** @Desc("Min. %minimum%") */ 'minimum_short', ['%minimum%' => $row['MinimumTransfer']]) . ')';
            }

            if (isset($tip)) {
                $data['tip'] = $tip;
            }

            if (StringUtils::isNotEmpty($row['Bonus'] ?? '')) {
                $data['info'] = $row['Bonus'];
            }

            return $data;
        };

        $isToKey = $this->apiVersioning->supports(MobileVersions::TRANSFER_TIMES_ROWS_TO_KEY);

        if ('transfer' === $source) {
            return $this->jsonResponse(
                it($this->transferTimes->getData(BalanceWatch::POINTS_SOURCE_TRANSFER)['data'])
                ->reindexByColumn('ProviderFromName')
                ->map($rowFormatter)
                ->collapseByKey()
                ->mapIndexed(fn (array $toRows, string $key) => [
                    'program' => $desanitizer($key),
                    $isToKey ? 'to' : 'data' => $toRows,
                ])
                ->toArray()
            );
        } else {
            // purchase
            return $this->jsonResponse(
                it($this->transferTimes->getData(BalanceWatch::POINTS_SOURCE_PURCHASE)['data'])
                ->map($rowFormatter)
                ->toArray()
            );
        }
    }
}
