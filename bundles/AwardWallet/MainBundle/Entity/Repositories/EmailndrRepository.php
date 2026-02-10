<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use Doctrine\ORM\EntityRepository;

class EmailndrRepository extends EntityRepository
{
    public function isNdr($email)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sth = $conn->prepare("SELECT EmailVerified FROM Usr WHERE Email = ?");
        $sth->execute([$email]);
        $row = $sth->fetch(\PDO::FETCH_ASSOC);

        if ($row !== false) {
            if ($row['EmailVerified'] == EMAIL_NDR) {
                return true;
            }
        }

        return false;
    }
}
