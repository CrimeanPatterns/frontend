<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User;

use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\AbstractTemplate;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\Tools;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilder;

class RetentionUser extends AbstractTemplate
{
    /**
     * @var bool
     */
    public $fromUS = false;

    /**
     * @var int
     */
    public $accountsCount = 0;

    /**
     * @var string html
     */
    public $ad;

    public static function getDescription(): string
    {
        return "AwardWallet Quick Follow Up";
    }

    public static function getKeywords(): array
    {
        return ['after register'];
    }

    public static function tuneManagerForm(FormBuilder $builder, ContainerInterface $container): FormBuilder
    {
        $builder->add('fromUS', CheckboxType::class, [
            'label' => /** @Ignore */ 'User from US',
        ]);
        $builder->add('accounts', NumberType::class, [
            'label' => /** @Ignore */ 'Number of accounts',
        ]);

        return $builder;
    }

    public static function createFake(ContainerInterface $container, $options = []): self
    {
        $template = new static(Tools::createUser(ACCOUNT_LEVEL_AWPLUS));
        $template->fromUS = isset($options['fromUS']) && $options['fromUS'];
        $template->accountsCount = isset($options['accounts']) ? intval($options['accounts']) : 0;

        $em = $container->get('doctrine.orm.default_entity_manager');
        $сriteria = new Criteria();
        $e = $сriteria->expr();
        $сriteria->where($e->andX(
            $e->eq('kind', ADKIND_RETENTION),
            $e->orX(
                $e->isNull('begindate'),
                $e->lt('begindate', new \DateTime())
            ),
            $e->orX(
                $e->isNull('enddate'),
                $e->gt('enddate', new \DateTime())
            )
        ));
        $ad = $em->getRepository(\AwardWallet\MainBundle\Entity\Socialad::class)
            ->matching($сriteria);

        if ($ad->count() > 0 && $template->fromUS) {
            $template->ad = $ad->first()->getContent();
        }

        return $template;
    }

    public static function getStatus(): int
    {
        return static::STATUS_READY;
    }
}
