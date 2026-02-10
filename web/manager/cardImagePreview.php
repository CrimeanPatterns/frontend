<?php

use AwardWallet\CardImageParser\Annotation\Detector;
use Doctrine\Common\Annotations\DocParser;
use \AwardWallet\CardImageParser\Annotation\CardExample;

$schema = "cardImageParsing";
require "start.php";
drawHeader('Card Image Preview');

$container = getSymfonyContainer();
$parsers = glob($container->getParameter('engine_path') . '/*/CardImageParser/*');
$providers = array_filter(array_map(function ($e){
		if( preg_match('/engine\/([a-z0-9]+)\/CardImageParser\/CardImageParser/', $e, $m) )
			return $m[1];
		return '';
	}, $parsers));
/** @var \Doctrine\DBAL\Connection $connection */
$connection = $container->get('doctrine')->getConnection();
$router = $container->get('router');
$parserLoader = $container->get(\AwardWallet\CardImageParser\CardImageParserLoader::class);

$cardImageParserList = [];
$providersList = [];
foreach ( $providers as $providerCode ) {
	$providersList["AwardWallet\\Engine\\{$providerCode}\\CardImageParser\\CardImageParser". ucfirst($providerCode)] = $providerCode;
}

if ( count($providersList) > 0 ) {
	echo '<form name="showExamples" id="showExamples" method="post" style="margin-left: 35%">';
	echo 'Please select provider: <select id="providersList" size="1" onchange="document.querySelector(\'[name=\\\'showExamples\\\']\').submit()" name="providersList">';
	echo "<option>-</option>";
	foreach ($providersList as $key => $provider) {
		echo "<option value='{$key}'>$provider</option>";
	}
	echo '</select>';
	echo '</form>';
} else {
	echo '<br>List of providers is empty<br>';
}

if( isset($_POST['providersList']) && ($prov = explode('\\', $_POST['providersList'])[2]) && preg_match('/\b[a-z0-9]+\b/', $prov) ){

	if ( empty($class = $parserLoader->loadParserClass($prov)) ) {
		throw new \RuntimeException("Parser '{$prov}' not found");
	}
	$refl = new \ReflectionClass($class);
	$files = [dirname($refl->getFileName())];
	$parserDir = realpath($container->getParameter('engine_path') . "/{$prov}");
	$parserData = getParserData($files, $parserDir);
	$docParser = new DocParser();
	$docParser->setImports(['cardexample' => CardExample::class, 'detector' => Detector::class]);
	$cardData = extractCardExamples($parserData, $docParser);
	$imageUuids = [];
	array_walk_recursive($cardData, function($val) use (&$imageUuids) {
		$imageUuids[] = $val;
	});
	$allUuidInDb = $connection->executeQuery("select ci.UUID from CardImage as ci where ci.UUID in(?);", [$imageUuids], [\Doctrine\DBAL\Connection::PARAM_STR_ARRAY]);
	$uuids = [];
	while( $val = $allUuidInDb->fetch(\PDO::FETCH_COLUMN) ){
		$uuids[] = $val;
	}

	$cardImageUrls = [];
	foreach ( $cardData as $cardGroup => $cardUuids ) {
		foreach ( $cardUuids as $cardUuid ) {
			if( in_array($cardUuid, $uuids) )
				$cardImageUrls[$cardGroup][] = $router->generate('aw_card_image_download_staff_proxy', ['cardImageUUID' => $cardUuid]);
		}
	}

	if( 0 < count($cardImageUrls) ){
		foreach ( $cardImageUrls as $group => $cardUrls ) {
			echo "<h1 style='margin-left: 35%'>".$group."</h1><br>";
			echo "<div id='group' style='display: inline-flex'>";
			foreach ( $cardUrls as $cardUrl ) {
				echo "<div id='cart' style='width: 320px; height: 180px; float: left; padding-left: 5px; padding-top: 5px'>
                <div>
                <a href='{$cardUrl}' target='_blank' style='
                		display: inline-block;
                		width: 320px;
                        height: 180px;
                        background-image: url({$cardUrl});
                        background-size: 320px 180px;
                        '>
				</a>  
                </div>
                <br>
        </div>";
			}
			echo "</div>";
		}
	}
}


function getParserData(array $files, string $parserDir) : array
	{
		$files = array_map('realpath', $files);
		$files = array_filter($files, function (string $path) use ($parserDir) { return strpos($path, $parserDir) === 0; });

		if (!$files) {
			throw new \RuntimeException('Invalid parser files provided.');
		}

		$result = [];

		foreach ($files as $file) {
			$result[] = getEntryInfo($file);
		}

		return $result;
	}

	function getEntryInfo(string $path, $deep = 0) : array
    {
        if (++$deep > 5) {
            throw new \RuntimeException('Too many files');
        }

        if (is_dir($path)) {
            $dirContent = [];

            foreach (glob("$path/*") as $file) {
                $dirContent[] = getEntryInfo($file, $deep);
            }

            return [
                'name' => basename($path),
                'type' => 'dir',
                'content' => $dirContent,
            ];
        } else {
            return [
                'name' => basename($path),
                'type' => 'file',
                'content' => file_get_contents($path),
            ];
        }
    }

    function extractCardExamples(array $parserData, DocParser $docParser) : array
    {
        $examples = [];

        foreach ($parserData as $datum) {
            if ('file' === $datum['type']) {
                foreach (token_get_all($datum['content']) as $token) {
                    if (T_DOC_COMMENT === $token[0]) {
                        try {
                            $annotations = $docParser->parse($token[1]);
                        } catch (\Throwable $e) {
                            continue;
                        }

	                    foreach ($annotations as $annotation) {
                        	if( !($annotation instanceof CardExample) )
                        		continue;
                        	if (
                        		isset($annotation->cardUuid) && isset($annotation->groupId)
                            ) {
                                $examples[$annotation->groupId][] = $annotation->cardUuid;
                            } elseif ( isset($annotation->cardUuid) ){
                        		$examples['Not grouped'][] = $annotation->cardUuid;
	                        }
                        }
                    }
                }
            } elseif ('dir' === $datum['type']) {
	            foreach ( extractCardExamples($datum['content'], $docParser) as $groupName => $cards ) {
		            if( isset($examples[$groupName]) )
		            	$examples[$groupName] = array_merge($examples[$groupName], $cards);
	            	$examples[$groupName] = $cards;
	            }
            }
        }

        return $examples;
    }

drawFooter();
?>
