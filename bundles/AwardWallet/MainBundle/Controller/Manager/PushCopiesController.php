<?php

namespace AwardWallet\MainBundle\Controller\Manager;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Service\Notification\Sender;
use AwardWallet\MainBundle\Service\Notification\Spy;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PushCopiesController extends AbstractController
{
    /**
     * @Security("is_granted('ROLE_MANAGE_PUSH_COPIES')")
     * @Route("/manager/push/copies", name="aw_manager_push_copies")
     */
    public function editAction(Request $request, Spy $spy, Sender $sender, EntityManagerInterface $entityManager)
    {
        $myDevices = $sender->loadDevices([$this->getUser()], MobileDevice::TYPES_ALL, null, true);

        $data = [];
        $builder = $this->createFormBuilder();
        $devices = [];

        foreach (Content::TYPE_NAMES as $typeId => $typeName) {
            $existing = $spy->getPushCopyDevices($typeId);

            foreach ($existing as $device) {
                $data['type_' . $typeId . '_' . $device->getMobileDeviceId()] = true;
            }

            foreach ($myDevices as $device) {
                if (!in_array($device, $existing)) {
                    $data['type_' . $typeId . '_' . $device->getMobileDeviceId()] = false;
                    $existing[] = $device;
                }
            }

            foreach ($existing as $device) {
                $builder->add('type_' . $typeId . '_' . $device->getMobileDeviceId(), CheckboxType::class, [
                    'label' => $device->getName(),
                    'required' => false,
                ]);
            }
            $devices[$typeId] = $existing;
        }

        $builder->add('save', SubmitType::class, ['label' => 'Save changes']);
        $builder->setData($data);
        $form = $builder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            foreach (Content::TYPE_NAMES as $typeId => $typeName) {
                $selection = [];

                foreach ($data as $key => $checked) {
                    [$prefix, $type, $deviceId] = explode("_", $key);

                    if ($type == $typeId && $checked) {
                        $selection[] = intval($deviceId);
                    }
                }
                $selectedDevices = $entityManager->getRepository(\AwardWallet\MainBundle\Entity\MobileDevice::class)->findBy(["mobileDeviceId" => $selection]);
                $spy->setPushCopyDevices($typeId, $selectedDevices);
            }

            return new RedirectResponse($request->getPathInfo());
        }

        return $this->render(
            "@AwardWalletMain/Manager/PushCopies/form.html.twig",
            [
                "types" => Content::TYPE_NAMES,
                "form" => $form->createView(),
                "devices" => $devices,
            ]
        );
    }
}
