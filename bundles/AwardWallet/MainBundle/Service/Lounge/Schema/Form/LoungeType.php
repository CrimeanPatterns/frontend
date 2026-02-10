<?php

namespace AwardWallet\MainBundle\Service\Lounge\Schema\Form;

use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\Alliance;
use AwardWallet\MainBundle\Entity\Repositories\AirlineRepository;
use AwardWallet\MainBundle\Form\Type\HtmlType;
use Doctrine\DBAL\Connection;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class LoungeType extends AbstractType
{
    private Connection $connection;

    private LoungeTransformer $transformer;

    public function __construct(Connection $connection, LoungeTransformer $transformer)
    {
        $this->connection = $connection;
        $this->transformer = $transformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choiceOptions = [
            'required' => true,
            'attr' => ['class' => 'col-3'],
            /** @Ignore */
            'choices' => [
                /** @Ignore */
                'Yes' => true,
                /** @Ignore */
                'No' => false,
                /** @Ignore */
                'Unknown' => null,
            ],
        ];

        $builder
            ->add('name', TextType::class, ['disabled' => true])
            ->add('airportCode', TextType::class, ['disabled' => true])
            ->add('terminal', TextType::class, ['disabled' => true])
            ->add('gate', TextType::class, ['disabled' => true])
            ->add('gate2', TextType::class, ['disabled' => true])
            ->add('airlines', EntityType::class, [
                'class' => Airline::class,
                'choice_label' => 'name',
                'query_builder' => fn (AirlineRepository $repo) => $repo->createQueryBuilder('a')->orderBy('a.name'),
                'multiple' => true,
            ])
            ->add('alliances', EntityType::class, [
                'class' => Alliance::class,
                'choice_label' => 'name',
                'multiple' => true,
            ])
            ->add('openingHours', TextareaType::class, ['allow_quotes' => true, 'allow_tags' => true])
            ->add('isRawOpeningHours', CheckboxType::class, ['label_attr' => ['class' => 'switch-custom']])
            ->add('isAvailable', CheckboxType::class, ['label_attr' => ['class' => 'switch-custom']])
            ->add('priorityPassAccess', ChoiceType::class, $choiceOptions)
            ->add('amexPlatinumAccess', ChoiceType::class, $choiceOptions)
            ->add('dragonPassAccess', ChoiceType::class, $choiceOptions)
            ->add('loungeKeyAccess', ChoiceType::class, $choiceOptions)
            ->add('location', TextareaType::class, ['allow_quotes' => true, 'allow_tags' => true, 'allow_urls' => true])
            ->add('locationParaphrased', TextareaType::class, [
                'required' => false,
                'allow_quotes' => true,
                'allow_tags' => true,
                'allow_urls' => true,
                'help' => /** @Ignore */ 'This field is shown to the user instead of Location. If left blank, AI will generate a lounge description based on Location. If filled in, AI will not overwrite it.',
            ])
            ->add('additionalInfo', TextareaType::class, ['allow_quotes' => true, 'allow_tags' => true, 'allow_urls' => true])
            ->add('amenities', TextareaType::class, ['allow_quotes' => true, 'allow_tags' => true, 'allow_urls' => true])
            ->add('rules', TextareaType::class, ['allow_quotes' => true, 'allow_tags' => true, 'allow_urls' => true])
            ->add('isRestaurant', ChoiceType::class, $choiceOptions)
            ->add('createDate', DateTimeType::class, [
                'disabled' => true,
                'date_widget' => 'single_text',
                'time_widget' => 'single_text']
            )
            ->add('updateDate', DateTimeType::class, [
                'disabled' => true,
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
            ])
            ->add('checkedDate', DateTimeType::class, [
                'disabled' => true,
                'date_widget' => 'single_text',
                'time_widget' => 'single_text']
            )
            ->add('checkedBy', TextType::class, [
                'disabled' => true,
                'attr' => ['class' => 'col-3'],
            ])
            ->add('visible', CheckboxType::class, [
                'label_attr' => ['class' => 'switch-custom'],
                'help' => /** @Ignore */ 'Lounge is visible to users',
            ]);

        // on event add field after 'visible' field
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            /** @var LoungeModel $lounge */
            $lounge = $form->getData();
            $changes = $this->connection->executeQuery("
                SELECT lsc.* 
                FROM LoungeSourceChange lsc
                    JOIN LoungeSource ls ON lsc.LoungeSourceID = ls.LoungeSourceID
                WHERE ls.LoungeID = ?
                ORDER BY lsc.ChangeDate DESC
            ", [$lounge->getId()])
                ->fetchAllAssociative();

            $html = [];

            foreach ($lounge->getSources() as $source) {
                $sourceChanges = it($changes)
                    ->filter(fn ($change) => $change['LoungeSourceID'] == $source->getId())
                    ->usort(fn ($a, $b) => $b['ChangeDate'] <=> $a['ChangeDate'])
                    ->toArray();
                $lastChange = reset($sourceChanges);
                $html[] = [
                    'lastChange' => is_array($lastChange) ? strtotime($lastChange['ChangeDate']) : 0,
                    'source' => sprintf(
                        '<a href="/manager/list.php?Schema=LoungeSource&LoungeSourceID=%d" target="_blank">%s (%s)</a>, <a href="%s" target="_blank">web</a>%s',
                        $source->getId(),
                        $source->getName(),
                        $source->getSourceCode(),
                        $source->getUrl(),
                        \count($sourceChanges) > 0
                            ? sprintf(
                                ", <a href='javascript:void(0);' class='lounge-changes' data-changes=\"%s\">%d changes</a>, last: %s",
                                htmlspecialchars(json_encode($sourceChanges)),
                                \count($sourceChanges),
                                $lastChange['ChangeDate']
                            ) : ''
                    ),
                ];
            }

            $form
                ->add('sources', HtmlType::class, [
                    'required' => false,
                    'mapped' => false,
                    'html' => sprintf('<p>%s</p>', it($html)
                        ->usort(fn ($a, $b) => $b['lastChange'] <=> $a['lastChange'])
                        ->map(fn ($item) => $item['source'])
                        ->joinToString('<br>')),
                ])
                ->add('freezeAction', FreezeActionType::class)
                ->add('removeOpeningHoursAi', CheckboxType::class, [
                    'mapped' => false,
                    'data' => false,
                    'label_attr' => ['class' => 'switch-custom'],
                    'help' => /** @Ignore */ 'Delete AI-generated opening hours',
                ])
                ->add('removeChanges', CheckboxType::class, [
                    'mapped' => false,
                    'data' => true,
                    'label_attr' => ['class' => 'switch-custom'],
                    'help' => /** @Ignore */ 'Delete all property changes across all sources (change indicator only)',
                ])
                ->add('checked', CheckboxType::class, [
                    'mapped' => false,
                    'data' => true,
                    'label_attr' => ['class' => 'switch-custom'],
                    'help' => /** @Ignore */ 'The lounge has been thoroughly checked. Only the information for the verifier will be removed. In the manager\'s list, the lounge will not have priority display.',
                ])
                ->add('save', SubmitType::class);
        });

        $builder->addModelTransformer($this->transformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => LoungeModel::class,
            'required' => false,
        ]);
    }
}
