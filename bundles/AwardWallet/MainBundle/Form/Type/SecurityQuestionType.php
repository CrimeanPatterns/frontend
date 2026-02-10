<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Form\Helper\MobileExtensionLoader;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class SecurityQuestionType extends AbstractType
{
    /**
     * @var MobileExtensionLoader
     */
    protected $mobileExtensionLoader;

    public function __construct(MobileExtensionLoader $mobileExtensionLoader)
    {
        $this->mobileExtensionLoader = $mobileExtensionLoader;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $questions = $options['data'];
        $choices = [];
        $typeMap = [];

        foreach ($questions as $question) {
            $choices[$question['question']] = $question['question'];
            $typeMap[$question['question']] = $question['maskInput'] ? 'password' : 'text';
        }

        $builder->add(
            'question',
            ChoiceType::class,
            [
                'label' => 'questions.text',
                'choices' => $choices,
                'placeholder' => 'select-question',
                'attr' => [
                    'type_map' => $typeMap,
                ],
            ]
        );
        $builder->add('answer', TextType::class, ['label' => 'award.account.popup.updater-question.label']);
        $this->mobileExtensionLoader->loadExtensionByPath($builder, 'mobile/scripts/controllers/SecurityQuestionsExtension.js');
    }
}
