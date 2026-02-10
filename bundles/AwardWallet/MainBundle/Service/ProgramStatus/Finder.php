<?php

namespace AwardWallet\MainBundle\Service\ProgramStatus;

use AwardWallet\MainBundle\Entity\Usr;

class Finder
{
    public function findProviders(string $msg, AbstractDescriptor $descriptor, Usr $user): array
    {
        return $descriptor->searchProviders($msg, $user, 10, function ($provider) use ($msg) {
            return
                in_array($provider['ProviderID'], [1, 154])
                && preg_match(
                    '/\bamerican\s+(a|ai|air|airl|airli|airlin|airline|airlines)\b/iums',
                    $msg
                );
        }, [
            7, // delta
            16, // rapidrewards
            26, // mileageplus
        ], [
            PROVIDER_DISABLED,
            PROVIDER_COLLECTING_ACCOUNTS,
        ]);
    }
}
