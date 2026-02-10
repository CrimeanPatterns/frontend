<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Service\TravelSummary\Formatter\MobileFormatter;
use AwardWallet\MainBundle\Service\TravelSummary\PeriodDatesHelper;
use AwardWallet\MainBundle\Service\TravelSummary\TravelSummaryService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @Route("/timeline/summary")
 */
class TravelSummaryController extends AbstractController
{
    /**
     * @var TravelSummaryService
     */
    private $travelSummary;
    /**
     * @var MobileFormatter
     */
    private $mobileFormatter;
    /**
     * @var AwTokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    private PeriodDatesHelper $periodDatesHelper;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        TravelSummaryService $travelSummary,
        MobileFormatter $mobileFormatter,
        TranslatorInterface $translator,
        PeriodDatesHelper $periodDatesHelper
    ) {
        $this->travelSummary = $travelSummary;
        $this->mobileFormatter = $mobileFormatter;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->periodDatesHelper = $periodDatesHelper;
    }

    /**
     * @Route("/data", name="awm_travel_summary_data", methods={"POST"})
     * @Security("is_granted('CSRF')")
     * @JsonDecode
     */
    public function dataAction(Request $request): JsonResponse
    {
        $period = $request->request->get('period');
        $owner = $request->request->get('owner');

        if (
            !\is_int($period)
            || (!\is_null($owner) && !\is_int($owner))
        ) {
            throw new BadRequestHttpException();
        }

        $user = $this->tokenStorage->getUser();
        $agents = $this->travelSummary->buildAvailableUserAgents($user);
        $userAgent = null;

        if ($owner && isset($agents[$owner])) {
            $userAgent = $agents[$owner];
        }

        if (
            !in_array($period, [PeriodDatesHelper::YEAR_TO_DATE, PeriodDatesHelper::LAST_YEAR])
            && (true !== $user->isAwPlus())
        ) {
            return new JsonResponse(\array_merge(
                ['needAwPlus' => true],
                $this->mobileFormatter->getStubData($user, $userAgent, $period),
                $this->getFormData($agents, $user)
            ));
        }

        return new JsonResponse(
            \array_merge(
                $this->mobileFormatter->format($user, $userAgent, $period),
                $this->getFormData($agents, $user)
            )
        );
    }

    /**
     * @param Useragent|string $agents
     */
    protected function getFormData(array $agents, Usr $user): array
    {
        return [
            'owners' => it($agents)
                ->mapIndexed(function (/** @var Useragent|string $agent */ $agent, $key) {
                    return \is_string($agent) ? [null, $agent] : [$key, $agent->getFullName()];
                })
                ->toArray(),
            'periods' => it($this->periodDatesHelper->getAvailablePeriods($user))
                ->toPairs()
                ->toArray(),
        ];
    }
}
