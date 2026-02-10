<?php

namespace AwardWallet\MainBundle\Form\Handler\Subscriber;

use AwardWallet\MainBundle\Entity\Repositories\TimelineShareRepository;
use AwardWallet\MainBundle\Entity\TimelineShare;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Form\Handler\FormHandlerHelper;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\Form\Model\UserConnectionModel;
use AwardWallet\MainBundle\Form\Transformer\SharedTimelinesTransformerFactory;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Manager\AccountManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class EditConnectionGeneric implements EventSubscriberInterface
{
    /**
     * @var TimelineShareRepository
     */
    private $timelineShareRepository;
    /**
     * @var FormHandlerHelper
     */
    private $formHandlerHelper;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var AccountManager
     */
    private $accountManager;
    /**
     * @var AwTokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(
        FormHandlerHelper $formHandlerHelper,
        TimelineShareRepository $timelineShareRepository,
        EntityManagerInterface $entityManager,
        AccountManager $accountManager,
        AwTokenStorageInterface $awTokenStorage
    ) {
        $this->timelineShareRepository = $timelineShareRepository;
        $this->formHandlerHelper = $formHandlerHelper;
        $this->entityManager = $entityManager;
        $this->accountManager = $accountManager;
        $this->tokenStorage = $awTokenStorage;
    }

    public static function getSubscribedEvents()
    {
        return [
            'form.generic.edit_connection.pre_handle' => ['preHandle'],
            'form.generic.edit_connection.on_valid' => ['onValid'],
        ];
    }

    public function preHandle(HandlerEvent $event)
    {
        $form = $event->getForm();
        $request = $event->getRequest();

        if ($this->formHandlerHelper->isSubmitted($form, $request)) {
            $this->formHandlerHelper->throwIfImpersonated();
        }
    }

    public function onValid(HandlerEvent $event)
    {
        $form = $event->getForm();
        /** @var UserConnectionModel $data */
        $data = $form->getData();
        /** @var Useragent $useragent */
        $useragent = $data->getEntity();

        $sharedTimelinesById =
            it($useragent->getSharedTimelines()->getValues())
            ->reindex(function (TimelineShare $timelineShare) {
                $fm = $timelineShare->getFamilyMember();

                return $fm ? $fm->getUseragentid() : 'my';
            })
            ->toArrayWithKeys();

        $sharingTimelinesChoices =
            it([$user = $useragent->getClientid()])
            ->chain($user->getFamilyMembers())
            ->reindex(function (object $object) {
                return SharedTimelinesTransformerFactory::generateId($object);
            })
            ->toArrayWithKeys();

        $shareHandler = function ($id, bool $selected) use ($sharedTimelinesById, $sharingTimelinesChoices, $useragent) {
            if ($selected) {
                if (!isset($sharedTimelinesById[$id])) {
                    $this->timelineShareRepository->addTimelineShare(
                        $useragent,
                        ('my' === $id) ? null : $sharingTimelinesChoices[$id]
                    );
                }
            } else {
                if (isset($sharedTimelinesById[$id])) {
                    /** @var TimelineShare $timelineShare */
                    $timelineShare = $sharedTimelinesById[$id];
                    $this->timelineShareRepository->removeTimelineShareByID($timelineShare->getTimelineShareId());
                }
            }
        };

        $useragent
            ->setSharebydefault($data->getSharebydefault())
            ->setAccesslevel($data->getAccesslevel())
            ->setTripsharebydefault($data->getTripsharebydefault())
            ->setTripAccessLevel($data->getTripAccessLevel());

        $selectedSharedTimelines = $data->getSharedTimelines();

        if ($selectedSharedTimelines) {
            if (\is_array(\current($selectedSharedTimelines))) {
                foreach ($selectedSharedTimelines as $id => [$_, $selected]) {
                    $shareHandler($id, $selected);
                }
            } else {
                foreach ($selectedSharedTimelines as $id) {
                    $shareHandler($id, true);
                }

                foreach (
                    // excluded
                    \array_diff_key(
                        $sharingTimelinesChoices,
                        \array_flip($selectedSharedTimelines)
                    ) as $id => $_
                ) {
                    $shareHandler($id, false);
                }
            }
        }

        $this->entityManager->flush($useragent);
        $this->accountManager->storeLocalPasswords($this->tokenStorage->getBusinessUser());
    }
}
