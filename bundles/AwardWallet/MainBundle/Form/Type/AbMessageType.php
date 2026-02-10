<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\AbMessageColor;
use AwardWallet\MainBundle\Entity\AbRequest;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Validator\Constraints as Assert;

class AbMessageType extends AbstractType
{
    protected $router;
    protected $request;
    protected $em;
    /**
     * @var AuthorizationChecker
     */
    protected $authorizationChecker;

    public function __construct(AuthorizationChecker $authorizationChecker, Router $router, RequestStack $request_stack, EntityManager $em)
    {
        $this->router = $router;
        $this->request = $request_stack->getCurrentRequest();
        $this->em = $em;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var AbRequest $request */
        $request = $options['request'];

        $colors = $this->em->getRepository(\AwardWallet\MainBundle\Entity\AbMessageColor::class)->getColorsForBooker($request->getBooker());
        $colorOptions = ['Border labels' => ''];
        $colorCodes = ['' => ''];

        foreach ($colors as $v) {
            /** @var AbMessageColor $v */
            $colorOptions[$v->getDescription()] = $v->getAbMessageColorID();
            $colorCodes[$v->getAbMessageColorID()] = $v->getColor();
        }

        if ($this->authorizationChecker->isGranted('USER_BOOKING_PARTNER')) {
            $baseHref = $this->request->getScheme() . "://" . $this->request->getHttpHost();
            $user = $request->getUser();
            $firstName = urlencode($user->getFirstname());
            $lastName = urlencode($user->getLastname());
            $email = urlencode($user->getEmail());
            $requestId = $request->getAbRequestID();
            $requestCode = urlencode($request->getHash());
            $phone = urlencode($request->getContactPhone());

            $builder->add('Post',
                HtmleditorType::class,
                [
                    'mapped' => true,
                    /** @Ignore */
                    'label' => false,
                    'required' => true,
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Valid(),
                        new Assert\Length(['max' => 2000000]),
                    ],
                    'allow_tags' => true,
                    'allow_quotes' => true,
                    'allow_urls' => true,
                    'custom_config' => "/assets/awardwalletmain/js/sceditor/ckeditorConfig.js?v=" . FILE_VERSION,
                    'filebrowser_image_upload_url' => ['url' => $this->router->generate('aw_common_upload_image', ['resource' => 'abmessage', 'resourceId' => $request->getAbRequestID()]) . '?'],
                    'templates_files' => $request->getBooker()->getBookerInfo()->getTemplates(),
                    //                    'transformers' => ['html_purifier'],
                    'toolbar' => ['main'],
                    'toolbar_groups' => [
                        'main' => [
                            'Bold', 'Italic', 'Underline',
                            '-', 'TextColor', 'BGColor',
                            '-', 'NumberedList', 'BulletedList', 'Image',
                            '-', 'Cut', 'Copy', 'Paste', 'PasteText', // 'Source',
                            '-', 'Link', 'Unlink', 'Anchor',
                            '-', 'Undo', 'Redo', 'Maximize', '-', 'Templates', ],
                    ],
                    'base_href' => $baseHref,
                    'ui_color' => null,
                    'on' => "{
                            pluginsLoaded: function(e) {
                                e.editor.dataProcessor.dataFilter.addRules({
                                    elements: {
                                        $: function(element) {
                                            if (element.attributes.id) {
                                                delete element.attributes.id;
                                            }
                                            return element;
                                        }
                                    }
                                });
                                CKEDITOR.filter.disabled = true;
                            },
                            setData: function(e) {
                                console.log('setData');
                                var content = e.data.dataValue;
                                if(!content) return;
                                var result = content.replace(
                                    /{{ (.+)_schedule }}/g,
                                    function(str, name){
                                        return '<iframe src=\"https://' + name + '.youcanbook.me/?noframe=true&skipHeaderFooter=true&FNAME={$firstName}&LNAME={$lastName}&EMAIL={$email}&REQUEST_ID={$requestId}&REQUEST_CODE={$requestCode}&PHONE={$phone}\" style=\"width:100%;height:900px;border:0;background-color:transparent;\" frameborder=\"0\" allowtransparency=\"true\"> </iframe>'
                                    }
                                );
                                if(result != content)
                                    e.data.dataValue = result;
                            }
                        }",
                ]);
            $builder->add('Internal', CheckboxType::class, [
                'mapped' => false,
                'label' => 'booking.request.messages.form.internal',
                'required' => false,
            ]);

            //			$builder->add('TextInclude', CheckboxType::class, array(
            /*
                        $builder->add('TextInclude', 'checkbox', array(
                            'mapped' => false,
                            'label' => 'booking.request.messages.form.text-include',
                            'required' => false,
                        ));
                        $builder->add('InfoMessage', CheckboxType::class, array(
                            'mapped' => false,
                            'label' => 'booking.request.messages.form.information',
                            'required' => false,
                        ));
                        $builder->add('ActionMessage', CheckboxType::class, array(
                            'mapped' => false,
                            'label' => 'booking.request.messages.form.action',
                            'required' => false,
                        ));
            */
            if (count($colorOptions) > 1) {
                $builder->add('Color', ChoiceColorType::class, [
                    'mapped' => false,
                    'choices' => $colorOptions,
                    'choice_attr' => function ($val, $key, $index) use ($colorCodes) { return ['color' => $colorCodes[$val]]; },
                    'label' => 'booking.request.messages.form.color',
                    'placeholder' => 'booking.request.messages.form.select_color',
                    'required' => false,
                ]);
            }
        } else {
            $builder->add('Post',
                TextareaType::class,
                [
                    'mapped' => true,
                    /** @Ignore */
                    'label' => false,
                    'required' => true,
                    'allow_quotes' => true,
                    'allow_tags' => true,
                    'allow_urls' => true,
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Length(['max' => 20000]),
                    ],
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'AwardWallet\\MainBundle\\Entity\\AbMessage',
            'translation_domain' => 'booking',
            'request' => null,
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'booking_request_message';
    }
}
