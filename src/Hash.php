<?php

namespace Kaishiyoku\HeraRssCrawler;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

class Hash
{
    public const MD5 = 'md5';

    public const SHA1 = 'sha1';

    public const SHA_224 = 'sha224';

    public const SHA_256 = 'sha256';

    public const SHA_384 = 'sha384';

    public const SHA_512 = 'sha512';

    public const RIPEMD_124 = 'ripemd128';

    public const RIPEMD_160 = 'ripemd160';

    public const RIPEMD_256 = 'ripemd256';

    public const RIPEMD_320 = 'ripemd320';

    public const WHIRLPOOL = 'whirlpool';

    public const TIGER_128_4 = 'tiger128,4';

    public const TIGER_160_4 = 'tiger160,4';

    public const TIGER_192_4 = 'tiger192,4';

    public const SNEFRU = 'snefru';

    public const SNEFRU_256 = 'snefru256';

    public const GOST = 'gost';

    public const GOST_CRYPTO = 'gost-crypto';

    public const HAVAL_128_5 = 'haval128,5';

    public const HAVAL_160_5 = 'haval160,5';

    public const HAVAL_192_5 = 'haval192,5';

    public const HAVAL_224_5 = 'haval224,5';

    public const HAVAL_256_5 = 'haval256,5';

    /**
     * @param string $algo
     * @param mixed $value
     * @return string|null
     */
    public static function hash(string $algo, $value): ?string
    {
        try {
            $availableAlgos = (new ReflectionClass(self::class))->getConstants();
            $availableAlgosOnMachine = hash_algos();

            if (!in_array($algo, $availableAlgos, true) || !in_array($algo, $availableAlgosOnMachine, true)) {
                throw new InvalidArgumentException('The chosen hash algorithm is not supported: ' . $algo);
            }

            return hash($algo, $value);
        } catch (ReflectionException $e) {
            return null;
        }
    }
}
