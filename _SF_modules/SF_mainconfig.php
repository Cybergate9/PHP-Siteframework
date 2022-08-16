<?php
/**
* This file is Siteframework's main configuration file
*
* @author Shaun Osborne (webmaster@cybergate9.net)
*
* @link https://github.com/Cybergate9/PHP-Siteframework
*
* @copyright Shaun Osborne, 2005-present
* @license https://github.com/Cybergate9/PHP-Siteframework/blob/master/LICENSE
*
* @version as per $sfversion in SF_mainconfig.php (below)
*/

/**
 * Siteframework (as a whole) version number
 */
$sfversion = '2022.08.16';

error_reporting(1); /* only report errors */
date_default_timezone_set('Australia/Adelaide');

/****************************************************************************
Global derived configuration values - shouldn't need to change these */
$SF_protocol = $_SERVER['HTTPS'] ? 'https://' : 'http://';
$SF_host = $SF_protocol.$_SERVER['HTTP_HOST'];
$SF_modulesdirname = '_SF_modules/';
$SF_moduleswebpath = $SF_sitewebpath.$SF_modulesdirname;
$SF_modulesdrivepath = $SF_documentroot.$SF_moduleswebpath;
$SF_sitedrivepath = $SF_documentroot.$SF_sitewebpath;
$SF_subsitewebpath = $SF_sitewebpath;
$SF_subsitedrivepath = $SF_sitedrivepath;
$SF_phpselfdrivepath = $SF_documentroot.$_SERVER['PHP_SELF'];
$SF_sitelogo = $SF_moduleswebpath.'sflogo_sml.jpg';

/****************************************************************************
Global default values - shouldn't need to change these */

/* data files */
$defaultmenudatafile = $SF_modulesdrivepath.'SF_default_config_menu.csv';
$defaultdirconfigfile = $SF_modulesdrivepath.'SF_default_config_dir.csv';
$defaultsiteconfigfile = $SF_modulesdrivepath.'SF_default_config_site.csv';

/* site default header, footer and css */
$defaultheaderfile = $SF_modulesdrivepath.'SF_defaultheader.html';
$defaultfooterfile = $SF_modulesdrivepath.'SF_defaultfooter.html';
$defaultcssfile = $SF_moduleswebpath.'SF_default.css';

/*metadata files if used*/
$defaultmetadatafile = $SF_modulesdrivepath.'SF_defaultmetadata.html';

/* print view files */
$defaultprintheaderfile = $SF_modulesdrivepath.'SF_defaultprintheader.html';
$defaultprintfooterfile = $SF_modulesdrivepath.'SF_defaultprintfooter.html';
$defaultprintcssfile = $SF_moduleswebpath.'SF_defaultprint.css';

/* text only view files */
$defaulttextonlycssfile = $SF_moduleswebpath.'SF_textonly.css';

$menutoplevelidentifier = '_toplevel';

$storedpreviewmetadatafile = $SF_sitedrivepath.'storedpreviewsmetadata.json';

if ($sfdebug >= 3) {
    SF_DebugMsg($SF_modulesdrivepath.'SF_mainconfig.php loaded');
}
