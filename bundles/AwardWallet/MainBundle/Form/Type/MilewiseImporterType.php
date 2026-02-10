<?php

namespace AwardWallet\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class MilewiseImporterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('csv', FileType::class, [
            'label' => /** @Desc("Browse to the csv file") */ 'milewiseimporter.browse-to-csv.label',
            'attr' => [
                'notice' => /** @Desc("The name of the file is probably going to be: programs.csv") */ 'milewiseimporter.browse-to-csv.notice',
            ],
            'constraints' => [
                new Assert\File(
                    [
                        'maxSize' => '1024k',
                        'mimeTypes' => ['text/csv', 'text/plain'],
                        'mimeTypesMessage' => /** @Desc("Please upload a valid CSV") */ 'milewiseimporter.browse-to-csv.constraint.message',
                    ]
                ),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([]);
    }

    public function getBlockPrefix()
    {
        return 'milewise_importer';
    }
}
