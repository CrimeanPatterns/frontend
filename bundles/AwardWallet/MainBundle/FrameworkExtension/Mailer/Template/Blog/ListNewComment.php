<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Blog;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilder;

class ListNewComment extends AbstractTemplate
{
    /**
     * @var string
     */
    public $unsubscribeUrl;

    /**
     * @var array
     */
    public $blogComment;

    public static function getDescription(): string
    {
        return 'Blog List New Comments';
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder->add('customUnsubscribe', CheckboxType::class, [
            'label' => /** @Ignore */ 'Custom Unsubscribe Link',
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static(Tools::createUser());
        $template->blogPosts = [
            '481b036bf2234b4d113bf839f36a433d' => [
                'postLink' => 'blog-post--1',
                'postTitle' => 'Title blogpost 111',
                'postUpdate' => date('Y-m-d H:i'),
                'commentCount' => rand(1, 100),
                'comments' => [
                    [
                        'CommentLink' => 'https://awardwallet.com/blog/test-url-post#comment-test',
                        'CommentAuthor' => 'author-0',
                        'CommentEmail' => 'test0@awardwallet.com',
                        'CommentContent' => 'comment-text',
                        'CommentDate' => new \DateTime(),
                        'avatarSrc' => '100408c711a4986ae68d2bde65da51e0',
                    ],
                ],
            ],
        ];
        $template->unsubscribeUrl = isset($options['customUnsubscribe']) && $options['customUnsubscribe'] ? 'http://custom-unsubscribe-link' : null;

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
