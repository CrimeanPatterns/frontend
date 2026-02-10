#!/usr/bin/php
<?php
require __DIR__ . '/../vendor/autoload.php';

if($argc != 4) {
    echo("expected params: <yml_file> <param/path> <param_path> <param_value>\n");
    exit(1);
}

echo "modifying  {$argv[1]}, setting {$argv[2]} to {$argv[3]}\n";

$content = file_get_contents($argv[1]);
if(empty($content)){
    echo("failed to load file {$argv[1]}\n");
    exit(2);
}
$yml = \Symfony\Component\Yaml\Yaml::parse($content);
$path = explode("/", $argv[2]);
$values = &$yml;
while(!empty($path)){
    $slug = array_shift($path);
    if(!isset($values[$slug])){
        echo("missing key $slug\n");
        exit(3);
    }
    if(empty($path))
        $values[$slug] = $argv[3];
    else
        $values = &$values[$slug];
}

if(!file_put_contents($argv[1], \Symfony\Component\Yaml\Yaml::dump($yml))){
    echo "failed to write file {$argv[1]}\n";
    exit(4);
}
echo "done\n";