<?php

namespace AwardWallet\MainBundle\Form\Type\Mobile\SpentAnalysis;

use AwardWallet\MainBundle\Form\Helper\MobileExtensionLoader;
use AwardWallet\MobileBundle\Form\Type\BlockContainerType;
use AwardWallet\MobileBundle\Form\View\Block\SubTitle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class TransactionAnalysisType extends AbstractType
{
    /**
     * @var MobileExtensionLoader
     */
    private $mobileExtensionLoader;

    public function __construct(MobileExtensionLoader $mobileExtensionLoader)
    {
        $this->mobileExtensionLoader = $mobileExtensionLoader;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $data = $options['data'];

        $builder->add('owner', ChoiceType::class, [
            'label' => /** @Ignore */ 'Owner',
            'required' => false,
            'choices' => /** @Ignore */
                it($data['definition']['ownersList'])
                ->column('name')
                ->flip()
                ->toArrayWithKeys(), // Alexi Verschaga => 7_0
        ]);

        $builder->add('select_filter', ChoiceType::class, [
            'label' => /** @Ignore */ 'Card Used to Make Recommendations',
            'submitData' => true,
            'mapped' => false,
            'required' => false,
            /** @Ignore */
            'choices' => $selectFilterChoices = [
                'Select all' => 'all',
                'Select only personal cards' => 'card-personal',
                'Select only business cards' => 'card-business',
                'Select only the cards that I have' => 'card-have',
            ],
            'attr' => [
                'fields' => it($data['offer_cards'] ?? [])
                    ->flatten(1)
                    ->keys()
                    ->toArray(),
            ],
        ]);

        $offersCardsBuilder = $builder->create('offer_cards', FormType::class, [
            'required' => false,
            'submitData' => false,
        ]);

        foreach ($data['offer_cards'] as $name => $offerCards) {
            $this->addGroup(
                $offersCardsBuilder,
                $name . '_title',
                $data['definition']['providerData'][$name]['name']
            );

            $offersCardsBuilder->add(
                $name,
                CollectionType::class,
                [
                    'entry_type' => CardSwitcherType::class,
                    'prototype' => false,
                    'required' => false,
                    'submitData' => false,
                    'entry_options' => [
                        'submitData' => true,
                        'required' => false,
                        'empty_data' => false,
                    ],
                ]
            );
        }

        $builder->add($offersCardsBuilder);

        $this->mobileExtensionLoader->loadExtensionByPath($builder, 'engine/awextension/form/SpendAnalysisExtension.js');
    }

    public function getBlockPrefix()
    {
        return 'mobile_transaction_analysis';
    }

    private function addGroup(FormBuilderInterface $builder, string $name, string $title)
    {
        $builder->add($name, BlockContainerType::class, [
            'blockData' => new SubTitle($title),
        ]);
    }
}
