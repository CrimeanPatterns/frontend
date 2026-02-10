<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Form\Handler\FormHandlerHelper;
use AwardWallet\MainBundle\Form\Model\DocumentModel;

class DocumentTransformer extends AbstractModelTransformer
{
    /**
     * @var string[]
     */
    private $properties;
    /**
     * @var FormHandlerHelper
     */
    private $formHandlerHelper;

    public function __construct(FormHandlerHelper $formHandlerHelper)
    {
        $this->properties = [
            'owner',
            'expirationDate',
            'isArchived',
            'description',
        ];
        $this->formHandlerHelper = $formHandlerHelper;
    }

    /**
     * @param Providercoupon $document
     * @return DocumentModel
     */
    public function transform($document)
    {
        if ($document->getTypeid() === Providercoupon::TYPE_PASSPORT) {
            $travelerProfile = array_merge([
                Providercoupon::FIELD_KEY_PASSPORT => [
                    'name' => null,
                    'number' => null,
                    'issueDate' => null,
                    'country' => null,
                ],
            ], $document->getCustomFields());

            $model = (new DocumentModel())
                ->setPassportName($travelerProfile['passport']['name'])
                ->setPassportNumber($travelerProfile['passport']['number'])
                ->setPassportIssueDate($this->convertDate($travelerProfile['passport']['issueDate']))
                ->setPassportCountry($travelerProfile['passport']['country']);
        } elseif (Providercoupon::TYPE_VACCINE_CARD === $document->getTypeid()) {
            $vaccineCard = array_merge(
                [
                    'disease' => null,
                    'firstDoseDate' => null,
                    'firstDoseVaccine' => null,
                    'secondDoseDate' => null,
                    'secondDoseVaccine' => null,
                    'boosterDate' => null,
                    'boosterVaccine' => null,
                    'secondBoosterDate' => null,
                    'secondBoosterVaccine' => null,
                    'passportName' => null,
                    'dateOfBirth' => null,
                    'passportNumber' => null,
                    'certificateIssued' => null,
                    'countryIssue' => null,
                ],
                $document->getCustomFields()[Providercoupon::FIELD_KEY_VACCINE_CARD] ?? []
            );
            $data[Providercoupon::FIELD_KEY_VACCINE_CARD] = $vaccineCard;

            $model = (new DocumentModel())
                ->setDisease($vaccineCard['disease'])
                ->setFirstDoseDate($this->convertDate($vaccineCard['firstDoseDate']))
                ->setFirstDoseVaccine($vaccineCard['firstDoseVaccine'])
                ->setSecondDoseDate($this->convertDate($vaccineCard['secondDoseDate']))
                ->setSecondDoseVaccine($vaccineCard['secondDoseVaccine'])
                ->setBoosterDate($this->convertDate($vaccineCard['boosterDate']))
                ->setBoosterVaccine($vaccineCard['boosterVaccine'])
                ->setSecondBoosterDate($this->convertDate($vaccineCard['secondBoosterDate']))
                ->setSecondBoosterVaccine($vaccineCard['secondBoosterVaccine'])
                ->setVaccinePassportName($vaccineCard['passportName'])
                ->setDateOfBirth($this->convertDate($vaccineCard['dateOfBirth']))
                ->setVaccinePassportNumber($vaccineCard['passportNumber'])
                ->setCertificateIssued($this->convertDate($vaccineCard['certificateIssued']))
                ->setCountryIssue($vaccineCard['countryIssue']);
        } elseif (Providercoupon::TYPE_INSURANCE_CARD === $document->getTypeid()) {
            $data = array_merge([
                Providercoupon::FIELD_KEY_INSURANCE_CARD => [
                    'insuranceType' => null,
                    'insuranceCompany' => null,
                    'nameOnCard' => null,
                    'memberNumber' => null,
                    'groupNumber' => null,
                    'policyHolder' => null,
                    'insuranceType2' => null,
                    'effectiveDate' => null,
                    'memberServicePhone' => null,
                    'preauthPhone' => null,
                    'otherPhone' => null,
                ],
            ], $document->getCustomFields());

            $insuranceCard = $data[Providercoupon::FIELD_KEY_INSURANCE_CARD];
            $model = (new DocumentModel())
                ->setInsuranceType($insuranceCard['insuranceType'])
                ->setInsuranceCompany($insuranceCard['insuranceCompany'])
                ->setNameOnCard($insuranceCard['nameOnCard'])
                ->setMemberNumber($insuranceCard['memberNumber'])
                ->setGroupNumber($insuranceCard['groupNumber'])
                ->setPolicyHolder($insuranceCard['policyHolder'])
                ->setInsuranceType2($insuranceCard['insuranceType2'])
                ->setEffectiveDate($this->convertDate($insuranceCard['effectiveDate']))
                ->setMemberServicePhone($insuranceCard['memberServicePhone'])
                ->setPreauthPhone($insuranceCard['preauthPhone'])
                ->setOtherPhone($insuranceCard['otherPhone']);
        } elseif (Providercoupon::TYPE_VISA === $document->getTypeid()) {
            $data = array_merge([
                Providercoupon::FIELD_KEY_VISA => [
                    'countryVisa' => null,
                    'numberEntries' => null,
                    'fullName' => null,
                    'issueDate' => null,
                    'validFrom' => null,
                    'visaNumber' => null,
                    'category' => null,
                    'durationInDays' => null,
                    'issuedIn' => null,
                ],
            ], $document->getCustomFields());

            $visa = $data[Providercoupon::FIELD_KEY_VISA];
            $model = (new DocumentModel())
                ->setCountryVisa($visa['countryVisa'])
                ->setNumberEntries($visa['numberEntries'])
                ->setFullName($visa['fullName'])
                ->setIssueDate($this->convertDate($visa['issueDate']))
                ->setValidFrom($this->convertDate($visa['validFrom']))
                ->setVisaNumber($visa['visaNumber'])
                ->setCategory($visa['category'])
                ->setDurationInDays($visa['durationInDays'])
                ->setIssuedIn($visa['issuedIn']);
        } elseif (Providercoupon::TYPE_DRIVERS_LICENSE === $document->getTypeid()) {
            $data = array_merge([
                Providercoupon::FIELD_KEY_DRIVERS_LICENSE => [
                    'country' => null,
                    'state' => null,
                    'internationalLicense' => null,
                    'licenseNumber' => null,
                    'dateOfBirth' => null,
                    'issueDate' => null,
                    'expirationDate' => null,
                    'fullName' => null,
                    'sex' => null,
                    'eyes' => null,
                    'height' => null,
                    'class' => null,
                    'organDonor' => null,
                ],
            ], $document->getCustomFields());

            $driverLicense = $data[Providercoupon::FIELD_KEY_DRIVERS_LICENSE];
            $model = (new DocumentModel())
                ->setCountry($driverLicense['country'])
                ->setState($driverLicense['state'])
                ->setInternationalLicense($driverLicense['internationalLicense'])
                ->setLicenseNumber($driverLicense['licenseNumber'])
                ->setDateOfBirth($this->convertDate($driverLicense['dateOfBirth']))
                ->setIssueDate($this->convertDate($driverLicense['issueDate']))
                ->setExpirationDate($this->convertDate($driverLicense['expirationDate']))
                ->setFullName($driverLicense['fullName'])
                ->setSex($driverLicense['sex'])
                ->setEyes($driverLicense['eyes'])
                ->setHeight($driverLicense['height'])
                ->setClass($driverLicense['class'])
                ->setOrganDonor($driverLicense['organDonor']);
        } elseif (Providercoupon::TYPE_PRIORITY_PASS === $document->getTypeid()) {
            $data = array_merge([
                Providercoupon::FIELD_KEY_PRIORITY_PASS => [
                    'accountNumber' => null,
                    'expirationDate' => null,
                    'isSelect' => null,
                    'creditCardId' => null,
                ],
            ], $document->getCustomFields());

            $priorityPass = $data[Providercoupon::FIELD_KEY_PRIORITY_PASS];
            $model = (new DocumentModel())
                ->setAccountNumber($priorityPass['accountNumber'])
                ->setExpirationDate($this->convertDate($priorityPass['expirationDate']))
                ->setIsSelect($priorityPass['isSelect'])
                ->setCreditCardId($priorityPass['creditCardId']);
        } else {
            $travelerProfile = array_merge([
                Providercoupon::FIELD_KEY_TRUSTED_TRAVELER => [
                    'travelerNumber' => null,
                ],
            ], $document->getCustomFields());

            $model = (new DocumentModel())->setTravelerNumber($travelerProfile[Providercoupon::FIELD_KEY_TRUSTED_TRAVELER]['travelerNumber']);
        }

        $this->formHandlerHelper->copyProperties($document, $model, [
            'useragents',
        ]);

        return $model
            ->setExpirationDate($document->getExpirationdate())
            ->setIsArchived($document->getIsArchived())
            ->setDescription($document->getDescription())
            ->setOwner($document->getOwner())
            ->setEntity($document);
    }

    /**
     * @return string[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    private function convertDate($value)
    {
        if (is_array($value) && array_key_exists('date', $value)) {
            return new \DateTime($value['date']);
        }

        return $value;
    }
}
