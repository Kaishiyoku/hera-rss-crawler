<?php

namespace Kaishiyoku\HeraRssCrawler;

use InvalidArgumentException;

enum Hash: string
{
    case MD5 = 'md5';

    case SHA1 = 'sha1';

    case SHA_224 = 'sha224';

    case SHA_256 = 'sha256';

    case SHA_384 = 'sha384';

    case SHA_512 = 'sha512';

    case RIPEMD_124 = 'ripemd128';

    case RIPEMD_160 = 'ripemd160';

    case RIPEMD_256 = 'ripemd256';

    case RIPEMD_320 = 'ripemd320';

    case WHIRLPOOL = 'whirlpool';

    case TIGER_128_4 = 'tiger128,4';

    case TIGER_160_4 = 'tiger160,4';

    case TIGER_192_4 = 'tiger192,4';

    case SNEFRU = 'snefru';

    case SNEFRU_256 = 'snefru256';

    case GOST = 'gost';

    case GOST_CRYPTO = 'gost-crypto';

    case HAVAL_128_5 = 'haval128,5';

    case HAVAL_160_5 = 'haval160,5';

    case HAVAL_192_5 = 'haval192,5';

    case HAVAL_224_5 = 'haval224,5';

    case HAVAL_256_5 = 'haval256,5';

    /**
     * @param self $algo
     * @param mixed $value
     * @return string|null
     */
    public static function hash(self $algo, mixed $value): ?string
    {
        $availableAlgos = array_map(fn (self $hash) => $hash->value, self::cases());
        $availableAlgosOnMachine = hash_algos();

        if (!in_array($algo->value, $availableAlgos, true) || !in_array($algo->value, $availableAlgosOnMachine, true)) {
            throw new InvalidArgumentException('The chosen hash algorithm is not supported: ' . $algo->value);
        }

        return hash($algo->value, $value);
    }
}
