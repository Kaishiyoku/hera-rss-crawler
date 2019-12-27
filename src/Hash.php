<?php

namespace Kaishiyoku\HeraRssCrawler;

use Illuminate\Support\Arr;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

class Hash
{
    /**
     * @var string
     */
    public const MD5 = 'md5';

    /**
     * @var string
     */
    public const SHA1 = 'sha1';

    /**
     * @var string
     */
    public const SHA_224 = 'sha224';

    /**
     * @var string
     */
    public const SHA_256 = 'sha256';

    /**
     * @var string
     */
    public const SHA_384 = 'sha384';

    /**
     * @var string
     */
    public const SHA_512 = 'sha512';

    /**
     * @var string
     */
    public const RIPEMD_124 = 'ripemd128';

    /**
     * @var string
     */
    public const RIPEMD_160 = 'ripemd160';

    /**
     * @var string
     */
    public const RIPEMD_256 = 'ripemd256';

    /**
     * @var string
     */
    public const RIPEMD_320 = 'ripemd320';

    /**
     * @var string
     */
    public const WHIRLPOOL = 'whirlpool';

    /**
     * @var string
     */
    public const TIGER_128_4 = 'tiger128,4';

    /**
     * @var string
     */
    public const TIGER_160_4 = 'tiger160,4';

    /**
     * @var string
     */
    public const TIGER_192_4 = 'tiger192,4';

    /**
     * @var string
     */
    public const SNEFRU = 'snefru';

    /**
     * @var string
     */
    public const SNEFRU_256 = 'snefru256';

    /**
     * @var string
     */
    public const GOST = 'gost';

    /**
     * @var string
     */
    public const GOST_CRYPTO = 'gost-crypto';

    /**
     * @var string
     */
    public const HAVAL_128_5 = 'haval128,5';

    /**
     * @var string
     */
    public const HAVAL_160_5 = 'haval160,5';

    /**
     * @var string
     */
    public const HAVAL_192_5 = 'haval192,5';

    /**
     * @var string
     */
    public const HAVAL_224_5 = 'haval224,5';

    /**
     * @var string
     */
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
