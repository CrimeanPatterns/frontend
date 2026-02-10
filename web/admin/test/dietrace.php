<?
require __DIR__ . '/../../kernel/public.php';

echo "started in " . getSymfonyContainer()->get("kernel")->getEnvironment();

DieTrace($_GET['Code']);

echo "done";