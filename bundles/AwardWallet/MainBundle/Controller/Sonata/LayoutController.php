<?php

namespace AwardWallet\MainBundle\Controller\Sonata;

use AwardWallet\MainBundle\Controller\Manager\LayoutController as CommonController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class LayoutController extends CommonController
{
    public function headerAction(?string $pageTitle, ?string $contentTitle)
    {
        if (!$this->isGranted('ROLE_MANAGE_INDEX')) {
            throw new AccessDeniedException();
        }

        if ($this->isGranted('USER_IMPERSONATED')) {
            throw new AccessDeniedException();
        }

        $nav = $this->headerMenu->getMenu();
        $menu = $this->menuFactory->createItem('root');

        foreach ($nav as $label => $item) {
            $level1 = $menu->addChild($label);

            foreach ($item['menu'] as $label2 => $item2) {
                $level2 = $level1->addChild($label2, [
                    'uri' => $item2['url'] ?? '#',
                ]);

                if (isset($item2['menu'])) {
                    $level2->setAttribute('icon', '<i class="fas fa-list"></i>');

                    foreach ($item2['menu'] as $label3 => $item3) {
                        $level3 = $level2->addChild($label3, [
                            'uri' => $item3['url'] ?? '#',
                        ]);
                        $level3->setAttribute('icon', '<i class="fas fa-caret-right"></i>');
                    }
                } else {
                    $level2->setAttribute('icon', '<i class="fas fa-caret-right"></i>');
                }
            }
        }

        return $this->render('@AwardWalletMain/Sonata/Layout/header.html.twig', [
            'menu' => $menu,
        ]);
    }
}
