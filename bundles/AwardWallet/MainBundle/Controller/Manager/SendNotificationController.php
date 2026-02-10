<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\NotificationTemplate;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Type\SeparatorType;
use AwardWallet\MainBundle\Globals\Paginator\Paginator;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Service\Notification\Sender;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Options;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * @Route("/manager/send-notification")
 */
class SendNotificationController extends AbstractController
{
    public const perPage = 20;

    public const SEND = 1;
    public const NOT_SEND = 0;
    public const NO_DEVICES = -1;
    public const DESKTOP_DISABLED = -2;

    private RouterInterface $router;
    private Sender $sender;
    private LoggerInterface $logger;
    private \Memcached $memcached;

    public function __construct(RouterInterface $router, Sender $sender, LoggerInterface $logger, \Memcached $memcached)
    {
        $this->router = $router;
        $this->sender = $sender;
        $this->logger = $logger;
        $this->memcached = $memcached;
    }

    /**
     * @Route("/", name="aw_manager_sendnotification_index", methods={"GET"})
     * @Security("is_granted('ROLE_MANAGE_SEND_NOTIFICATION')")
     * @Template("@AwardWalletMain/Manager/SendNotification/index.html.twig")
     * @return array
     */
    public function indexAction(Request $request, Paginator $paginator)
    {
        $ntRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\NotificationTemplate::class);

        $query = $ntRep->createQueryBuilder('nt')->select('nt')->orderBy('nt.CreateDate', 'desc');
        $pagination = $paginator->paginate($query, $request->query->get('page', 1), self::perPage);
        $data = $pagination->getItems();

        return [
            'notificationsCount' => $pagination->count(),
            'notifications' => $data,
            'pagination' => $pagination,
        ];
    }

    /**
     * @Route("/view", name="aw_manager_sendnotification_view", methods={"GET"})
     * @Security("is_granted('ROLE_MANAGE_SEND_NOTIFICATION')")
     * @Template("@AwardWalletMain/Manager/SendNotification/view.html.twig")
     * @return array|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function viewAction(Request $request)
    {
        $ntRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\NotificationTemplate::class);
        $userGroups = $this->getUserGroups();
        $user = $this->getUser();
        $ip = $request->getClientIp();

        $id = $request->get('id');

        if (empty($id)) {
            throw $this->createNotFoundException();
        }

        $notification = $ntRep->find($id);

        if (empty($notification)) {
            throw $this->createNotFoundException();
        }

        return [
            'notification' => $notification,
            'userGroups' => $userGroups,
            'user' => $user,
            'ip' => $ip,
            'testForm' => $this->getTestForm()->createView(),
        ];
    }

    /**
     * @Route("/add", name="aw_manager_sendnotification_add", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_SEND_NOTIFICATION')")
     * @Template("@AwardWalletMain/Manager/SendNotification/add.html.twig")
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function addAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $form = $this->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if (is_array($data) && isset($data['title']) && isset($data['body'])) {
                $data['title'] = trim($data['title']);
                $data['body'] = trim($data['body']);
                $data['url'] = strtolower(trim($data['url']));
                $data['ttl'] = $data['ttl'] ?? new \DateTime('+' . NotificationTemplate::DEFAULT_TTL . ' seconds');
                $data['user_groups'] = $data['user_groups'] ?? [];
                $data['delivery_mode'] = $data['delivery_mode'] ?? NotificationTemplate::DELIVERY_MODE_DEFAULT;

                if (!empty($data['title']) && !empty($data['body'])) {
                    $notification = new NotificationTemplate();
                    $notification->setType($data['contentType']);
                    $notification->setTitle($data['title']);
                    $notification->setMessage($data['body']);
                    $notification->setLink($data['url']);
                    $notification->setTTL($data['ttl']);
                    $notification->setAutoClose((int) $data['auto_close']);
                    $notification->setUserGroups($data['user_groups']);
                    $notification->setDeliveryMode($data['delivery_mode']);
                    $notification->setState(NotificationTemplate::STATE_NEW);
                    $em->persist($notification);
                    $em->flush();

                    return $this->redirect($this->router->generate('aw_manager_sendnotification_view', ['id' => $notification->getNotificationTemplateID()]));
                }
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/edit", name="aw_manager_sendnotification_edit", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_SEND_NOTIFICATION')")
     * @Template("@AwardWalletMain/Manager/SendNotification/edit.html.twig")
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function editAction(Request $request)
    {
        $ntRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\NotificationTemplate::class);
        $em = $this->getDoctrine()->getManager();

        $id = $request->get('id');

        if (empty($id)) {
            throw $this->createNotFoundException();
        }

        /** @var NotificationTemplate $notification */
        $notification = $ntRep->find($id);

        if (empty($notification)) {
            throw $this->createNotFoundException();
        }

        $form = $this->getForm();
        $form->setData([
            'contentType' => $notification->getType(),
            'title' => $notification->getTitle(),
            'body' => $notification->getMessage(),
            'url' => $notification->getLink(),
            'ttl' => $notification->getTTL(),
            'auto_close' => (bool) $notification->isAutoClose(),
            'user_groups' => $notification->getUserGroups(),
            'delivery_mode' => $notification->getDeliveryMode(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if (is_array($data) && isset($data['title']) && isset($data['body'])) {
                $data['title'] = trim($data['title']);
                $data['body'] = trim($data['body']);
                $data['url'] = strtolower(trim($data['url']));
                $data['ttl'] = $data['ttl'] ?? new \DateTime('+' . NotificationTemplate::DEFAULT_TTL . ' seconds');
                $data['user_groups'] = $data['user_groups'] ?? [];
                $data['delivery_mode'] = $data['delivery_mode'] ?? NotificationTemplate::DELIVERY_MODE_DEFAULT;

                if (!empty($data['title']) && !empty($data['body'])) {
                    $notification->setType($data['contentType']);
                    $notification->setTitle($data['title']);
                    $notification->setMessage($data['body']);
                    $notification->setLink($data['url']);
                    $notification->setTTL($data['ttl']);
                    $notification->setAutoClose((int) $data['auto_close']);
                    $notification->setUserGroups($data['user_groups']);
                    $notification->setDeliveryMode($data['delivery_mode']);
                    $notification->setState(NotificationTemplate::STATE_NEW);
                    $em->flush();

                    return $this->redirect($this->router->generate('aw_manager_sendnotification_view', ['id' => $notification->getNotificationTemplateID()]));
                }
            }
        }

        return [
            'form' => $form->createView(),
            'notification' => $notification,
        ];
    }

    /**
     * @Route("/delete", name="aw_manager_sendnotification_delete", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_SEND_NOTIFICATION')")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function deleteAction(Request $request)
    {
        $ntRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\NotificationTemplate::class);
        $em = $this->getDoctrine()->getManager();

        $id = $request->get('id');

        if (empty($id)) {
            throw $this->createNotFoundException();
        }

        /** @var NotificationTemplate $notification */
        $notification = $ntRep->find($id);

        if (empty($notification)) {
            throw $this->createNotFoundException();
        }

        if (!in_array($notification->getState(), [NotificationTemplate::STATE_NEW, NotificationTemplate::STATE_TESTED])) {
            throw $this->createNotFoundException();
        }

        $em->remove($notification);
        $em->flush();

        return $this->redirect($this->router->generate('aw_manager_sendnotification_index'));
    }

    /**
     * @Route("/test", name="aw_manager_sendnotification_test", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_SEND_NOTIFICATION')")
     * @Template("@AwardWalletMain/Manager/SendNotification/test.html.twig")
     */
    public function testAction(Request $request)
    {
        $ntRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\NotificationTemplate::class);
        $userRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $user = $this->getUser();
        $ip = $request->getClientIp();

        $id = $request->get('id');

        if (empty($id)) {
            throw $this->createNotFoundException();
        }

        /** @var NotificationTemplate $notification */
        $notification = $ntRep->find($id);

        if (empty($notification)) {
            throw $this->createNotFoundException();
        }

        $form = $this->getTestForm();

        $tested = [];

        $message = new Content(
            $notification->getTitle(),
            $notification->getMessage(),
            $notification->getType(),
            $notification->getLink(),
            (new Options())
                ->setAutoClose($notification->isAutoClose())
                ->setDeadlineTimestamp($notification->getTTL()->getTimestamp())
                ->setPriority(15)
        );

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);
            $data = $request->get('form');
            $registeredUsers = [];
            $anonymousUsers = [];

            if (is_array($data) && isset($data['users']) && $data['type'] == 'custom') {
                $users = preg_split('/\s+/', $data['users']);

                foreach ($users as $userId) {
                    $userId = trim($userId);

                    if (empty($userId)) {
                        continue;
                    }

                    if (is_numeric($userId)) {
                        $registeredUsers[$userId] = $userRep->find(intval($userId));
                    } elseif (filter_var($userId, FILTER_VALIDATE_IP)) {
                        $anonymousUsers[] = $userId;
                    } elseif (filter_var($userId, FILTER_VALIDATE_EMAIL)) {
                        $registeredUsers[$userId] = $userRep->findOneByEmail($userId);
                    } else {
                        $registeredUsers[$userId] = $userRep->findOneByLogin($userId);
                    }
                }
            } elseif (is_array($data) && $data['type'] == 'staff') {
                $ids = $ntRep->getStaffUserIds();

                foreach ($ids as $id) {
                    $user = $userRep->find(intval($id));
                    $registeredUsers[$id] = $user;
                }
            } elseif (is_array($data) && $data['type'] == 'self') {
                $registeredUsers[$this->getUser()->getLogin()] = $this->getUser();
            } elseif (is_array($data) && $data['type'] == 'self_anon') {
                $anonymousUsers[$request->getClientIp()] = $request->getClientIp();
            }

            /**
             * @var mixed $userId
             * @var Usr $user
             */
            foreach ($registeredUsers as $userId => $user) {
                $testing = [
                    'userId' => $userId,
                    'user' => $user,
                    'result' => '-',
                ];

                if (array_key_exists($user->getUserid(), $tested)) {
                    continue;
                }

                if ($user->isBusiness()) {
                    $testing['result'] = 'skip (Business)';
                } else {
                    if ($notification->getDeliveryMode() == NotificationTemplate::DELIVERY_MODE_DEFAULT) {
                        $result = $this->sendToDesktop($user, $message);

                        if ($result == self::SEND) {
                            $testing['result'] = 'send to Desktop';
                        } else {
                            $result = $this->sendToMobile($user, $message);

                            if ($result == self::SEND) {
                                $testing['result'] = 'send to Mobile';
                            } else {
                                if (($result == self::NO_DEVICES && $result == self::NO_DEVICES) || ($result == self::DESKTOP_DISABLED && $result == self::NO_DEVICES)) {
                                    $testing['result'] = 'skip (no devices)';
                                } else {
                                    $testing['result'] = 'error';
                                }
                            }
                        }
                    } elseif ($notification->getDeliveryMode() == NotificationTemplate::DELIVERY_MODE_DESKTOP) {
                        $result = $this->sendToDesktop($user, $message);

                        if ($result == self::SEND) {
                            $testing['result'] = 'send to Desktop';
                        } elseif ($result == self::NO_DEVICES) {
                            $testing['result'] = 'skip (no devices)';
                        } elseif ($result == self::DESKTOP_DISABLED) {
                            $testing['result'] = 'skip (disabled)';
                        } else {
                            $testing['result'] = 'error (send to Desktop)';
                        }
                    } elseif ($notification->getDeliveryMode() == NotificationTemplate::DELIVERY_MODE_MOBILE) {
                        $result = $this->sendToMobile($user, $message);

                        if ($result == self::SEND) {
                            $testing['result'] = 'send to Mobile';
                        } elseif ($result == self::NO_DEVICES) {
                            $testing['result'] = 'skip (no devices)';
                        } else {
                            $testing['result'] = 'error (send to Mobile)';
                        }
                    } elseif ($notification->getDeliveryMode() == NotificationTemplate::DELIVERY_MODE_MOBILE_AND_DESKTOP) {
                        $resultDesktop = $this->sendToDesktop($user, $message);
                        $resultMobile = $this->sendToMobile($user, $message);
                        $textResults = [];

                        foreach ([[$resultDesktop, 'Desktop'], [$resultMobile, 'Mobile']] as [$sendResult, $deliveryName]) {
                            if (self::SEND === $sendResult) {
                                $textResults[] = "send to {$deliveryName}";
                            } elseif (self::NO_DEVICES === $sendResult) {
                                $textResults[] = "skip {$deliveryName} (no devices)";
                            } else {
                                $textResults[] = "error (send to {$deliveryName})";
                            }
                        }

                        $testing['result'] = implode(', ', $textResults);
                    }
                }
                $tested[$user->getUserid()] = $testing;
            }

            foreach ($anonymousUsers as $userId => $ip) {
                $testing = [
                    'userId' => $ip,
                    'user' => null,
                    'result' => '-',
                ];
                $result = $this->sendToDesktop($ip, $message);

                if ($result == self::SEND) {
                    $testing['result'] = 'send to Desktop';
                } elseif ($result == self::NO_DEVICES) {
                    $testing['result'] = 'skip (no devices)';
                } elseif ($result == self::DESKTOP_DISABLED) {
                    $testing['result'] = 'skip (disabled)';
                } else {
                    $testing['result'] = 'error (send to Desktop)';
                }
                $tested[$ip] = $testing;
            }
        }

        return [
            'tested' => $tested,
            'notification' => $notification,
            'user' => $user,
            'ip' => $ip,
            'testForm' => $form->createView(),
        ];
    }

    /**
     * @Route("/send", name="aw_manager_sendnotification_send", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_SEND_NOTIFICATION')")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function sendAction(Request $request, ProducerInterface $producer)
    {
        $ntRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\NotificationTemplate::class);
        $em = $this->getDoctrine()->getManager();

        $id = $request->get('id');

        if (empty($id)) {
            throw $this->createNotFoundException();
        }

        /** @var NotificationTemplate $notification */
        $notification = $ntRep->find($id);

        if (empty($notification)) {
            throw $this->createNotFoundException();
        }

        if (!in_array($notification->getState(), [NotificationTemplate::STATE_NEW, NotificationTemplate::STATE_TESTED])) {
            throw $this->createNotFoundException();
        }

        $notification->setState(NotificationTemplate::STATE_SENDING);
        $em->flush();

        $task = [
            'id' => $notification->getNotificationTemplateID(),
        ];
        $producer->publish(@serialize($task));

        return $this->redirect($this->router->generate('aw_manager_sendnotification_index'));
    }

    /**
     * @param Content $message
     * @return int
     */
    protected function sendToDesktop($user, $message)
    {
        if ($user instanceof Usr) {
            $desktopDevices = $this->sender->loadDevices([$user], MobileDevice::TYPES_DESKTOP, $message->type);

            if (count($desktopDevices)) {
                $sended = $this->sender->send($message, $desktopDevices);
                $this->logger->warning("sending test push to desktop", ["devices" => array_map(function (MobileDevice $device) { return $device->getMobileDeviceId(); }, $desktopDevices), "success" => $sended]);

                if ($sended) {
                    return self::SEND;
                }

                return self::NOT_SEND;
            }

            return self::NO_DEVICES;
        } elseif (filter_var($user, FILTER_VALIDATE_IP)) {
            $desktopDevices = $this->sender->loadAnonymousDevices([$user], MobileDevice::TYPES_DESKTOP, $message->type);

            if (count($desktopDevices)) {
                $sended = $this->sender->send($message, $desktopDevices);

                if ($sended) {
                    return self::SEND;
                }

                return self::NOT_SEND;
            }

            return self::NO_DEVICES;
        }

        return self::NOT_SEND;
    }

    /**
     * @param Content $message
     * @return int
     */
    protected function sendToMobile($user, $message)
    {
        if ($user instanceof Usr) {
            $mobileDevices = $this->sender->loadDevices([$user], MobileDevice::TYPES_MOBILE, $message->type);

            if (count($mobileDevices)) {
                $sended = $this->sender->send($message, $mobileDevices);
                $this->logger->warning("sending test push to mobile", ["devices" => array_map(function (MobileDevice $device) { return $device->getMobileDeviceId(); }, $mobileDevices), "success" => $sended]);

                if ($sended) {
                    return self::SEND;
                }

                return self::NOT_SEND;
            }

            return self::NO_DEVICES;
        } elseif (filter_var($user, FILTER_VALIDATE_IP)) {
            $mobileDevices = $this->sender->loadAnonymousDevices([$user], MobileDevice::TYPES_MOBILE, $message->type);

            if (count($mobileDevices)) {
                $sended = $this->sender->send($message, $mobileDevices);

                if ($sended) {
                    return self::SEND;
                }

                return self::NOT_SEND;
            }

            return self::NO_DEVICES;
        }

        return self::NOT_SEND;
    }

    /**
     * @return \Symfony\Component\Form\FormInterface
     */
    private function getForm()
    {
        $userGroups = $this->getUserGroups();

        $form = $this->createFormBuilder(
            null,
            ['constraints' => [new Valid()], 'method' => 'POST']
        )
            ->add('user_groups', ChoiceType::class, [
                'multiple' => true,
                'expanded' => true,
                'required' => true,
                'label' => 'User groups',
                'choices' => array_flip($userGroups),
            ])
            ->add('delivery_mode', ChoiceType::class, [
                'required' => true,
                'label' => 'Delivery mode',
                'expanded' => true,
                'choices' => [
                    'Default (Mobile OR Desktop)' => NotificationTemplate::DELIVERY_MODE_DEFAULT,
                    'Mobile Only' => NotificationTemplate::DELIVERY_MODE_MOBILE,
                    'Desktop Only' => NotificationTemplate::DELIVERY_MODE_DESKTOP,
                    'Mobile AND Desktop' => NotificationTemplate::DELIVERY_MODE_MOBILE_AND_DESKTOP,
                ],
            ])
            ->add('1', SeparatorType::class)
            ->add('contentType', ChoiceType::class, [
                'required' => true,
                'label' => 'Content type',
                'expanded' => true,
                'choices' => [
                    'Promotional Offers' => Content::TYPE_OFFER,
                    'Product Updates' => Content::TYPE_PRODUCT_UPDATES,
                    'New Blog Posts' => Content::TYPE_BLOG_POST,
                ],
            ])
            ->add('title', TextType::class, [
                'required' => true,
                'attr' => ['style' => 'width:500px;'],
            ])
            ->add('body', TextareaType::class, [
                'required' => true,
                'attr' => ['style' => 'width:500px; height:200px;'],
            ])
            ->add('auto_close', CheckboxType::class, [
                'required' => false,
                'label' => 'Auto-close (Chrome only)',
            ])
            ->add('ttl', DateTimeType::class, [
                'required' => true,
                'label' => 'TTL (UTC)',
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
            ])
            ->add('url', TextType::class, [
                'required' => false,
                'allow_urls' => true,
                'attr' => ['style' => 'width:500px;'],
                "help" =>
                    "Url should be in format: <span class='code-term'>/blog/link/campaign-2025521-mobile</span><br/>\n"
                    . \sprintf("Accepts merge field <b>{{ refCode }}</b><i> (case- and space-insensitive)</i> i.e. <span class='code-term'>/blog/link/campaign-%d%d%d-mobile?rkbtyn=<b>{{ refCode }}</b></span><br/>\n", \date('Y'), \date('m'), \date('d'))
                    . \sprintf("Anonymous users will receive empty param: <span class='code-term'>/blog/link/campaign-%d%d%d-mobile?rkbtyn=</span>", \date('Y'), \date('m'), \date('d')),
                "help_html" => true,
                'constraints' => [
                    new Regex(['pattern' => '#^\/blog/link\/.+$#']),
                ],
            ])
            ->getForm();

        return $form;
    }

    /**
     * @return \Symfony\Component\Form\FormInterface
     */
    private function getTestForm()
    {
        $form = $this->createFormBuilder(null, [
            'csrf_protection' => true,
        ])
            ->add('users', TextareaType::class, [
                'required' => false,
                'label' => 'Users',
                'attr' => ['style' => 'width:500px; height: 200px;', 'notice' => 1],
            ])
            ->add('type', HiddenType::class, [
                'required' => false,
                'data' => 'custom',
            ])
            ->getForm();

        return $form;
    }

    /**
     * @return array
     */
    private function getUserGroups()
    {
        $ntRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\NotificationTemplate::class);
        $userGroups = $this->memcached->get('send_notification_groups_v7');

        if (!$userGroups) {
            $userGroups = $ntRep->getUserGroups();
            $this->memcached->set('send_notification_groups_v7', $userGroups, 3600);
        }

        return $userGroups;
    }
}
