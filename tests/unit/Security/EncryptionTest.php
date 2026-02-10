<?php

namespace AwardWallet\Tests\Unit\Security;

use AwardWallet\Tests\Unit\BaseUserTest;

/**
 * @group frontend-unit
 */
class EncryptionTest extends BaseUserTest
{
    public function testAesDecode()
    {
        $source = 'some-string-for-aes-test';
        $key = $this->container->getParameter('local_passwords_key');

        $mcrypt_SourceEncode = $this->mcrypt_AESEncode($source, $key); // old crypt with deprecated mcrypt
        $openssl_SourceEncode = AESEncode($source, $key); // new openssl with prefix

        $this->assertNotEquals($mcrypt_SourceEncode, $openssl_SourceEncode);

        $resultDecode_mcrypt = AESDecode($mcrypt_SourceEncode, $key);
        $resultDecode_openssl = AESDecode($openssl_SourceEncode, $key);

        $this->assertEquals($resultDecode_mcrypt, $resultDecode_openssl);
        $this->assertEquals($source, $resultDecode_openssl);
    }

    private function mcrypt_AESEncode($source, $key)
    {
        if (!function_exists('mcrypt_list_algorithms')) {
            require_once __DIR__ . '/../../../web/service/mcrypt.php';
        }

        $s = "";
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_ECB, '');
        $iv_size = mcrypt_enc_get_iv_size($td);
        $key = substr($key, 0, mcrypt_enc_get_key_size($td));
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

        if (mcrypt_generic_init($td, $key, $iv) != -1) {
            $s = mcrypt_generic($td, $source);
            mcrypt_generic_deinit($td);
            mcrypt_module_close($td);
        }

        return $s;
    }
}
