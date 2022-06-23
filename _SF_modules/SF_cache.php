<?php 
/** 
* Cache control settings 
*
* requires Cache_Lite to be installed
*
* if $SF_caching=false then all caching turned off. 
*
* @package SiteFramework
* @author Shaun Osborne (smo30@cam.ac.uk)
* @link http://www.fitzmuseum.cam.ac.uk/projects/phpsiteframework/
* @access public 
* @copyright The Fitzwilliam Museum, University of Cambridge, UK
* @licence http://www.fitzmuseum.cam.ac.uk/projects/phpsiteframework/licences.html GPL
* @see http://pear.php.net/package/Cache_Lite
* @see https://dzone.com/articles/how-to-create-a-simple-and-efficient-php-cache
*/ 

require_once('SF_localconfig.php');


/* optional to change */
$SF_forcecache=false;
/* lifeTime 3600 (1 hr), 14400 (4 hrs)  */
$SF_cachetime = 14400;
$SF_cachehashlevel = 0; // 0 = nosubdir, 1 = 16 subdirs, 2 = 512, etc.


/* directorys on the server to exclude from SF caching */
$SF_cacheexcludes = array();

$SF_cachefile;
$SF_cachetime;


function SF_cachestart()
{
	global $SF_cachedir;
	global $SF_cachefile;
	global $SF_cachetime;
	global $SF_forcecache;
	global $SF_cachehashlevel;
	$url = $_SERVER["PHP_SELF"];
	if(array_key_exists("QUERY_STRING",$_SERVER)){
		$url=$url.$_SERVER["QUERY_STRING"];
	}
	$break = Explode('/', $url);
	$file = $break[count($break) - 1];
	//echo $url;	
	$SF_cachefile = $SF_cachedir.'cached-'.preg_replace("/\?/","",$file).'.html';
	//echo $SF_cachefile ;
	$SF_hash = hash('md5',$SF_cachefile);	
	//echo "[H:".$SF_hash."]" ;

	$SF_cachefile = $SF_cachedir.substr($SF_hash,0,$SF_cachehashlevel).'/cached-'.preg_replace("/\?/","",$file).'.html';
	//echo $SF_cachefile ;
	//make the directory is it doesn't exist (base or hash subdirs)
	$dirname = dirname($SF_cachefile);
	if (!is_dir($dirname))
	{
	    mkdir($dirname, 0755, true);
	}

	// Serve from the cache if it is younger than $cachetime
	if (file_exists($SF_cachefile) and time() - $SF_cachetime < filemtime($SF_cachefile) and $SF_forcecache==false)
		{
	    echo "<!-- Cached copy, generated ".date('H:i', filemtime($SF_cachefile))." -->\n";
	    readfile($SF_cachefile);
	    exit;
		}
	ob_start(); // Start the output buffer
	return 'caching';
}


function SF_cacheend()
{
	global $SF_cachefile;
	global $SF_cachetime;
	// Cache the contents to a cache file
	$cached = fopen($SF_cachefile, 'w');
	fwrite($cached, ob_get_contents());
	fclose($cached);
	ob_end_flush(); // Send the output to the browser	
	return 'cached';
}



?>
