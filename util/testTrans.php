<?
require __DIR__ . '/../web/kernel/public.php';

$translator = getSymfonyContainer()->get('translator');
echo $translator->trans("account.error.sharing.required") . "\n";
echo $translator->trans("missing.trans") . "\n";
