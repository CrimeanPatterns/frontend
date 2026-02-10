<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use Doctrine\ORM\EntityRepository;

class AbInvoiceRepository extends EntityRepository
{
    public function markAsCheckSended($id)
    {
        if ($invoice = $this->find($id)) {
            return [
                'status' => 'success',
            ];
        } else {
            return [
                'status' => 'error',
                'error' => 'Invoice not found!',
            ];
        }
    }
}
