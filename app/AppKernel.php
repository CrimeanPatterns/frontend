<?php

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    public $masterRequest;
    protected $localConfig;

    public function __construct($environment, $debug)
    {
        parent::__construct($environment, $debug);

        if (!$debug) {
            error_reporting(error_reporting() & ~E_STRICT);
        }
    }

    public function getMasterRequest()
    {
        return $this->masterRequest;
    }

    public function getCacheDir()
    {
        // this fix is for Windows host, because it does not support chmod, and JMS will not work properly
        // in this case you need to create dir /tmp/awardwallet on ubuntu guest, and cache will be written in this folder
        // can't move this option to parameters, because parameters are not available at this time, kernel not booted
        $dir = sys_get_temp_dir() . '/awardwallet';

        if (file_exists($dir)) {
            return $dir . '/cache/' . $this->environment;
        } else {
            return $this->rootDir . '/cache/' . $this->environment;
        }
    }

    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new \JMS\SerializerBundle\JMSSerializerBundle(),
            new AwardWallet\MainBundle\AwardWalletMainBundle(),
            new Gregwar\CaptchaBundle\GregwarCaptchaBundle(),
            new AwardWallet\MobileBundle\AwardWalletMobileBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new FOS\JsRoutingBundle\FOSJsRoutingBundle(),
            new Bazinga\Bundle\JsTranslationBundle\BazingaJsTranslationBundle(),
            new JMS\TranslationBundle\JMSTranslationBundle(),
            new AwardWallet\WidgetBundle\AwardWalletWidgetBundle(),
            new OldSound\RabbitMqBundle\OldSoundRabbitMqBundle(),
            new \RMS\PushNotificationsBundle\RMSPushNotificationsBundle(),

            new \Sonata\BlockBundle\SonataBlockBundle(),
            new \Knp\Bundle\MenuBundle\KnpMenuBundle(),

            new \Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle(),
            new \Sonata\AdminBundle\SonataAdminBundle(),
            new \Sonata\Form\Bridge\Symfony\SonataFormBundle(),
            new \Sonata\Twig\Bridge\Symfony\SonataTwigBundle(),
            new \Sonata\Doctrine\Bridge\Symfony\SonataDoctrineBundle(),
            new FM\ElfinderBundle\FMElfinderBundle(),
            new \Symfony\WebpackEncoreBundle\WebpackEncoreBundle(),
        ];

        if (in_array($this->getEnvironment(), ['dev', 'test'])) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $localFile = __DIR__ . '/config/local_' . $this->getEnvironment() . '.yml';

        if (is_file($localFile)) {
            $loader->load($localFile);
        } else {
            $loader->load(__DIR__ . '/config/config_' . $this->getEnvironment() . '.yml');
        }
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        // compatibility with codeception, it will bootstrap container on each request
        global $symfonyContainer;
        $symfonyContainer = $this->container;

        if ($type == HttpKernelInterface::MASTER_REQUEST) {
            $this->masterRequest = $request;
        }

        return parent::handle($request, $type, $catch);
    }

    public function shutdown()
    {
        if (false === $this->booted) {
            return;
        }

        $this->booted = false;

        foreach ($this->getBundles() as $bundle) {
            if ($this->environment != 'codeception') {
                // we do not want to close doctrine entity managers
                // it will lead to detached enitities and failed
                // tests/functional-symfony/Mobile/Booking/MessagesCest.php:loadAllUnread
                $bundle->shutdown();
            }
            $bundle->setContainer(null);
        }

        $this->container = null;
    }

    public function getLogDir()
    {
        return $this->getProjectDir() . '/app/logs';
    }

    protected function initializeContainer()
    {
        global $symfonyContainer, $Config;

        $cacheDir = $this->getCacheDir();

        if (is_file($cacheDir . '/aggressive_cache')) {
            $class = $this->getContainerClass();
            $cache = new \Symfony\Component\Config\ConfigCache($cacheDir . '/' . $class . '.php', $this->debug);

            require_once $cache;
            $this->container = new $class();
            $this->container->set('kernel', $this);
        } else {
            parent::initializeContainer();

            if ($this->getContainer()->hasParameter('aw.dev.aggressive_cache') && $this->getContainer()->getParameter('aw.dev.aggressive_cache')) {
                file_put_contents($cacheDir . '/aggressive_cache', 'do not check container files freshness');
            }
        }

        // symfonyContainer global used in old site code
        if (empty($symfonyContainer)) {
            $symfonyContainer = $this->getContainer();
        }

        // may be move this configuration to container? override in aw.email.message?
        // swift boots on any request now, so it does not matter in terms of performance no
        // may be mailer service should be lazy
        \Swift_DependencyContainer::getInstance()
            ->register('mime.grammar')
            ->asSharedInstanceOf(\AwardWallet\MainBundle\Email\Grammar::class);

        // set site state for old code
        $Config[CONFIG_SITE_STATE] = $this->debug ? SITE_STATE_DEBUG : SITE_STATE_PRODUCTION;
        $Config[CONFIG_HTTPS_ONLY] = $this->getContainer()->getParameter("requires_channel") == 'https';

        if ($this->debug) {
            global $arPaymentType;
            $arPaymentType = $arPaymentType + [
                PAYMENTTYPE_TEST_CREDITCARD => "Test credit card",
                PAYMENTTYPE_TEST_PAYPAL => "Test PayPal",
            ];
        } else {
            $Config[CONFIG_CONNECTION_ERROR_HANDLER] = 'onConnectionError';
        }

        \AwardWallet\MainBundle\FrameworkExtension\ContainerConstants::define($symfonyContainer);
    }
}
