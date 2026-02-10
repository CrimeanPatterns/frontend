<?php

namespace AwardWallet\Tests\Unit\Security\Reauthentication;

use AwardWallet\MainBundle\Security\Reauthentication\Action;
use AwardWallet\MainBundle\Security\Reauthentication\AuthenticatedUser;
use AwardWallet\MainBundle\Security\Reauthentication\Environment;
use AwardWallet\MainBundle\Security\Reauthentication\Reauthenticator;
use AwardWallet\MainBundle\Security\Reauthentication\ReauthenticatorWrapper;
use AwardWallet\MainBundle\Security\Reauthentication\ReauthRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @group frontend-unit
 * @group security
 */
class ReauthenticatorWrapperTest extends AbstractReauthenticatorTest
{
    public function testMethods()
    {
        $request = $this->getRequest(
            'xxx',
            'password',
            $action = Action::getChangeEmailAction()
        );
        $httpRequest = new Request();
        $httpRequest->server->add(['REMOTE_ADDR' => $this->environment->getIp()]);
        $httpRequest->request->set('action', $action);
        $httpRequest->request->set('context', $request->getContext());
        $httpRequest->request->set('input', $request->getInput());

        $reauth = $this->mockServiceWithBuilder(Reauthenticator::class);
        $reauth->expects($this->once())
            ->method('start')
            ->with(
                $this->callback(function (AuthenticatedUser $authUser) {
                    return $authUser->getEntity() === $this->user && !$authUser->isBusiness();
                }),
                $this->equalTo($action),
                $this->callback(function (Environment $environment) {
                    return $environment->getIp() === $this->environment->getIp();
                })
            );
        $reauth->expects($this->once())
            ->method('verify')
            ->with(
                $this->callback(function (AuthenticatedUser $authUser) {
                    return $authUser->getEntity() === $this->user && !$authUser->isBusiness();
                }),
                $this->callback(function (ReauthRequest $r) use ($request) {
                    return $r->getAction() === $request->getAction()
                        && $r->getInput() === $request->getInput()
                        && $r->getContext() === $request->getContext();
                }),
                $this->callback(function (Environment $environment) {
                    return $environment->getIp() === $this->environment->getIp();
                })
            );
        $reauth->expects($this->once())
            ->method('isReauthenticated')
            ->with($this->equalTo($action), $this->equalTo($this->environment->getIp()));
        $reauth->expects($this->once())
            ->method('reset')
            ->with($this->equalTo($action));

        $token = $this->getMockBuilder(TokenInterface::class)->getMock();
        $token
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($this->user);

        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)
            ->getMock();
        $tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $authChecker = $this->getMockBuilder(AuthorizationCheckerInterface::class)->getMock();
        $authChecker
            ->expects($this->any())
            ->method('isGranted')
            ->willReturn(false);

        $requestStack = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
        $requestStack
            ->expects($this->any())
            ->method('getMasterRequest')
            ->willReturn($httpRequest);

        $wrapper = new ReauthenticatorWrapper(
            $reauth,
            $tokenStorage,
            $authChecker,
            $requestStack
        );

        $wrapper->start();
        $wrapper->verify();
        $wrapper->isReauthenticated($action, $this->environment->getIp());
        $wrapper->reset($action);
    }
}
