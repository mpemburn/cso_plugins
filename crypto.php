<?php
/** Crypto: URL-safe encryption */

if (!class_exists('Crypto')) {
    class Crypto
    {
        /**
         * @param $plainText
         * @return bool|string
         */
        public static function encrypt($plainText)
        {
            return self::encryptor('encrypt', $plainText);
        }

        /**
         * @param $cipher
         * @return bool|string
         */
        public static function decrypt($cipher)
        {
            return self::encryptor('decrypt', $cipher);
        }

        /**
         * @param $action
         * @param $string
         * @return bool|string
         */
        public static function encryptor($action, $string) {
            $output = false;

            $encrypt_method = "AES-256-CBC";
            //pls set your unique hashing key
            $secret_key = 'muni';
            $secret_iv = 'muni123';

            // hash
            $key = hash('sha256', $secret_key);

            // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
            $iv = substr(hash('sha256', $secret_iv), 0, 16);

            //do the encyption given text/string/number
            if( $action == 'encrypt' ) {
                $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
                $output = base64_encode($output);
            }
            else if( $action == 'decrypt' ){
                //decrypt the given text/string/number
                $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
            }

            return $output;
        }
    }
} // !class_exists('Crypto')