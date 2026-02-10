<?php

namespace AwardWallet\MainBundle\Security\Voter;

use AwardWallet\MainBundle\Security\AuthenticationEntryPointHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CSRFVoter extends AbstractVoter
{
    public function hasCSRFToken()
    {
        if (
            ($requestStack = $this->container->get('request_stack', ContainerInterface::NULL_ON_INVALID_REFERENCE))
            && ($request = $requestStack->getCurrentRequest())
        ) {
            $manager = $this->container->get('security.csrf.token_manager');
            $result = !empty($request->headers->get('X-XSRF-TOKEN')) && $request->headers->get('X-XSRF-TOKEN') == $manager->getToken('')->getValue();

            if ($result === false) {
                $request->attributes->set(AuthenticationEntryPointHandler::REQUEST_ATTRIBUTE_REDIRECT_TO_LOGIN, false);
                $dispatcher = $this->container->get('event_dispatcher');
                $dispatcher->addListener(KernelEvents::RESPONSE, function (FilterResponseEvent $event) use ($manager) {
                    if ($event->isMasterRequest()) {
                        $headers = $event->getResponse()->headers;
                        $headers->set('X-XSRF-FAILED', 'true');
                        $headers->set('X-XSRF-TOKEN', $manager->getToken('')->getValue());
                        $event->getResponse()
                            ->setContent(json_encode('CSRF failed'))
                            ->setStatusCode(Response::HTTP_FORBIDDEN);
                    }
                }, 0);
            }

            return $result;
        }

        return null;
    }

    protected function getAttributes()
    {
        return [
            'CSRF' => [$this, "hasCSRFToken"],
        ];
    }
}
