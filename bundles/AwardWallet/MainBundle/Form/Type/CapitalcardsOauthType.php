<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Form\Transformer\CapitalcardsTransformerFactory;
use AwardWallet\MainBundle\Globals\StringHandler;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class CapitalcardsOauthType extends AbstractType
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
        // TODO: check mobile compatibility
        // $builder->resetModelTransformers();

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

    public function getParent()
    {
        return OauthType::class;
    }

    public function getBlockPrefix()
    {
        return 'capitalcards_oauth';
    }
}
