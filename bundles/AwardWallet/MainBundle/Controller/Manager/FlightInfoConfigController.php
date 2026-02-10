<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Entity\FlightInfoConfig;
use AwardWallet\MainBundle\Service\FlightInfo\FlightInfo;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * @Route("/manager/flight-info-config")
 */
class FlightInfoConfigController extends AbstractController
{
    private \Memcached $memcached;
    private RouterInterface $router;
    private FlightInfo $flightInfo;

    public function __construct(\Memcached $memcached, RouterInterface $router, FlightInfo $flightInfo)
    {
        $this->memcached = $memcached;
        $this->router = $router;
        $this->flightInfo = $flightInfo;
    }

    /**
     * @Route("/", name="aw_manager_flightinfoconfig_index", methods={"GET"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @Template("@AwardWalletMain/Manager/FlightInfoConfig/index.html.twig")
     * @return array
     */
    public function indexAction(Request $request)
    {
        $configRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\FlightInfoConfig::class);

        $q = $configRep->createQueryBuilder('c');
        $configRules = $q->select('c')
            ->orderBy('c.Name')
            ->orderBy('c.Type')
            ->getQuery()
            ->getResult();

        return [
            'configRules' => $configRules,
        ];
    }

    /**
     * @Route("/view", name="aw_manager_flightinfoconfig_view", methods={"GET"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @Template("@AwardWalletMain/Manager/FlightInfoConfig/view.html.twig")
     * @return array|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function viewAction(Request $request)
    {
        $configRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\FlightInfoConfig::class);

        $id = $request->get('id');

        if (empty($id)) {
            return $this->createNotFoundException();
        }

        $configRule = $configRep->find($id);

        if (empty($configRule)) {
            return $this->createNotFoundException();
        }

        return [
            'configRule' => $configRule,
        ];
    }

    /**
     * @Route("/add", name="aw_manager_flightinfoconfig_add", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @Template("@AwardWalletMain/Manager/FlightInfoConfig/add.html.twig")
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function addAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $form = $this->getForm();

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);
            $data = $request->get('form');

            if (is_array($data) && isset($data['name']) && isset($data['service']) && isset($data['type'])) {
                $data['name'] = trim($data['name']);
                $data['comment'] = trim($data['comment']);
                $data['service'] = strtolower(trim($data['service']));
                $data['enable'] = isset($data['enable']) ? (bool) $data['enable'] : false;
                $data['schedule'] = isset($data['schedule']) ? (bool) $data['schedule'] : false;
                $data['debug'] = isset($data['debug']) ? (bool) $data['debug'] : false;
                $data['awplus'] = $data['awplus'] ?? FlightInfoConfig::AWPLUS_ALL;
                $data['region'] = $data['region'] ?? FlightInfoConfig::REGION_ALL;

                if (!empty($data['name']) && !empty($data['service'])) {
                    $configRule = new FlightInfoConfig();
                    $configRule->setName($data['name']);
                    $configRule->setType($data['type']);
                    $configRule->setService($data['service']);
                    $configRule->setComment($data['comment']);
                    $configRule->setEnable($data['enable']);
                    $configRule->setSchedule($data['schedule']);
                    $configRule->setDebug($data['debug']);
                    $configRule->setAWPlusFlag($data['awplus']);
                    $configRule->setRegionFlag($data['region']);
                    $configRule->setScheduleRules($data['rules']);
                    $configRule->setIgnoreFields($data['ignores']);
                    $em->persist($configRule);
                    $em->flush();

                    $this->memcached->delete(FlightInfo::CONFIG_CACHE_KEY);

                    return $this->redirect($this->router->generate('aw_manager_flightinfoconfig_index'));
                    //                    return $this->redirect($this->generateUrl('aw_manager_flightinfoconfig_view', ['id' => $configRule->getFlightInfoConfigID()]));
                }
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/edit", name="aw_manager_flightinfoconfig_edit", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @Template("@AwardWalletMain/Manager/FlightInfoConfig/edit.html.twig")
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function editAction(Request $request)
    {
        $configRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\FlightInfoConfig::class);
        $em = $this->getDoctrine()->getManager();

        $id = $request->get('id');

        if (empty($id)) {
            return $this->createNotFoundException();
        }

        $configRule = $configRep->find($id);

        if (empty($configRule)) {
            return $this->createNotFoundException();
        }

        $form = $this->getForm();
        $form->setData([
            'name' => $configRule->getName(),
            'type' => $configRule->getType(),
            'service' => $configRule->getService(),
            'comment' => $configRule->getComment(),
            'rules' => $configRule->getScheduleRules(),
            'ignores' => $configRule->getIgnoreFields(),
            'enable' => $configRule->isEnable(),
            'schedule' => $configRule->isSchedule(),
            'debug' => $configRule->isDebug(),
            'awplus' => $configRule->getAWPlusFlag(),
            'region' => $configRule->getRegionFlag(),
        ]);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);
            $data = $request->get('form');

            if (is_array($data) && isset($data['name']) && isset($data['service']) && isset($data['type'])) {
                $data['name'] = trim($data['name']);
                $data['comment'] = trim($data['comment']);
                $data['service'] = strtolower(trim($data['service']));
                $data['enable'] = isset($data['enable']) ? (bool) $data['enable'] : false;
                $data['schedule'] = isset($data['schedule']) ? (bool) $data['schedule'] : false;
                $data['debug'] = isset($data['debug']) ? (bool) $data['debug'] : false;
                $data['awplus'] = $data['awplus'] ?? FlightInfoConfig::AWPLUS_ALL;
                $data['region'] = $data['region'] ?? FlightInfoConfig::REGION_ALL;

                if (!empty($data['name']) && !empty($data['service'])) {
                    $configRule->setName($data['name']);
                    $configRule->setType($data['type']);
                    $configRule->setService($data['service']);
                    $configRule->setComment($data['comment']);
                    $configRule->setEnable($data['enable']);
                    $configRule->setSchedule($data['schedule']);
                    $configRule->setDebug($data['debug']);
                    $configRule->setAWPlusFlag($data['awplus']);
                    $configRule->setRegionFlag($data['region']);
                    $configRule->setScheduleRules($data['rules']);
                    $configRule->setIgnoreFields($data['ignores']);
                    $em->flush();

                    $this->memcached->delete(FlightInfo::CONFIG_CACHE_KEY);

                    return $this->redirect($this->router->generate('aw_manager_flightinfoconfig_index'));
                    //                    return $this->redirect($this->generateUrl('aw_manager_flightinfoconfig_view', ['id' => $configRule->getFlightInfoConfigID()]));
                }
            }
        }

        return [
            'form' => $form->createView(),
            'configRule' => $configRule,
        ];
    }

    /**
     * @Route("/delete", name="aw_manager_flightinfoconfig_delete", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function deleteAction(Request $request)
    {
        $configRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\FlightInfoConfig::class);
        $em = $this->getDoctrine()->getManager();

        $id = $request->get('id');

        if (empty($id)) {
            return $this->createNotFoundException();
        }

        $configRule = $configRep->find($id);

        if (empty($configRule)) {
            return $this->createNotFoundException();
        }

        $em->remove($configRule);
        $em->flush();

        $this->memcached->delete(FlightInfo::CONFIG_CACHE_KEY);

        return $this->redirect($this->router->generate('aw_manager_flightinfoconfig_index'));
    }

    /**
     * @Route("/enable", name="aw_manager_flightinfoconfig_enable", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function enableAction(Request $request)
    {
        $configRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\FlightInfoConfig::class);
        $em = $this->getDoctrine()->getManager();

        $id = $request->get('id');

        if (empty($id)) {
            return $this->createNotFoundException();
        }

        $configRule = $configRep->find($id);

        if (empty($configRule)) {
            return $this->createNotFoundException();
        }

        $configRule->setEnable(true);
        $em->flush();

        $this->memcached->delete(FlightInfo::CONFIG_CACHE_KEY);

        return $this->redirect($this->router->generate('aw_manager_flightinfoconfig_index'));
    }

    /**
     * @Route("/disable", name="aw_manager_flightinfoconfig_disable", methods={"GET", "POST"})
     * @Security("is_granted('ROLE_MANAGE_FLIGHTINFO')")
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function disableAction(Request $request)
    {
        $configRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\FlightInfoConfig::class);
        $em = $this->getDoctrine()->getManager();

        $id = $request->get('id');

        if (empty($id)) {
            return $this->createNotFoundException();
        }

        $configRule = $configRep->find($id);

        if (empty($configRule)) {
            return $this->createNotFoundException();
        }

        $configRule->setEnable(false);
        $em->flush();

        $this->memcached->delete(FlightInfo::CONFIG_CACHE_KEY);

        return $this->redirect($this->router->generate('aw_manager_flightinfoconfig_index'));
    }

    /**
     * @return \Symfony\Component\Form\FormInterface
     */
    private function getForm()
    {
        $services = $this->flightInfo->getServices();

        $form = $this->createFormBuilder(null, [
            'csrf_protection' => true,
        ])
            ->add('name', TextType::class, ['required' => true])
            ->add('comment', TextType::class, ['required' => false])
            ->add('type', ChoiceType::class, [
                'required' => true,
                'label' => 'Request type',
                'choices' => [
                    'Check flight exists' => FlightInfoConfig::TYPE_CHECK,
                    'Subscribe to alerts' => FlightInfoConfig::TYPE_SUBSCRIBE,
                    'Update' => FlightInfoConfig::TYPE_UPDATE,
                ],
            ])
            ->add('service', ChoiceType::class, [
                'required' => true,
                'label' => 'Request service',
                'choices' => array_combine($services, $services),
            ])
            ->add('rules', TextareaType::class, [
                'required' => false,
                'label' => 'Schedule rules',
                'help' => 'One rule per line, in {DateVariable DateInterval} format (eg "DepDate +1 hour", "ArrDate -1 day"). Empty = now. Supported DateVariables: DepDate, ArrDate, FlightDate',
            ])
            ->add('ignores', TextareaType::class, [
                'required' => false,
                'label' => 'Ignore fields',
                'help' => 'One field per line. Empty = none. Supported values: DepDate, ArrDate, Gate, DepartureTerminal, ArrivalTerminal, ArrivalGate, BaggageClaim',
            ])
            ->add('enable', CheckboxType::class, [
                'required' => false,
                'label' => 'Enable',
            ])
            ->add('schedule', CheckboxType::class, [
                'required' => false,
                'label' => 'Schedule',
            ])
            ->add('debug', CheckboxType::class, [
                'required' => false,
                'label' => 'Debug',
            ])
            ->add('awplus', ChoiceType::class, [
                'required' => false,
                'label' => 'User level',
                'placeholder' => FlightInfoConfig::AWPLUS_ALL,
                'choices' => [
                    'All' => FlightInfoConfig::AWPLUS_ALL,
                    'Plus Only' => FlightInfoConfig::AWPLUS_PLUS,
                    'Regular Only' => FlightInfoConfig::AWPLUS_REGULAR,
                ],
            ])
            ->add('region', ChoiceType::class, [
                'required' => false,
                'label' => 'Flight region',
                'placeholder' => FlightInfoConfig::REGION_ALL,
                'choices' => [
                    'All' => FlightInfoConfig::REGION_ALL,
                    'Domestic Only' => FlightInfoConfig::REGION_DOMESTIC,
                    'International Only' => FlightInfoConfig::REGION_INTERNATIONAL,
                ],
            ])
            ->getForm();

        return $form;
    }
}
