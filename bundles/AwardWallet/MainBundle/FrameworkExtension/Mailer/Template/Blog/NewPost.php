<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Blog;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilder;

class NewPost extends AbstractTemplate
{
    /**
     * @var string
     */
    public $unsubscribeUrl;
    /**
     * @var array
     */
    public $blogpost;

    public static function getDescription(): string
    {
        return "Blog New Post";
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder->add('customUnsubscribe', CheckboxType::class, [
            'label' => /** @Ignore */ 'Custom Unsubscribe Link',
        ]);
        $builder->add('registeredUser', CheckboxType::class, [
            'label' => /** @Ignore */ 'Registered user',
            'data' => true,
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static();

        if ($options['registeredUser'] ?? true) {
            $template->toUser(Tools::createUser());
        } else {
            $template->awUser = null;
            $user = new Usr();
            $user->setLogin('test@mail.com');
            $user->setCreationdatetime(new \DateTime());
            $template->toUser($user);
        }

        $template->blogpost = [
            'date' => time(),
            'title' => 'test blogpost title',
            'url' => 'https://awardwallet.com/blog/test-url',
            'image' => 'https://awardwallet.com/blog/wp-content/uploads/2017/10/United-30-Percent-Transfer-Bonus-from-Hotels-Fall-2017.jpg',
            'announce' => 'test blogpost announce',
        ];
        $template->unsubscribeUrl = isset($options['customUnsubscribe']) && $options['customUnsubscribe'] ? 'http://custom-unsubscribe-link' : null;

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
