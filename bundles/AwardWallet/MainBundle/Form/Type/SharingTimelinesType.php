<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Transformer\SharedTimelinesTransformerFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SharingTimelinesType extends AbstractType
{
    /**
     * @var SharedTimelinesTransformerFactory
     */
    private $sharedTimelinesTransformerFactory;

    public function __construct(
        SharedTimelinesTransformerFactory $sharedTimelinesTransformerFactory
    ) {
        $this->sharedTimelinesTransformerFactory = $sharedTimelinesTransformerFactory;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Useragent $useragent */
        $useragent = $builder->getOption('useragent');
        /** @var Usr $usr */
        $user = $useragent->getClientid();

        $builder->add('my', CheckboxType::class, [
            'label' => $user->getFullName(),
            'required' => false,
        ]);

        /** @var Useragent $familyMember */
        foreach ($user->getFamilyMembers() as $familyMember) {
            $builder->add($familyMember->getUseragentid(), CheckboxType::class, [
                'label' => $familyMember->getFullName(),
                'required' => false,
            ]);
        }

        $builder->addModelTransformer(
            $this->sharedTimelinesTransformerFactory->createTransformer()
        );
    }

    public function getBlockPrefix()
    {
        return 'sharing_timelines';
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /** @var Usr|Useragent $timeline */
        foreach ($form->getData() as [$timeline, $_]) {
            if ($timeline instanceof Usr) {
                $view->vars['my'] = [
                    'id' => 'my',
                    'name' => $timeline->getFullName(),
                ];
            } else {
                $view->vars[(string) $timeline->getUseragentid()] = [
                    'id' => $timeline->getUseragentid(),
                    'name' => $timeline->getFullName(),
                ];
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([]);
        $resolver->setRequired('useragent');
    }
}
