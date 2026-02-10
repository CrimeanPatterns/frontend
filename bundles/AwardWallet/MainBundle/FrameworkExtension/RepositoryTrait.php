<?php

namespace AwardWallet\MainBundle\FrameworkExtension;

trait RepositoryTrait
{
    public function save($entity = null): void
    {
        if ($entity === null) {
            $this->_em->flush();

            return;
        }

        $this->_em->persist($entity);
        $this->_em->flush($entity);
    }

    public function remove($entity): void
    {
        $this->_em->remove($entity);
    }
}
