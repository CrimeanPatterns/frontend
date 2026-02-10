<?php

namespace AwardWallet\MainBundle\Service\Notification;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class Unsubscriber
{
    private string $secret;

    private EntityRepository $deviceRep;

    private UsrRepository $userRep;

    public function __construct(string $secret, EntityManagerInterface $em)
    {
        $this->secret = $secret;
        $this->deviceRep = $em->getRepository(\AwardWallet\MainBundle\Entity\MobileDevice::class);
        $this->userRep = $em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
    }

    /**
     * @param string $url
     * @return string
     */
    public function addUnsubscribeCode(MobileDevice $device, $url)
    {
        $parts = parse_url($url);

        parse_str($parts['query'] ?? '', $params);
        $params['usc'] = $this->getUnsubscribeCode($device);
        $params['q'] = '1'; // stub parameter for old versions concatenating url with '?KeepDesktop=1' postfix

        return
            (!empty($parts['scheme']) ? $parts['scheme'] . ':' : '') .
            (!empty($parts['host']) ? '//' . $parts['host'] : '') .
            (!empty($parts['path']) ? $parts['path'] : '') .
            "?" . http_build_query($params) .
            (!empty($parts['fragment']) ? '#' . $parts['fragment'] : '');
    }

    public function getUnsubscribeCode(MobileDevice $device)
    {
        if (!empty($device->getUser())) {
            $result = "u-" . $device->getUser()->getRefcode() . "-" . $device->getMobileDeviceId() . "-" . $this->calcUserSha($device->getUser(), $device->getMobileDeviceId());
        } else {
            $result = "d-" . $device->getMobileDeviceId() . "-" . $this->calcDeviceSha($device);
        }

        return $result;
    }

    /**
     * @param string $code
     * @return UnsubscribeInfo|null
     */
    public function extractInfoFromCode($code)
    {
        $parts = explode("-", $code);

        if (count($parts) == 4 && $parts[0] == 'u') {
            // user device
            $user = $this->userRep->findOneBy(['refcode' => $parts[1]]);

            if (!empty($user) && $this->calcUserSha($user, $parts[2]) == $parts[3]) {
                return new UnsubscribeInfo($user, $this->deviceRep->find($parts[2]));
            }
        }

        if (count($parts) == 3 && $parts[0] == 'd') {
            // anonymous device
            array_shift($parts);
        }

        // old codes
        if (count($parts) != 2) {
            return null;
        }

        /** @var MobileDevice $device */
        $device = $this->deviceRep->find($parts[0]);

        if (empty($device)) {
            return null;
        }

        if ($this->calcDeviceSha($device) != $parts[1]) {
            return null;
        }

        return new UnsubscribeInfo(null, $device);
    }

    private function calcDeviceSha(MobileDevice $device)
    {
        return sha1($device->getDeviceKey() . $this->secret . $device->getCreationDate()->format("c"));
    }

    private function calcUserSha(Usr $user, $deviceId)
    {
        return sha1($user->getUserid() . $this->secret . ($user->getCreationdatetime() !== null ? $user->getCreationdatetime()->format("c") : "null") . $deviceId);
    }
}
