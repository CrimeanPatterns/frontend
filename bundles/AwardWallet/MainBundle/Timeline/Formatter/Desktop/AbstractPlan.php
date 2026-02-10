<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Desktop;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Manager\Files\PlanFileManager;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractPlan extends AbstractItem
{
    protected AuthorizationCheckerInterface $authorizationChecker;
    protected LocalizeService $localizeService;
    protected TranslatorInterface $translator;
    protected DateTimeIntervalFormatter $intervalFormatter;
    protected PlanFileManager $planFileManager;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        LocalizeService $localizeService,
        TranslatorInterface $translator,
        DateTimeIntervalFormatter $intervalFormatter,
        PlanFileManager $planFileManager
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->localizeService = $localizeService;
        $this->translator = $translator;
        $this->intervalFormatter = $intervalFormatter;
        $this->planFileManager = $planFileManager;
    }
}
