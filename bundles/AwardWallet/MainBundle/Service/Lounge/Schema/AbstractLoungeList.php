<?php

namespace AwardWallet\MainBundle\Service\Lounge\Schema;

use AwardWallet\MainBundle\Entity\Lounge;
use AwardWallet\MainBundle\Entity\LoungeSource as LoungeSourceAlias;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile\OpeningHoursItemView;
use AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile\OpeningHoursScheduleBuilder;
use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\AbstractOpeningHours;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\RawOpeningHours;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\StructuredOpeningHours;
use Spatie\OpeningHours\Exceptions\Exception;
use Spatie\OpeningHours\Exceptions\InvalidTimezone;

abstract class AbstractLoungeList extends \TBaseList
{
    private OpeningHoursScheduleBuilder $openingHoursScheduleBuilder;

    public function __construct($table, $fields, OpeningHoursScheduleBuilder $openingHoursScheduleBuilder)
    {
        $this->openingHoursScheduleBuilder = $openingHoursScheduleBuilder;

        parent::__construct($table, $fields);
    }

    public function DrawButtons($closeTable = true)
    {
        global $Interface;

        parent::DrawButtons($closeTable);

        $additionCss = $this->getCssStyles();
        $styles = <<<HTML
<style>
    {$additionCss}
    .open-hours-header {
        font-family: "Open Sans", sans-serif;
        font-size: 12px;
        margin: 10px 0;
        color: #797979;
    }
    .open-hours-header.ai {
        color: #ff8f8f;
    }
    .open-hours-body {
        font-family: "Open Sans", sans-serif;
        font-size: 10px;
    }
    .location-field {
        font-size: 10px;
    }
    .location-header {
        color:#797979;
    }
    .img-tooltip {
        color: #b2b2b2;
    }
    .create-at {
        color: #b2b2b2;
        font-size: 8px;
    }
</style>
HTML;
        $styles = addslashes(str_replace("\n", '', $styles));
        echo "<script>$(document.body).append('$styles');</script>";

        $Interface->FooterScripts['loungeList'] = <<<JS
$(function() {
    $('.img-tooltip').hover(
        function() {
            var image = $('<img src="' + $(this).data().image + '" class="removable"></img>');
            $('body').append(image);
            $(image).css({
                position: "absolute",
                top: $(this).position().top + $(this).height(),
                left: $(this).position().left + 25,
                maxWidth: 350
            });
        },
        function() {
            $('.removable').remove();
        }
    );
});
JS;
    }

    protected function defaultFormat(LoungeInterface $lounge)
    {
        $this->Query->Fields['Terminal'] = ''
            . $this->formatLocation('Terminal', $this->Query->Fields['Terminal'])
            . $this->formatLocation('Gate', $this->Query->Fields['Gate'])
            . $this->formatLocation('Gate2', $this->Query->Fields['Gate2'])
            . $this->formatLocation('Location', $this->Query->Fields['Location'])
            . ($lounge instanceof Lounge && !empty($lounge->getLocationParaphrased()) ? $this->formatLocation('Location Paraphrased', $lounge->getLocationParaphrased()) : '')
            . $this->formatLocation('Airlines', implode(', ', $lounge->getAirlines()->toArray()))
            . $this->formatLocation('Alliances', implode(', ', $lounge->getAlliances()->toArray()));

        $oh = $this->formatOpeningHours($lounge->getOpeningHours());

        if ($lounge instanceof Lounge && !empty($ohAi = $lounge->getOpeningHoursAi())) {
            $oh .= $this->formatOpeningHours($ohAi, true);
        }

        $this->Query->Fields['OpeningHours'] = $oh;
        $this->Query->Fields['AdditionalInfo'] = ''
            . $this->formatLocation('AdditionalInfo', $this->Query->Fields['AdditionalInfo'])
            . $this->formatLocation('Amenities', $this->Query->Fields['Amenities'])
            . $this->formatLocation('Rules', str_replace("\n", '<br>', $this->Query->Fields['Rules']));

        $this->Query->Fields['UpdateDate'] = sprintf(
            '%s<br><span class="create-at">Created: %s</span>%s',
            $this->Query->Fields['UpdateDate'] ?? '<none>',
            $this->Query->Fields['CreateDate'],
            isset($this->Query->Fields['ParseDate'])
                ? sprintf('<br><span class="create-at">Parsed: %s</span>', $this->Query->Fields['ParseDate'])
                : ''
        );
    }

    protected function formatAssets(array $assets)
    {
        $url = null;
        $images = [];

        foreach ($assets as $asset) {
            if (!isset($asset['type'], $asset['url'])) {
                continue;
            }

            if ($asset['type'] === LoungeSourceAlias::ASSET_TYPE_URL) {
                $url = $asset['url'];
            } elseif ($asset['type'] === LoungeSourceAlias::ASSET_TYPE_IMG) {
                $images[] = $asset['url'];
            }
        }

        if (!empty($url)) {
            $this->Query->Fields['Name'] = sprintf('<a href="%s" target="_blank">%s</a>', $url, $this->Query->Fields['Name']);
        }

        if ($images) {
            $imageLinks = [];

            foreach ($images as $i => $image) {
                $imageLinks[] = sprintf(
                    '<a href="%s" data-image="%s" class="img-tooltip" target="_blank">%s</a>',
                    $image, $image, $i + 1
                );
            }

            $this->Query->Fields['Name'] = sprintf('%s<div style="color: #b2b2b2">img: %s</div>', $this->Query->Fields['Name'], implode(' ', $imageLinks));
        }
    }

    protected function formatLocation(string $name, $value): string
    {
        if (StringHandler::isEmpty($value)) {
            return '';
        }

        $color = '#000';

        if (in_array($name, ['Airlines', 'Alliances'])) {
            $color = '#49a339';
        }

        return sprintf(
            '<div class="location-field"><span class="location-header">%s:</span> <span style="color: %s;">%s</span></div>',
            $name,
            $color,
            $value
        );
    }

    protected function formatOpeningHours(?AbstractOpeningHours $openingHours, bool $ai = false): string
    {
        if ($openingHours instanceof StructuredOpeningHours) {
            try {
                $stringOpeningHours = $this->openingHoursScheduleBuilder->build($openingHours->build(), 'en');

                if (is_array($stringOpeningHours)) {
                    $stringOpeningHours = implode('', array_map(function (OpeningHoursItemView $item) {
                        return sprintf(
                            '<strong>%s</strong>: %s<br>',
                            implode(', ', $item->days),
                            is_string($item->openingHours)
                                ? $item->openingHours
                                : implode(', ', array_map(fn ($item) => (string) $item, $item->openingHours))
                        );
                    }, $stringOpeningHours));
                }

                $header = 'Structured';
            } catch (Exception $e) {
                $header = 'Error';
                $stringOpeningHours = sprintf('<div style="color: red;">%s</div>', $e->getMessage());
            } catch (InvalidTimezone $e) {
                $header = 'Invalid Timezone';
                $stringOpeningHours = sprintf('<div style="color: red;">%s</div>', $e->getMessage());
            }
        } elseif ($openingHours instanceof RawOpeningHours) {
            $header = 'Raw';
            $stringOpeningHours = nl2br($openingHours->getRaw());
        } else {
            $header = 'Unknown';
            $stringOpeningHours = $openingHours;
        }

        if ($ai) {
            return sprintf(
                '<h5 class="open-hours-header ai">%s</h5><div class="open-hours-body">%s</div>',
                sprintf('%s AI', $header),
                $stringOpeningHours
            );
        }

        return sprintf(
            '<h5 class="open-hours-header">%s</h5><div class="open-hours-body">%s</div>',
            $header,
            $stringOpeningHours
        );
    }

    abstract protected function getCssStyles(): string;
}
