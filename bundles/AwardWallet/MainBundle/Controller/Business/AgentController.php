<?php

namespace AwardWallet\MainBundle\Controller\Business;

use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Exceptions\ImpersonatedException;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Exception\MailerExceptionInterface;
use AwardWallet\MainBundle\Manager\ConnectionManager;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Validator\Constraints\EmailsTextarea;
use AwardWallet\WidgetBundle\Widget\ConnectionsPersonsWidget;
use Doctrine\Common\Collections\Criteria;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AgentController extends AbstractController
{
    private AwTokenStorageInterface $tokenStorage;
    private TranslatorInterface $translator;
    private ValidatorInterface $validator;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
        ValidatorInterface $validator
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->validator = $validator;
    }

    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS')")
     * @Route("/agents/add-connection", host="%business_host%", name="aw_business_create_connection", methods={"GET", "POST"}, options={"expose"=true})
     * @return array|Response
     */
    public function addBusinessConnectionAction(
        Request $request,
        AuthorizationCheckerInterface $authorizationChecker,
        ConnectionsPersonsWidget $personsWidget,
        ConnectionManager $connectionManager,
        \Memcached $memcached
    ) {
        $personsWidget->setActiveItem('add-new');

        $form = $this->getConnectForm();
        $form->handleRequest($request);
        $tplParameters = [];

        if ($form->isSubmitted() && $form->isValid()) {
            if ($authorizationChecker->isGranted('USER_IMPERSONATED')) {
                throw new ImpersonatedException();
            }

            $user = $this->tokenStorage->getBusinessUser();
            $uaRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);

            $locker = new AntiBruteforceLockerService($memcached, "business_agent", 60, 5, 5, $this->translator->trans("connection.user_lockout"));
            $userIp = $request->getClientIp();
            $error = $locker->checkForLockout($userIp);

            if (empty($error)) {
                $emails = $form->get('email_addresses')->getData();
                $tplParameters['emailCount'] = \count($emails);
                $tplParameters['found'] = [];

                foreach ($emails as $email) {
                    $invitee = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->matching($this->getUserCriteria($email));
                    $found = $invitee->count() > 0;

                    try {
                        if (
                            $found && $uaRep->findOneBy(['agentid' => $user, 'clientid' => $invitee->first()])
                        ) {
                            $tplParameters['found'][] = $email;

                            continue;
                        } elseif ($found) {
                            $connectionManager->connectUser($invitee->first(), $user);
                        } else {
                            if ($familyMember = $uaRep->findOneBy(['email' => $email])) {
                                $connectionManager->inviteFamilyMember($email, $familyMember, $user);
                            } else {
                                $connectionManager->inviteUser($email, $user);
                            }
                        }
                    } catch (MailerExceptionInterface $_) {
                        // error propagated by header
                    }
                }

                return $this->render('@AwardWalletMain/Agent/connectStep4.html.twig', $tplParameters);
            } else {
                $form->addError(new FormError($error));
            }
        }

        return $this->render('@AwardWalletMain/Agent/addConnection.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    protected function getUserCriteria($email)
    {
        $criteria = Criteria::create()
            ->where(
                Criteria::expr()->andX(
                    Criteria::expr()->eq('email', $email),
                    Criteria::expr()->neq('userid', $this->tokenStorage->getBusinessUser()->getUserid()),
                    Criteria::expr()->in('accountlevel', [ACCOUNT_LEVEL_FREE, ACCOUNT_LEVEL_AWPLUS])
                )
            );

        return $criteria;
    }

    /**
     * @return Form
     */
    private function getConnectForm()
    {
        $builder = $this->createFormBuilder();

        $builder->add('email_addresses', TextareaType::class, [
            'required' => true,
            'constraints' => [
                new EmailsTextarea(),
            ],
            'label' => 'email.addresses',
            'allow_urls' => true,
            'attr' => [
                'notice' => $this->translator->trans(/** @Desc("Email addresses of people you wish to invite to your business account. One email per line.") */
                    'agents.connect.email.business.notice'),
            ],
        ]);
        $builder->get('email_addresses')->addModelTransformer(new CallbackTransformer(
            function () {
            },
            function ($emailsString) {
                $emails = explode(PHP_EOL, $emailsString);
                $emailConstraint = new Email();
                $validEmails = [];

                foreach ($emails as $email) {
                    $email = trim($email);
                    $errors = $this->validator->validate(
                        $email,
                        $emailConstraint
                    );

                    if (!count($errors)) {
                        $validEmails[] = $email;
                    }
                }

                return $validEmails;
            }
        ));

        $businessUserEmail = $this->tokenStorage->getBusinessUser()->getEmail();

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($businessUserEmail) {
            $emails = $event->getData()['email_addresses'];
            $isValid = true;

            foreach ($emails as $email) {
                if ($email == $businessUserEmail) {
                    $isValid = false;
                }
            }

            if (!$isValid) {
                $event->getForm()->get('email_addresses')->addError(new FormError($this->translator->trans(/** @Desc("You are not able to connect with yourself") */ 'agents.connect.email.yourself')));
            }
        });

        $form = $builder->getForm();

        return $form;
    }
}
