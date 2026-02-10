<?php

namespace AwardWallet\MainBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class AwardWalletMainExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('accountlist.yml');
        $loader->load('admin.yml');
        $loader->load('controllers.yml');
        $loader->load('diff.yml');
        $loader->load('email.yml');
        $loader->load('extension.yml');

        $loader->load('form/add_agent.yml');
        $loader->load('form/booking/invoice.yml');
        $loader->load('form/booking/message.yml');
        $loader->load('form/common.yml');
        $loader->load('form/location.yml');
        $loader->load('form/profile/connection.yml');
        $loader->load('form/profile/coupon.yml');
        $loader->load('form/profile/email.yml');
        $loader->load('form/profile/notification.yml');
        $loader->load('form/profile/other_settings.yml');
        $loader->load('form/profile/password.yml');
        $loader->load('form/profile/personal.yml');
        $loader->load('form/profile/regional.yml');
        $loader->load('form/program/account.yml');
        $loader->load('form/program/common.yml');
        $loader->load('form/program/provider_coupon.yml');

        $loader->load('handler.yml');
        $loader->load('itineraries_processing.yml');
        $loader->load('locale.yml');
        $loader->load('manager.yml');
        $loader->load('mobile_view.yml');
        $loader->load('oauth.yml');
        $loader->load('repositories.yml');
        $loader->load('security.yml');
        $loader->load('services.yml');

        $loader->load('timeline/common.yml');
        $loader->load('timeline/formatters/desktop.yml');
        $loader->load('timeline/formatters/mobile.yml');
    }

    /**
     * Allow an extension to prepend the extension configurations.
     */
    public function prepend(ContainerBuilder $container)
    {
        // framework.assets.base_urls does not allow empty string, so, we will add base_urls only when cdn_host is not empty
        $host = $container->getParameter("cdn_host");

        if (!empty($host)) {
            $container->prependExtensionConfig('framework', ['assets' => ['base_urls' => [$host]]]);
        }
    }
}
