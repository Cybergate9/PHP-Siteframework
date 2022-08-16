<?php

$updebug = 0;

if (isset($_SERVER['QUERY_STRING']) and preg_match('/^http/', $_SERVER['QUERY_STRING'])) {
    $updebug = 1;
    include 'SF_localconfig.php';
    include 'SF_mainconfig.php';
    $inurl = $_SERVER['QUERY_STRING'];
    updmsg('(Direct) Looking up: '.$inurl.'<br/>', false);
    $json = '';
    $returnedjson = [];
    $result = checkPreviewMetadata($inurl);
    updmsg('check result: ', $result);
    if (! $result) {
        $json = getPreviewMetadata($inurl);
        updmsg('get result: ', $json);
        $returnedjson = $json;
    } else { //we got it out of datafile
        $returnedjson = (array) $result;
    }
    if ($json) { // if this is newly looked up then store in datafile
        $result = storePreviewMetadata($json);
        updmsg('store result: ', $result);
    } else {
        updmsg('store result: No store ', false);
        //return false;
    }
    exit(); /* we've come via direct call so now just force exit() */
}

function updmsg($string, $value)
{
    global $updebug;
    if ($updebug > 0) {
        echo '[debug]:'.$string;
        var_dump($value);
        echo '<br/>';
    }
}

function cURLcheckBasicFunctions()
{
    if (! function_exists('curl_init') &&
      ! function_exists('curl_setopt') &&
      ! function_exists('curl_exec') &&
      ! function_exists('curl_close')) {
        return false;
    } else {
        return true;
    }
}

function SF_GenerateMetadataPreview($inurl, $output = true)
{
    global $updebug;
    global $storedpreviewmetadatafile;
    global $sfdebug;
    global $SF_commands;

    updmsg('Looking up: '.$inurl.'<br/>', false);
    $json = '';
    $returnedjson = [];
    $result = checkPreviewMetadata($inurl);
    updmsg('check result: ', $result);
    if (! $result) {
        $json = getPreviewMetadata($inurl);
        updmsg('get result: ', $json);
        $returnedjson = $json;
    } else {
        $returnedjson = (array) $result;
    }
    if ($json) {
        $result = storePreviewMetadata($json);
        updmsg('store result: ', $result);
    } else {
        updmsg('store result: No store ', false);
        //return false;
    }

    if ($returnedjson == false) { // if we totally fail, warn and exit
        if ($sfdebug > 0 or $updebeg > 0) {
            Output_line('WARNING[SF_GenerateMetadataPreview()]: Metadata lookup failed');
        }

        return false;
    }
    if ($output) {
        echo '<div class="metacard">';
        Output_line('FromURL : '.$returnedjson['url']);
        Output_line('Title : '.$returnedjson['title']);
        foreach (str_split($returnedjson['title']) as $char) {
            Output_line($char.'['.(string) ord($char)."]\n");
        }
        Output_line('Description : '.$returnedjson->desc);
        echo '<p>Image:<br/><img src="'.$returnedjson['image'].'" alt="'.$returnedjson['title'].'"/></p>';
        Output_line("[<meta's> found count:".count($a).']');
        echo '<hr/>';
        echo '</div>';
    } else {
        $ra = [];
        $ra['url'] = $returnedjson['url'];
        $ra['title'] = $returnedjson['title'];
        $ra['image'] = $returnedjson['image'];
        if (isset($returnedjson['description'])) {
            $ra['description'] = $returnedjson['description'];
        }

        return $ra;
    }
}

function Output_head($string = '')
{
    if (defined('STDIN')) {
        echo 'From:['.$_SERVER['PHP_SELF']."]\n";
        echo $string."\n";
    } else {
        echo '<html><head><meta charset="UTF-8"><style>p {line-height: 16px; margin: 5px 0 0 0;}</style><head>';
        echo '<p>GeneratedBy:['.$_SERVER['PHP_SELF'].']from['.$string.']<p>';
    }
}

function Output_line($string, $tag = 'p')
{
    if (defined('STDIN')) {
        echo $string."\n";
    } else {
        echo '<'.$tag.'>'.htmlspecialchars($string).'</'.$tag.">\n";
    }
}

function clean_trim($string)
{
    $string = utf8_decode($string);
    $string = preg_replace("/\xa0|\xc2/", '', trim($string));
    $string = utf8_encode($string);
    $string = htmlentities($string);

    return $string;
}

function storePreviewMetadata($json)
{
    global $updebug;
    global $storedpreviewmetadatafile;

    $storedjson = [];
    $json['dtz'] = date('Y-m-d,G:i,O'); //timestamp this data

    $result = file_get_contents($storedpreviewmetadatafile);
    //echo '<br/><br/>$result:'; print_r($result); echo '<br/><br/>';
    $storedjson = json_decode($result, true);
    //echo '<br/><br/>$storedjson:'; print_r($storedjson); echo '<br/><br/>';
    if ($result === null) {
        return false;
    }
    if (strlen($result) < 2) { // deal with newly initialised file
        $storedjson[1] = $json;
    } else { // otherwise concat json
        $storedjson = json_decode($result, true);
        //var_dump($storedjson);
        //$result=''; //clear
        $storedjson[count($storedjson) + 1] = $json;
    }

    return file_put_contents($storedpreviewmetadatafile, json_encode($storedjson));
}

function checkPreviewMetadata($qurl)
{
    global $updebug;
    global $storedpreviewmetadatafile;

    if (! file_exists($storedpreviewmetadatafile)) {
        //file_put_contents($storedpreviewmetadatafile, '{"1":{"url":"nil","title":"nil"}}');
        file_put_contents($storedpreviewmetadatafile, "\n");
        updmsg('<br/>FILE ZEROED OUT<br/>', $storedpreviewmetadatafile);
    }
    $storedjson = [];
    $result = file_get_contents($storedpreviewmetadatafile);
    if (strlen($result) < 2) {
        return false;
    }

    $storedjson = json_decode($result, true);
    foreach ($storedjson as $key=>$record) {
        //updmsg("$key: ",$record['url']);
        if (($record['url'] <=> $qurl) == 0) {
            updmsg('MATCHED', true);

            return $record;
        }
    }

    return false;
}

function getPreviewMetadata($qurl)
{
    global $SF_sitewebpath;
    global $SF_cachedir;

    if (! cURLcheckBasicFunctions()) {
        echo 'UNAVAILABLE: cURL Basic Functions';
        exit(0);
    }

    /*if(preg_match("/bloomberg/",$qurl)){  // this is a kludge because bloomberg is constantly returning errors
        $json['url'] = $qurl;
        $json['ogurl'] = $qurl;
        $json['title'] = "Bloomberg Article";
        $json['image'] = $_SERVER['HTTPS'] ? 'https://' : 'http://';
        $json['image'] .= $_SERVER['HTTP_HOST'].$SF_sitewebpath.'images/noderivs/bbrepl.png';
        return $json;
    }*/

    // create curl resource
    $ch = curl_init();
    // set url
    curl_setopt($ch, CURLOPT_URL, $qurl);

    //return the transfer as a string
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // bloomberg and the like will 'bark' if you don't their redirects
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // allow for cookies
    $cookie = $SF_cachedir.'cookie.txt';
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:103.0) Gecko/20100101 Firefox/103.0');
    // $contents contains the output string
    $contents = curl_exec($ch);
    if (! $contents) {
        echo 'WARNING[getPreviewMetadata()]:  CURL failed - ';
        var_dump($contents);

        return false;
    }
    // close curl resource to free up system resources
    curl_close($ch);

    // if direct with querystring and debug
    if (preg_match('/^http/', $_SERVER['QUERY_STRING'])) {
        updmsg('cookie contents: ', $cookie);
        updmsg('CURL result: ', $contents);
    }
    // put $contents into DOM structure
    $dom = new DOMDocument();
    @$dom->loadHTML($contents);
    $a = $dom->getElementsByTagName('meta');
    if (! $a) {
        echo 'ERROR[SF_getPreviewMetadata()]:  DOM failed - ';
        var_dump($a);

        return false;
    }

    //gather meta attributes we want
    $image = $url = $title = $dec = '';
    $attr = $value = null;
    for ($i = 0; $i < $a->length; $i++) {
        $attr = $a->item($i)->getAttribute('property');
        $value = $a->item($i)->getAttribute('content');
        if (($attr <=> 'og:url') == 0) {
            $url = $value;
        }
        if (($attr <=> 'og:image') == 0) {
            $image = $value;
        }
        if (($attr <=> 'og:title') == 0) {
            $title = clean_trim($value);
        }
        if (($attr <=> 'og:description') == 0) {
            $description = clean_trim($value);
            $json['description'] = $description;
        }
        if ($attr) {
            //Output_line('L:'.$attr.'='.utf8_decode($value));
        }
        $attr = null;
    }

    $json['url'] = $qurl;
    $json['ogurl'] = $url;
    $json['title'] = $title;
    $json['image'] = $image;

    if ((($json['title'] <=> '') == 0)) {
        return false;
    }

    return $json;
}
