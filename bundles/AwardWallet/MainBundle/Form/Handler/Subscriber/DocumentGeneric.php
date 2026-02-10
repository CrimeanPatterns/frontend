<?php

namespace AwardWallet\MainBundle\Form\Handler\Subscriber;

use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Form\Handler\FormHandlerHelper;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\Form\Model\DocumentModel;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DocumentGeneric implements EventSubscriberInterface
{
    /**
     * @var FormHandlerHelper
     */
    private $formHandlerHelper;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var AwTokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(
        FormHandlerHelper $formHandlerHelper,
        EntityManagerInterface $entityManager,
        AwTokenStorageInterface $tokenStorage
    ) {
        $this->formHandlerHelper = $formHandlerHelper;
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
    }

    public static function getSubscribedEvents()
    {
        return [
            'form.generic.document.on_valid' => ['onValid'],
        ];
    }

    public function onValid(HandlerEvent $event)
    {
        $form = $event->getForm();
        /** @var DocumentModel $model */
        $model = $form->getNormData();
        $request = $event->getRequest();
        /** @var Providercoupon $document */
        $document = $model->getEntity();

        if ($this->formHandlerHelper->isSubmitted($form, $request)) {
            $this->formHandlerHelper->throwIfImpersonated();
        }

        $document->setOwner($model->getOwner());
        $document->setExpirationdate($model->getExpirationDate());
        $document->setIsArchived($model->getIsArchived());
        $document->setDescription($model->getDescription());
        $document->setUseragents($model->getUseragents());

        if ($document->getTypeid() === Providercoupon::TYPE_PASSPORT) {
            $document->setCustomFields([
                'passport' => [
                    'name' => $model->getPassportName(),
                    'number' => $model->getPassportNumber(),
                    'issueDate' => $model->getPassportIssueDate(),
                    'country' => $model->getPassportCountry(),
                ],
            ]);
        } elseif ($document->getTypeid() === Providercoupon::TYPE_TRUSTED_TRAVELER) {
            $document->setCustomFields([
                Providercoupon::FIELD_KEY_TRUSTED_TRAVELER => [
                    'travelerNumber' => $model->getTravelerNumber(),
                ],
            ]);
        } elseif (Providercoupon::TYPE_VACCINE_CARD === $document->getTypeid()) {
            $document->setCustomFields([
                Providercoupon::FIELD_KEY_VACCINE_CARD => [
                    'disease' => $model->getDisease(),
                    'firstDoseDate' => $model->getFirstDoseDate(),
                    'firstDoseVaccine' => $model->getFirstDoseVaccine(),
                    'secondDoseDate' => $model->getSecondDoseDate(),
                    'secondDoseVaccine' => $model->getSecondDoseVaccine(),
                    'boosterDate' => $model->getBoosterDate(),
                    'boosterVaccine' => $model->getBoosterVaccine(),
                    'secondBoosterDate' => $model->getSecondBoosterDate(),
                    'secondBoosterVaccine' => $model->getSecondBoosterVaccine(),
                    'passportName' => $model->getVaccinePassportName(),
                    'dateOfBirth' => $model->getDateOfBirth(),
                    'passportNumber' => $model->getVaccinePassportNumber(),
                    'certificateIssued' => $model->getCertificateIssued(),
                    'countryIssue' => $model->getCountryIssue(),
                ],
            ]);
        } elseif (Providercoupon::TYPE_INSURANCE_CARD === $document->getTypeid()) {
            $document->setCustomFields([
                Providercoupon::FIELD_KEY_INSURANCE_CARD => [
                    'insuranceType' => $model->getInsuranceType(),
                    'insuranceCompany' => $model->getInsuranceCompany(),
                    'nameOnCard' => $model->getNameOnCard(),
                    'memberNumber' => $model->getMemberNumber(),
                    'groupNumber' => $model->getGroupNumber(),
                    'policyHolder' => $model->getPolicyHolder(),
                    'insuranceType2' => $model->getInsuranceType2(),
                    'effectiveDate' => $model->getEffectiveDate(),
                    'memberServicePhone' => $model->getMemberServicePhone(),
                    'preauthPhone' => $model->getPreauthPhone(),
                    'otherPhone' => $model->getOtherPhone(),
                ],
            ]);
        } elseif (Providercoupon::TYPE_VISA === $document->getTypeid()) {
            $document->setCustomFields([
                Providercoupon::FIELD_KEY_VISA => [
                    'countryVisa' => $model->getCountryVisa(),
                    'numberEntries' => $model->getNumberEntries(),
                    'fullName' => $model->getFullName(),
                    'issueDate' => $model->getIssueDate(),
                    'validFrom' => $model->getValidFrom(),
                    'visaNumber' => $model->getVisaNumber(),
                    'category' => $model->getCategory(),
                    'durationInDays' => $model->getDurationInDays(),
                    'issuedIn' => $model->getIssuedIn(),
                ],
            ]);
        } elseif (Providercoupon::TYPE_DRIVERS_LICENSE === $document->getTypeid()) {
            $document->setCustomFields([
                Providercoupon::FIELD_KEY_DRIVERS_LICENSE => [
                    'country' => $model->getCountry(),
                    'state' => $model->getState(),
                    'internationalLicense' => $model->isInternationalLicense(),
                    'licenseNumber' => $model->getLicenseNumber(),
                    'dateOfBirth' => $model->getDateOfBirth(),
                    'issueDate' => $model->getIssueDate(),
                    'expirationDate' => $model->getExpirationDate(),
                    'fullName' => $model->getFullName(),
                    'sex' => $model->getSex(),
                    'eyes' => $model->getEyes(),
                    'height' => $model->getHeight(),
                    'class' => $model->getClass(),
                    'organDonor' => $model->isOrganDonor(),
                ],
            ]);
        } elseif (Providercoupon::TYPE_PRIORITY_PASS === $document->getTypeid()) {
            $document->setCustomFields([
                Providercoupon::FIELD_KEY_PRIORITY_PASS => [
                    'accountNumber' => $model->getAccountNumber(),
                    'expirationDate' => $model->getExpirationDate(),
                    'isSelect' => $model->getIsSelect(),
                    'creditCardId' => $model->getCreditCardId(),
                ],
            ]);
        }

        if (!$document->getProvidercouponid()) {
            $document->setProgramname($document->getDocumentTypeName());
            $document->setKind(PROVIDER_KIND_DOCUMENT);
            $this->entityManager->persist($document);
        }

        $connection = $this->tokenStorage->getBusinessUser()->getConnectionWith($document->getOwner()->getUser());

        if (null !== $connection && !$document->getOwner()->isBusiness()) {
            $document->addUserAgent($connection);
        }

        $this->entityManager->flush();
    }
}
