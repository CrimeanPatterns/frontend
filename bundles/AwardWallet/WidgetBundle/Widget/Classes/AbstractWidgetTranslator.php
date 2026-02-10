<?php

namespace AwardWallet\WidgetBundle\Widget\Classes;

use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

abstract class AbstractWidgetTranslator extends AbstractWidget implements TranslationContainerInterface
{
    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return [
            (new Message('menu.promos', 'menu'))->setDesc('Promos'),
            (new Message('menu.reviews', 'menu'))->setDesc('Reviews'),
            (new Message('menu.contact-us', 'menu'))->setDesc('Contact Us'),
            (new Message('menu.my-account', 'menu'))->setDesc('My Account'),
            (new Message('menu.business-account', 'menu'))->setDesc('Business Account'),
            (new Message('menu.personal-settings', 'menu'))->setDesc('Edit Personal Info'),
            (new Message('menu.regional-settings', 'menu'))->setDesc('Edit Regional Settings'),
            (new Message('menu.business-api', 'menu'))->setDesc('API Settings'),
            (new Message('menu.edit-website-settings', 'menu'))->setDesc('Edit Website Settings'),
            (new Message('menu.faqs', 'menu'))->setDesc('FAQs'),
            (new Message('menu.news', 'menu'))->setDesc('News'),
            (new Message('menu.onecard', 'menu'))->setDesc('OneCard'),
            (new Message('menu.onecard.new', 'menu'))->setDesc('Place New Order'),
            (new Message('menu.onecard.history', 'menu'))->setDesc('Order History'),
            (new Message('menu.onecard.order', 'menu'))->setDesc('OneCard Order (%count%)'),
            (new Message('menu.credit-card-offers', 'menu'))->setDesc('Credit Card Offers'),
            (new Message('menu.blog', 'menu'))->setDesc('Blog'),
            (new Message('menu.community', 'menu'))->setDesc('Community'),
            (new Message('menu.tools', 'menu'))->setDesc('Tools'),
            (new Message('credit-card.merchant_reverse_title'))->setDesc('Reverse Merchant Lookup'),

            (new Message('menu.privacy-notice', 'menu'))->setDesc('Privacy Notice'),
            (new Message('menu.terms-of-use', 'menu'))->setDesc('Terms of Use'),
            (new Message('menu.about-us', 'menu'))->setDesc('About Us'),
            (new Message('menu.media', 'menu'))->setDesc('Media'),
            (new Message('menu.services', 'menu'))->setDesc('Services'),
            (new Message('menu.partners', 'menu'))->setDesc('Partners'),
            (new Message('menu.supported', 'menu'))->setDesc('Supported Programs'),
            (new Message('award-booking-service', 'menu'))->setDesc('Award Booking Service'),

            (new Message('language.en', 'menu'))->setDesc('English'),
            (new Message('language.ru', 'menu'))->setDesc('Russian'),
            (new Message('language.de', 'menu'))->setDesc('German'),
            (new Message('language.fr', 'menu'))->setDesc('French'),
            (new Message('language.pt', 'menu'))->setDesc('Portugal'),
            (new Message('language.es', 'menu'))->setDesc('Spain'),
            (new Message('language.ja', 'menu'))->setDesc('Japan'),
            (new Message('language.it', 'menu'))->setDesc('Italy'),
            (new Message('language.ch', 'menu'))->setDesc('Chinese'),
            (new Message('language.zh_TW', 'menu'))->setDesc('Chinese (Traditional)'),
            (new Message('language.zh_CN', 'menu'))->setDesc('Chinese (Simplified)'),
        ];
    }
}
