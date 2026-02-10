#!/usr/bin/php
<?php
require __DIR__ . '/../vendor/autoload.php';

if($argc != 3) {
    echo("expected params: <yml_file> <param/path>\n");
    exit(1);
}

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
        $result = $values[$slug];
    else
        $values = &$values[$slug];
}

if(!isset($result)){ // null handling ?
    echo "failed to get value {$argv[1]}:{$argv[2]}, does not exists\n";
    exit(4);
}

echo $result . "\n";