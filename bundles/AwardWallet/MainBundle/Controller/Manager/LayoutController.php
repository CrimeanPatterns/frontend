<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Security\RoleManager;
use AwardWallet\Manager\HeaderMenu;
use Knp\Menu\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class LayoutController extends AbstractController
{
    protected HeaderMenu $headerMenu;
    protected RoleManager $roleManager;
    protected FactoryInterface $menuFactory;
    private AuthorizationCheckerInterface $authorizationChecker;
    private TokenStorageInterface $tokenStorage;

    public function __construct(
        HeaderMenu $headerMenu,
        RoleManager $roleManager,
        FactoryInterface $menuFactory,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        RouterInterface $router,
        string $emailApiUrl
    ) {
        $this->headerMenu = $headerMenu;
        $this->roleManager = $roleManager;
        $this->menuFactory = $menuFactory;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
        $this->emailApiUrl = $emailApiUrl;
    }

    // do not using Template annotation - it will not work inside manager/start.php
    public function headerAction(?string $title, ?string $contentTitle)
    {
        if (!$this->authorizationChecker->isGranted('ROLE_MANAGE_INDEX')) {
            throw new AccessDeniedException();
        }

        if ($this->authorizationChecker->isGranted('USER_IMPERSONATED')) {
            throw new AccessDeniedException();
        }

        return $this->render('@AwardWalletMain/Manager/header.html.twig', [
            'username' => $this->tokenStorage->getToken()->getUser()->getLogin(),
            'menu' => $this->headerMenu->getMenu(),
            'menuJson' => $this->headerMenu->getJsonMenu(),
            'file_version' => FILE_VERSION,
            'title' => $title,
            'contentTitle' => $contentTitle ?? $title,
        ]);
    }

    // do not using Template annotation - it will not work inside manager/start.php
    public function footerAction()
    {
        global $Interface;

        $onLoadScripts = [];

        if (isset($Interface)) {
            $onLoadScripts = array_merge($Interface->onLoadScripts, $Interface->FooterScripts);
        }

        return $this->render('@AwardWalletMain/Manager/footer.html.twig', ['onLoadScripts' => $onLoadScripts]);
    }
}
