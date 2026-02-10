<?php

namespace AwardWallet\MainBundle\Globals;

// use AwardWallet\MainBundle\FrameworkExtension\Controller;

/**
 * @deprecated
 */
class LeftMenuHandler
{
    private $translator;
    private $router;
    private $doctrine;
    private $controller;
    private $currentRoute;

    private $menu = [];
    private $groups = [];

    private $previousGroups;

    /**
     * 	Menu structure.
     *
     *  - caption
     *  - path
     *  - count
     *  - actionPath
     *  - actionCaption
     *  - targetPath
     *  - targetActionPath
     *  - onclick
     *  - selected
     *  - show
     *  - group
     *  - inPages
     *  - excludePages
     */
    public function __construct($translator, $router, $doctrine)
    {
        $this->translator = $translator;
        $this->router = $router;
        $this->doctrine = $doctrine;
    }

    /*
    public function setController(Controller $controller)
    {
        $this->controller = $controller;
    }
    */
    public function setCurrentRoute($route)
    {
        $this->currentRoute = $route;
    }

    public function getCurrentRoute()
    {
        return $this->currentRoute;
    }

    public function init()
    {
        $this->createMenu();
        $this->createGroups();
    }

    public function getAvailableMenuByCurrentPage()
    {
        if (isset($this->previousGroups)) {
            return $this->previousGroups;
        }

        return $this->previousGroups = $this->getGroups(array_keys($this->groups));
    }

    public function hasGroup($groupName)
    {
        $groups = $this->getAvailableMenuByCurrentPage();

        return isset($groups[$groupName]);
    }

    public function hasMenu($menuName)
    {
        $groups = $this->getAvailableMenuByCurrentPage();

        if ($groups != false) {
            foreach ($groups as $group) {
                if (isset($group['Menu'][$menuName])) {
                    return true;
                }
            }
        }

        return false;
    }

    public function createMenu()
    {
        $this->menu = [
            'Contact us' => [
                'caption' => 'Contact us',
                'path' => ['aw_contactus_index', []],
                'group' => 'Contact',
                'inPages' => ['aw_contactus_index'],
            ],
            'Status' => [
                'caption' => 'Provider Health Dashboard',
                'path' => '/status/',
                'group' => 'Contact',
                'inPages' => ['aw_contactus_index'],
            ],
        ];
    }

    public function createGroups()
    {
        $controller = $this->controller;
        $this->groups = [
            'Main' => [
                'caption' => '',
                'show' => false,
                'classes' => 'menu',
            ],
            'Contact' => [
                'caption' => '',
                'show' => true,
                'classes' => 'menu',
            ],
            'Invite' => [
                'caption' => 'Invite to AwardWallet',
                'show' => function () use ($controller) {
                    if (SITE_MODE == SITE_MODE_PERSONAL && $controller->isAuthorized()) {
                        return true;
                    }

                    return false;
                },
                'classes' => 'menu',
                'allowEmpty' => true,
            ],
        ];
    }

    public function getGroups($groups)
    {
        $groups = (!is_array($groups)) ? [$groups] : $groups;

        $forGroupNormalize = [];

        foreach ($groups as $k => $group) {
            if (!isset($this->groups[$group])) {
                unset($groups[$k]);
            } else {
                $forGroupNormalize[$group] = $this->groups[$group];
            }
        }

        if (sizeof($groups) == 0) {
            return false;
        }

        $forGroupNormalize = $this->normalizeGroups($forGroupNormalize);
        $forMenuNormalize = $this->normalizeMenu($this->menu);
        $groups = [];

        foreach ($forGroupNormalize as $groupName => $groupBody) {
            // Show
            if (is_bool($groupBody['show']) && !$groupBody['show']) {
                continue;
            } elseif (is_callable($groupBody['show']) && call_user_func($groupBody['show']) === false) {
                continue;
            }

            // inPages
            if (sizeof($groupBody['inPages']) > 0 && !in_array($this->getCurrentRoute(), $groupBody['inPages'])) {
                continue;
            }

            // excludePages
            if (sizeof($groupBody['excludePages']) > 0 && in_array($this->getCurrentRoute(), $groupBody['excludePages'])) {
                continue;
            }
            $_menu = $this->getMenuByGroup($groupName, $forMenuNormalize);

            if (sizeof($_menu) == 0 && !$groupBody['allowEmpty']) {
                continue;
            }
            $groups[$groupName] = $groupBody;
            $groups[$groupName]['Menu'] = $_menu;
        }

        return $groups;
    }

    protected function getMenuByGroup($group, $menu)
    {
        $result = [];

        foreach ($menu as $name => $item) {
            if ($item['group'] != $group) {
                continue;
            }

            // Show
            if (is_bool($item['show']) && !$item['show']) {
                continue;
            } elseif (is_callable($item['show']) && call_user_func($item['show']) === false) {
                continue;
            }

            // inPages
            if (sizeof($item['inPages']) > 0 && !in_array($this->getCurrentRoute(), $item['inPages'])) {
                continue;
            }

            // excludePages
            if (sizeof($item['excludePages']) > 0 && in_array($this->getCurrentRoute(), $item['excludePages'])) {
                continue;
            }
            // # Menu Show

            // Count
            if (isset($item['count']) && is_callable($item['count'])) {
                $item['count'] = call_user_func($item['count']);
            }

            // Selected
            if (is_callable($item['selected'])) {
                $item['selected'] = call_user_func($item['selected']);
            } elseif (is_array($this->menu[$name]['path']) && sizeof($this->menu[$name]['path']) == 2 && $this->getCurrentRoute() == $this->menu[$name]['path'][0]) {
                $item['selected'] = true;
            }
            $result[$name] = $item;
        }

        return $result;
    }

    protected function normalizeMenu($menu)
    {
        $default_path = "/";
        $default_group = "Main";

        foreach ($menu as $key => $item) {
            // Caption
            if (!isset($item['caption'])) {
                $menu[$key]['caption'] = $item['caption'] = '';
            }

            if ($item['caption'] != '') {
                $menu[$key]['caption'] = $item['caption'] = $this->translator->trans(/** @Ignore */ $item['caption']);
            }

            // Path
            if (!isset($item['path'])) {
                $menu[$key]['path'] = $item['path'] = $default_path;
            }

            if ($item['path'] != '') {
                if (is_array($item['path']) && sizeof($item['path']) == 2) {
                    $menu[$key]['path'] = $item['path'] = $this->router->generate($item['path'][0], $item['path'][1]);
                }
            }

            // Count
            if (!isset($item['count'])) {
                $menu[$key]['count'] = $item['count'] = null;
            }

            // ActionPath
            if (!isset($item['actionPath'])) {
                $menu[$key]['actionPath'] = $item['actionPath'] = null;
            }

            if (isset($item['actionPath'])) {
                if (is_array($item['actionPath']) && sizeof($item['actionPath']) == 2) {
                    $menu[$key]['actionPath'] = $item['actionPath'] = $this->router->generate($item['actionPath'][0], $item['actionPath'][1]);
                }
            }

            // ActionCaption
            if (!isset($item['actionCaption'])) {
                $menu[$key]['actionCaption'] = $item['actionCaption'] = null;
            }

            if (isset($item['actionCaption']) && $item['actionCaption'] != '') {
                $menu[$key]['actionCaption'] = $item['actionCaption'] = $this->translator->trans(/** @Ignore */ $item['actionCaption']);
            }

            // TargetPath
            if (!isset($item['targetPath'])) {
                $menu[$key]['targetPath'] = $item['targetPath'] = null;
            }

            // TargetActionPath
            if (!isset($item['targetActionPath'])) {
                $menu[$key]['targetActionPath'] = $item['targetActionPath'] = null;
            }

            // Onclick
            if (!isset($item['onclick'])) {
                $menu[$key]['onclick'] = $item['onclick'] = null;
            }

            // Selected
            if (!isset($item['selected'])) {
                $menu[$key]['selected'] = $item['selected'] = false;
            }

            // Show
            if (!isset($item['show'])) {
                $menu[$key]['show'] = $item['show'] = true;
            }

            // Group
            if (!isset($item['group']) || $item['group'] == '') {
                $menu[$key]['group'] = $item['group'] = $default_group;
            }

            // inPages
            if (!isset($item['inPages']) || !is_array($item['inPages'])) {
                $menu[$key]['inPages'] = $item['inPages'] = [];
            }

            // excludePages
            if (!isset($item['excludePages']) || !is_array($item['excludePages'])) {
                $menu[$key]['excludePages'] = $item['excludePages'] = [];
            }
        }

        return $menu;
    }

    protected function normalizeGroups($groups)
    {
        foreach ($groups as $key => $item) {
            // Caption
            if (!isset($item['caption'])) {
                $groups[$key]['caption'] = $item['caption'] = '';
            }

            // Classes
            if (!isset($item['classes'])) {
                $groups[$key]['classes'] = $item['classes'] = '';
            }

            // Show
            if (!isset($item['show'])) {
                $groups[$key]['show'] = $item['show'] = true;
            }

            // inPages
            if (!isset($item['inPages']) || !is_array($item['inPages'])) {
                $groups[$key]['inPages'] = $item['inPages'] = [];
            }

            // excludePages
            if (!isset($item['excludePages']) || !is_array($item['excludePages'])) {
                $groups[$key]['excludePages'] = $item['excludePages'] = [];
            }

            // Counts
            if (!isset($item['counts'])) {
                $groups[$key]['counts'] = $item['counts'] = true;
            }

            // AllowEmpty
            if (!isset($item['allowEmpty'])) {
                $groups[$key]['allowEmpty'] = $item['allowEmpty'] = false;
            }
        }

        return $groups;
    }
}
