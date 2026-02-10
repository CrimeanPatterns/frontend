<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\Contactus;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactUsAuthType extends AbstractType implements TranslationContainerInterface
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = array_keys(self::getRequesttypes());
        $choices_text = self::getRequesttypesLabels();

        if ($options['disable_booking']) {
            unset($choices[sizeof($choices) - 1]);
            unset($choices_text[sizeof($choices_text) - 1]);
        }
        $builder->add('requesttype', ChoiceType::class, [
            'choices' => array_combine($choices_text, $choices),
            'label' => /** @Desc("Request Type") */ 'contactus.request-type.label',
            'error_bubbling' => false,
            'placeholder' => /** @Desc("Please select") */ 'contactus.request-type.empty-value',
            'attr' => ['class' => 'selectTxt'],
        ]);
        $builder->add('message', TextareaType::class, [
            'label' => /** @Desc("Message") */ 'contactus.message.label',
            'allow_quotes' => true,
            'allow_urls' => true,
            'error_bubbling' => false,
            'attr' => ['class' => 'inputTxt', 'rows' => 15],
        ]);
        $builder->add('shownData', HiddenType::class);
        $builder->add('extensionVersion', HiddenType::class, ['required' => false, 'mapped' => false]);
        $builder->add('v3', HiddenType::class, ['required' => false, 'mapped' => false]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => ['auth'],
            'translation_domain' => 'contactus',
            'data_class' => Contactus::class,
            'disable_booking' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'contact_us_auth';
    }

    public static function getRequesttypes()
    {
        return [
            'General' => ['General'],
            'Suggestions' => ['Suggestions'],
            'Issue' => ['Issue'],
            'Abuse' => ['Abuse'],
            'Marketing' => ['Marketing'],
            'Business Development' => ['Business'],
            'Award Ticket Booking Requests' => ['Booking', 'AwardBooking'],
            'AwardWallet API Inquiry' => ['APIInquiry', 'API', 'AwardWalletAPI'],
            'Security / Report Vulnerability' => ['Security', 'Vulnerability'],
        ];
    }

    public function getRequesttypesLabels()
    {
        $result = [];

        foreach (self::getTranslationMessages() as $message) {
            $result[] = $this->translator->trans($message);
        }

        return $result;
    }

    /**
     * Returns an array of messages.
     *
     * @return array<Message>
     */
    public static function getTranslationMessages()
    {
        return [
            (new Message('contactus.general'))->setDesc('General'),
            (new Message('contactus.suggestions'))->setDesc('Suggestions'),
            (new Message('contactus.issue'))->setDesc('Report an Issue'),
            (new Message('contactus.abuse'))->setDesc('Report an Abuse'),
            (new Message('contactus.inquiries'))->setDesc('Marketing Inquiries'),
            (new Message('contactus.busines-development'))->setDesc('Business Development'),
            (new Message('contactus.booking'))->setDesc('Award Ticket Booking Requests'),
            (new Message('awardwallet-api-inquiry'))->setDesc('AwardWallet API Inquiry'),
            (new Message('security-report-vulnerability'))->setDesc('Security / Report Vulnerability'),
        ];
    }
}
