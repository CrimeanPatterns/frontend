<?php

namespace AwardWallet\MainBundle\Form\Transformer;

use AwardWallet\MainBundle\Entity\AbInvoiceItem;
use AwardWallet\MainBundle\Entity\BookingInvoiceItem\BookingServiceFee;
use AwardWallet\MainBundle\Entity\BookingInvoiceItem\Item;
use AwardWallet\MainBundle\Entity\BookingInvoiceItem\Tax;
use AwardWallet\MainBundle\Form\Model\Booking\InvoiceItemModel;

class InvoiceItemTransformer extends AbstractModelTransformer
{
    /**
     * @param AbInvoiceItem $item
     * @return InvoiceItemModel
     */
    public function transform($item)
    {
        if (is_null($item)) {
            return new InvoiceItemModel();
        }

        return (new InvoiceItemModel())
            ->setDescription($item->getDescription())
            ->setQuantity($item->getQuantity())
            ->setPrice($item->getPrice())
            ->setDiscount($item->getDiscount());
    }

    /**
     * @param InvoiceItemModel $model
     * @return AbInvoiceItem
     */
    public function reverseTransform($model)
    {
        if (!$model instanceof InvoiceItemModel) {
            return null;
        }

        $entity = $this->createInvoiceItemByDescription($model->getDescription());

        return $entity
            ->setDescription($model->getDescription())
            ->setQuantity($model->getQuantity())
            ->setPrice($model->getPrice())
            ->setDiscount($model->getDiscount());
    }

    /**
     * @return AbInvoiceItem
     */
    private function createInvoiceItemByDescription(string $desc)
    {
        if (preg_match("/\btax(es)?\b/ims", $desc)) {
            return new Tax();
        } elseif (
            !preg_match("/\brefund\b/ims", $desc)
            && preg_match("/\bfees?\b/ims", $desc)
        ) {
            return new BookingServiceFee();
        }

        return new Item();
    }
}
