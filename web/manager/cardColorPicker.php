<?php

use AwardWallet\MainBundle\FrameworkExtension\Error\SafeExecutorFactory;
use AwardWallet\MainBundle\Globals\StringUtils;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

$schema = "cardColorPicker";

require "start.php";

require_once $sPath . "/kernel/siteFunctions.php";

require_once "$sPath/kernel/public.php";

require_once "$sPath/kernel/TForm.php";

require_once __DIR__ . "/reports/common.php";

if ('POST' === $_SERVER['REQUEST_METHOD']) {
    /*
     * Request example:
     * {
     *     "provider-1": {
     *         "background": "#ffffff",
     *         "font": "#000000",
     *         "accent": "#000000"
     *     },
     *     "provider-12": {
     *         "background": "#ffffff",
     *         "accent": "#000000"
     *     },
     *     "provider-9": {
     *         "accent": "#000000"
     *     }
     * }
     */
    $data = \json_decode(\file_get_contents('php://input'), true);

    if (!\is_array($data)) {
        (new JsonResponse(['error' => 'invalid data'], 400))->send();

        exit;
    }

    $_POST['FormToken'] = $data['FormToken'] ?? null;

    if (!isValidFormToken()) {
        (new JsonResponse(['error' => 'invalid form token'], 400))->send();

        exit;
    }

    $data = $data['data'] ?? [];

    if (!\is_array($data)) {
        (new JsonResponse(['error' => 'invalid data'], 400))->send();

        exit;
    }

    $connection = getSymfonyContainer()->get('database_connection');
    $safeExecFactory = getSymfonyContainer()->get(SafeExecutorFactory::class);
    $result = [];

    $safeExecFactory
        ->make(function () use ($connection, $data, &$result) {
            $connection->transactional(function () use ($connection, $data, &$result) {
                foreach ($data as $providerKey => $providerData) {
                    $providerId = (int) \substr($providerKey, \strlen('provider-'));

                    foreach (
                        [
                            ['border-lm', 'Border_LM'],
                            ['border-dm', 'Border_DM'],
                        ] as [$flagType, $flagField]
                    ) {
                        if (!\array_key_exists($flagType, $providerData)) {
                            continue;
                        }

                        $flag = $providerData[$flagType];

                        if (!\is_scalar($flag)) {
                            continue;
                        }

                        $flag = (int) ((bool) $flag);

                        $connection->executeStatement(
                            "update Provider set {$flagField} = ? where ProviderID = ?",
                            [
                                $flag,
                                $providerId,
                            ]
                        );
                        $result[$providerKey][$flagType] = $flag;
                    }

                    foreach ([
                        ['background', 'BackgroundColor'],
                        ['font', 'FontColor'],
                        ['accent', 'AccentColor']] as [$colorType, $colorField]
                    ) {
                        if (!\array_key_exists($colorType, $providerData)) {
                            continue;
                        }

                        $color = $providerData[$colorType];

                        if (!\is_scalar($color)) {
                            continue;
                        }

                        $color = \preg_replace('/\s/', '', (string) $color);

                        if (\in_array($color, ['#', ''])) {
                            $color = null;
                        }

                        if (null !== $color) {
                            if (\preg_match('/^#?([0-9a-f]{6})$/i', $color, $m)) {
                                $color = $m[1];
                            } else {
                                continue;
                            }
                        }

                        $connection->executeStatement(
                            "update Provider set {$colorField} = ? where ProviderID = ?",
                            [
                                $color,
                                $providerId,
                            ]
                        );
                        $result[$providerKey][$colorType] = $color;
                    }
                }
            });

            getSymfonyContainer()->get('logger')->info('Provider data saved', ['provider_data_update_map' => $result]);
            (new JsonResponse(['success' => true, 'data' => $result]))->send();
        })
        ->orElse(function () {
            (new JsonResponse(['error' => 'Failed to save. Try again later.'], 500))->send();
        })
        ->run();

    exit;
}

drawHeader("Card Color Picker");

$urlGenerator = getSymfonyContainer()->get('router');
$connection = getSymfonyContainer()->get('database_connection');

function getProviders(): \Generator
{
    $connection = getSymfonyContainer()->get('database_connection');
    $connection->executeQuery('SET @@session.group_concat_max_len = 400');
    $stmt = $connection->executeQuery("
            select 
                popular.*,
                '' as groupedIds
            from (
                select
                    p.ProviderID,
                    p.Accounts,
                    p.ShortName,
                    p.Code,
                    p.BackgroundColor,
                    p.FontColor,
                    p.AccentColor,
                    p.Border_LM,
                    p.Border_DM,
                    coalesce(
                        if(p.Site = '', null, p.Site),
                        if(p.LoginURL = '', null, p.LoginURL)
                    ) as URL
                from Provider p
                order by p.Accounts desc
                limit 1000
            ) popular
            group by popular.ProviderID, popular.Accounts
            order by popular.Accounts desc"
    );

    while ($providerRow = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        $groupedIds = StringUtils::isNotEmpty($providerRow['groupedIds']) ?
            \explode(',', $providerRow['groupedIds']) :
            [];

        $accounts = array_slice($groupedIds, 0, min(\count($groupedIds) - 1, 20));

        if (!$accounts) {
            yield [\array_merge(
                $providerRow,
                [
                    'CardImageID' => null,
                    'UUID' => null,
                ]
            )];

            continue;
        }

        $stmtInner = $connection->executeQuery("
            select
                p.Code,
                p.ProviderID,
                p.ShortName,
                p.BackgroundColor,
                p.FontColor,
                p.AccentColor,
                p.Border_LM,
                p.Border_DM,
                coalesce(
                    if(p.Site = '', null, p.Site),
                    if(p.LoginURL = '', null, p.LoginURL)
                ) as URL,
                ci.CardImageID,
                ci.UUID
            from CardImage ci
            join Account a on ci.AccountID = a.AccountID
            join Provider p on a.ProviderID = p.ProviderID
            where
                ci.CardImageID in (?) and
                ci.ProviderID = ?
            order by p.Code
            ",
            [$accounts, $providerRow['ProviderID']],
            [Connection::PARAM_INT_ARRAY, \PDO::PARAM_INT]
        );

        yield from it($stmtInner)
            ->groupAdjacentBy(fn (array $row1, array $row2) => $row1['Code'] <=> $row2['Code']);
        $stmtInner->closeCursor();
    }
}

?>
<style>
    .status {
        font-weight: bold;
    }
    .status-saved {
        color: rgb(68, 208, 53);
    }

    .status-empty {
        color: rgb(175, 175, 175);
    }

    .status-unsaved {
        color: red;
    }

    .button-container {
        display: flex;
        justify-content: space-between;
        width: 100%;
        padding: 10px;
        box-sizing: border-box;
    }

    .left-button {
        margin-right: auto;
    }

    .right-button {
        margin-left: auto;
    }
</style>
<script>
    var lastKnownScrollPosition = 0;
    var ticking = false;
    var storage = window.localStorage;
    var providerColors;
    const CURRENT_VERSION = 2;

    function changeFlag(event) {
        const input = event.target;
        const flagType = input.dataset.flagtype;
        const providerId = input.dataset.providerid;
        const value = input.checked;

        if (!providerColors.data[providerId]) {
            providerColors.data[providerId] = {};
        }

        providerColors.data[providerId][flagType] = value;
        const status = document.querySelector(`#${providerId} .${flagType}-flag span.status`);
        status.classList.remove('status-saved');
        status.classList.remove('status-empty');
        status.innerHTML = flagType.toUpperCase() + ' UNSAVED';
        status.classList.add('status-unsaved');

        saveToStorage();
    }

    function changeColor(event) {
        const input = event.target;
        const colorType = input.dataset.colortype;
        const providerId = input.dataset.providerid;
        let value = input.value;

        if (value === '#') {
            value = '';
        }

        if (!providerColors.data[providerId]) {
            providerColors.data[providerId] = {};
        }

        providerColors.data[providerId][colorType] = value;
        document.querySelector(`#${providerId} .${colorType}-color input[type="color"]`).value = value;
        document.querySelector(`#${providerId} .${colorType}-color input[type="text"]`).value = value;
        const status = document.querySelector(`#${providerId} .${colorType}-color span.status`);
        status.classList.remove('status-saved');
        status.classList.remove('status-empty');
        status.innerHTML = colorType.toUpperCase() + ' UNSAVED';
        status.classList.add('status-unsaved');

        updateTextExample(document.querySelector(`#${providerId} .text-example`), providerColors.data[providerId]);
        saveToStorage();
    }

    function saveToStorage() {
        storage.setItem('providersColors_v5', JSON.stringify(providerColors));
    }

    function updateTextExample(elem, colors) {
        elem.style.color = colors.font;
        elem.style.backgroundColor = colors.background;
        elem.style.accentColor = colors.accent;
    }

    function saveProvider(providerId) {
        saveData({[providerId]: providerColors.data[providerId]});
    }

    function saveAll() {
        saveData(providerColors.data);
    }

    function resetAll() {
        resetData(providerColors.data);
    }

    function resetProvider(providerId) {
        const data = providerColors.data[providerId];
        resetData({[providerId]: data});
    }

    function resetData(data) {
        for (const [providerKey, providerData] of Object.entries(data)) {
            const providerRow = document.querySelector(`#${providerKey}`);
            const defaultValues = {
                font: '',
                background: '',
                accent: '',
                "border-lm": '',
                "border-dm": '',
            };

            for (const flagType of ['border-lm', 'border-dm']) {
                const status = providerRow.querySelector(`td.${flagType}-flag span.status`);
                defaultValues[flagType] = status.dataset.savedchecked === '1';

                if (!providerData || !Object.prototype.hasOwnProperty.call(providerData, flagType)) {
                    continue;
                }

                const flagElem = providerRow.querySelector(`input[type=checkbox][data-flagType="${flagType}"]`);
                flagElem.checked = flagElem.dataset.savedchecked === '1';
                status.innerHTML = status.dataset.savedvalue;
                status.classList.remove('status-unsaved');
                status.classList.remove('status-empty');
                status.classList.add(status.dataset.savedclass);
                delete providerColors.data[providerKey][flagType];
            }

            for (const colorType of ['font', 'background', 'accent']) {
                const status = providerRow.querySelector(`td.${colorType}-color span.status`);

                if (status.dataset.savedclass !== 'status-empty') {
                    defaultValues[colorType] = status.dataset.savedcolor;
                }

                if (!providerData || !providerData[colorType]) {
                    continue;
                }

                const colorElem = providerRow.querySelector(`input[type=color][data-colorType="${colorType}"]`);
                colorElem.value = colorElem.dataset.savedvalue;
                const colorText = providerRow.querySelector(`input[type=text][data-colorType="${colorType}"]`);
                colorText.value = colorText.dataset.savedvalue;
                status.innerHTML = status.dataset.savedvalue;
                status.classList.remove('status-unsaved');
                status.classList.remove('status-empty');
                status.classList.add(status.dataset.savedclass);
                delete providerColors.data[providerKey][colorType];
            }

            if (Object.keys(providerColors.data[providerKey]).length === 0) {
                delete providerColors.data[providerKey];
            }

            updateTextExample(
                providerRow.querySelector('.text-example'),
                defaultValues
            );
        }

        saveToStorage()
    }

    async function saveData(data) {
        const resp = await fetch('/manager/cardColorPicker.php', {
            'method': 'POST',
            'headers': {
                'Content-Type': 'application/json',
            },
            'body': JSON.stringify({
                FormToken: '<?php echo GetFormToken(); ?>',
                data: data,
            }),
        })

        if (resp.ok) {
            const savedProviders = await resp.json();

            for ([providerKey, providerData] of Object.entries(savedProviders.data)) {
                const providerRow = document.querySelector(`#${providerKey}`);

                for (const flagType of ['border-lm', 'border-dm']) {
                    if (!Object.prototype.hasOwnProperty.call(providerData, flagType)) {
                        continue;
                    }

                    delete providerColors.data[providerKey][flagType];

                    if (Object.keys(providerColors.data[providerKey]).length === 0) {
                        delete providerColors.data[providerKey];
                    }

                    const status = providerRow.querySelector(`td.${flagType}-flag span.status`);
                    status.classList.remove('status-unsaved');
                    status.classList.remove('status-empty');
                    status.innerHTML = flagType.toUpperCase() + ' SAVED';
                    status.classList.add('status-saved');
                }

                for (const colorType of ['font', 'background', 'accent']) {
                    if (!providerData.hasOwnProperty(colorType)) {
                        continue;
                    }

                    delete providerColors.data[providerKey][colorType];

                    if (Object.keys(providerColors.data[providerKey]).length === 0) {
                        delete providerColors.data[providerKey];
                    }

                    const status = providerRow.querySelector(`td.${colorType}-color span.status`);
                    status.classList.remove('status-unsaved');
                    status.classList.remove('status-empty');
                    status.innerHTML = colorType.toUpperCase() + ' SAVED';
                    status.classList.add('status-saved');
                }
            }

            saveToStorage();
        } else {
            try {
                const err = await resp.json();
                alert('Save error: ' + resp.status + ', ' + err.error);
            } catch (e) {
                alert('Save error: ' + resp.status + ', unknown error');

            }
        }
    }

    window.onload = () => {
        let serializedData = storage.getItem('providersColors_v5');

        if (null === serializedData) {
            providerColors = {
                version: CURRENT_VERSION,
                data: {}
            };
        } else {
            providerColors = JSON.parse(serializedData);
        }

        if (
            (typeof providerColors.version === 'number') &&
            (providerColors.version !== CURRENT_VERSION)
        ) {
            providerColors = {
                version: CURRENT_VERSION,
                data: {}
            };
        }

        for (const [providerId, providerData] of Object.entries(providerColors.data)) {
            const providerRow = document.querySelector(`#${providerId}`);

            if (!providerRow) {
                continue;
            }

            for (const flagType of ['border-lm', 'border-dm']) {
                if (!Object.prototype.hasOwnProperty.call(providerData, flagType)) {
                    continue;
                }

                const flagElem = providerRow.querySelector(`input[type=checkbox][data-flagType="${flagType}"]`);
                flagElem.checked = providerData[flagType];
                const status = providerRow.querySelector(`td.${flagType}-flag span.status`);
                status.classList.remove('status-saved');
                status.classList.remove('status-empty');
                status.innerHTML = flagType.toUpperCase() + ' UNSAVED';
                status.classList.add('status-unsaved');
            }

            for (const colorType of ['font', 'background', 'accent']) {
                if (!Object.prototype.hasOwnProperty.call(providerData, colorType)) {
                    continue;
                }

                providerRow.querySelector(`input[type=color][data-colorType="${colorType}"]`).value = providerData[colorType];
                providerRow.querySelector(`input[type=text][data-colorType="${colorType}"]`).value = providerData[colorType];
                const status = providerRow.querySelector(`td.${colorType}-color span.status`);
                status.classList.remove('status-saved');
                status.classList.remove('status-empty');
                status.innerHTML = colorType.toUpperCase() + ' UNSAVED';
                status.classList.add('status-unsaved');
            }

            updateTextExample(providerRow.querySelector('.text-example'), providerData);
        }

        document.querySelectorAll('input[type=color]').forEach( el => {
            el.oninput = el.onchange = changeColor;
        });

        document.querySelectorAll('input[type=text]').forEach( el => {
            el.oninput = el.onchange = changeColor;
        });

        document.querySelectorAll('input[type=checkbox]').forEach(el => {
            el.onchange = el.oninput = changeFlag;
        });
    }

    function openUrl(code, url) {
        window.open(url,
            `${code}previewWindow`,
            `   toolbar=no,
                top=0,
                left=0,
                location=no,
                status=no,
                menubar=no,
                scrollbars=yes,
                resizable=yes,
                width=300,
                height=200
            `
        );
    }
</script>
<div class="button-container">
    <button class="left-button" onclick="saveAll()">💾 SAVE ALL</button>
    <button class="right-button" onclick="resetAll()">↩️ RESET ALL</button>
</div>
<br/>
<table border="1px" style="border-collapse: collapse">
    <thead>
        <th>Provider Code</th>
        <th>Background Color</th>
        <th>Font Color</th>
        <th>Accent Color</th>
        <th>Border-LM</th>
        <th>Border-DM</th>
        <th>Result</th>
        <th>Actions</th>
    </thead>
    <tbody>
<?php

foreach (getProviders() as $providerData) {
    $firstImage = current($providerData);
    $providerId = (int) $firstImage['ProviderID'];
    $visibleImages = array_slice($providerData, 0, 3);
    $hiddenImages = array_slice($providerData, 3);
    ?>
        <tr id="provider-<?php echo $providerId; ?>" class="provider" valign="top">
            <td>
                <?php echo $firstImage['Code']; ?>
                <br/>
                <br/>
                <?php if (isset($firstImage['URL'])) {?>
                    <a href="#" onclick="window.openUrl('<?php echo $firstImage['Code']; ?>', '<?php echo htmlspecialchars($firstImage['URL']); ?>'); return false;">site</a>
                <?php } ?>
            </td>
            <td class="background-color" valign="top">
                <input class="background-color" data-providerid="provider-<?php echo $providerId; ?>" data-colorType="background" data-savedvalue="#<?php echo $firstImage['BackgroundColor'] ?? ''; ?>" value="#<?php echo $firstImage['BackgroundColor'] ?? ''; ?>" style="width:85%;" type="color">
                <br/>
                <input type="text" data-providerid="provider-<?php echo $providerId; ?>" data-colorType="background" size="7" maxlength="7" data-savedvalue="#<?php echo $firstImage['BackgroundColor'] ?? ''; ?>" value="#<?php echo $firstImage['BackgroundColor'] ?? ''; ?>"/>
                <br/>
                <span data-providerid="provider-<?php echo $providerId; ?>" data-savedcolor="#<?php echo $firstImage['BackgroundColor'] ?? ''; ?>" data-savedclass="<?php echo (null === ($firstImage['BackgroundColor'] ?? null)) ? 'status-empty' : 'status-saved'; ?>" class="status <?php echo (null === ($firstImage['BackgroundColor'] ?? null)) ? 'status-empty' : 'status-saved'; ?>" data-savedvalue="<?php echo (null === ($firstImage['BackgroundColor'] ?? null)) ? 'BACKGROUND EMPTY' : 'BACKGROUND SAVED'; ?>"><?php echo (null === ($firstImage['BackgroundColor'] ?? null)) ? 'BACKGROUND EMPTY' : 'BACKGROUND SAVED'; ?></span>
            </td>
            <td class="font-color" valign="top">
                <input class="font-color" data-providerid="provider-<?php echo $providerId; ?>" data-colorType="font" data-savedvalue="#<?php echo $firstImage['FontColor'] ?? ''; ?>" value="#<?php echo $firstImage['FontColor'] ?? ''; ?>" style="width:85%;" type="color">
                <br/>
                <input type="text" data-providerid="provider-<?php echo $providerId; ?>" data-colorType="font" size="7" maxlength="7" data-savedvalue="#<?php echo $firstImage['FontColor'] ?? ''; ?>" value="#<?php echo $firstImage['FontColor'] ?? ''; ?>"/>
                <br/>
                <span data-providerid="provider-<?php echo $providerId; ?>" data-savedcolor="#<?php echo $firstImage['FontColor'] ?? ''; ?>" data-savedclass="<?php echo (null === ($firstImage['FontColor'] ?? null)) ? 'status-empty' : 'status-saved'; ?>" class="status <?php echo (null === ($firstImage['FontColor'] ?? null)) ? 'status-empty' : 'status-saved'; ?>" data-savedvalue="<?php echo (null === ($firstImage['FontColor'] ?? null)) ? 'FONT EMPTY' : 'FONT SAVED'; ?>"><?php echo (null === ($firstImage['FontColor'] ?? null)) ? 'FONT EMPTY' : 'FONT SAVED'; ?></span>
            </td>
            <td class="accent-color" valign="top">
                <input class="accent-color" data-providerid="provider-<?php echo $providerId; ?>" data-colorType="accent" data-savedvalue="#<?php echo $firstImage['AccentColor'] ?? ''; ?>" value="#<?php echo $firstImage['AccentColor'] ?? ''; ?>" style="width:85%;" type="color">
                <br/>
                <input type="text" data-providerid="provider-<?php echo $providerId; ?>" data-colorType="accent" size="7" maxlength="7" data-savedvalue="#<?php echo $firstImage['AccentColor'] ?? ''; ?>" value="#<?php echo $firstImage['AccentColor'] ?? ''; ?>"/>
                <br/>
                <span data-providerid="provider-<?php echo $providerId; ?>" data-savedcolor="#<?php echo $firstImage['AccentColor'] ?? ''; ?>" data-savedclass="<?php echo (null === ($firstImage['AccentColor'] ?? null)) ? 'status-empty' : 'status-saved'; ?>" class="status <?php echo (null === ($firstImage['AccentColor'] ?? null)) ? 'status-empty' : 'status-saved'; ?>" data-savedvalue="<?php echo (null === ($firstImage['AccentColor'] ?? null)) ? 'ACCENT EMPTY' : 'ACCENT SAVED'; ?>"><?php echo (null === ($firstImage['AccentColor'] ?? null)) ? 'ACCENT EMPTY' : 'ACCENT SAVED'; ?></span>
            </td>
            <td class="border-lm-flag" valign="top">
                <input class="border-lm-flag" data-providerid="provider-<?php echo $providerId; ?>" data-savedchecked="<?php echo $firstImage['Border_LM']; ?>" data-flagType="border-lm" <?php echo $firstImage['Border_LM'] ? "checked" : ''; ?> type="checkbox">
                <br/>
                <span data-providerid="provider-<?php echo $providerId; ?>" data-savedchecked="<?php echo $firstImage['Border_LM']; ?>" data-savedclass="status-saved" class="status status-saved" data-savedvalue="BORDER-LM SAVED">BORDER-LM SAVED</span>
            </td>
            <td class="border-dm-flag" valign="top">
                <input class="border-dm-flag" data-providerid="provider-<?php echo $providerId; ?>" data-savedchecked="<?php echo $firstImage['Border_DM']; ?>" data-flagType="border-dm" <?php echo $firstImage['Border_DM'] ? "checked" : ''; ?> type="checkbox">
                <br/>
                <span data-providerid="provider-<?php echo $providerId; ?>" data-savedchecked="<?php echo $firstImage['Border_DM']; ?>" data-savedclass="status-saved" class="status status-saved" data-savedvalue="BORDER-DM SAVED">BORDER-DM SAVED</span>
            </td>
            <td valign="top">
                <div class="text-example" style="padding: 40px 20px; font-size: xx-large; background-color: #<?php echo $firstImage['BackgroundColor'] ?? ''; ?>; color: #<?php echo $firstImage['FontColor'] ?? ''; ?>; accent-color: #<?php echo $firstImage['AccentColor'] ?? ''; ?>">
                    <input checked="" type="checkbox" id="checkbox-<?php echo $providerId; ?>" style="height: 30px; width: 30px;">
                    <label for="checkbox-<?php echo $providerId; ?>"><?php echo strtoupper($firstImage['ShortName']); ?></label>
                </div>
            </td>
            <td valign="top">
                <button type="button" onclick="saveProvider('provider-<?php echo $providerId; ?>')">💾 SAVE PROVIDER</button>
                <br/>
                <button type="button" onclick="resetProvider('provider-<?php echo $providerId; ?>')">↩️ RESET PROVIDER</button>
            </td>
        </tr> <?php
}
?>
    </tbody>
</table>
<?php

drawFooter();
