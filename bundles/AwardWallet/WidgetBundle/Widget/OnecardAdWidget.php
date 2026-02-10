<?php

namespace AwardWallet\WidgetBundle\Widget;

class OnecardAdWidget extends TemplateWidget
{
    public function isVisible()
    {
        return $this->visible && $this->container->get('security.authorization_checker')->isGranted('NOT_SITE_BUSINESS_AREA');
    }
}
