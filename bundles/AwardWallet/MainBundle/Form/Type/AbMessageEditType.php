<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\AbMessage;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class AbMessageEditType extends AbMessageType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        if ($builder->has('Internal')) {
            $builder->remove('Internal');
        }

        if ($builder->has('TextInclude')) {
            $builder->remove('TextInclude');
        }

        if ($builder->has('InfoMessage')) {
            $builder->remove('InfoMessage');
        }

        if ($builder->has('ActionMessage')) {
            $builder->remove('ActionMessage');
        }

        if (!$this->authorizationChecker->isGranted('USER_BOOKING_MANAGER')) {
            $builder->addEventListener(FormEvents::POST_SET_DATA,
                function (FormEvent $event) {
                    /** @var AbMessage $data */
                    $data = $event->getData();
                    $event->getForm()->get('Post')->setData(htmlspecialchars_decode(preg_replace('/\<br(\s*)?\/?\>/i', "", $data->getPost())));
                }
            );
        }

        if ($this->authorizationChecker->isGranted('USER_BOOKING_MANAGER')) {
            $builder->addEventListener(FormEvents::PRE_SET_DATA,
                function (FormEvent $event) {
                    /** @var AbMessage $data */
                    $data = $event->getData();

                    if ($data->getColor() && $event->getForm()->has('Color')) {
                        $event->getForm()->get('Color')->setData($data->getColor()->getAbMessageColorID());
                    }
                }
            );
        }
    }

    public function getBlockPrefix()
    {
        return 'booking_request_edit_message';
    }
}
