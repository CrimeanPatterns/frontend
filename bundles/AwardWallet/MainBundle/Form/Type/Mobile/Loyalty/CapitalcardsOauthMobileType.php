<?php

namespace AwardWallet\MainBundle\Form\Type\Mobile\Loyalty;

use AwardWallet\MainBundle\Form\Transformer\CapitalcardsTransformerFactory;
use AwardWallet\MainBundle\Form\Type\OauthType;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\StringUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CapitalcardsOauthMobileType extends AbstractType
{
    /**
     * @var CapitalcardsTransformerFactory
     */
    private $capitalcardsTransformerFactory;

    public function __construct(CapitalcardsTransformerFactory $capitalcardsTransformerFactory)
    {
        $this->capitalcardsTransformerFactory = $capitalcardsTransformerFactory;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new CallbackTransformer(
            function ($value) {
                $val = \json_decode($value, true);

                return [
                    'miles' => StringUtils::isNotEmpty($val['rewards'] ?? null) ? $val['rewards'] : null,
                    'transactions' => StringUtils::isNotEmpty($val['tx'] ?? null) ? $val['tx'] : null,
                ];
            },
            function ($value) {
                return \json_encode([
                    'tx' => StringUtils::isNotEmpty($value['transactions'] ?? null) ? $value['transactions'] : null,
                    'rewards' => StringUtils::isNotEmpty($value['miles'] ?? null) ? $value['miles'] : null,
                ]);
            }
        ));
        $builder->addModelTransformer($this->capitalcardsTransformerFactory->make());
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $provider = $options['provider'];

        $view->vars['program_name'] = $provider->getName();
        $view->vars['request_id'] = StringHandler::getRandomCode(20);
        $view->vars['program_code'] = $provider->getCode();
        $view->vars['autologin_notice'] = $options['autologin_notice'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'allow_extra_fields' => true,
            'multiple' => true,
        ]);
    }

    public function getParent()
    {
        return OauthType::class;
    }

    public function getBlockPrefix()
    {
        return 'capitalcards_oauth_mobile';
    }
}
