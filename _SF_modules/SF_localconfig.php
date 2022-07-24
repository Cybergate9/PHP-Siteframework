<?php
/**
* This file is Site Framework's global configuration file
*
* Edit at least $SF_sitewebpath
*
* @author Shaun Osborne (webmaster@cybergate9.net)
*
* @link https://github.com/Cybergate9/PHP-Siteframework
*
* @copyright Shaun Osborne, 2005-present
* @license https://github.com/Cybergate9/PHP-Siteframework/blob/master/LICENSE
*/

/****************************************************************************
Site Framework (SF) Local Configuration Settings
note: leading and trailing slashes SHOULD be used  (just / for root is OK though)
*/
$SF_documentroot = '/Users/Shaun/dev/httpd/public_html';
$SF_sitewebpath = '/cg9/';

/* optionals */
$SF_sitetitle = 'Cybergate9.Net';
$SF_contentpreprocessor = true;
$SF_defaultindexfile = 'index.html';

/* MUST config these two if caching turned on
   SF_caching = turn caching on (1/true) or off (0/false)
   SF_cachedir = set directory (mush be read/write apache obviously
*/
global $SF_caching;
global $SF_cachedir;
$SF_caching = 0;
$SF_cachedir = '/Users/Shaun/dev/httpd/temp/'; // with trailing slash

/* end of Local Configuration Settings */

if ($sfdebug >= 3) {
    SF_DebugMsg('SF_localconfig.php loaded');
}
