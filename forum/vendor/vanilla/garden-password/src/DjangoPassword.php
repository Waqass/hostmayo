<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license MIT
 */

namespace Garden\Password;

/**
 * Implements tha password hashing algorithm from the Django framework.
 */
class DjangoPassword implements PasswordInterface {
    private static $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * @var string The hash method to use when hashing passwords.
     */
    private $hashFunction;

    /**
     * Initialize an instance of the {@link DjangoPassword} class.
     *
     * @param string $hashFunction The hash method used to hash the passwords.
     */
    public function __construct($hashFunction = 'crypt') {
        $this->hashFunction = $hashFunction;
    }

    /**
     * Generate a random salt that is compatible with {@link crypt()}.
     *
     * @return string|null Returns the salt as a string or **null** if the crypt algorithm isn't known.
     */
    private function generateCryptSalt() {
        if (CRYPT_BLOWFISH === 1) {
            $salt = str_replace('+', '/', base64_encode(openssl_random_pseudo_bytes(12)));
        } elseif (CRYPT_EXT_DES) {
            $count_log2 = 24;
            // This should be odd to not reveal weak DES keys. The max valid value is (2**24 - 1) which is odd anyway.
            $count = (1 << $count_log2) - 1;

            $salt = '_';
            $salt .= self::$itoa64[$count & 0x3f];
            $salt .= self::$itoa64[($count >> 6) & 0x3f];
            $salt .= self::$itoa64[($count >> 12) & 0x3f];
            $salt .= self::$itoa64[($count >> 18) & 0x3f];

            $salt .= substr(base64_encode(openssl_random_pseudo_bytes(3)), 0, 3);
        } else {
            $salt = null;
        }
        return $salt;
    }

    /**
     * Hashes a plaintext password.
     *
     * @param string $password The password to hash.
     * @return string Returns the hashed password.
     * @throws \Exception Throws an exception when the hash method is invalid.
     */
    public function hash($password) {
        if ($this->hashFunction === 'crypt') {
            $salt = $this->generateCryptSalt();
            try {
                $hash = crypt($password, $salt);
            } catch (\Exception $ex) {
                throw new \Exception("$salt is an invalid salt.", $ex);
            }
        } elseif (in_array($this->hashFunction, hash_algos())) {
            $salt = base64_encode(openssl_random_pseudo_bytes(12));
            $hash = hash($this->hashFunction, $salt.$password);
        } else {
            throw new \Exception("The {$this->hashFunction} hash method is invalid.", 500);
        }

        $result = $this->hashFunction.'$'.$salt.'$'.$hash;
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function needsRehash($hash) {
        if (strpos($hash, '$') === false) {
            return true;
        } else {
            list($method,,) = explode('$', $hash, 3);
            switch (strtolower($method)) {
                case 'crypt':
                case 'sha256':
                    return false;
                default:
                    return true;
            }
        }
    }

    /**
     * Check to make sure a password matches its stored hash.
     *
     * @param string $password The password to verify.
     * @param string $hash The stored password hash.
     * @return bool Returns `true` if the password matches the stored hash.
     */
    public function verify($password, $hash) {
        if (strpos($hash, '$') === false) {
            return md5($password) == $hash;
        } else {
            list($method, $salt, $rawHash) = explode('$', $hash);

            if ($method === 'crypt') {
                return crypt($password, $salt) === $rawHash;
            } elseif (in_array($method, hash_algos())) {
                return hash($method, $salt.$password) === $rawHash;
            } else {
                return false;
            }
        }
    }

    /**
     * Get the hash function.
     *
     * @return string Returns the name of hash function.
     */
    public function getHashFunction() {
        return $this->hashFunction;
    }

    /**
     * Set the hash function.
     *
     * @param string $hashFunction The name of the new hash function. Some examples would be: crypt, sha256, sha1.
     * @return DjangoPassword Returns `$this` for fluent calls.
     */
    public function setHashFunction($hashFunction) {
        $this->hashFunction = $hashFunction;
        return $this;
    }
}
