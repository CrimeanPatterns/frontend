<?php

namespace AwardWallet\MainBundle\Security\Reauthentication;

use Monolog\Processor\ProcessorInterface;

class LogProcessor implements ProcessorInterface
{
    public function __invoke(array $record)
    {
        foreach ($record['context'] as $key => $val) {
            if ($val instanceof AuthenticatedUser) {
                $record['context'][$key] = json_encode([
                    'userId' => $val->getEntity()->getUserid(),
                    'isBusiness' => $val->isBusiness(),
                ]);
            } elseif ($val instanceof ReauthResponse) {
                $record['context'][$key] = json_encode([
                    'action' => $val->action,
                    'inputType' => $val->inputType,
                    'context' => $val->context,
                ]);
            } elseif ($val instanceof ResultResponse) {
                $record['context'][$key] = json_encode([
                    'success' => $val->success,
                    'error' => $val->error,
                ]);
            }
        }

        $record['context']['service'] = 'reauth';

        return $record;
    }
}
