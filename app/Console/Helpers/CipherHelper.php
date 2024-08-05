<?php

namespace App\Helpers;

class CipherHelper
{
    /**
     * Returns encrypted string using STORE_ENCRYPTION_KEY
     *
     * @param string $string
     * @return string
     * @throws \Exception
     */
    public static function encryptString(string $string = ''): string
    {
        $secretKey = sodium_hex2bin(config('app.store_encrypt_key') ?? '');
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $secretStr = sodium_crypto_secretbox($string, $nonce, $secretKey);

        return sodium_bin2base64($nonce . $secretStr, SODIUM_BASE64_VARIANT_ORIGINAL);
    }

    /**
     * Returns decrypted string using STORE_ENCRYPTION_KEY
     *
     * @param string $encryptedString
     * @return string|bool
     */
    public static function decryptString(string $encryptedString = ''): string|bool
    {
        $secretKey = sodium_hex2bin(config('app.store_encrypt_key') ?? '');
        $cipherText = sodium_base642bin($encryptedString, SODIUM_BASE64_VARIANT_ORIGINAL);
        $nonce = \App\Helpers\mb_substr($cipherText, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $cipherText = \App\Helpers\mb_substr($cipherText, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

        $result = false;

        try {
            $result = sodium_crypto_secretbox_open($cipherText, $nonce, $secretKey);
        } catch (\Exception) {}

        return $result;
    }
}
