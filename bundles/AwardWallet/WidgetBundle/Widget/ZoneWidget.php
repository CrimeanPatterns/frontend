<?php

namespace AwardWallet\WidgetBundle\Widget;

use AwardWallet\WidgetBundle\Widget\Classes\AbstractWidgetContainer;
use AwardWallet\WidgetBundle\Widget\Classes\UserWidgetInterface;
use AwardWallet\WidgetBundle\Widget\Exceptions\AuthenticationRequiredException;

class ZoneWidget extends AbstractWidgetContainer
{
    public function getWidgetContent($options = [])
    {
        $ret = '';

        foreach ($this->getItems() as $widget) {
            try {
                $ret .= $widget->render($options);
            } catch (AuthenticationRequiredException $e) {
                if ($widget instanceof UserWidgetInterface) {
                    // dont show user specific widgets to anon
                } else {
                    throw $e;
                }
            }
        }

        return $ret;
    }
}
