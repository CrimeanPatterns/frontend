<?php

namespace AwardWallet\MainBundle\Service\Lounge\Schema;

use AwardWallet\MainBundle\Entity\Lounge;
use AwardWallet\MainBundle\Entity\LoungeAction;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Service\EnhancedAdmin\AbstractEnhancedSchema;
use AwardWallet\MainBundle\Service\EnhancedAdmin\ActionInterface;
use AwardWallet\MainBundle\Service\EnhancedAdmin\EditActionInterface;
use AwardWallet\MainBundle\Service\EnhancedAdmin\FormRenderer;
use AwardWallet\MainBundle\Service\EnhancedAdmin\PageRenderer;
use AwardWallet\MainBundle\Service\Lounge\Action\FreezeAction;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\RawOpeningHours;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\StructuredOpeningHours;
use AwardWallet\MainBundle\Service\Lounge\Schema\Form\LoungeModel;
use AwardWallet\MainBundle\Service\Lounge\Schema\Form\LoungeType;
use Doctrine\ORM\EntityManagerInterface;
use Spatie\OpeningHours\Exceptions\Exception;
use Spatie\OpeningHours\Exceptions\InvalidTimezone;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LoungeEnhancedSchema implements EditActionInterface, ActionInterface
{
    private EntityManagerInterface $em;

    private FormFactoryInterface $formFactory;

    private AwTokenStorageInterface $tokenStorage;

    public function __construct(
        EntityManagerInterface $em,
        FormFactoryInterface $formFactory,
        AwTokenStorageInterface $tokenStorage
    ) {
        $this->em = $em;
        $this->formFactory = $formFactory;
        $this->tokenStorage = $tokenStorage;
    }

    public static function getSchema(): string
    {
        return 'Lounge';
    }

    public function editAction(Request $request, FormRenderer $renderer, ?int $id = null): Response
    {
        if (is_null($id)) {
            throw new NotFoundHttpException();
        }

        $lounge = $this->em->getRepository(Lounge::class)->find($id);

        if (!$lounge) {
            throw new NotFoundHttpException(sprintf('Lounge #%d not found', $id));
        }

        $form = $this->formFactory->create(LoungeType::class, $lounge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var LoungeModel $data */
            $data = $form->getData();

            // Opening hours
            $ohForm = $form->get('openingHours');
            $oh = $data->getOpeningHours();
            $isRawOpeningHours = $data->getIsRawOpeningHours();

            if ($isRawOpeningHours) {
                $newOh = new RawOpeningHours($oh);
            } else {
                $loungeOh = $lounge->getOpeningHours();
                $loungeTimezone = $loungeOh instanceof StructuredOpeningHours ? $loungeOh->getTz() : null;
                $isInvalidJson = false;
                $newOh = null;

                try {
                    $newJson = json_decode($oh, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    $newJson = null;

                    if (strpos($oh, '{') === 0) {
                        $isInvalidJson = true;
                    }
                }

                if ($isInvalidJson) {
                    $ohForm->addError(new FormError('Invalid JSON'));
                } else {
                    if ($newJson) {
                        if (empty($loungeTimezone)) {
                            $loungeTimezone = $this->em->getConnection()->fetchOne(
                                "SELECT TimeZoneLocation FROM AirCode WHERE AirCode = ?",
                                [$lounge->getAirportCode()]
                            );

                            if (empty($loungeTimezone)) {
                                $loungeTimezone = 'UTC';
                            }
                        }

                        try {
                            $newOh = new StructuredOpeningHours($loungeTimezone, $newJson);
                            $newOh->build();
                        } catch (Exception $e) {
                            $ohForm->addError(new FormError($e->getMessage()));
                        } catch (InvalidTimezone $e) {
                            $ohForm->addError(new FormError($e->getMessage()));
                        }
                    } elseif (!empty($oh)) {
                        $newOh = new RawOpeningHours($oh);
                    }
                }
            }

            if ($form->isValid()) {
                $user = $this->tokenStorage->getUser();

                if (is_null($user)) {
                    throw new \RuntimeException('User is null');
                }

                // Actions
                $actions = $lounge->getActions();
                $freezeAction = $data->getFreezeAction();
                /** @var LoungeAction $freezeActionEntity */
                $freezeActionEntity = $freezeAction->getEntity();
                $emails = array_filter(array_map('trim', explode(',', $freezeAction->getEmails())));

                if (!empty($freezeAction->getProps()) && \count($emails) > 0) {
                    $action = new FreezeAction($freezeAction->getProps(), $emails, false);

                    if (isset($freezeActionEntity)) {
                        $freezeActionEntity
                            ->setAction($action)
                            ->setUpdateDate(new \DateTime())
                            ->setDeleteDate($freezeAction->getDeleteDate());
                    } else {
                        $freezeActionEntity = (new LoungeAction())
                            ->setAction($action)
                            ->setCreateDate(new \DateTime())
                            ->setUpdateDate(new \DateTime())
                            ->setLounge($lounge)
                            ->setDeleteDate($freezeAction->getDeleteDate());

                        $actions->add($freezeActionEntity);
                    }
                } else {
                    if (isset($freezeActionEntity)) {
                        $actions->removeElement($freezeActionEntity);
                        $this->em->remove($freezeActionEntity);
                    }
                }

                $lounge
                    ->setName($data->getName())
                    ->setTerminal($data->getTerminal())
                    ->setGate($data->getGate())
                    ->setGate2($data->getGate2())
                    ->setOpeningHours($newOh)
                    ->setIsAvailable($data->getIsAvailable())
                    ->setPriorityPassAccess($data->getPriorityPassAccess())
                    ->setAmexPlatinumAccess($data->getAmexPlatinumAccess())
                    ->setDragonPassAccess($data->getDragonPassAccess())
                    ->setLoungeKeyAccess($data->getLoungeKeyAccess())
                    ->setLocation($data->getLocation())
                    ->setLocationParaphrased($data->getLocationParaphrased())
                    ->setAdditionalInfo($data->getAdditionalInfo())
                    ->setAmenities($data->getAmenities())
                    ->setRules($data->getRules())
                    ->setIsRestaurant($data->getIsRestaurant())
                    ->setUpdateDate(new \DateTime())
                    ->setCheckedBy($user)
                    ->setCheckedDate(new \DateTime())
                    ->setVisible($data->isVisible())
                    ->setAirlines($data->getAirlines())
                    ->setAlliances($data->getAlliances())
                    ->setActions($actions);

                if ($form->get('checked')->getData()) {
                    $lounge->setAttentionRequired(false);
                    $lounge->setState(null);
                }

                if ($form->get('removeOpeningHoursAi')->getData()) {
                    $lounge->setOpeningHoursAi(null);
                }

                $this->em->flush();

                if ($form->get('removeChanges')->getData()) {
                    $this->removeLoungeChanges($lounge->getId());
                }

                $backTo = $request->getSession()->get(AbstractEnhancedSchema::BACK_URL_SESSION_KEY);

                if (empty($backTo)) {
                    $backTo = sprintf('/manager/list.php?Schema=%s', self::getSchema());
                }

                return new RedirectResponse($backTo);
            }
        }

        return $renderer->render($form, true);
    }

    public function action(Request $request, PageRenderer $renderer, string $actionName): Response
    {
        $user = $this->tokenStorage->getUser();

        switch ($actionName) {
            case 'check-and-remove-changes':
                $loungesIds = $request->get('loungesIds');
                $loungesIds = array_filter(array_map(function ($id) {
                    if (!is_numeric($id)) {
                        return null;
                    }

                    return (int) $id;
                }, $loungesIds));

                $lounges = $this->em->getRepository(Lounge::class)->findBy(['id' => $loungesIds]);

                foreach ($lounges as $lounge) {
                    $lounge->setCheckedBy($user);
                    $lounge->setCheckedDate(new \DateTime());
                    $lounge->setAttentionRequired(false);
                    $lounge->setState(null);
                    $this->removeLoungeChanges($lounge->getId());
                }

                $this->em->flush();

                return new JsonResponse([
                    'success' => true,
                ]);

            default:
                throw new NotFoundHttpException();
        }
    }

    private function removeLoungeChanges(int $loungeId): void
    {
        $this->em->getConnection()->executeQuery(
            "
                DELETE FROM LoungeSourceChange 
                WHERE LoungeSourceID IN (
                    SELECT LoungeSourceID
                    FROM LoungeSource
                    WHERE LoungeID = ?
                )
            ",
            [$loungeId]
        );
    }
}
