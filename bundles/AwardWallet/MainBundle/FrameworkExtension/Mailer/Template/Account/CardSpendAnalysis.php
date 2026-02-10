<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Account;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilder;

class CardSpendAnalysis extends AbstractTemplate
{
    public $user;
    public $top;
    public $queryChart;
    public $dateRange;

    public function __construct(Usr $user, $top, $queryChart, $dateRange)
    {
        parent::__construct($user);
        $this->top = $top;
        $this->queryChart = $queryChart;
        $this->dateRange = $dateRange;
    }

    public static function getDescription(): string
    {
        return 'Card Spend Analytics Report';
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        return new self(
            Tools::createUser(),
            [
                'category' => 'top category',
                'earned' => 'miles-points',
                'potential' => 'potential-points',
                'blogUrl' => 'blog-link',
            ],
            'query=chart',
            'date-range'
        );
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
