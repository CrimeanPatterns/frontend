<?php

namespace AwardWallet\MainBundle\Globals;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\UserAvatar;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class StandartViewCreator
{
    public const AWARD_MENU = 1;
    public const CONTACT_MENU = 2;
    public const USER_PROFILE = 3;

    protected $userInfo;
    protected $otherUsersInfo;
    protected $userInfoB;
    protected $userInfoT;

    /**
     * @var ViewElementCreator
     */
    protected $view;

    /**
     * @var Usr
     */
    protected $user;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    protected $rootDir;

    /**
     * @var ContainerInterface
     */
    protected $ci;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var UserAvatar
     */
    private $userAvatar;

    public function __construct(ViewElementCreator $view,
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        EntityManagerInterface $em,
        RouterInterface $router,
        UserAvatar $userAvatar,
        ContainerInterface $container)
    {
        $this->view = $view;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $token = $tokenStorage->getToken();

        if (is_object($token)) {
            $this->user = $token->getUser();
        }
        $this->em = $em;
        $this->router = $router;
        $this->userAvatar = $userAvatar;
        $this->ci = $container;

        if (
            ($requestStack = $container->get('request_stack', ContainerInterface::NULL_ON_INVALID_REFERENCE))
            && ($request = $requestStack->getCurrentRequest())
        ) {
            $this->request = $request;
        }

        $this->rootDir = $container->getParameter('kernel.root_dir') . "/../web";
    }

    public function userId()
    {
        if (isset($this->user)) {
            return $this->user->getUserid();
        } else {
            if ($_SESSION['AccountLevel'] == ACCOUNT_LEVEL_BUSINESS) {
                return $_SESSION['ManagerFields']['UserID'];
            }

            return $_SESSION['UserID'];
        }
    }

    public function getLocale()
    {
        if (isset($this->request)) {
            return $this->request->getLocale();
        } else {
            return $_SESSION['_locale'] ?? 'en';
        }
    }

    public function userAgentInfo($userAgentID)
    {
        $agents = $this->userAgents();

        foreach ($agents as $info) {
            if ($info['UserAgentID'] == $userAgentID) {
                return [
                    'id' => $userAgentID,
                    'name' => $info['UserName'],
                ];
            }
        }

        return false;
    }

    public function userAgents()
    {
        if (empty($this->otherUsersInfo)) {
            $agentRepository = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
            $this->otherUsersInfo = $agentRepository->getOtherUsers($userID = $this->userId());
        }

        return $this->otherUsersInfo;
    }

    public function topButtonsGuestData()
    {
        $creator = $this->view->clear();
        $creator->addButtonGroup()
            ->addButton('Register', 'javascript:userRegister();', '', 'red big', false)
            ->addButtonGroup()
            ->addButton('Log in', 'javascript:showLogin();', '', 'big', false)
            ->addButtonGroup()
            ->addButton('Programs We Support', '#', '', 'outstanding', false)
            ->addButton('About Us', '#', '', '', false)
            ->addButton('Partners', '#', '', '', false);
        $top = $creator->getButtons();

        return $top;
    }

    public function addProviderData($filter = '')
    {
        // $result=array();

        $connection = $this->em->getConnection();
        // Helper variables
        $userID = $this->user->getUserid();

        // List
        $orderBy = "ORDER BY program";
        $userList = "userid = $userID" . $this->getAgentFilter();
        $sql = "
            SELECT DISTINCT
                p.providerid AS id,
                p.programname AS fullname,
                p.name AS name,
                p.displayname AS program,
                p.kind AS kind,
                (aa.providerid IS NOT NULL) AS has,
                aa.c as count
            FROM Provider AS p
                LEFT JOIN (
                    SELECT Providerid, COUNT(Distinct(AccountID)) as c
                    FROM Account a
                    WHERE (" . $userList . ") group by Providerid) AS aa
                ON p.ProviderId=aa.ProviderId
			WHERE  " . $this->user->getProviderFilter() . "
			       AND p.ProviderID<>4
			       $filter
			$orderBy
		";
        $providers = $connection->executeQuery($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return $providers; // $result;
    }

    public function getAgentFilter()
    {
        $agentList = "";

        $userID = $this->user->getUserid();
        $connection = $this->em->getConnection();
        $agentSql = "
select ua.UserAgentID as uaid, ua.ClientID as cid
from UserAgent ua
join Usr c on c.UserID = ua.ClientID
where ua.IsApproved = 1 and ua.AgentID = $userID
and ua.TripShareByDefault = 1 AND ua.AccessLevel in (" . ACCESS_READ_ALL . ", " . ACCESS_WRITE . ", " . ACCESS_ADMIN . ", " . ACCESS_BOOKING_MANAGER . ", " . ACCESS_BOOKING_VIEW_ONLY . ")
";
        $agents = $connection->executeQuery($agentSql)->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($agents as $agent) {
            $agentList .= " or ( a.UserID = {$agent['cid']}
	        and a.AccountID in (
                select ash.AccountID
        		from AccountShare ash, Account a
        		where ash.AccountID = a.AccountID and a.UserID = {$agent['cid']}
        		and ash.UserAgentID = {$agent['uaid']}
            	)
            )";
        }

        return $agentList;
    }

    public function leftColumnMenu($menuType, $activePerson = 'owner')
    {
        $creator = $this->view->clear();

        if ($menuType == self::CONTACT_MENU) {
            $creator->addBoxOld('Contact', '', 'menu')
                ->addButtonOld('Contact us', 'Contact us', $this->router->generate('aw_contactus_index', []), '', '', strpos($_SERVER['REQUEST_URI'], '/contact') !== false)
                ->addButtonOld('Provider Health Dashboard', 'Status', '/status/', '', '', false);
        } elseif ($menuType == self::USER_PROFILE) {
            $text = $this->authorizationChecker->isGranted('SITE_BUSINESS_AREA') ?
                "Delete my business account" : "Delete my account";
            $creator->addBoxOld('Profile', '', 'menu')
                ->addButtonOld($text, $text, '/user/delete.php', '', '', false);
        } elseif ($menuType == self::AWARD_MENU) {
            $info = $this->userInfoBalances();

            if (count($info) > 0 && !$this->authorizationChecker->isGranted('SITE_BUSINESS_AREA')) {
                $creator->addBoxOld('Others menu', '', 'menu', true);

                foreach ($info as $uInfo) {
                    if ($uInfo['Count'] == 0) {
                        continue;
                    }
                    $text = $uInfo['UserName'];

                    if (strcasecmp($text, 'all') === 0) {
                        $id = 'All';
                        $text = 'All Award Programs';
                    } elseif (empty($uInfo['UserAgentID'])) {
                        $id = 'owner';
                    } else {
                        $id = $uInfo['UserAgentID'];
                    }

                    if (empty($uInfo['FirstName']) && empty($uInfo['LastName'])) {
                        $creator->addButtonOld($text, '', "/account/list.php?UserAgentID={$id}", '', '', false, $uInfo['Count']);
                    } else {
                        $creator->addButtonOld($text, '', "/account/list.php?UserAgentID={$id}", '', '', false, $uInfo['Count'], '', "/account/add.php?UserAgentID={$id}", "Add a new loyalty program account for " . $uInfo['FirstName'] . " " . $uInfo['LastName']);
                    }
                }
            }

            if (!($this->authorizationChecker->isGranted('SITE_BOOKING_AREA') && $this->authorizationChecker->isGranted('USER_BOOKING_PARTNER') && !$this->authorizationChecker->isGranted('USER_BOOKING_MANAGER'))) {
                $creator->addBoxOld('Manage rewards', 'Manage Rewards', 'menu');

                if (false) {
                    // Repositories
                    $rep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class);
                    // Balances
                    $balancesCount = $rep->getCountAccountsByUser($this->userId());

                    if (empty($balancesCount)) {
                        $balancesCount = 0;
                    }
                    $agents = $this->userAgents();

                    if (count($agents) > 0) {
                        $creator->addButtonOld("All Award Programs", '', "/account/list.php?UserAgentID=All", '', '', false);
                    }
                    $creator
                        ->addButtonOld('My Balances', '', "/account/list.php", '', '', false, $balancesCount, '', "/account/add.php", "Add a new loyalty program account");
                }
                $creator
                    ->addButtonOld("Add a New Program", '', "/account/add.php", '', '', false)
//					->addButtonOld("Add New Person",'',"showPopupWindow(document.getElementById('newAgentPopup'), true); return false;",'','',false)
                    ->addButtonOld("Add Coupon or Gift Card", '', "/coupon/edit.php?ID=0", '', '', false)
                    ->addButtonOld("Add to Your iGoogle Page", '', "http://www.google.com/ig/adde?moduleurl=http://http://awardwallet.com/gadgets/awardwallet.xml", '', '', false)//            ->addButtonOld("Manage Users",'',"/agent/connections.php?Source=".CONNECTION_AWARD,'','',false,MyConnectionsCount(""),'',"javascript:showPopupWindow(document.getElementById('newAgentPopup'));","Add Connections")
                ;
            }
            $this->addPasswordMenu();
            $creator->addBoxOld('Award Booking Requests', 'Award Booking Requests', 'menu');
            $this->addBookingMenu(isset($this->user) ? $this->authorizationChecker->isGranted('USER_BOOKING_PARTNER') : false);
        }
        $creator->addBoxOld('Invite', 'Invite to AwardWallet', 'menu');

        return $creator->getList();
    }

    public function leftColumnData($activePerson = 'owner', $base = 'balances')
    {
        $creator = $this->view->clear();
        $type = strcasecmp($base, 'balances') === 0 ? 1 : 2;
        $route = $type == 1 ? 'aw_newdesign_balances_list' : 'aw_newdesign_timeline_index';
        $route1 = $type == 1 ? 'aw_newdesigntest_addaccount' : 'aw_newdesigntest_retrievetrips';

        $info = $this->userInfo();
        $creator->addBox('user-box', '', $info['pic'], '')
            ->addButton($info['name'], '#', '', 'light')
            ->addButton('', '#', 'icon-caret', 'merged light')
            ->addButtonGroup();
        $this->generateLocaleMenu();

        if ($type == 1) {
            $creator->addBox()
                ->addButtonGroup()
                ->addButton('left.button.addprogram', $this->router->generate('aw_newdesigntest_addaccount'), '', 'spec');
        }

        /*
         * this part to show whole account info with counters of tracking program
         */
        if ($type == 1) {
            $info = $this->userInfoBalances();
        } else {
            $info = $this->userInfoTrips();
            $info1 = $this->userInfoBalances();
        }
        $creator->addBox('user-tabs');

        foreach ($info as $uInfo) {
            $text = $uInfo['UserName'];
            $class = '';
            $en = true;

            if (isset($info1)) {
                $i = current($info1);
                next($info1);
                $en = $i['Count'] > 0;
            }

            if (strcasecmp($text, 'all') === 0) {
                $text = 'left.users.all.' . $base;
                $id = 'all';
                $en = false;
            } elseif (empty($uInfo['UserAgentID'])) {
                $id = 'owner';
            } else {
                $id = $uInfo['UserAgentID'];
            }

            if ($activePerson == $id) {
                $class = 'active';
            }
            $creator->addOptionLink($text, $this->router->generate($route, ['ownerID' => $id]), $class, $uInfo['Count'], null, $en
                ? $this->router->generate($route1, ['ownerID' => $id]) : null);
        }
        $creator->addBox()
            ->addButton('left.button.adduser', '#', '', 'light')
            ->addButton('left.button.manageuser', '#', '', 'light');

        // This menu to track new programs
        $creator->addBox('action-box', 'left.group.track.header', '', 'action-box1')
            ->addOptionLink('left.group.track.add')
            ->addOptionLink('left.group.track.manually')
            ->addOptionLink('left.group.track.giftcard')
            ->addOptionLink('left.group.track.voucher');
        $creator->addBox('action-box', 'left.group.manage.header', '', 'action-box2')
            ->addOptionLink('left.group.manage.profile')
            ->addOptionLink('left.group.manage.google')
            ->addOptionLink('left.group.manage.coupon')
            ->addOptionLink('left.group.manage.upgrade');
        $creator->addBox('action-box options', 'left.options.display', '', 'action-box3')
            ->addOptionInput('left.options.grouping', 'checkbox', 'grouping-rewards')
            ->addOptionLink('left.download.excel', '#', '', null, 'icon-xls')
            ->addOptionLink('left.download.pdf', '#', '', null, 'icon-pdf');
        $result = $creator->getList();

        return $result;
    }

    public function userInfo()
    {
        if (empty($this->userInfo)) {
            $userID = $this->user->getUserid();
            $this->userInfo = [
                'id' => $userID,
                'name' => $this->user->getFullName(),
                'pic' => $this->userAvatar->getUserUrl($this->user, false),
            ];
        }

        return $this->userInfo;
    }

    public function userInfoBalances()
    {
        if (empty($this->userInfoB)) {
            $accRepo = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class);
            //            $this->userInfoB = $accRepo->getDetailsCountAccountsByUser($this->user);
        }

        return $this->userInfoB;
    }

    public function userInfoTrips()
    {
        if (empty($this->userInfoT)) {
            $tripRepo = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Trip::class);
            $this->userInfoT = $tripRepo->getDetailsCountTripsByUser($this->user);
        }

        return $this->userInfoT;
    }

    public function addOldMenu($menuPart, $params = [], $returnOrAppendTo = null)
    {
        $creator = $this->view;

        if (isset($returnOrAppendTo)) {
            $creator->clear();
        }
        $fn = 'add' . $menuPart;

        if (method_exists($this, $fn)) {
            call_user_func_array([$this, $fn], $params);
            $menu = $creator->getButtonsOld();
        } else {
            $menu = [];
        }

        if (isset($returnOrAppendTo)) {
            if ($returnOrAppendTo === true) {
                return $menu;
            }

            if (is_array($returnOrAppendTo)) {
                return array_merge($returnOrAppendTo, $menu);
            }
        }
    }

    public function addBookingMenu($isBooker = false, $return = false, $oldStyle = true)
    {
        $creator = $this->view;

        if ($return) {
            $creator->clear();
        }
        $uid = $this->userId();
        $user = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($uid);
        $businessUser = null;
        $businessUid = null;

        if ($isBooker) {
            $businessUser = $user->getBooker();
            $businessUid = $businessUser->getUserid();
        }

        if ($isBooker) {
            $c = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->getUnreadCountForUser($user, $isBooker);
            $link = $this->router->generate('aw_booking_list_queue');

            $creator->addButtonOld('Booking Requests Queue', "Award booker interface", $link, '', '', strpos($_SERVER['REQUEST_URI'], 'awardBooking/queue') !== false, $c, 'append');
            $link = $this->router->generate('aw_booking_add_index');
            $creator->addButtonOld('Add New Booking Request', "Add Award booking request", $link,
                null, '', strpos($_SERVER['REQUEST_URI'], 'awardBooking/add') !== false);
        }
        $link = $this->router->generate('aw_booking_list_requests', ['active' => 1]);
        $action = $this->router->generate('aw_booking_add_index');
        $actionText = 'Add New Booking Request';
        $c = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->getActiveRequestsCountByUser($user, false);

        if (!$this->authorizationChecker->isGranted('SITE_BUSINESS_AREA') && $c) {
            $creator->addButtonOld('Active booking requests', "Active booking requests", $link,
                null, '', strpos($_SERVER['REQUEST_URI'], 'requests?active=1') !== false, $c, 'append');
        }

        if (!$this->authorizationChecker->isGranted('SITE_BUSINESS_AREA')) {
            $c = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->getPreviousRequestsCountByUser($user, false);

            if ($c) {
                $link = $this->router->generate('aw_booking_list_requests', ['archive' => 1]);
                $creator->addButtonOld('Previous booking requests', "Previous booking requests", $link,
                    null, '', strpos($_SERVER['REQUEST_URI'], 'requests?archive=1') !== false, $c, 'append');
            }

            $link = $this->router->generate('aw_booking_add_index');
            $creator->addButtonOld('Add New Booking Request', "Add Award booking request", $link,
                null, '', strpos($_SERVER['REQUEST_URI'], 'awardBooking/add') !== false);
        }

        if ($return) {
            return $creator->getButtonsOld();
        }
    }

    public function addPasswordMenu($return = false, $oldStyle = true)
    {
        $creator = $this->view;

        if ($return) {
            $creator->clear();
        }
        // $m   = 'addButton' . ($oldStyle ? 'Old' : '');
        $connection = $this->em->getConnection();
        $q = $connection->executeQuery("select a.AccountID, a.Pass, a.SavePassword, p.DisplayName, a.Login
					from Account a
					join Provider p on a.ProviderID = p.ProviderID
					where a.SavePassword = " . SAVE_PASSWORD_LOCALLY
            . " and a.UserID = {$this->userId()} and a.ProviderID is not null limit 0, 1")
            ->fetchAll(\PDO::FETCH_ASSOC);

        if (count($q) > 0) {
            $creator
                ->addButtonOld("Backup Local Passwords" /* checked */ , '', "/account/backupPasswords.php", "var elem = event.srcElement ? event.srcElement : event.target; askUserPassword('Backup local passwords', 'Get backup', function() { location.href = '/account/backupPasswords.php'; }, elem ); return false;", '', false)
                ->addButtonOld("Restore Local Passwords" /* checked */ , '', "/account/restorePasswords.php", "return false;", '', false);
        }

        if ($return) {
            return $creator->getButtonsOld();
        }
    }

    public function addOthersMenu($return = false, $oldStyle = true)
    {
        $creator = $this->view;

        if ($return) {
            $creator->clear();
        }
        // $m   = 'addButton' . ($oldStyle ? 'Old' : '');
        $agents = $this->userAgents();

        if (count($agents) > 0) {
            $creator->addButtonOld("All Award Programs", '', "/account/list.php?UserAgentID=All", '', '', false);
        }

        if ($return) {
            return $creator->getButtonsOld();
        }
    }

    public function getDatepickerI18nFile($locale)
    {
        $locale = str_replace("_", "-", $locale);
        $cache = \Cache::getInstance()->get('datepicker.i18n');

        if ($cache === false) {
            $path = 'assets/common/vendors/jqueryui/ui/i18n';
            $finder = new Finder();
            $finder->files()->in($this->rootDir . '/' . $path);
            $cache = [];

            foreach ($finder as $file) {
                $filename = $file->getFilename();

                if (preg_match("/^(jquery\.ui\.)?datepicker-([a-z-]+)\.js$/i", $filename, $matches)) {
                    $cache[$matches[2]] = '/' . $path . '/' . $filename;
                }
            }
            \Cache::getInstance()->set('datepicker.i18n', $cache, 3600 * 24 * 1);
        }

        $_locale = substr($locale, 0, 5);

        if (isset($cache[$_locale])) {
            return [$_locale, $cache[$_locale]];
        }
        $_locale = substr($locale, 0, 2);

        if (isset($cache[$_locale])) {
            return [$_locale, $cache[$_locale]];
        }

        return ["", ""];
    }

    private function generateLocaleMenu()
    {
        $creator = $this->view;
        $lng = strtolower(substr($this->getLocale(), 0, 2));
        $creator->addButton('user.settings.language.' . $lng, '#', 'icon-flag-' . $lng, 'disabled smaller');
        $creator->addButton('', '#', '', 'flat')
            ->addPullMenu()
//            ->addPullItem('Sorry this feature is not available now','','',null,'icon-flag-en')
            ->addPullItem('user.settings.language.en', '?locale=en_US', '', null, 'icon-flag-en')
            ->addPullItem('user.settings.language.ru', '?locale=ru_RU', '', null, 'icon-flag-ru')
            ->addPullItem('user.settings.language.de', '?locale=de_DE', '', null, 'icon-flag-de')
            ->addPullItem('user.settings.language.fr', '?locale=fr_FR', '', null, 'icon-flag-fr')
            ->addPullItem('user.settings.language.ch', '?locale=ch_CH', '', null, 'icon-flag-ch');
    }
}
