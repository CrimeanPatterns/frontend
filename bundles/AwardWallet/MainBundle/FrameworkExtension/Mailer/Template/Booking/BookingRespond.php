<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Booking;

use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Form\Type\HtmleditorType;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilder;

class BookingRespond extends AbstractBookingTemplate
{
    public const TYPE_BASIC = 1;
    public const TYPE_INCLUDE = 2;

    /**
     * @var int
     */
    public $type = self::TYPE_INCLUDE;

    /**
     * @var AbMessage
     */
    public $message;

    public static function getDescription(): string
    {
        return 'Booker/user responded to booking request' /** @Ignore */;
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder = parent::tuneManagerForm($builder, $container);

        $builder->add('toBooker', CheckboxType::class, [
            'label' => /** @Ignore */ 'To booker',
        ]);
        $builder->add('typeMessage', ChoiceType::class, [
            'label' => /** @Ignore */ 'Message Type',
            'choices' => [
                /** @Ignore */
                'Basic' => self::TYPE_BASIC,
                /** @Ignore */
                'Include' => self::TYPE_INCLUDE,
            ],
        ]);
        $builder->add('post', HtmleditorType::class, [
            /** @Ignore */
            'label' => 'Booker post',
            'required' => false,
            'custom_config' => "/assets/awardwalletmain/js/sceditor/ckeditorConfig.js?v=" . FILE_VERSION,
            'transformers' => ['html_purifier'],
            'toolbar' => ['main'],
            'toolbar_groups' => [
                'main' => [
                    'Bold', 'Italic', 'Underline',
                    '-', 'TextColor', 'BGColor',
                    '-', 'NumberedList', 'BulletedList', 'Image',
                    '-', 'Cut', 'Copy', 'Paste', 'PasteText', // 'Source',
                    '-', 'Link', 'Unlink', 'Anchor',
                    '-', 'Undo', 'Redo', 'Maximize', '-', 'Templates', ],
            ],
            'ui_color' => null,
            'on' => "{
                            pluginsLoaded: function(e) {
                                e.editor.dataProcessor.dataFilter.addRules({
                                    elements: {
                                        $: function(element) {
                                            if (element.attributes.id) {
                                                delete element.attributes.id;
                                            }
                                            return element;
                                        }
                                    }
                                });
                            }
            }",
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        /** @var self $template */
        $template = parent::createFake($container, $options);
        $template->toBooker = isset($options['toBooker']) && $options['toBooker'];
        $template->confirm = !$template->toBooker;
        $template->enableUnsubscribe($template->toBooker, $template->toBooker);
        $template->toUser($template->request->getUser(), $template->toBooker);

        if (isset($options['typeMessage'])) {
            $template->type = $options['typeMessage'];
        }

        $message = Tools::createAbMessage('
             Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut
             labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut
             aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore
             eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt
             mollit anim id est laborum.
        ');

        if (isset($options['post']) && !empty($options['post'])) {
            $message->setPost($options['post']);
        }

        $template->request->addMessage($message);
        $template->message = $message;

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
