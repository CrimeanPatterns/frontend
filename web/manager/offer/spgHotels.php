<?
$schema = "Offer";
require "../start.php";
drawHeader("SPG Hotels");
require "spgHotelsRequired.php";
//echo $hotelDiv;
$hotelList = array();
preg_match_all('#(<a class="propertyName" href=".*?</a>)#is', $hotelDiv, $matches);
ob_end_flush();
$Connection->Execute("delete from HotelData");
print '<table border = 1><tr><td>Id</td><td>Full</td><td style="white-space: nowrap">Name</td><td>Link</td><td>Category</td><td>MinPoints</td><td>MaxPoints</td><td style="white-space: nowrap">SQL</td></tr>';
$i = 0;
print count($matches[1])." hotels to parse...\n<br>";
foreach ($matches[1] as $match) {
    //if ($match != $matches[0])
//    ob_start();
    $min = 0;
    $max = 0;
    $category = 0;
    echo "<tr>";
    $match = preg_replace('#&amp;#i','&',$match);
    preg_match('#<a.*?>(.*?)<span>.*?</span>.*?</a>#is', $match, $name);
    preg_match('#<a.*?href="(.*?)"#is', $match, $link);
    $linkp = 'http://www.starwoodhotels.com'.$link[1];
    $linka = '<a href="'.$linkp.'">'.$link[1].'</a>';
    $opts = array('http'=>array('header' => "User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64; rv:19.0) Gecko/20100101 Firefox/19.0\r\n"));
    $context = stream_context_create($opts);
    $hotelPage = file_get_contents($linkp,false,$context);
    //$hotelPage = file_get_contents($linkp);
    if ($hotelPage != false){
        if (strpos($hotelPage,'Note: This hotel has') === false){
            if (preg_match('#SPG Category (.*?)</a>#is',$hotelPage,$hm))
                $category = $hm[1];
            else
            $category = 'none';
            if (preg_match('#([\d,]+? to [\d,]+? Starpoints)#is',$hotelPage,$hm)){
                preg_match('#([\d,]+?) to [\d,]+? Starpoints#is',$hotelPage,$hmt);
                $min = preg_replace('#,#','',$hmt[1]);
                preg_match('#[\d,]+? to ([\d,]+?) Starpoints#is',$hotelPage,$hmt);
                $max = preg_replace('#,#','',$hmt[1]);
            }
            else{
                if (preg_match('#([\d,]+?) Starpoints#is',$hotelPage,$hm)){
                    $min = preg_replace('#,#','',$hm[1]);
                    $max = $min;
                }
            }
        }
        else
            $category = 0;
    $sql = "insert into HotelData (HotelName,ProviderCode,Category,MinPoints,MaxPoints,URL) VAlUES ('".addslashes($name[1])."','spg','$category','$min','$max','$linkp')";
    }
    else
        $category = 0;
    $i++;
    echo "<td>".$i."</td>";
    echo "<td>".$match."</td>";
    echo "<td style=\"white-space: nowrap\">".$name[1]."</td>";
    echo "<td>".$linka."</td>";
    echo "<td>".$category."</td>";
    echo "<td>".$min."</td>";
    echo "<td>".$max."</td>";
    echo "<td style=\"white-space: nowrap\">".$sql."</td>";
//    echo "<td style=\"white-space: nowrap\">".htmlspecialchars($hotelPage)."</td>";
    echo "</tr>";
    flush();
    if ($category != 'none' && $category != 0)
        $Connection->Execute($sql);
}
print "</table>";
print "$i hotels total";
drawFooter();
?>
