<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners;

use AwardWallet\MainBundle\Manager\LocalPasswordsManager;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class LocalPasswordsListener
{
    /**
     * @var LocalPasswordsManager
     */
    private $localPasswordsManager;

    public function __construct(LocalPasswordsManager $localPasswordsManager)
    {
        $this->localPasswordsManager = $localPasswordsManager;
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if ($event->getRequest()->attributes->has(LocalPasswordsManager::ATTR_NAME) && $this->localPasswordsManager->isUnsaved()) {
            $response = $event->getResponse();
            $this->localPasswordsManager->save($response);
            $this->localPasswordsManager->clearUnmanagedCookies($response);
        }
    }
}
