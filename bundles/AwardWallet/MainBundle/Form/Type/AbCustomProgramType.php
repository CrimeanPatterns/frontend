<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\AbCustomProgram;
use AwardWallet\MainBundle\Form\Transformer\BalanceTransformer;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AbCustomProgramType extends AbstractType
{
    /**
     * @var AbCustomProgram
     */
    protected $data;

    protected $providerRepo;
    protected $provider;

    public function __construct(EntityManager $em)
    {
        $this->providerRepo = $em->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('Name', TextType::class, [
            'label' => 'account.program',
            'required' => true,
            'attr' => ['class' => 'cp-autocomplete', 'maxlength' => 255],
        ]);
        $builder->add('Owner', TextType::class, [
            'label' => 'account.owner',
            'required' => true,
            'attr' => [
                'maxlength' => 255,
            ],
        ]);
        $builder->add('EliteStatus', TextType::class, [
            'label' => 'account.status.ifany',
            'required' => false,
            'attr' => [
                'maxlength' => 255,
            ],
        ]);
        $builder->add('Balance', NumberType::class, [
            'label' => 'account.balance',
            'required' => true,
            'error_bubbling' => false,
            'compound' => false,
        ]);
        $builder->get('Balance')->addViewTransformer(new BalanceTransformer());
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            // set status options on form load
            /** @var AbCustomProgram $data */
            $data = $event->getData();

            if (!empty($data)) {
                $this->setStatusOptions($event->getForm(), $data->getName(), $data->getEliteStatus());
            }
        });
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            // save Name.POST_SUBMIT, because we have not this information in field handler
            $this->data = $event->getData();
        });
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $event->getData()->setProvider($this->provider);

            if (empty($this->provider)) {
                $event->getData()->setRequested(false);
            }
        });
        $builder->get('Name')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            // set dropdown options and value on submit
            $this->setStatusOptions($event->getForm()->getParent(), $event->getData(), empty($this->data['EliteStatus']) ? null : $this->data['EliteStatus']);
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'AwardWallet\\MainBundle\\Entity\\AbCustomProgram',
            'translation_domain' => 'messages',
        ]);
    }

    public function getBlockPrefix()
    {
        return 'booking_request_custom_program';
    }

    /** @noinspection PhpDeprecationInspection */
    protected function setStatusOptions(FormInterface $form, $displayName, $data)
    {
        $this->provider = null;

        if (!empty($displayName)) {
            $provider = $this->providerRepo->findOneBy(['displayname' => $displayName]);

            if (isset($provider)) {
                $this->provider = $provider;
                $levels = $this->providerRepo->getEliteLevels($provider->getProviderid());

                if (!empty($levels)) {
                    $form->add('EliteStatus', ChoiceType::class, [
                        'label' => 'account.status.ifany',
                        'required' => true,
                        'choices' => array_combine($levels, $levels),
                        'data' => $data,
                        'attr' => ['data-provider' => $displayName],
                    ]);
                    //					if(!empty($this->data['EliteStatus'])) // caused error in symfony 2.5
                    //						$form['EliteStatus']->submit($this->data['EliteStatus']);
                }
            }
        }
    }
}
