<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Encryptor\Backend;

use AwardWallet\MainBundle\Security\Encryptor\Backend\Sodium1Backend;
use AwardWallet\MainBundle\Security\Encryptor\Backend\Util\SodiumEncryptor;
use AwardWallet\Tests\Unit\BaseTest;

/**
 * @group frontend-unit
 * @coversDefaultClass \AwardWallet\MainBundle\Security\Encryptor\Backend\Sodium1Backend
 */
class Sodium1BackendTest extends BaseTest
{
    private $keyRaw = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    private $keyBase64 = 'YWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWE=';

    public function testSupportsCiphertextSuccess()
    {
        $backend = new Sodium1Backend($this->prophesize(SodiumEncryptor::class)->reveal(), 'sodium1:', $this->keyBase64);
        $this->assertTrue($backend->supports('sodium1:ciphertextciphertext'));
    }

    public function testSupportsCiphertextFail()
    {
        $backend = new Sodium1Backend($this->prophesize(SodiumEncryptor::class)->reveal(), 'sodium1:', $this->keyBase64);
        $this->assertFalse($backend->supports('sodium1otherssss:ciphertextciphertext'));
    }

    public function testEncrypt()
    {
        $plaintext = 'plaintext';
        $ciphertext = 'ciphertext';
        $encryptor = $this->prophesize(SodiumEncryptor::class);
        $encryptor
            ->encrypt($plaintext, $this->keyRaw)
            ->willReturn($ciphertext)
            ->shouldBeCalledOnce();

        $backend = new Sodium1Backend($encryptor->reveal(), 'sodium1:', $this->keyBase64);
        $this->assertEquals('sodium1:ciphertext', $backend->encrypt($plaintext));
    }

    public function testDecrypt()
    {
        $plaintext = 'plaintext';
        $ciphertext = 'ciphertext';
        $encryptor = $this->prophesize(SodiumEncryptor::class);
        $encryptor
            ->decrypt($ciphertext, $this->keyRaw)
            ->willReturn($plaintext)
            ->shouldBeCalledOnce();

        $backend = new Sodium1Backend($encryptor->reveal(), 'sodium1:', $this->keyBase64);
        $this->assertEquals($plaintext, $backend->decrypt('sodium1:ciphertext'));
    }
}
