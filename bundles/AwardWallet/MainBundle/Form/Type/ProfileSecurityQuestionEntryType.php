<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\UserQuestion;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Model\Profile\SecurityQuestionEntryModel;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\StringUtils;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileSecurityQuestionEntryType extends AbstractType implements TranslationContainerInterface
{
    private Usr $user;

    public function __construct(AwTokenStorageInterface $tokenStorage)
    {
        $this->user = $tokenStorage->getUser();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    public function onPreSetData(PreSetDataEvent $event): void
    {
        /** @var SecurityQuestionEntryModel $data */
        $data = $event->getData();
        $form = $event->getForm();

        $hiddenChoices = $this->getHiddenChoices($data->getSortIndex());
        $form->add('question', ChoiceType::class, [
            'required' => false,
            'label' => /** @Desc("Question") */ 'user.security_question.question',
            'choices' => UserQuestion::getQuestionsArray(),
            'choice_attr' => function ($choice) use ($hiddenChoices) {
                if (in_array($choice, $hiddenChoices)) {
                    return ['style' => 'display: none;'];
                }

                return [];
            },
            'placeholder' => 'please-select',
        ]);
        $form->add('answer', TextType::class, [
            'required' => false,
            'label' => /** @Desc("Answer") */ 'user.security_question.answer',
        ]);
    }

    public function onPreSubmit(PreSubmitEvent $event): void
    {
        $data = $event->getData();

        if (StringUtils::isEmpty($data['question'])) {
            $data['answer'] = '';
        }

        $event->setData($data);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SecurityQuestionEntryModel::class,
        ]);
    }

    /**
     * @return Message[]
     */
    public static function getTranslationMessages()
    {
        return [
            (new Message('user.questions.childhood-nickname'))->setDesc('What was your childhood nickname?'),
            (new Message('user.questions.meeting-spouse-significant-other'))->setDesc('In what city did you meet your spouse/significant other?'),
            (new Message('user.questions.favorite-childhood-friend'))->setDesc('What is the name of your favorite childhood friend?'),
            (new Message('user.questions.street-third-grade'))->setDesc('What street did you live on in third grade?'),
            (new Message('user.questions.oldest-sibling-birthday'))->setDesc('What is your oldest sibling\'s birthday month and year? (e.g., January 1900)'),
            (new Message('user.questions.middle-name-youngest-child'))->setDesc('What is the middle name of your youngest child?'),
            (new Message('user.questions.oldest-sibling-middle-name'))->setDesc('What is your oldest sibling\'s middle name?'),
            (new Message('user.questions.school-sixth-grade'))->setDesc('What school did you attend for sixth grade?'),
            (new Message('user.questions.childhood-phone-number'))->setDesc('What was your childhood phone number including area code? (e.g., 000-000-0000)'),
            (new Message('user.questions.oldest-cousin-name'))->setDesc('What is your oldest cousin\'s first and last name?'),
            (new Message('user.questions.name-stuffed-animal'))->setDesc('What was the name of your first stuffed animal?'),
            (new Message('user.questions.city-mother-father-meeting'))->setDesc('In what city or town did your mother and father meet?'),
            (new Message('user.questions.place-first-kiss'))->setDesc('Where were you when you had your first kiss?'),
            (new Message('user.questions.name-boy-girl-first-kiss'))->setDesc('What is the first name of the boy or girl that you first kissed?'),
            (new Message('user.questions.name-third-grade-teacher'))->setDesc('What was the last name of your third grade teacher?'),
            (new Message('user.questions.city-nearest-sibling'))->setDesc('In what city does your nearest sibling live?'),
            (new Message('user.questions.youngest-brother-birthday'))->setDesc('What is your youngest brother\'s birthday month and year? (e.g., January 1900)'),
            (new Message('user.questions.grandmother-maiden-name'))->setDesc('What is your maternal grandmother\'s maiden name?'),
            (new Message('user.questions.city-first-job'))->setDesc('In what city or town was your first job?'),
            (new Message('user.questions.place-wedding-reception'))->setDesc('What is the name of the place your wedding reception was held?'),
            (new Message('user.questions.college-didnt-attend'))->setDesc('What is the name of a college you applied to but didn\'t attend?'),
            (new Message('user.questions.first-heard-9-11'))->setDesc('Where were you when you first heard about 9/11?'),
        ];
    }

    /**
     * Get the questions to be hidden in the dropdown list.
     *
     * @param int $order numeric index of the current dropdown list
     */
    private function getHiddenChoices(int $order): array
    {
        $choices = [];

        foreach ($this->user->getSecurityQuestions() as $question) {
            if ($question->getOrder() !== $order) {
                $choices[] = $question->getQuestion();
            }
        }

        return $choices;
    }
}
