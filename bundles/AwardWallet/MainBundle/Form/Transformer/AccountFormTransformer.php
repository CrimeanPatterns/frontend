<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Form\Handler\FormHandlerHelper;
use AwardWallet\MainBundle\Form\Model\AccountModel;
use AwardWallet\MainBundle\Service\BalanceWatch\Query;

class AccountFormTransformer extends AbstractModelTransformer
{
    /**
     * @var string[]
     */
    private $properties;
    /**
     * @var FormHandlerHelper
     */
    private $formHandlerHelper;
    private Query $bwQuery;

    public function __construct(FormHandlerHelper $formHandlerHelper, Query $bwQuery)
    {
        $this->properties = [
            'owner',
            'login',
            'login2',
            'login3',
            'balance',
            'kind',
            'pass',
            'savepassword',
            'comment',
            'programname',
            'loginurl',
            'goal',
            'expirationdate',
            'disabled',
            'disablebackgroundupdating',
            'donttrackexpiration',
            'notrelated',
            'authInfo',
            'useragents',
            'isArchived',
            'disableextension',
            'disableclientpasswordaccess',
            'balancewatchstartdate',
            'currency',
            'customEliteLevel',
        ];
        $this->formHandlerHelper = $formHandlerHelper;
        $this->bwQuery = $bwQuery;
    }

    /**
     * @param Account $account
     * @return AccountModel
     */
    public function transform($account)
    {
        $model = $this->createAccountModel()->setEntity($account);

        $this->formHandlerHelper->copyProperties($account, $model, $this->properties);
        $this->balanceWatchTransform($account, $model);

        return $model;
    }

    /**
     * @return \string[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    protected function createAccountModel(): AccountModel
    {
        return new AccountModel();
    }

    private function balanceWatchTransform(Account $account, AccountModel $model): ?bool
    {
        if ($account->isBalanceWatchDisabled() && null === $account->getBalanceWatchStartDate()) {
            return null;
        }

        $balanceWatch = $this->bwQuery->getAccountBalanceWatch($account);

        if (null === $balanceWatch) {
            return null;
        }

        $properties = ['pointsSource', 'transferFromProvider', 'sourceProgramRegion', 'expectedPoints', 'transferRequestDate'];
        $this->formHandlerHelper->copyProperties($balanceWatch, $model, $properties);

        return true;
    }
}
