<?php

namespace AwardWallet\MainBundle\Updater;

use Symfony\Component\HttpFoundation\Request;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class RequestSerializer
{
    public function serializeRequest(Request $request): string
    {
        $serialized = [
            'query' => $request->query->all(),
            'request' => $request->request->all(),
            'attributes' => it($request->attributes->all())
                ->filter('\\is_scalar')
                ->toArrayWithKeys(),
            'cookies' => $request->cookies->all(),
            'server' => $request->server->all(),
        ];

        return \json_encode($serialized);
    }

    public function deserializeRequest(string $requestSerialized): Request
    {
        $decoded = @\json_decode($requestSerialized, true);

        if (JSON_ERROR_NONE !== \json_last_error()) {
            throw new \RuntimeException(\sprintf('Invalid request data: %s', \json_last_error_msg()));
        }

        if (!isset(
            $decoded['query'],
            $decoded['request'],
            $decoded['attributes'],
            $decoded['cookies'],
            $decoded['server']
        )) {
            throw new \RuntimeException(\sprintf('Missing request data'));
        }

        [
            'query' => $query,
            'request' => $post,
            'attributes' => $attributes,
            'cookies' => $cookies,
            'server' => $server,
        ] = $decoded;

        return new Request($query, $post, $attributes, $cookies, [], $server);
    }
}
