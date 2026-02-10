<?php

namespace AwardWallet\MainBundle\Email;

use AwardWallet\MainBundle\Entity\Provider;

class StatementHelper
{
    public static function isCeasedProvider(Provider $p): bool
    {
        return in_array($p->getCode(), [
            'delta', 'deltacorp', 'mileageplus', 'rapidrewards', 'perksplus',
        ]);
    }

    public static function isEmailProvider(Provider $p): bool
    {
        return self::isCeasedProvider($p) || in_array($p->getCode(), ['testprovider']);
    }

    public static function matchMaskedField(?string $data, ?string $mask, ?string $value): bool
    {
        if (strlen($data) == 0 || strlen($value) == 0) {
            return false;
        }

        switch ($mask) {
            case 'left':
                $regex = '/' . preg_quote($data) . '$/i';

                break;

            case 'right':
                $regex = '/^' . preg_quote($data) . '/i';

                break;

            case 'center':
                [$left, $right] = explode('**', $data) + ['', ''];

                if (strlen($left) > 0 && strlen($right) > 0) {
                    $regex = '/^' . preg_quote($left) . '.+' . preg_quote($right) . '$/i';
                }

                break;

            default:
                break;
        }

        if (isset($regex)) {
            return preg_match($regex, $value) > 0;
        } else {
            return strcasecmp($data, $value) === 0;
        }
    }
}
