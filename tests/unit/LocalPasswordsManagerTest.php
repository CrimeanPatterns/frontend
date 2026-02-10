<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Manager\LocalPasswordsManager;
use Aws\S3\S3Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertEqualsWithDelta;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

/**
 * @group frontend-unit
 */
class LocalPasswordsManagerTest extends BaseTest
{
    public function testCookiesLoad()
    {
        $localPasswordsManager = $this->getLocalPasswordsManagerMock(new Request());
        $request = new Request([], [], [],
            $this->prepareCookies($localPasswordsManager, [
                12345 => 'somepass1',
                12346 => 'somepass2',
                'UserID' => 1,
            ])
        );
        $localPasswordsManager = $this->getLocalPasswordsManagerMock($request);

        assertEquals('somepass1', $localPasswordsManager->getPassword(12345));
        assertEquals('somepass2', $localPasswordsManager->getPassword(12346));

        assertFalse($localPasswordsManager->hasPassword(12347));
        assertEquals('', $localPasswordsManager->getPassword(12347));
    }

    public function testOldCookiesExpirationRenew()
    {
        $localPasswordsManager = $this->getLocalPasswordsManagerMock(new Request());
        // create request with old format w\o expiration date
        $request = new Request([], [], [],
            $this->prepareCookies($localPasswordsManager, [
                12345 => 'somepass1',
                'UserID' => 1,
            ])
        );

        $localPasswordsManager = $this->getLocalPasswordsManagerMock($request);
        assertFalse($localPasswordsManager->isUnsaved());
        assertTrue($localPasswordsManager->hasPassword(12345));
        assertTrue($localPasswordsManager->isUnsaved());

        // cookies should be saved to response
        $response = new Response();
        $localPasswordsManager->save($response);

        $cookie = $response->headers->getCookies()[0];
        assertEqualsWithDelta(time() + LocalPasswordsManager::COOKIE_LIFETIME, $cookie->getExpiresTime(), 100, 'invalid date');

        // new request with stored expiration date
        $request = new Request([], [], [], ['APv2-0' => $cookie->getValue()]);
        $localPasswordsManager = $this->getLocalPasswordsManagerMock($request);
        assertTrue($localPasswordsManager->hasPassword(12345));

        // cookie should not be renewed
        $response = new Response();
        $localPasswordsManager->save($response);
        assertEmpty($response->headers->getCookies());
    }

    public function testNewCookiesShouldBeRenewedOneYearBeforeExpiration()
    {
        $localPasswordsManager = $this->getLocalPasswordsManagerMock(new Request());
        $request = new Request([], [], [],
            $this->prepareCookies($localPasswordsManager, [
                12345 => base64_encode('somepass1'),
                'UserID' => 1,
                'expiration' => time() + LocalPasswordsManager::COOKIE_RENEW_OFFSET - 200,
            ])
        );

        $localPasswordsManager = $this->getLocalPasswordsManagerMock($request);
        assertTrue($localPasswordsManager->hasPassword(12345));
        assertTrue($localPasswordsManager->isUnsaved());
    }

    public function testCookiesOldKeyShouldBeRenewed()
    {
        $localPasswordsManager = $this->getLocalPasswordsManagerMock(new Request(), 'key1', 'doesntmatter');
        $request = new Request([], [], [],
            $this->prepareCookies($localPasswordsManager, [
                12345 => base64_encode('somepass1'),
                'UserID' => 1,
                'expiration' => time() + LocalPasswordsManager::COOKIE_RENEW_OFFSET + 86400,
            ])
        );
        // localPasswordManager will modify request attributes
        $requestClone = new Request([], [], [],
            $this->prepareCookies($localPasswordsManager, [
                12345 => base64_encode('somepass1'),
                'UserID' => 1,
                'expiration' => time() + LocalPasswordsManager::COOKIE_RENEW_OFFSET + 86400,
            ])
        );

        $localPasswordsManager = $this->getLocalPasswordsManagerMock($request, 'key1', 'doesntmatter');
        assertTrue($localPasswordsManager->hasPassword(12345));
        assertFalse($localPasswordsManager->isUnsaved());

        $localPasswordsManager = $this->getLocalPasswordsManagerMock($requestClone, 'key2', 'key1');
        assertTrue($localPasswordsManager->hasPassword(12345));
        assertTrue($localPasswordsManager->isUnsaved());
    }

    public function testEncodePasswordWithSpecialCharacters()
    {
        $localPasswordsManager = $this->getLocalPasswordsManagerMock(new Request());
        $encoded = $localPasswordsManager->encode(['12345' => 'somep,,,ass1', '6432' => 'some:::pass2', 'UserID' => 1]);
        assertEquals([
            [
                '12345' => 'somep,,,ass1',
                '6432' => 'some:::pass2',
            ],
            1,
        ], $localPasswordsManager->decode($encoded));
    }

    protected function getLocalPasswordsManagerMock(Request $request, $localPasswordsKey = 'v5Nzz0F0JmPrQr7V3T6bSB99kzCMPpxzxrdBa54631Y', $localPasswordsKeyOld = 'z4wh3Xq7Ume_wQdQX85k3oPHdkVoTY7g')
    {
        return new LocalPasswordsManager(
            $this->prophesize(RequestStack::class)
                ->getCurrentRequest()->willReturn($request)
                ->getObjectProphecy()->reveal(),
            $this->prophesize(AwTokenStorageInterface::class)
                ->getToken()->willReturn(
                    $this->prophesize(TokenInterface::class)
                        ->getUser()->willReturn(
                            $this->prophesize(Usr::class)
                                ->getUserid()->willReturn(1)
                                ->getObjectProphecy()->reveal()
                        )->getObjectProphecy()->reveal()
                )->getObjectProphecy()->reveal(),
            $this->prophesize(KernelInterface::class)
                ->getEnvironment()->willReturn('prod')
                ->getObjectProphecy()->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->prophesize(S3Client::class)->reveal(),
            $localPasswordsKey,
            $localPasswordsKeyOld
        );
    }

    protected function prepareCookies(LocalPasswordsManager $localPasswordsManager, array $data)
    {
        foreach ($data as $key => $pass) {
            $data[$key] = is_numeric($key) ? base64_encode($pass) : $pass;
        }

        $encoded = $localPasswordsManager->encode($data);
        $result = [];

        foreach (str_split($encoded, 1024) as $partId => $partValue) {
            $result['APv2-' . $partId] = $partValue;
        }

        return $result;
    }
}
