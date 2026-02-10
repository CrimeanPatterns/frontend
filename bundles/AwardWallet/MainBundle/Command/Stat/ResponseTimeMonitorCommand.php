<?php

namespace AwardWallet\MainBundle\Command\Stat;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use AwardWallet\MainBundle\Service\ResponseTimeMonitor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouterInterface;

class ResponseTimeMonitorCommand extends Command
{
    public const SAMPLE_TIME = 14;
    protected static $defaultName = 'aw:response-time-monitor';

    private $routes = [
        // mobile
        'awm_new_login_check',
        'awm_new_login_status',
        'awm_newapp_account_add',
        'awm_newapp_account_edit',
        'awm_newapp_coupon_add',
        'awm_newapp_coupon_edit',
        'awm_newapp_account_updater_start',
        'awm_newapp_account_updater_progress',
        //        'awm_newapp_data_timeline_chunk',
        'awm_newapp_register',
        'aw_mobile_purchase_confirm_old',
        'aw_mobile_purchase_confirm',
        'aw_mobile_purchase_refund',

        // desktop
        'aw_users_register',
        'aw_users_logincheck',
        'aw_account_data',
        'aw_account_list',
        'aw_account_add',
        'aw_account_edit',
        'aw_coupon_add',
        'aw_coupon_edit',
        'aw_register',
        'aw_login',
        'aw_login_locale',
        'aw_register_locale',
        'aw_home',
        'aw_home_locale',
        'aw_contactus_index_locale',
        'aw_contactus_index',
        'aw_business_account_list',
        'aw_business_account_data',
        'aw_cart_common_paymenttype',
        'aw_cart_common_complete',
        'aw_cart_common_orderdetails',
        'aw_cart_common_orderpreview',
        'aw_business_users_pay',
        'aw_users_pay',
        'aw_account_updater_start',

        'awm_data',
        'awm_newapp_account_updater_progress',
        'aw_emailcallback_save',
        'aw_account_updater_progress',
        'aw_account_list',
        'aw_icalendar_itinerarycalendar',
        'aw_users_logincheck',
        'aw_business_api_by_id',
        'awm_newapp_account_updater_start',
        'aw_mailbox_progress',
        'aw_account_list_html5',
        'awardwallet_main_account_extension_receivebrowserlog_1',
        'aw_account_balancechart',
        'aw_account_updater_start',
        'aw_loyalty_callback',
        'aw_booking_list_queue',
        'aw_tripalert_callback',
        'aw_account_edit',
        'aw_account_accountinfo',
        'aw_get_advertise',
    ];

    private ResponseTimeMonitor $responseTimeMonitor;
    private RouterInterface $router;

    public function __construct(
        ResponseTimeMonitor $responseTimeMonitor,
        RouterInterface $router
    ) {
        parent::__construct();
        $this->responseTimeMonitor = $responseTimeMonitor;
        $this->router = $router;
    }

    protected function configure()
    {
        $this
            ->setDescription('Monitor response time anomalies')
            ->addOption('routes', 'r', InputOption::VALUE_OPTIONAL, 'routes, divided by comma', $this->routes)
            ->addOption('length', 'l', InputOption::VALUE_OPTIONAL, 'the time interval for which to take data', self::SAMPLE_TIME);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $routes = $input->getOption('routes');

        if (is_string($routes) && false !== strpos($routes, ',')) {
            $routes = explode(',', $routes);
        } elseif (is_string($routes)) {
            $routes = (array) $routes;
        }
        $sampleTime = (int) $input->getOption('length');

        $coll = $this->router->getRouteCollection();

        foreach ($routes as $route) {
            if ($routeName = $coll->get($route)) {
                $this->responseTimeMonitor->search($route, $sampleTime, $routeName->getPath());
            }
        }

        return 0;
    }
}
