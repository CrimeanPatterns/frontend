<?php

namespace AwardWallet\MainBundle\Service\EmailTemplate\DataProvider;

use AwardWallet\MainBundle\Entity\EmailTemplate;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\SkipException;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\Manager\OfferManager;
use AwardWallet\MainBundle\Service\EmailTemplate\AbstractFailTolerantDataProvider;
use AwardWallet\MainBundle\Service\EmailTemplate\Options;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class DataUsWithAirCanadaStatusMatch extends AbstractFailTolerantDataProvider
{
    private const OFFER_ID = 44;

    /** @var OfferManager */
    private $offerManager;

    /** @var Request */
    private $request;

    /** @var EntityManager */
    private $entityManager;

    /** @var UsrRepository */
    private $userRepository;

    public function __construct(ContainerInterface $container, EmailTemplate $template)
    {
        parent::__construct($container, $template);
        $this->offerManager = $container->get('aw.manager.offer');
        $this->request = new Request();
        $this->entityManager = $this->container->get('doctrine.orm.entity_manager');
        $this->userRepository = $this->entityManager->getRepository(Usr::class);
    }

    public function getQueryOptions()
    {
        $options = parent::getQueryOptions();
        array_walk($options, static function (Options $option) {
            $option->offerIdUsers = self::OFFER_ID;
        });

        return $options;
    }

    public function getDescription(): string
    {
        return 'Users from US, with airline accounts: AA; Alaska; Delta; Hawaiian; Southwest and updated this year';
    }

    public function getTitle(): string
    {
        return 'US users with AirCanada status match Offer';
    }

    protected function renderTemplateParts(AbstractTemplate $template): void
    {
        $user = $this->userRepository->find($this->fields['UserID']);
        $offerUserId = (int) $this->entityManager->getConnection()->fetchOne(
            'SELECT OfferUserID FROM OfferUser WHERE OfferID = :offerId AND UserID = :userId',
            ['offerId' => self::OFFER_ID, 'userId' => $user->getId()],
            ['offerId' => \PDO::PARAM_INT, 'userId' => \PDO::PARAM_INT]
        );

        $data = $this->offerManager->getOfferData($offerUserId, $this->request, $user);

        if (null === $data) {
            throw new SkipException('Error data: offerManager->getOfferData ');
        } else {
            $data = array_merge($data, $this->providerSpecificText($data));

            foreach ($data as $key => $value) {
                $this->fields[$key] = $value;
            }

            $requiredFields = [
                'redirectUrl',
                'upgradeProgram',
                'matchProgram',
                'matchStatus',
                'providerId',
                'accountId',
            ];

            foreach ($requiredFields as $key) {
                if (empty($this->fields[$key])) {
                    throw new SkipException('Error fetch fields');
                }
            }
        }

        parent::renderTemplateParts($template);
    }

    private function providerSpecificText(array $data): array
    {
        $userFields = [
            'Name:' => $data['fullName'],
            'Email:' => '<a href="mailto:' . $data['email'] . '" style="color:#4684c4;text-decoration:none;">' . $data['email'] . '</a>',
        ];

        if (Provider::AA_ID === (int) $data['providerId']) {
            $matchProgramText = '<b style="color:#4684c4">' . $data['upgradeProgram'] . '</b> elite level.';
        } else {
            $matchProgramText = 'elite level because you have <b style="color:#4684c4">' . $data['matchProgram'] . ' ' . $data['matchStatus'] . '</b> status.';

            if (!empty($data['member_id'])) {
                $userFields['Member ID:'] = $data['member_id'];
            }
            $userFields['Elite Status to Match:'] = trim($data['matchProgram'] . ', ' . $data['matchStatus']);
        }

        if (!empty($data['existsAirCanadaId'])) {
            $userFields['Current Aeroplan Number:'] = $data['existsAirCanadaId'];
        }

        $matchUserFields = '<table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-top: solid 2px #bdc2cc;border-spacing: 0;border-collapse: collapse;"><tbody>';
        $tdStyle = 'padding: 6px 15px 6px 0;font-size:13px;font-family: \'Open Sans\', Arial, sans-serif;color:#535457;border-bottom: solid 1px #bdc2cc;';

        foreach ($userFields as $title => $value) {
            $matchUserFields .= '<tr>
                <td style="' . $tdStyle . '">' . $title . '</td>
                <td style="' . $tdStyle . '"><b>' . $value . '</b></td>
            </tr>';
        }
        $matchUserFields .= '</tbody></table>';

        if (false !== stripos($data['upgradeProgram'], '50K')
            || false !== stripos($data['upgradeProgram'], '75K')
            || false !== stripos($data['upgradeProgram'], 'Super Elite')
        ) {
            $eliteLevelText = '<b style="color:#4684c4">' . $data['upgradeProgram'] . '</b> elite level gives you <b>complimentary access to Star Alliance Gold Lounges</b> around the world and gives you the ability to invite one guest at no charge into eligible Star Alliance lounges if your guest is traveling on the same flight with you.';
        }

        return [
            'matchProgramText' => $matchProgramText,
            'matchUserFields' => $matchUserFields,
            'eliteLevelText' => $eliteLevelText ?? '',
        ];
    }
}
