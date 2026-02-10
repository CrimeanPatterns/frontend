<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Encryptor;

use AwardWallet\MainBundle\Security\Encryptor\Backend\BackendInterface;
use AwardWallet\MainBundle\Security\Encryptor\Encryptor;
use AwardWallet\Tests\Unit\BaseTest;
use Prophecy\Argument;

/**
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Security\Encryptor\Encryptor
 */
class EncryptorTest extends BaseTest
{
    public function testEncryptShouldCallOnlyActualBackend()
    {
        $plaintext = 'plaintext';
        $ciphertext = 'encrypted';

        $actualBackend = $this->prophesize(BackendInterface::class);
        $actualBackend
            ->encrypt($plaintext)
            ->willReturn($ciphertext)
            ->shouldBeCalledOnce();

        $otherBackend = $this->prophesize(BackendInterface::class);
        $otherBackend
            ->encrypt(Argument::any())
            ->willReturn('no_return')
            ->shouldNotBeCalled();

        $encryptor = new Encryptor($actualBackend->reveal(), [$otherBackend->reveal()]);
        $this->assertEquals($ciphertext, $encryptor->encrypt($plaintext));
    }

    public function testDecryptShouldCallActualBackendFirst()
    {
        $plaintext = 'plaintext';
        $ciphertext = 'encrypted';

        $otherBackend = $this->prophesize(BackendInterface::class);
        $actualBackend = $this->prophesize(BackendInterface::class);
        $actualBackend
            ->supports($ciphertext)
            ->will(function () use ($otherBackend, $actualBackend, $ciphertext, $plaintext) {
                $actualBackend
                    ->decrypt($ciphertext)
                    ->willReturn($plaintext)
                    ->shouldBeCalledOnce();

                $otherBackend
                    ->supports($ciphertext)
                    ->shouldNotBeCalled();

                $otherBackend
                    ->decrypt($ciphertext)
                    ->shouldNotBeCalled();

                return true;
            })
            ->shouldBeCalledOnce();

        $encryptor = new Encryptor($actualBackend->reveal(), [$otherBackend->reveal()]);
        $this->assertEquals($plaintext, $encryptor->decrypt($ciphertext));
    }

    public function testDecryptShouldFallBackToOtherBackend()
    {
        $plaintext = 'plaintext';
        $ciphertext = 'encrypted';

        $otherBackend = $this->prophesize(BackendInterface::class);
        $actualBackend = $this->prophesize(BackendInterface::class);
        $actualBackend
            ->supports($ciphertext)
            ->will(function () use ($otherBackend, $actualBackend, $ciphertext, $plaintext) {
                $actualBackend
                    ->decrypt($ciphertext)
                    ->willReturn($plaintext)
                    ->shouldNotBeCalled();

                $otherBackend
                    ->supports($ciphertext)
                    ->will(function () use ($otherBackend, $ciphertext, $plaintext) {
                        $otherBackend
                            ->decrypt($ciphertext)
                            ->willReturn($plaintext)
                            ->shouldBeCalledOnce();

                        return true;
                    })
                    ->shouldBeCalledOnce();

                return false;
            })
            ->shouldBeCalledOnce();

        $encryptor = new Encryptor($actualBackend->reveal(), [$otherBackend->reveal()]);
        $this->assertEquals($plaintext, $encryptor->decrypt($ciphertext));
    }

    public function testExceptionShouldBeThrownWhenNoBackendSupportCiphertext()
    {
        $this->expectException(\AwardWallet\MainBundle\Security\Encryptor\Exception\NoEncryptionDetectedException::class);
        $ciphertext = 'encrypted';

        $otherBackend = $this->prophesize(BackendInterface::class);
        $actualBackend = $this->prophesize(BackendInterface::class);

        $actualBackend
            ->supports($ciphertext)
            ->willReturn(false)
            ->shouldBeCalledOnce();

        $otherBackend
            ->supports($ciphertext)
            ->willReturn(false)
            ->shouldBeCalledOnce();

        $encryptor = new Encryptor($actualBackend->reveal(), [$otherBackend->reveal()]);
        $encryptor->decrypt($ciphertext);
    }
}
