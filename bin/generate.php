#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

//////////////////////////////////////////////////////////////

use Elephox\Mimey\MimeMappingGenerator;

$mimeTypes = dirname(__DIR__) . "/data/mime.types";
$mimeTypesCustom = dirname(__DIR__) . "/data/mime.types.custom";
$jsonDestination = dirname(__DIR__) . "/dist/mime.types.json";
$minJsonDestination = dirname(__DIR__) . "/dist/mime.types.min.json";
$enumDestination = dirname(__DIR__) . "/dist/MimeType.php";

$mimeTypesContent = file_get_contents($mimeTypes);
$mimeTypesCustomContent = file_get_contents($mimeTypesCustom);

$generator = new MimeMappingGenerator($mimeTypesCustomContent . PHP_EOL . $mimeTypesContent);
$mapping = $generator->generateMapping();

file_put_contents($jsonDestination, $generator->generateJson(false));
file_put_contents($minJsonDestination, $generator->generateJson());
file_put_contents($enumDestination, $generator->generatePhpEnum(withOverrideAttribute: false));

echo "Generated MIME types mapping to:" . PHP_EOL;
echo " - " . $jsonDestination . PHP_EOL;
echo " - " . $minJsonDestination . PHP_EOL;
echo " - " . $enumDestination . PHP_EOL;
echo PHP_EOL;
