<?php
/**
*  Swish-E PHP Tools Web Spider module
*
* @package Swish-ePHPTools
* @access public
* @license http://www.fitzmuseum.cam.ac.uk/projects/phpsiteframework/licences.html GPL
* @copyright The Fitzwilliam Museum, University of Cambridge, UK
* @link http://www.fitzmuseum.cam.ac.uk/projects/phpsiteframework/swish-ephptools/
* @author Shaun Osborne (smo30@cam.ac.uk)
* @version 0.7 (2005-10-23)
*/

#$initial ="http://www.fitzmuseum.cam.ac.uk/projects/websig/siteframework/";
$initial ="http://www.fitzmuseum.cam.ac.uk/intra/projects/websig/siteframework";

$allow_urls=array("http://www.fitzmuseum.cam.ac.uk/intra/projects/websig/siteframework");
/*disallow's are designed to be a sub-set of allows so that you can allow
a directory structure generally (and everything outside it off by default)
but then if you wish to turn off things INSIDE the allowed directory structure
 then you can use $disallow_urls to do this */
$disallow_urls=array();

$directory_index_file="index.html";
$allow_filetypes=array('.htm','.pdf','.php');
$disallow_uritypes=array('ftp:','javascript:','https:','mailto:','file:');
/*standard index files for webserver e.g. when dir/ is specified what file do you get */
$defaultindexfile="index.html";

/*settings for SWISH*/
$SPT_swishe_dir="w:\\projects\\websig\\siteframework\\SWISH-E\\";
$SPT_swishe_modules_dir="lib\\swish-e\\";
/* globals */
$spider_urls=array($initial=>array('url'=>$initial));
$excluded_urls=array();


$SW_debug=0; /* 0=off, 1=on */
$swishinput=false;

#print_r($_SERVER['argv']);
foreach($_SERVER['argv'] as $argument)
{
 switch($argument)
 {
 case '-s':
           $swishinput=true;
           break;

 }
}


/* if we are outputting for swish turn off all but errors */
if($swishinput)
  {error_reporting(E_ERROR);}

SPT_WebSpider($initial);

if(!$swishinput)
{
  echo "\nVALID spider URLS\n\n";
  $x=0;
  foreach($spider_urls as $entry)
  {
   echo $x++.":".$entry['url']."[".$entry['length']."]\n";
  }
  echo "\nDISALLOWED spider URLS\n";
  $x=0;
  foreach($excluded_urls as $entry)
  {
   echo $x++.":".$entry['url']."[".$entry['reason']."]\n";
  }
  exit;
}
else
{
  foreach($spider_urls as $url)
  {
  #SPT_GetFileForIndexer($url['url']);
  }
}
exit;
/* end of main */



function SPT_OutputFileForSwish($content,$url)
{
global $SPT_swishe_dir;
global $SPT_swishe_modules_dir;

$tempfilename="SPT_temp"; /* only ever writing one at a time so use same name */
if(!$content)
{ return; } /*#echo "ERROR no content $iurl\n";*/

/* if its a pdf...*/
if(preg_match("@\.pdf@",$url))
{
  file_put_contents($_ENV['TMP']."\\".$tempfilename.".pdf",$content);
  #echo $SPT_swishe_dir.$SPT_swishe_modules_dir."pdftotext.exe -nopgbrk ".$_ENV['TMP']."\\".$tempfilename.".pdf";
  exec($SPT_swishe_dir.$SPT_swishe_modules_dir."pdftotext.exe -nopgbrk ".$_ENV['TMP']."\\".$tempfilename.".pdf");
  $content = file_get_contents($_ENV['TMP']."\\".$tempfilename.".txt");
}
/* this is a cludge for swish-e which is expecting a un*x file - so remove CR's (x0D) from contents */
$content=preg_replace("@\x0D@","",$content);
$size=(strlen($content));
echo "Path-Name: $url\n";
echo "Content-Length: $size\n\n";
echo $content;
return;
}



function SPT_WebSpider($url)
/***************************************************************************
This is recursive - give a start and it will keep calling itself until it 
runs out of valid urls to spider.
In production this function should not output anything (note it will produce warnings
if any invalid urls are found but these go to stderr so dont affect its operation
as an input script to an indexer)
*/
{
global $spider_urls;
global $SW_debug;
global $allow_urls;
global $disallow_urls;
global $allow_filetypes;
global $disallow_uritypes;
global $httpdomain;
global $swishinput;
if($SW_debug==1){echo "SPT_WebSpider($url)\n";}

$contents = file_get_contents($url);
/* store the length */
$spider_urls[$url]['length']=strlen($contents);
if(!$contents)
{
if($SW_debug){echo "FAILED GETTING $url\n";}
return; /* if we get nothing from this file exit */
}

if($swishinput){SPT_OutputFileForSwish($contents, $url);}



/* remove html comments from $contents*/
$contents=preg_replace("@<!--.*?-->@i","",$contents);

/* get all the matches for a href=""s into an array, $1 matches are in $a_href_matches[1] */
preg_match_all("@<a href=\"(.*?)\".*?\>@",$contents,$a_href_matches);

/* now cycle through all the collected href's */
foreach($a_href_matches[1] as $extracted_url)
{
  $matched=false;
  /* normalise the url */
  $extracted_url=normaliseurl($extracted_url,$url); 
  /* if this is a http:// link and doesnt match our limit -store them in $excluded_urls array */
  if(preg_match("/http:\/\//",$extracted_url))
  {
     $disallowurl=true;
     foreach($allow_urls as $testdomain)
     {
      if(preg_match("@$testdomain@i",$extracted_url))
        {
         $disallowurl=false;
        }   
     }
    foreach($disallow_urls as $testdomain)
     {
      if(preg_match("@$testdomain@i",$extracted_url))
        {
         $disallowurl=true;
        }   
     }
     
   if($disallowurl)
   {
    addtoexcludedurls($extracted_url,"disallowed_url");
    continue;
   } /* this starts next foreach($a_href_matches[1] as $extracted_url) */    
  }
  $uritypeok=true;
  foreach($disallow_uritypes as $uritype)
  {
   if(preg_match("@$uritype@i",$extracted_url))
     {$uritypeok=false;}
  }
  if(!$uritypeok)
  {
   addtoexcludedurls($extracted_url,"disallowed_uritype");
   continue;/* this starts next foreach($a_href_matches[1] as $extracted_url) */
  } 
  if(!preg_match("@\/$@",$extracted_url))
    { 
      $typeok=false;
      foreach($allow_filetypes as $type)
      {
       if(preg_match("@$type@i",$extracted_url))
         {$typeok=true;}
      }
     if(!$typeok)
     {
      addtoexcludedurls($extracted_url,"disallowed_filetype");
      continue;/* this starts next foreach($a_href_matches[1] as $extracted_url) */
     } 
    }
  /* if we didnt get a match on the add - we havent seen this yet - so go spider this one too */
  if(!addtospiderurls($extracted_url))
  {
  if(!$swishinput)
  {echo "Fetching $extracted_url\n";}
  SPT_WebSpider($extracted_url); /* RECURSIVE - The finish is when no more valid urls can be found to spider */
  }

} /*end of foreach */
} /* end of function */

function addtoexcludedurls($url,$reason)
/*************************************************************************/
{
  global $excluded_urls;
  $matched=false;
  foreach($excluded_urls as $existingitem)
    {
      if(!strcasecmp($existingitem['url'],$url))
      { /* already have it do nothing*/
      #echo "SKIP:$extracted_url\n";
      $matched=true;
      break;
      }
    }
  if(!$matched)
  {
   $excluded_urls[]['url']=$url;
   $excluded_urls[key($excluded_urls)]['reason']=$reason;
   
  }
  return $matched;
}

function addtospiderurls($url)
/*************************************************************************/
{
  global $spider_urls;
  $matched=false;
  foreach($spider_urls as $existingitem)
    {
      if(!strcasecmp($existingitem['url'],$url))
      { /* already have it do nothing*/
      #echo "SKIP:$extracted_url\n";
      $matched=true;
      break;
      }
    }
  if(!$matched)
  {
   $spider_urls[$url]['url']=$url;
  }
  return $matched;
}

function getpath($urlstring)
/*************************************************************************/
{
  $urlstring=trim($urlstring); 
  /*if we are at the root just return with root */
  if(!strcmp("/",$urlstring))
  {return $urlstring;}
  /* search from right of string and remove all characters back to the next right-most '/' - effect is clipping to path*/
  for($x=strlen($urlstring)-1; $urlstring[$x] != '/' and $x>=0; $x--)
  {$urlstring[$x]=' ';}
  return trim($urlstring);
}
function getpreviouspath($urlstring)
/*************************************************************************/
{
  $urlstring=trim($urlstring); 
  /*if we are at the root just return with root */
  if(!strcmp("/",$urlstring))
  {return $urlstring;}
  /* always knock of the first / if there is one */
  if($urlstring[(strlen($urlstring)-1)] == '/')
  {
  $urlstring[strlen($urlstring)-1] = ' ';
  }
  /* search from right of string and remove all characters back to the next right-most '/' - effect is clipping to path*/
  for($x=strlen($urlstring)-1; $urlstring[$x] != '/' and $x>=0; $x--)
  {$urlstring[$x]=' ';}
  return trim($urlstring);
}


function normaliseurl($extracted_url,$url)
{
#global $httpdomain;
global $defaultindexfile;
global $disallow_uritypes;
global $directory_index_file;
$adjusted_url="";
$disallowed=false;

$callingurlbits = parse_url($url);

$url=preg_replace("@$defaultindexfile$@","",$url);
if(preg_match("@^http@",$extracted_url))
  {
  $adjusted_url=$extracted_url;
  }
else
  {
    if(preg_match("@^\/@",$extracted_url))
    {  
      $adjusted_url="http://".$callingurlbits['host'].$extracted_url;
    }
    else
    {
      if(preg_match("@^[0-9a-z]@i",$extracted_url))
      {
      foreach($disallow_uritypes as $type)
             {
               if(preg_match("@^$type@",$extracted_url))
               {
               $adjusted_url=$extracted_url;
               $disallowed=true;
               }
             }
      if(!$disallowed)
        {$adjusted_url=getpath($url).$extracted_url;}
      }
      else
      {
      $turl=$url;
      $texurl=$extracted_url;
        while(preg_match("@^\.\.\/@",$texurl))
            {
            $texurl=preg_replace("@^\.\.\/@","",$texurl);
            $turl=getpreviouspath($turl);
            }
      $adjusted_url=$turl.$texurl;
      }
    }
  }

 $adjusted_url=preg_replace("@#.*$@","",$adjusted_url); /* remove anything on end of url after a # */
 $adjusted_url=preg_replace("@\?.*$@","",$adjusted_url); /* remove anything on end of url after a ? */
 $adjusted_url=preg_replace("@$directory_index_file$@i","",$adjusted_url); /* remove anything on end of url after a ? */
 
  
  /*echo"[U]".$url."\n";
  echo"[S]".$extracted_url."\n";
  echo"[F]".$adjusted_url."\n";*/
 return $adjusted_url; 
}

function SPT_GetFileForIndexer($url)
/* function no longer used ************************************************/
{
$tempfilename="SPT_temp";
$content = file_get_contents($url);
if(!$content)
{#echo "ERROR no content $iurl\n";
 return;
}
if(preg_match("@\.pdf@",$iurl))
{
  file_put_contents($_ENV['TMP']."\\".$tempfilename.".pdf",$content);
  exec("c:/temp/pdftotext.exe -nopgbrk ".$_ENV['TMP']."\\".$tempfilename.".pdf");
  $content = file_get_contents($_ENV['TMP']."\\".$tempfilename.".txt");
#  $content=wordwrap($content,80,"\n");
}
/* this is a cludge for swish-e which is expecting a un*x file - so remove CR's (x0D) from contents */
$content=preg_replace("@\x0D@","",$content);
$size=(strlen($content));
$mtime="x";
echo "Path-Name: $url\n";
echo "Content-Length: $size\n\n";
echo $content;
return;
}


?>
