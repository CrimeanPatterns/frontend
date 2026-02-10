<?php

namespace AwardWallet\MainBundle\Form\Type\Mobile\SpentAnalysis;

use AwardWallet\MainBundle\Form\Helper\MobileExtensionLoader;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MobileBundle\Form\Type\BlockContainerType;
use AwardWallet\MobileBundle\Form\View\Block\SubTitle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class SpentAnalysisType extends AbstractType
{
    private MobileExtensionLoader $mobileExtensionLoader;
    private ApiVersioningService $apiVersioning;

    public function __construct(
        MobileExtensionLoader $mobileExtensionLoader,
        ApiVersioningService $apiVersioning
    ) {
        $this->mobileExtensionLoader = $mobileExtensionLoader;
        $this->apiVersioning = $apiVersioning;
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
            'attr' => [
                'fields' => it($data['definition']['ownersList'])
                    ->mapIndexed(function (array $_, string $ownerId) { return "owner_cards:{$ownerId}"; })
                    ->toArrayWithKeys(),
            ],
        ]);

        $builder->add('date_range', ChoiceType::class, [
            'label' => /** @Ignore */ 'Date Range',
            'required' => false,
            'choices' => /** @Ignore */
                it($data['definition']['dateRanges'])
                ->reindexByColumn('name')
                ->column('value')
                ->toArrayWithKeys(), // Current Month => 1
        ]);

        $ownersCardsBuilder = $builder->create('owner_cards', FormType::class, [
            'required' => false,
            'submitData' => false,
            'attr' => [
                'providers' => $data['definition']['providerData'],
            ],
        ]);

        $availableCards = it($data['definition']['ownersList'])
            ->flatMap(function ($row) {
                return $row['availableCards'];
            });
        $subAccountCards = it($availableCards)
            ->flatMapIndexed(function ($value, $index) {
                yield $value['subAccountId'] => $value;
            })
            ->toArrayWithKeys();

        foreach ($data['definition']['ownersList'] as $name => $ownerData) {
            $ownersCardsBuilder->add(
                "owner_cards:{$name}",
                ChoiceType::class,
                [
                    'label' => /** @Ignore */ 'Credit Cards',
                    'multiple' => true,
                    'required' => false,
                    'choices' => /** @Ignore */
                        it($ownerData['availableCards'])
                        ->reindexByColumn('creditCardName')
                        ->column('subAccountId')
                        ->toArrayWithKeys(),
                    'choice_attr' => function ($choice, $key, $index) use ($subAccountCards) {
                        if (!array_key_exists($choice, $subAccountCards)) {
                            return [];
                        }

                        return [
                            'image' => $subAccountCards[$choice]['creditCardImage'],
                            'providerCode' => $subAccountCards[$choice]['providerCode'],
                        ];
                    },
                ]
            );
        }

        $builder->add($ownersCardsBuilder);

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
            'choice_attr' => function ($choice, $key, $index) {
                switch ($choice) {
                    case 'all':
                        return ['title' => 'All types'];

                    case 'card-personal':
                        return ['title' => 'Personal'];

                    case 'card-business':
                        return ['title' => 'Business'];

                    case 'card-have':
                        return ['title' => 'That I have'];
                }

                return [];
            },
        ]);

        $offersCardsBuilder = $builder->create('offer_cards', FormType::class, [
            'required' => false,
            'submitData' => false,
        ]);

        foreach ($data['offer_cards'] as $name => $offerCards) {
            if (!$this->apiVersioning->supports(MobileVersions::SPENT_ANALYSIS_FORMAT_V2)) {
                $this->addGroup(
                    $offersCardsBuilder,
                    $name . '_title',
                    $data['definition']['providerData'][$name]['name']
                );
            }

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
                    'attr' => [
                        'providerName' => $data['definition']['providerData'][$name]['name'] ?? null,
                        'providerCode' => $data['definition']['providerData'][$name]['code'] ?? null,
                    ],
                ]
            );
        }

        $builder->add($offersCardsBuilder);

        $this->mobileExtensionLoader->loadExtensionByPath($builder, 'engine/awextension/form/SpendAnalysisExtension.js');
    }

    public function getBlockPrefix()
    {
        return 'mobile_spent_analysis';
    }

    private function addGroup(FormBuilderInterface $builder, string $name, string $title)
    {
        $builder->add($name, BlockContainerType::class, [
            'blockData' => new SubTitle($title),
        ]);
    }
}
