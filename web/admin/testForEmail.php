<?
error_reporting(E_ALL);

$html = file_get_contents("http://www.nytimes.com/");
$dom = new DOMDocument('1.0', 'utf-8');
@$dom->loadHTML($html);
$xpath = new DOMXPath($dom);

$stories = array();
foreach($xpath->query("//div[@class = 'bColumn opening']/div[h6[contains(text(), 'More News')]]/preceding-sibling::div/div[@class = 'story']") as $story){
	$stories[] = array(
		'title' => trim($xpath->query("h5", $story)->item(0)->nodeValue),
		'author' => trim($xpath->query("h6[@class = 'byline']", $story)->item(0)->nodeValue),
		'link' => trim($xpath->query("h5/a/@href", $story)->item(0)->nodeValue),
	);
}

echo "<pre>";
var_dump($stories);
echo "</pre>";

