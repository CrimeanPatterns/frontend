<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Encryptor\Backend\Util;

use AwardWallet\MainBundle\Security\Encryptor\Backend\Util\SodiumEncryptor;
use AwardWallet\Tests\Unit\BaseTest;

/**
 * @group frontend-unit
 * @coversDefaultClass  \AwardWallet\MainBundle\Security\Encryptor\Backend\Util\SodiumEncryptor
 */
class SodiumEncryptorTest extends BaseTest
{
    /**
     * @var SodiumEncryptor
     */
    protected $encryptor;

    public function _before()
    {
        parent::_before();

        $this->encryptor = new SodiumEncryptor();
    }

    public function testEncryptionWithInvalidKey()
    {
        $this->expectException(\AwardWallet\MainBundle\Security\Encryptor\Exception\EncryptionFailedException::class);
        $this->encryptor->encrypt('plaintext', '1');
    }

    public function testDecryptionWithMalformedKey()
    {
        $this->expectException(\AwardWallet\MainBundle\Security\Encryptor\Exception\DecryptionFailedException::class);
        $plaintext = 'plaintext';
        $key = \random_bytes(\SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $ciphertext = $this->encryptor->encrypt($plaintext, $key);
        $this->encryptor->decrypt($ciphertext, '1');
    }

    public function testDecryptionOfPreviouslyEncryptedText()
    {
        $plaintext = 'plaintext';
        $key = \random_bytes(\SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $ciphertext = $this->encryptor->encrypt($plaintext, $key);
        $this->assertEquals($plaintext, $this->encryptor->decrypt($ciphertext, $key));
    }

    public function testTamperedCiphertextShouldBeRejected()
    {
        $this->expectException(\AwardWallet\MainBundle\Security\Encryptor\Exception\DecryptionFailedException::class);
        $plaintext = 'plaintext';
        $key = \random_bytes(\SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $ciphertext = $this->encryptor->encrypt($plaintext, $key);

        foreach (\range(30, 40) as $pos) {
            $ciphertext[$pos] = 'f';
        }

        $this->encryptor->decrypt($ciphertext, $key);
    }
}
