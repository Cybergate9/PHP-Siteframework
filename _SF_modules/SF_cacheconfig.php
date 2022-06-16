<?php 
/** 
* Cache control settings 
*
* requires Cache_Lite to be installed
*
* if $SF_caching=false then all caching turned off. 
*
* @package PHP Siteframework
* @author Shaun Osborne
* @link https://github.com/Cybergate9/PHP-Siteframework
* @license https://github.com/Cybergate9/PHP-Siteframework/blob/main/LICENSE
* @copyright Shaun Osborne, 2005-present
* @access public 
* @see http://pear.php.net/package/Cache_Lite
*/ 

/* turn caching on (true) or off (false) */
$SF_caching=false;

$SF_forcecache=false;
/* settings to be passed to Cache_Lite as options */
/* lifeTime 3600 (1 hr), 14400 (4 hrs)  */
$SF_cacheoptions = array(
'cacheDir' => 'e:/wwwtemp/cache/',
'lifeTime' => 14400,
'hashedDirectoryLevel' => 0
);

/* directorys on the server to exclude from SF caching */
$SF_cacheexcludes = array(
'/intra/',
'/projects/ae',
'/dept/it/smo30/',
'/projects/oai'
);

?>
