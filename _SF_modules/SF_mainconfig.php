<?php
/**
* This file is Siteframework's main configuration file
*
*
* @package PHP-SiteFramework
* @author Shaun Osborne (webmaster@cybergate9.net)
* @link https://github.com/Cybergate9/PHP-Siteframework
* @access public 
* @copyright Shaun Osborne, 2005-present
* @license https://github.com/Cybergate9/PHP-Siteframework/blob/master/LICENSE
*/
        

/****************************************************************************
Global derived configuration values - shouldn't need to change these */
$SF_modulesdirname='_SF_modules/';
$SF_moduleswebpath=$SF_sitewebpath.$SF_modulesdirname;
$SF_modulesdrivepath=$SF_documentroot.$SF_moduleswebpath;
$SF_sitedrivepath=$SF_documentroot.$SF_sitewebpath;
$SF_subsitewebpath=$SF_sitewebpath;
$SF_subsitedrivepath=$SF_sitedrivepath;
$SF_phpselfdrivepath=$SF_documentroot.$_SERVER['PHP_SELF'];
$SF_sitelogo=$SF_moduleswebpath.'images/sflogo_sml.jpg';


/****************************************************************************
Global default values - shouldn't need to change these */

/* include parsedown */
$SF_parsedownpath=$SF_modulesdrivepath.'vendor/erusev/parsedown/Parsedown.php';

/* data files */
$defaultmenudatafile=$SF_modulesdrivepath.'SF_default_config_menu.csv';
$defaultdirconfigfile=$SF_modulesdrivepath.'SF_default_config_dir.csv';
$defaultsiteconfigfile=$SF_modulesdrivepath.'SF_default_config_site.csv';

/* site default header, footer and css */
$defaultheaderfile=$SF_modulesdrivepath.'SF_defaultheader.html';
$defaultfooterfile=$SF_modulesdrivepath.'SF_defaultfooter.html';
$defaultcssfile=$SF_moduleswebpath.'SF_default.css';

/*metadata files if used*/
$defaultmetadatafile=$SF_modulesdrivepath."SF_defaultmetadata.html";

/* print view files */
$defaultprintheaderfile=$SF_modulesdrivepath.'SF_defaultprintheader.html';
$defaultprintfooterfile=$SF_modulesdrivepath.'SF_defaultprintfooter.html';
$defaultprintcssfile=$SF_moduleswebpath.'SF_defaultprint.css';

/* text only view files */
$defaulttextonlycssfile=$SF_moduleswebpath.'SF_textonly.css';

$menutoplevelidentifier='_toplevel';

/* just in case we've come directly here (not via autoprepend) then set sfdebug to 0 if it's not set already */
if(!isset($sfdebug)){$sfdebug = 0;}

if($sfdebug >= 3)
  {
    SF_DebugMsg($SF_modulesdrivepath.'SF_mainconfig.php loaded');
  }
?>
