<?php

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
    global $SF_sitedrivepath;
    $storedpreviewmetadatafile = $SF_sitedrivepath.'storedpreviewmetadata.json';
    if (! file_exists($storedpreviewmetadatafile)) {
        file_put_contents($storedpreviewmetadatafile, "\n");
    }

    $returnedjson = checkPreviewMetadata($storedpreviewmetadatafile, $inurl); //already stored?
    if ($returnedjson) {
        //echo 'OOC: '; var_dump($returnedjson);
        $returnedjson = (array) $returnedjson; //yes, convert to array and use
    } else {
        $returnedjson = getPreviewMetadata($inurl); //no, go look it up
        //echo 'L&S: '; var_dump($returnedjson);
        storePreviewMetadata($storedpreviewmetadatafile, $returnedjson); //store it
        $returnedjson = (array) $returnedjson; // convert to array and use it
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
        if (array_key_exists('description', $returnedjson)) {
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
    //$string = htmlentities($string);
    return $string;
}

function storePreviewMetadata($file, $json)
{
    $storedjson = [];
    $storedjson = (array) json_decode(file_get_contents($file));
    $storedjson[count($storedjson) + 1] = $json;
    file_put_contents($file, json_encode($storedjson));
}

function checkPreviewMetadata($file, $qurl)
{
    $storedjson = [];
    $storedjson = (array) json_decode(file_get_contents($file));
    //var_dump($storedjson);
    foreach ($storedjson as $record) {
        //var_dump($record);
        if (isset($record->url) and (($record->url <=> $qurl) == 0)) {
            return $record;
        }
    }
    return false;
}

function getPreviewMetadata($qurl)
{
    if (! cURLcheckBasicFunctions()) {
        echo 'UNAVAILABLE: cURL Basic Functions';
        exit(0);
    }
    // create curl resource
    $ch = curl_init();
    // set url
    curl_setopt($ch, CURLOPT_URL, $qurl);
    //return the transfer as a string
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // bloomberg and the like will 'bark robot?' if you don't give 'em a user-agent
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)');
    // $contents contains the output string
    $contents = curl_exec($ch);
    if (! $contents) {
        return false;
    }
    // close curl resource to free up system resources
    curl_close($ch);

    // put $contents into DOM structure
    $dom = new DOMDocument();
    @$dom->loadHTML($contents);
    $a = $dom->getElementsByTagName('meta');

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
    $json['title'] = $title;
    $json['image'] = $image;

    return $json;
}
