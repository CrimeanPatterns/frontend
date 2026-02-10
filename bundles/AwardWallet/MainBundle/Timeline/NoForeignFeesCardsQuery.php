<?php

namespace AwardWallet\MainBundle\Timeline;

use AwardWallet\MainBundle\Entity\UserCreditCard;
use AwardWallet\MainBundle\Globals\StringHandler;
use Doctrine\ORM\EntityManagerInterface;

class NoForeignFeesCardsQuery
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function getCards(int $userId, bool $isSupCopyright = true): ?array
    {
        $business = $personal = [];

        $qb = $this->entityManager->createQueryBuilder('cards');
        $qb->select('ucc')
            ->from(UserCreditCard::class, 'ucc')
            ->join('ucc.creditCard', 'cc')
            ->andWhere('ucc.user = :userId')->setParameter('userId', $userId)
            ->andWhere('ucc.isClosed = 0')
            ->andWhere("cc.foreignTransactionFee = 0.0")
            ->andWhere("cc.pictureExt is not null")
            ->andWhere("cc.pictureVer is not null")
            ->andWhere("cc.pictureExt != ''")
            ->andWhere("cc.pictureVer != ''")
        ;
        $rows = $qb->getQuery()->getResult();

        /** @var UserCreditCard $row */
        foreach ($rows as $row) {
            $card = $row->getCreditCard();

            $data = [
                'id' => $card->getId(),
                'name' => $card->getCardFullName() ?? $card->getName(),
                'image' => $card->getPicturePath('medium'),
            ];

            if ($isSupCopyright) {
                $data['name'] = StringHandler::supCopyrightSymbols($data['name']);
            }

            if ($card->isBusiness()) {
                $business[] = $data;
            } else {
                $personal[] = $data;
            }
        }

        if (empty($business) && empty($personal)) {
            return null;
        }

        if (!empty($business) && !empty($personal)) {
            return [
                'isFlat' => false,
                'business' => $business,
                'personal' => $personal,
            ];
        }

        return [
            'isFlat' => true,
            'list' => ($list = ($business ?: $personal)),
            'isOne' => 1 === count($list),
            // 'isBusiness' => !empty($business),
            // 'isPersonal' => !empty($personal),
        ];
    }
}
