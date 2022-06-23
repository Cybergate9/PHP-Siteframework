<?php
/**
* This file is Site Framework's global configuration file
*
* Edit at least $SF_sitewebpath
*
* @package SiteFramework
* @author Shaun Osborne (smo30@cam.ac.uk)
* @link http://www.fitzmuseum.cam.ac.uk/projects/phpsiteframework/
* @license http://www.fitzmuseum.cam.ac.uk/projects/phpsiteframework/licences.html GPL
* @access public 
* @copyright The Fitzwilliam Museum, University of Cambridge, UK
*/
        

/****************************************************************************
Site Framework (SF) Local Configuration Settings
note: leading and trailing slashes SHOULD be used  (just / for root is OK though)
*/
$SF_documentroot='/Users/Shaun/dev/httpd/public_html';
$SF_sitewebpath='/cg9/';

/* optionals */
$SF_sitetitle='Cybergate9.Net';
$SF_contentpreprocessor=true;
$SF_defaultindexfile='index.html';

/* MUST config these two if caching turned on
   SF_caching = turn caching on (1/true) or off (0/false) 
   SF_cachedir = set directory (mush be read/write apache obviously
*/
$SF_caching=0;
$SF_cachedir = '/Users/Shaun/dev/httpd/temp/'; // with trailing slash

/* end of Local Configuration Settings */