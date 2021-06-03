<?php
namespace App\Libraries;
class EncryptUtil{
    const AES_KEY = "nishiwodebabyabc"; //16位
    const AES_IV  = "1234567890123456"; //16位
    public static function decrypt($str){
        return openssl_decrypt(base64_decode($str), 'aes-128-cbc', self::AES_KEY, OPENSSL_RAW_DATA, self::AES_IV);
    }
    public static function encrypt($plain_text){
        $encrypted_data = openssl_encrypt($plain_text, 'aes-128-cbc', self::AES_KEY, OPENSSL_RAW_DATA, self::AES_IV);
        return base64_encode($encrypted_data);
    }
}