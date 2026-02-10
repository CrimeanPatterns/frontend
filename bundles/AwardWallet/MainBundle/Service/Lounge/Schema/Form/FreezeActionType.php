<?php

namespace AwardWallet\MainBundle\Service\Lounge\Schema\Form;

use AwardWallet\MainBundle\Entity\Lounge;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FreezeActionType extends AbstractType
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $props = array_values(
            array_filter(
                array_merge(
                    $this->em->getClassMetadata(Lounge::class)->getColumnNames(),
                    ['Airlines', 'Alliances']
                ),
                function ($column) {
                    return !in_array($column, [
                        'LoungeID',
                        'AirportCode',
                        'Name',
                        'Terminal',
                        'Gate',
                        'Gate2',
                        'CreateDate',
                        'UpdateDate',
                        'CheckedBy',
                        'CheckedDate',
                        'Visible',
                        'AttentionRequired',
                        'State',
                        'LocationParaphrased',
                        'OpeningHoursAi',
                    ]);
                }
            )
        );

        $builder
            ->add('props', ChoiceType::class, [
                'label' => /** @Ignore */ 'Freeze properties',
                'choices' => array_combine($props, $props),
                'label_attr' => [
                    'class' => 'switch-custom',
                ],
                'expanded' => true,
                'multiple' => true,
                'help' => /** @Ignore */ 'Properties which will be frozen and not updated by parsers.',
            ])
            ->add('emails', TextType::class, [
                'label' => /** @Ignore */ 'Emails',
                'help' => /** @Ignore */ 'Emails which will be notified about changes in frozen lounge. Separate emails by comma.',
            ])
            ->add('deleteDate', DateType::class, [
                'label' => /** @Ignore */ 'Unfreeze date',
                'widget' => 'single_text',
                'help' => /** @Ignore */ 'Date when lounge will be unfrozen.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => FreezeActionModel::class,
            'label' => false,
        ]);
    }
}
