<?php 
/** 
* Cache control module 
*
* config for this module is in SF_localconfig.php and mainconfig.php
* if $SF_caching=false then all caching turned off. 
*
* @package PHP-SiteFramework
* @author Shaun Osborne (webmaster@cybergate9.net)
* @link https://github.com/Cybergate9/PHP-Siteframework
* @access public 
* @copyright Shaun Osborne, 2005-present
* @license https://github.com/Cybergate9/PHP-Siteframework/blob/master/LICENSE
* @see https://dzone.com/articles/how-to-create-a-simple-and-efficient-php-cache
*/ 

require_once('SF_localconfig.php');


/* globals */
global $SF_forcecache;

/* optional to change */

$SF_cachetime = 14400;  // lifeTime 3600 (1 hr), 14400 (4 hrs)  
$SF_cachehashlevel = 0; // 0 = nosubdir, 1 = 16 subdirs, 2 = 512, etc.


/* directorys on the server to exclude from SF caching */
$SF_cacheexcludes = array();
$SF_cachequerystringexcluderegex = "/sf_.*=/";

$SF_cachefile;
$SF_cachetime;



function SF_cachestart()
{
	global $SF_cachedir;
	global $SF_cachefile;
	global $SF_cachetime;
	global $SF_forcecache;
	global $SF_cachehashlevel;
	global $SF_cachequerystringexcluderegex;
	global $SF_caching;

	$url = $_SERVER["PHP_SELF"];

	if(array_key_exists("QUERY_STRING",$_SERVER)){
		if(preg_match($SF_cachequerystringexcluderegex, $_SERVER['QUERY_STRING'])){
			// discard
			$SF_caching = false; //turn caching off for this page
			return 'notcaching(excluderegex)';
		}
		else
		{
		$url=$url.$_SERVER["QUERY_STRING"];	
		}
	   $url = preg_replace("/debug=[0-9]/", "", $url);
	   $url = preg_replace("/cache=force]/", "", $url);
	   $url = preg_replace("/c=f/", "", $url);

	}
	$break = Explode('/', $url);
	$file = $break[count($break) - 1];
	$SF_cachefile = $SF_cachedir.'cached-'.preg_replace("/\?/","",$file).'.html';
	$SF_hash = hash('md5',$SF_cachefile);	

	$SF_cachefile = $SF_cachedir.substr($SF_hash,0,$SF_cachehashlevel).'cached-'.preg_replace("/\?/","",$file).'.html';

	//make the directory is it doesn't exist (base or hash subdirs)
	$dirname = dirname($SF_cachefile);
	if (!is_dir($dirname))
	{
	    mkdir($dirname, 0755, true);
	}

	// Serve from the cache if it is younger than $cachetime
	if (file_exists($SF_cachefile) and time() - $SF_cachetime < filemtime($SF_cachefile) and $SF_forcecache==false)
		{
	    echo "<!-- Cached copy, generated:".date('YMd:H:i:e', filemtime($SF_cachefile))." -->\n";
	    readfile($SF_cachefile);
	    exit;
		}	
	if($SF_forcecache == true)
		{
		$res=unlink($SF_cachefile);
		echo 'SF_debug:[SF_cachestart()]cache forced and cachefile ('.$SF_cachefile.') deletion/unlink returned='.$res;
	    }

	ob_start(); // Start the output buffer
	return 'caching';
}



function SF_cacheend()
{
	global $SF_cachefile;
	global $SF_forcecache;
	global $SF_cachetime;
	global $sfdebug;

	if($sfdebug >= 4)
		{
			echo 'SF_cacheend()';
		}
	if($SF_forcecache == true and file_exists($SF_cachefile))
		{
		$res=unlink($SF_cachefile);
		echo 'SF_debug:[SF_cacheend()] cache forced and cachefile ('.$SF_cachefile.') deletion/unlink returned='.$res;
	    }
	

	// Cache the contents to a cache file
	$cached = fopen($SF_cachefile, 'w');
	
	if($cached != false)
		{
		fwrite($cached, ob_get_contents());
		fclose($cached);
		}
	else
		{
			echo "OOPS:cache file open error";
		}
	ob_end_flush(); // Send the output to the browser	
	return 'cached';
}



?>
