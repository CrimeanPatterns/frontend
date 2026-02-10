<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Form\Handler\FormHandlerHelper;
use AwardWallet\MainBundle\Form\Model\ProviderCouponModel;

class ProviderCouponFormTransformer extends AbstractModelTransformer
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
            'description',
            'value',
            'currency',
            'expirationdate',
            'isArchived',
            'programname',
            'kind',
            'owner',
            'useragents',
            'typeName',
            'cardnumber',
            'donttrackexpiration',
            'pin',
            'account',
        ];
        $this->formHandlerHelper = $formHandlerHelper;
    }

    /**
     * @param Providercoupon $coupon
     * @return ProviderCouponModel
     */
    public function transform($coupon)
    {
        $model = $this->createModel()->setEntity($coupon);

        $this->formHandlerHelper->copyProperties($coupon, $model, $this->properties);

        return $model;
    }

    /**
     * @return \string[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    protected function createModel(): ProviderCouponModel
    {
        return new ProviderCouponModel();
    }
}
