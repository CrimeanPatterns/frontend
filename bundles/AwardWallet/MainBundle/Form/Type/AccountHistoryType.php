<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\AccountHistory;
use AwardWallet\MainBundle\Form\Transformer\BalanceTransformer;
use AwardWallet\MainBundle\Globals\GlobalVariables;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AccountHistoryType extends AbstractType
{
    private $globalVariables;
    private $exclude = [
        'Miles',
        'PostingDate',
        'Description',
    ];

    public function __construct(GlobalVariables $globalVariables)
    {
        $this->globalVariables = $globalVariables;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('postingdate', DatePickerType::class, [
            'required' => true,
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ]);
        $builder->add('description', TextareaType::class, ['required' => false, 'allow_quotes' => true]);
        $builder->add('miles', NumberType::class, ['required' => false]);
        $builder->get('miles')->addViewTransformer(new BalanceTransformer());

        /** @var Account $account */
        $account = $options['account'];
        $fields = $this->globalVariables->getAccountChecker($account->getProviderid())->GetHistoryColumns();

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($fields) {
            $form = $event->getForm();

            /** @var AccountHistory $data */
            $data = $event->getData();

            foreach ($fields as $key => $field) {
                if (!in_array($field, $this->exclude)) {
                    $form->add(md5($key), TextType::class, [
                        'mapped' => false,
                        'label' => $key,
                        'data' => isset($data->getInfo()[$key]) ? $data->getInfo()[$key] : '',
                        'required' => false,
                    ]);
                }
            }

            $form->add('note', TextareaType::class, [
                /** @Ignore */
                'label' => 'Notes',
                'allow_quotes' => true,
                'allow_urls' => true,
                'required' => false,
            ]);
        });

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($fields) {
            $form = $event->getForm();

            foreach ($fields as $key => $field) {
                if (in_array($field, $this->exclude)) {
                    $formField = $form->get(strtolower($field));
                    $config = $formField->getConfig();
                    $options = $config->getOptions();
                    $options['label'] = $key;
                    $form->add($formField->getName(), get_class($config->getType() instanceof ResolvedFormTypeInterface ? $config->getType()->getInnerType() : $config->getType()), $options);
                }
            }
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($fields) {
            $form = $event->getForm();

            /** @var AccountHistory $data */
            $data = $event->getData();

            $result = [];

            foreach ($fields as $key => $field) {
                if (!in_array($field, $this->exclude)) {
                    $result[$key] = $form->get(md5($key))->getViewData();
                }
            }

            $data->setInfo($result);
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AccountHistory::class,
            'account' => null,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'account_history';
    }
}
