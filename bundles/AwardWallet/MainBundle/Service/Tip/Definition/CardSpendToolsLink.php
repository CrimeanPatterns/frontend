<?php

namespace AwardWallet\MainBundle\Service\Tip\Definition;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\AccountHistory\BankTransactionsAnalyser;
use Doctrine\ORM\EntityManagerInterface;

class CardSpendToolsLink extends Generic implements TipDefinitionInterface
{
    private BankTransactionsAnalyser $analyser;

    public function __construct(EntityManagerInterface $entityManager, BankTransactionsAnalyser $analyser)
    {
        parent::__construct($entityManager);
        $this->analyser = $analyser;
    }

    public function getElementId(): string
    {
        return 'cardSpendToolsLink';
    }

    public function show(Usr $user, string $routeName): bool
    {
        if (!$this->isAvailable($user, $routeName)) {
            return false;
        }

        $params = $this->analyser->getSpentAnalysisInitial();

        if (isset($params["ownersList"]) && !empty($params["ownersList"])) {
            return true;
        }

        return false;
    }
}
