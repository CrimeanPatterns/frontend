<?php

namespace AwardWallet\MainBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Show\ShowMapper;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class MultiplierLookupAdmin extends AbstractAdmin
{
    public function __construct()
    {
        $this->setUniqId('multiplier-lookup');
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('account.user.userid', null, ["label" => 'UserID'])
            ->add('account.user.fullName', null, ["label" => 'User'])
            ->add('account.accountid', null, ["label" => 'AccountID'])
            ->add('subaccount.id', null, ['label' => 'SubAccountID'])
            ->add('subaccount.displayname', null, ['label' => 'SubAccount Name'])
            ->add('subaccount.creditcard.name', null, ['label' => 'CreditCard'])
            ->add('account.providerid.displayname', null, ['label' => 'Provider'])
            ->add('uuid', null, ["label" => 'UUID'])
            ->add('postingdate', FieldDescriptionInterface::TYPE_DATE, ['format' => 'F d, Y'])
            ->add('description', TextType::class, ['allow_quotes' => true, 'allow_tags' => true, 'allow_urls' => true])
            ->add('merchant.name')
            ->add('amount')
            ->add('amountbalance')
            ->add('currency.code', null, ["label" => 'Currency'])
            ->add('miles')
            ->add('milesbalance')
            ->add('multiplier')
            ->add('category', null, ["label" => 'Category (parsed)'])
            ->add('shoppingcategory.name', null, ["label" => 'Shopping Category'])
            ->add('transactionDescriptionAdmin', FieldDescriptionInterface::TYPE_HTML, ["label" => 'Transaction Description'])
            ->add('parsedInfo', FieldDescriptionInterface::TYPE_ARRAY, ["label" => 'Additional Info'])
        ;
    }

    protected function generateBaseRouteName(bool $isChildAdmin = false): string
    {
        return 'multiplier_lookup';
    }

    protected function generateBaseRoutePattern(bool $isChildAdmin = false): string
    {
        return 'multiplier-lookup';
    }
}
