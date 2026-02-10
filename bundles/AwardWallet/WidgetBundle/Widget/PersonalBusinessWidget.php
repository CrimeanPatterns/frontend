<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetInterface;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetTrait;

class PersonalBusinessWidget extends TemplateWidget implements UserWidgetInterface
{
    use UserWidgetTrait;

    public function getWidgetContent($options = [])
    {
        $user = $this->getCurrentUser();

        $em = $this->container->get('doctrine.orm.entity_manager');
        $rep = $em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);

        $options['isBusiness'] = $rep->isAdminBusinessAccount($user->getUserid());

        return parent::getWidgetContent($options);
    }
}
