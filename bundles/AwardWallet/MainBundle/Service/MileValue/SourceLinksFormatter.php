<?php

namespace AwardWallet\MainBundle\Service\MileValue;

class SourceLinksFormatter
{
    public static function formatSources(array $sources)
    {
        if (!empty($sources)) {
            $sourcesLinks = [];

            foreach ($sources as $source) {
                $source = json_decode($source, true);

                if (!empty($source) && array_key_exists('data', $source)) {
                    $data = array_values($source['data']);

                    foreach ($data as $item) {
                        if (isset($item['accountId']) || 'account' === $item['type']) {
                            $sourcesLinks[] = '<a href="/manager/loginAccount.php?ID=' . $item['accountId'] . '" target="account">account</a>';
                        } elseif ('email' === $item['type']) {
                            $date = $item['date'] ?? $item['dates'][0] ?? null;
                            $sourcesLinks[] = '<a href="/manager/email/parser/list/all?' . http_build_query([
                                'requestId' => $item['requestId'] ?? null,
                                'sort' => '',
                                'direction' => '',
                                'preview' => '',
                                'region' => '',
                                'id' => '',
                                'subject' => '',
                                'from' => '',
                                'to' => $item['recipient'] ?? '',
                                'partner' => '',
                                'userData' => 'parser',
                                'date' => date('m/d/Y', strtotime($date)),
                                'show' => ['all'],
                                'subjectAdv' => '',
                                'fromAdv' => '',
                                'toAdv' => '',
                                'providerAdv' => '',
                                'partnerAdv' => '',
                                'userDataAdv' => '',
                            ]) . '" target="email">email</a>';
                        } else {
                            $sourcesLinks[] = '! unknown type: ' . $item['type'];
                        }
                    }
                }
            }
        }

        return empty($sourcesLinks) ? '' : implode('<br>', array_unique($sourcesLinks));
    }
}
