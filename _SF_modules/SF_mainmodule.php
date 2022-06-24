<?php
/**
* This file is Siteframework's main module
*
* !!!Important!!! 
* this module should never output anything itself when in production
*
* Note: Shouldn't need to change values in here, default settings for SF in:
*
* 1) SF_mainconfig.php - for SF directory paths etc
*
* 2) SF_localconfig.php - intended to be the file changed on a per installation basis
*
* 2) SF_config_site.csv (csv text file) for defining site and subsite 'config_dir' files
*
* 3) SF_config_dir.csv (csv text file) for per directory configuration (menu,css,header,footer)
*
* 4) SF_config_menu.csv (csv text file) for menu configuration data
* 
*
* CHANGE HISTORY
* 
* 1.19    (23Jun2022)  SF_GeneratefromMarkdownURL() updates to fine tune outputs, deal with metadata better (titles, data, author etc)
* 1.9     (22Jun2022)  clean implementation of pure php caching without Cache_Lite (cacheconfig.php removed and replaced with SF_cache.php)
*                      functionality remains similar:
*                      1) caching into single directory, or multiple subdirs (if hash value > 0, only 1 or 2 recommended), 
*                      2) timeouts in secs (3600 = 1 hour)
*                      3) top level caching config in SF_localconfig.php, details in SF_cache.php
* 1.83    (18Jun2022)  decided on parsedown config, composer install into _SF_modules, configure via mainconfig.php
*                      decided to split mainconfig.php in two adding a localconfig.php as in practice over-writing mainconfig.php on remote 
*                      installs is a pain
*                      tidies up meta, header, footer and accessibility pages including accesskeys
*                      SF_GeneratefromMarkdownURL() can do short summaries now as well as full output
*                      SF_GenerateTextOnlyHTML($url,$output=true) added check on file_get_contents to catch allow_url_fopen = false in servfer config
* 
* 1.7    (15Jun2022)  fixed wrong references in, and added meta via SF_commands values to dublin core defaultmetadata.html
*                     added SF_GeneratefromMarkdownURL() and simpleyaml() as first implementation for Markdown content
*                     modified SF_autoprepend.php to sense if ext is .md and process it as Markdown if so
* 
* 1.6    (13Jun2022)  fixes for php8, split() deprecated, replaced with explode()'s
*                     fixed 'forever while' bug in SF_LoadMenuData()
*                     added check for duplicate menuid's in SF_LoadMenuData, will put out 'warnings' if ?debug=1
*                     fixed sfdebug warning if we're not called via autoprepend
*                     created str_convert_htmlentities() for email encodes in SF_GenerateEmailLink() due to deprecated preg_replace feature
*                     SF_defaultheader.html now html5, utf8
* 
* 1.51    (31May2006) SF_LoadMenuData() and SF_GenerateNavigationMenu() updated so both level 1 and level 2 are highlighted when a level 2 is active
*                     and menuhighlighting is on (true)
*
* 1.50    (27May2006) SF_GenerateNavigationMenu() updated to output different div's if different levels are chosen
*
* 1.49    (25may2006) added $displayhome =true,$limitchars=200 paramater to SF_GenerateBreadcrumbLine()
* 
* 1.48    (23may2006) SF_autoprepend UPDATED can now use <!-- SF_Command:httpredirect:url --> 
*                     (if content pp is on in directory) to generate a hhtp 302 class redirect back to browser for this page
*
* 1.47   (22may2006) Added $displaylevelmatch parameter to SF_GenerateSiteMap
*
* 1.46   (17may2006) SF_GenerateEmailLink() added. SF_GenerateSiteMap() updated to be able to restrict levels to show
* 
* 1.45   (15may2006) SF_autoprepend UPDATED can now use sf_function=nosf or <!-- SF_Command:nosf:anything --> 
*                     (if content pp is on in directory) to turn off the framework for this file
* 
* 1.44  (14May2006) removed logic put in with V1.5 (_fallback etc) and built in proper fallback ability so logic now runs
*         find exact match, find variation match (e.g. index.2.html), find previous dir match and keeping falling back on ddir's unless we hit 'root'
*         so upshot is menu can be defined as /gallery/something/ and this will match
*
*                   /gallery/something/anypage
*
*                   /gallery/something/dirone/anypage
*
*                   /gallery/something/dirone/dirtwo/anypage etc
*
*
* 1.43   (12May2006) SF_GenerateContentsFromURL() can now take into account if one calls in content via http from a framework 
*                    delivered page the header and footer will automatically be removed 
*                    (based on SF_Command:content:begins and SF_Command:content:ends tags)
*                     doing what is expected - ie get just the content
* 
* 1.42  (11may2006) fixed SF_LoadSiteConfigData(), and SF_LoadDirConfigData() and SF_LoadMenuData() to skip incomplete of blank lines in config files 
*
* 1.41  (3may2006) fixed bug in LoadMenuData() that wouldnt mark right menu item for directory levels deeper than
*                  those named in menu config (ie paths it couldnt recognise), first two passes remain the same, third and forth are:
*                  
*                  3) choose the item marked as _toplevel_fallback, or
*                  
*                  4) choose the first item (ordered) from the subset we are working in  
*
* 1.4b  (17Jan05) SF_GenerateTextOnlyHTML now also removes <style></style> & <center></center> tags
*
* 1.4a  (16Jan06) added SF_GetSectionTitle() function and support to SF_LoadDirConfigData(); for it
*
* 1.4   (10Jan06) text only now applies a CSS
*
* 1.3e  (2Dec05)  fixes in SF_mainmodule for SF_documentroot
*
* 1.3c (23nov05)  added SF_documentroot as a gloabl in SF_mainconfig.php and fixed SF_GetPageModifiedDate() to use it
*
* 1.3b (28Oct05)  fine tune interaction between querystrings and caching, debug=x now turns caching off
*
* 1.3a (27Oct05)  added ability in SF_autoprepend.php to respond to 'none' dir config settings for header and footer
*
*                 fixed pre-processing configuration 'yes' to be case insensitive
*
* 1.3  (26Oct05)  cleaned up querystring logic in SF_autoappend.php, added debug=x querystring ability and made changes
*                 required for that to work
*
* 1.2c  (25Oct2005) if no order information now given in menu config they will still display (just in no particular order)
*
*                   fixed bug in SF_GenerateContentFromURL where it wasn't cleaning http path properly
*
* 1.2b (24Oct2005) fixed sf_f=force in autoprepend.php to properly handle updating cached copy of page
*
*                  fixed caching so sf_f=time|force themselves do not create new cache copies
*
* 1.2a (23Oct2005) textonly rearrangement commands changed to SF_command:content:begins and SF_command:content:ends
*
* 1.2 (22Oct2005) removed autoappend.php altogether moving all functionality into autoprepend.php. Has not only benefit of simplifying configuration but allowing pre-processing of 'commands' from the 'content' file (not sure about efficiency of this but we'll see).
*
*                 new gloabls $SF_phpselfdrivepath and array $SF_commands
*              
*                 implemented ability to turn pre-processing on and off via config_dir
*
*                 implemented caching using Cache_Lite (can be turned on/off, config'd in SF_cacheconfig.php)   
*        
*                 some minor cleanup in SF_LoadMenuData() loops          
*
* 1.1e (20Oct05) query strings for SF now case-insentive
*
* 1.1d (19Oct05) breadcrumb lines were not being htmlspecialchar'd - fixed
*
*                'print' link wasn't working from text only - fixed
*
* 1.1c (18Oct05) minor changes - global $SF_sitetitle, GPT_* constants introduced
*
* 1.1 (18Oct05) added SF_LoadSiteConfigData() and made adjustments throughout framework to cope with this.
* This allows all the configuration for a directory running under SF to be delegated.
* Functionally it means the directory is declared and its 'config_dir' file named and then all configuration
* for that directory is contained in that 'config_dir' file and its associated 'config_menu' files
*
* 1.0b fixed SF_GenerateContentFromURL() so it fixes no http:// relative references properly
*
* 1.0a added a few trim's to SF_LoadMenuData() so config file formatting is more forgiving
*
* @package PHP-SiteFramework
* @author Shaun Osborne (webmaster@cybergate9.net)
* @link https://github.com/Cybergate9/PHP-Siteframework
* @access public 
* @copyright Shaun Osborne, 2005-present
* @license https://github.com/Cybergate9/PHP-Siteframework/blob/master/LICENSE
* @version 1.9 (2022-06-19)
*/

/**
* brings in global paths and default values for variables
*/
require_once('SF_localconfig.php');
require_once('SF_mainconfig.php');


/**
* Siteframework (as a whole) version number
*/
$sfversion='1.9 (2022-06-19)';
#error_reporting(1); /* only report errors */

/****************************************************************************
Global variables */
$currentmenuarray=array();
$menudataarray=array();
$menuitemidentifier='0';  /*don't change this starting value*/
$menuitemparent=' ';      /*don't change this starting value*/
$menuitemtitle='';
$dirconfigarray = array();
$siteconfigarray = array();
$SF_commands=array();
$textonlyqs='sf_function=textonly'; /* query string to append to get textonly version */
$printlayoutqs='sf_function=print'; /* query string to append to get print layout version */
/* Global Constants */
/*GetPageTitle (GPT) constants */
define("GPT_PAGE",0);
define("GPT_SITEnPAGE",1);
define("GPT_BREADCRUMB",2);
define("GPT_SITEnBREADCRUMB",3);
define("GPT_SITE",4);



/*************  BEGINNING OF SF_mainmodule.php 'main()  ****/

if($sfdebug >= 1)
  {
    SF_DebugMsg($SF_modulesdrivepath.'SF_mainmodule.php, Version: ['.$sfversion.'] loaded');
  }
SF_PageInitialise();

/*************  END OF SF_mainmodule.php 'main()'   *****/


/**
* This (based on the page which called it) initialises everything for Siteframework
*
* It calls:
*
* SF_LoadSiteConfigData (which determines file for SF_LoadDirConfigData())
*
* SF_LoadDirConfigData() (which determines menudata, css, header and footer to load)
*
* SF_LoadMenuData() to initialise everything for this page (menu wise)
*
* Also
*
* @see SF_LoadMenuData
* @see SF_LoadDirConfigData
* @see SF_LoadSiteConfigData
* @access private
*/
function SF_PageInitialise()
/****************************************************************************/
{
  SF_LoadSiteConfigData();
  SF_LoadDirConfigData();
  SF_LoadMenuData();
}


/**
* Gets the config_dir filename 
*
* Load the SF_config_site.csv file and based on our current path extracts
* the appropriate config_dir filename. Result loaded into global array
* $siteconfigarray
*
* @access private
*/
function SF_LoadSiteConfigData()
/****************************************************************************/
{
global $siteconfigarray;
global $sfdebug;
global $SF_documentroot;
global $defaultsiteconfigfile;
global $defaultdirconfigfile;
global $SF_sitewebpath;
global $SF_sitedrivepath;
global $SF_subsitewebpath;
global $SF_subsitedrivepath;
global $SF_phpselfpathdrivepath;

$filelinesarray=array();
$datafile=$defaultsiteconfigfile;

if(count($siteconfigarray) >= 1)
  {
  if($sfdebug>=1){SF_DebugMsg('WARNING: SF_LoadSiteConfigData() has already run - skipping');}
  return;
  }

$adjustedSFsitewebpath=removeleadingslash($SF_sitewebpath);
$currentpath=getpath(preg_replace("~$adjustedSFsitewebpath~i","",$_SERVER['PHP_SELF']));
$SF_phpselfpathdrivepath = $currentpath;

if($sfdebug>=1)
  {
  SF_DebugMsg('SF_LoadSiteConfigData(SF_documentroot:['.$SF_documentroot.'])');  
  SF_DebugMsg('SF_LoadSiteConfigData(SF_sitewebpath:['.$SF_sitewebpath.'])');
  SF_DebugMsg('SF_LoadSiteConfigData(currentpath:'.$currentpath.' (will match '.$currentpath.' or '.removeleadingslash($currentpath).'), DATAFILE:'.$datafile.')');
  }

$filelinesarray = file($datafile);
if(!$filelinesarray)
  {
    SF_ErrorExit('SF_LoadSiteConfigData()','no data from file '.$datafile);
  }

$atroot=false;
while(!array_key_exists('dirconfigfile',$siteconfigarray) and $atroot==false)
     {
      foreach($filelinesarray as $line)
             {    
                $line=preg_replace("@\"@",'',$line);  /* remove any "'s from data */
                $values=explode(',',$line);
                if(count($values) <= 1)continue; #skip incomplete lines
                if(preg_match("@^/@",$values[0])) /* if path begins with a forward slash */
                  {$comparepath=$values[0];}      /* just use it */
                else
                  {$comparepath="/".$values[0];}  /* else add the forward slash */
                if($sfdebug >=3)
                  {SF_DebugMsg('SF_LoadSiteConfigData(COMPARE: Config:['.$comparepath.'] Current Path:['.$currentpath.']');}  
                if(!strcasecmp($comparepath,$currentpath))
                  {
                  if($sfdebug >=2)
                    {SF_DebugMsg('SF_LoadSiteConfigData(MATCHED: Config:['.$comparepath.'] Current Path:['.$currentpath.']');}
                  if(trim($values[1])!="")
                    {
                     if($values[1][0] == '/')
                       {
                       $siteconfigarray['dirconfigfile']=$SF_sitedrivepath.trim($values[1]);
                       $siteconfigarray['dirconfigpath']='';               
                       }
                     else
                       {
                       $siteconfigarray['dirconfigpath']=removeleadingslash($currentpath);
                       $siteconfigarray['dirconfigfile']=$SF_sitedrivepath.$siteconfigarray['dirconfigpath'].removeleadingslash($values[1]);
                       }
                    }  
                  break;
                  } 
            } /* end of foreach */
      if($currentpath=='/')
        {
         $atroot=true;
        }
      $currentpath=previousdir($currentpath);
     } /* end of while */

/* if we've got back to root (i.e. we're here) and some values have not been set then use global defaults */
if(!array_key_exists('dirconfigfile',$siteconfigarray))
  {
  $siteconfigarray['dirconfigfile']=$defaultdirconfigfile;
  $siteconfigarray['dirconfigpath']='';
  }

$SF_subsitewebpath=$SF_sitewebpath.$siteconfigarray['dirconfigpath'];
$SF_subsitedrivepath=$SF_sitedrivepath.$siteconfigarray['dirconfigpath'];

if($sfdebug >=1)
  {
    SF_DebugMsg('SF_LoadSiteConfigData('.print_r($siteconfigarray,true).')');
  }

return;  
}


/**
* Gets the settings for menu, css, header and footer files
*
* Load the dir_config file (determined by SF_LoadSiteConfigData()) and based 
* on our current path extracts the appropriate config_menu, css , header and 
* footer filenames.
*
* @access private
*/
function SF_LoadDirConfigData()
/****************************************************************************/
{
global $siteconfigarray;
global $dirconfigarray;
global $sfdebug;
global $SF_modulesdrivepath;
global $SF_sitedrivepath;
global $defaultmenudatafile;
global $defaultdirconfigfile;
global $defaultheaderfile;
global $defaultfooterfile;
global $defaultcssfile;
global $SF_sitewebpath;
$filelinesarray=array();

if(count($dirconfigarray) >= 1)
  {
   if($sfdebug>=1){SF_DebugMsg('WARNING: SF_LoadDirConfigData() has already run - skipping');}
   return;
  }

if(array_key_exists('dirconfigfile',$siteconfigarray))
  {
   $datafile=$siteconfigarray['dirconfigfile'];
  }
else
  {
   $datafile=$defaultdirconfigfile;
  }

$adjustedSFsitewebpath=removeleadingslash($SF_sitewebpath);

$currentpath=getpath(preg_replace("~$adjustedSFsitewebpath~i","",$_SERVER['PHP_SELF']));

if($sfdebug>=1){SF_DebugMsg('SF_LoadDirConfigData(currentpath:'.$currentpath.' (will match '.$currentpath.' or '.removeleadingslash($currentpath).'), DATAFILE:'.$datafile.')');}

$filelinesarray = file($datafile);
if(!$filelinesarray){SF_ErrorExit('SF_LoadDirConfigData()','no data from file '.$datafile);}

$atroot=false;
while(configdataisincomplete() and $atroot==false)
     {
      foreach($filelinesarray as $line)
             {               
                $line=preg_replace("@\"@","",$line);  /* remove any "'s from data */
                $values=explode(",",$line);
                if(count($values) <= 1)continue; #skip incomplete lines
                foreach($values as $key=>$junk){$values[$key]=trim($values[$key]);}
                if(preg_match("@^/@",$values[0])) /* if path begins with a forward slash */
                  {$comparepath=$values[0];}      /* just use it */
                else
                  {$comparepath='/'.$siteconfigarray['dirconfigpath'].$values[0];}  /* else add the forward slash */
                if($sfdebug>=3){SF_DebugMsg('SF_LoadDirConfigData(COMPARE: currentpath:['.$currentpath.'] comparepath:['.$comparepath.']');}
                if(!strcasecmp($comparepath,$currentpath))
                {
                  if($sfdebug >=2)
                  {SF_DebugMsg('SF_LoadConfigData(MATCHED: Config:['.$comparepath.'] Current Path:['.$currentpath.']');}
                  if($values[1]!="" and !array_key_exists('menudatafile',$dirconfigarray))
                  {
                    if($values[1][0] == '/')
                    $dirconfigarray['menudatafile']=$SF_sitedrivepath.$values[1];
                    else
                    $dirconfigarray['menudatafile']=$SF_sitedrivepath.$siteconfigarray['dirconfigpath'].removeleadingslash($values[1]);          
                  }
                  if($values[2]!="" and !array_key_exists('menu',$dirconfigarray))
                  {
                  $dirconfigarray['menu']=$values[2];
                  }
                  if(trim($values[3]) != "" and !array_key_exists('menukey',$dirconfigarray))
                  {
                  $dirconfigarray['menukey']=$values[3];
                  }
                  if(trim($values[4]) != "" and !array_key_exists('cssfile',$dirconfigarray))
                  {
                    if($values[4][0] == '/')
                    $dirconfigarray['cssfile']=$SF_sitedrivepath.$values[4];
                    else
                    $dirconfigarray['cssfile']=$SF_sitewebpath.$siteconfigarray['dirconfigpath'].removeleadingslash($values[4]);          
                  }
                  if($values[5] != "" and !array_key_exists('headerfile',$dirconfigarray))
                  {
                    if($values[5][0] == '/')
                    $dirconfigarray['headerfile']=$SF_sitedrivepath.$values[5];
                    else
                    $dirconfigarray['headerfile']=$SF_sitedrivepath.$siteconfigarray['dirconfigpath'].removeleadingslash($values[5]);          
                  }
                  if($values[6] != "" and !array_key_exists('footerfile',$dirconfigarray))
                  {
                    if($values[6][0] == '/')
                    $dirconfigarray['footerfile']=$SF_sitedrivepath.$values[6];
                    else
                    $dirconfigarray['footerfile']=$SF_sitedrivepath.$siteconfigarray['dirconfigpath'].removeleadingslash($values[6]);          
                  }              
                  if($values[7] != '' and !array_key_exists('contentpp',$dirconfigarray))
                  {
                    $dirconfigarray['contentpp']=strtolower($values[7]);          
                  }
                  if($values[8] != '' and !array_key_exists('sectionheadingtext',$dirconfigarray))
                  {
                    $dirconfigarray['sectionheading']=$values[8];          
                  }
                  if($values[9] != '' and !array_key_exists('custom1',$dirconfigarray))
                  {
                    $dirconfigarray['custom1']=$values[9];          
                  }
                  if($values[10] != '' and !array_key_exists('custom2',$dirconfigarray))
                  {
                    $dirconfigarray['custom2']=$values[10];          
                  }
                  if($values[11] != '' and !array_key_exists('custom3',$dirconfigarray))
                  {
                    $dirconfigarray['custom3']=$values[11];          
                  }
                  break;
                }
            }
      if($currentpath=='/')
        {
          $atroot=true;
        }
      $currentpath=previousdir($currentpath);
     }
/* if weve got back to root (i.e here) and some values have not been set then use global defaults */
if(!array_key_exists('menudatafile',$dirconfigarray))
  {$dirconfigarray['menudatafile']=$defaultmenudatafile;}
if(!array_key_exists('cssfile',$dirconfigarray))
  {$dirconfigarray['cssfile']=$defaultcssfile;}
if(!array_key_exists('headerfile',$dirconfigarray))
  {$dirconfigarray['headerfile']=$defaultheaderfile;}
if(!array_key_exists('footerfile',$dirconfigarray))
  {$dirconfigarray['footerfile']=$defaultfooterfile;}

if($sfdebug >=1)
  {SF_DebugMsg('SF_LoadDirConfigData('.print_r($dirconfigarray,true).')');}

return;  
}


/**
* Loads the menu data for the page we are on
*
* Load the menu_config file (determined by SF_LoadDirConfigData()) and based 
* on our current path figure out what menu item we are on.  Popoulates two
* globals arrays; $menudataarray (contains the whole menu data file) and 
* contains a sort sorted list of current menu items to display
*
* @access private
*/
function SF_LoadMenuData()
/****************************************************************************/
{
global $sfdebug;
global $menutoplevelidentifier;
global $currentmenuarray;
global $menudataarray;
global $dirconfigarray;
global $siteconfigarray;
global $defaultmenudatafile;
global $menuitemidentifier;
global $menuitemparent;
global $menuitemtitle;
global $SF_sitedrivepath;
global $SF_sitewebpath;
global $SF_defaultindexfile;

$datafile=$dirconfigarray['menudatafile'];
$menu=$dirconfigarray['menu'];
$menukey=$dirconfigarray['menukey'];

if(count($menudataarray) >= 1)
{
  if($sfdebug>=1){SF_DebugMsg('WARNING: SF_LoadMenuData() has already run - skipping');}
  return;
}

if($sfdebug >=1){SF_DebugMsg("SF_LoadMenuData($datafile,$menu,$menukey)");}

$filelinesarray = file($datafile);
if(!$filelinesarray)
  {
    SF_ErrorExit('SF_LoadMenuData()','no data from menu file '.$datafile);
  }

/*get the toplevel and requested level values into new array keyed by order value from csv file*/
$filelinesarray=preg_replace("@\"@","",$filelinesarray);  /* remove any "'s from csv data */
$menudataarray=$filelinesarray;
array_shift($menudataarray); /* remove the header line from array*/

foreach($menudataarray as $key=>$item)
       {
        $item=preg_replace("@,\/@",",",$item); /* this is a bit of a cludge to remove leading slashes in paths */
        if($sfdebug >=2){SF_DebugMsg("SF_LoadMenuData($key, $item)");}
        $subitem=explode(',',trim($item));
        if(count($subitem) <= 1)continue; #skip incomplete lines
        if(!strcmp($menu,trim($subitem[0])) and (!strcmp($menutoplevelidentifier,trim($subitem[1])) or !strcmp($menukey,trim($subitem[1])) ))
          {
          if(!strcmp('',$subitem[3])){$cmkey=$key;}else{$cmkey=$subitem[3];} /* this covers if no ordering info given */
          $currentmenuarray[$cmkey]=$subitem;
          }
       }
       
/*sort the currentmenuarray on keys (order value)*/
ksort($currentmenuarray,SORT_STRING);

$tpath=$SF_sitewebpath.$siteconfigarray['dirconfigpath'];
$currentpath=preg_replace("@^$tpath@i","",$_SERVER['PHP_SELF']);
if($sfdebug >=2){SF_DebugMsg('SF_LoadMenuData(currentpath:'.$currentpath.')');}
/*find what order number we are at e.g. 1, 1.1, 1.3.1
first pass, this will match exacts or dir+index.htm(l)'s or dir+filename+.anything.htm(l)*/
foreach($currentmenuarray as $item)
       {
        /* if we get a direct match, or match on value+index.html, or match on altered path
        e.g gettinghere.2.html becomes gettinghere.html 
        then remember it as the current item */
        $item[4]=trim($item[4]);
        if(!strcasecmp($currentpath,$item[4]) or !strcasecmp($currentpath,$item[4].$SF_defaultindexfile) or !strcasecmp(preg_replace("/\.[0-9a-zA-Z].*\.htm/",".htm",$currentpath),$item[4]))
        {
          $menuitemidentifier=$item[3];
          $menuitemtitle=$item[2];
        }
       }
       
/*second pass if nothing from first - on just paths and keeping walking back until we will either get a match, find root, of have exhausted possibilities, cx provides the safety on while() loop */
$tpath=getpath($currentpath);
$cx=0;
while($menuitemidentifier == '0' and strcmp($tpath,$SF_sitewebpath) and $cx <= count($menudataarray))
  {
  
  if($sfdebug >=3){SF_DebugMsg('SF_LoadMenuData(comparing with tpath='.$tpath.', & $SF_sitewebpath='.$SF_sitewebpath.')');}
  foreach($currentmenuarray as $item)
       {
        if($sfdebug >=3){SF_DebugMsg('SF_LoadMenuData(tpath:'.$tpath.', getpath(menudataitem):'.getpath(trim($item[4])).')');}
        if(!strcasecmp($tpath,getpath(trim($item[4]))))
        {
         $menuitemidentifier=trim($item[3]);
         $menuitemtitle=trim($item[2]);
         break; /*stop when we find the first one*/
        }
       }
   $tpath=previousdir($tpath);  
   $cx++;  
   if($sfdebug >=3){SF_DebugMsg('SF_LoadMenuData(count($menudataarray):'.count($menudataarray).', $cx:'.$cx.')');}
  }

$tempmii=$menuitemidentifier;
if(preg_match("/\./",$tempmii))
     {
     /* strip numbers from the right if any ie 1.1 becomes 1*/
     for($x=strlen($tempmii)-1; $tempmii[$x] != '.' and $x>=0; $x--)
        {
         $tempmii[$x]=' ';
        }
      $tempmii[$x++]=' '; #strip decimal ie 1. becomes 1
      $menuitemparent = trim($tempmii); #trim off spaces
     }
      
if($sfdebug >=1)
  {
    SF_DebugMsg("SF_LoadMenuData(has chosen MENUID:$menuitemidentifier, TITLE:$menuitemtitle PARENTID: $menuitemparent)");
    foreach($menudataarray as $key=>$item)
           {
            $itemvals = explode(',',$item);
            foreach($menudataarray as $ckey=>$compareitem)
                   {
                    $comparevals = explode(',',$compareitem);
                    if( $ckey != $key and !strcmp($comparevals[3],$itemvals[3]))
                      {SF_DebugMsg("SF_LoadMenuData(<b>***WARNING***</b>: Duplicate menu ID: [$itemvals[3]] (line:$key) and $comparevals[3](line:$ckey)");}
                   }
            }
  }

return;
}


/**
* Ouput the HTML for the current menu
* 
* Use global array $currentmenuarray to ouput the currently selected menu set
* as was determined by SF_LoadMenuData().
*
* Menu block is surrounded by a <div id="SF_menuarea" class="SF_menuarea">
* 
* Menu level 1's are tagged as <p class="SF_menu_level_1">
* 
* Menu level 2's are tagged as <p class="SF_menu_level_1">
*
* @see SF_LoadMenuData()
* @param bool mixed controls whether we tag what menuitem is selected true=on [default], false=off (CSS=SF_menu_level_1_highlight and SF_menu_level_2_highlight)
* @param bool controls whether we tag items that are off site links (start with http://), true=on [default], false=off (CSS=SF_offsite_link)
* @param integer control whether to show all menu levels (0), only menu level 1's (1) or only menu level 2's (2)
*/
function SF_GenerateNavigationMenu($menuhighlight=true,$dooffsitelinktags=true,$showonlylevel=0, $separator='')
/****************************************************************************/
{
global $SF_modulesdrivepath;
global $sfdebug;
global $SF_sitewebpath;
global $dirconfigarray;
global $siteconfigarray;
global $defaultmenudatafile;
global $menuitemidentifier;
global $menuitemparent;
global $currentmenuarray;
global $menutoplevelidentifier;

#get the values we want from global config
foreach($dirconfigarray as $key=>$value)
        {
          switch($key)
                {
                  case 'menukey':
                                $menukey=$value;
                                 break;
                  case 'menu':
                                $menu=$value;
                                 break;
                  default:
                                break;
                }
        }

if($sfdebug >=3){SF_DebugMsg("SF_GenerateNavigationMenu($menu,$menukey,hl:$menuhighlight,mi:$menuitemidentifier)<br/>"); }

# create navigation menu div
if($showonlylevel == 0)
  {echo('<div id="SF_menuarea" class="SF_menuarea">');}
elseif($showonlylevel==1)
  {echo('<div id="SF_menuarea1" class="SF_menuarea1">');}
elseif($showonlylevel==2)
  {echo('<div id="SF_menuarea2" class="SF_menuarea2">');}

#print out the menu from the global array
foreach($currentmenuarray as $key=>$item)
       {
        if(!strcmp($menutoplevelidentifier,$item[1]))
          {
          if((!strcmp($menuitemidentifier,$item[3]) or !strcmp($menuitemparent,$item[3])) and $menuhighlight)$cssclass='SF_menu_level_1_highlight';
          else $cssclass='SF_menu_level_1';
          }
        else
          {
          if(!strcmp($menuitemidentifier,$item[3]) and $menuhighlight)$cssclass='SF_menu_level_2_highlight';
          else $cssclass='SF_menu_level_2';
          }

        if(preg_match("/^http:\/\/.*/",$item[4]) and $dooffsitelinktags)
          {
          $menuitemhtml = '<p class="'.$cssclass.'"><span class="SF_offsite_link"><a href="'.$item[4].'" target="_blank">'.htmlspecialchars($item[2])."</a></span>".$separator."</p>\n";
          }
        else
          {
          $menuitemhtml = '<p class="'.$cssclass.'">';
          if ($key != 0) $menuitemhtml = $menuitemhtml.$separator;
          $menuitemhtml = $menuitemhtml.'<a href="'.$SF_sitewebpath.$siteconfigarray['dirconfigpath'].$item[4].'">'.htmlspecialchars($item[2])."</a></p>\n";
          }
        /* deal with $showonlylevels */
        if($showonlylevel == 0)
          {echo $menuitemhtml;}
        elseif($showonlylevel==1 and !strcmp($menutoplevelidentifier,$item[1]))
          {echo $menuitemhtml;}
        elseif($showonlylevel==2 and strcmp($menutoplevelidentifier,$item[1]))
          {echo $menuitemhtml;}
       } /* end of foreach() */

/* end SF_menuarea div */
echo("</div>");    

return;
}


/**
* Output breadcrumb html for current page
*
* CSS styles are: SF_breadcrumbarea, SF_breadcrumb_line, SF_breadcrumb_title and SF_breadcrumb_item
*
* @access public
* @param string $breadcrumbleadtext the lead text for the breadcrumbline
* @param string $breadcrumbseparator  the separator between each breadcrumb item
* @param bool $output output (true) html or return result from function as string (false)
*
*/
function SF_GenerateBreadCrumbLine($breadcrumbleadtext="You are in: ",$breadcrumbseparator=" > ",$output=true,$displayhome =true,$limitchars=200)
/****************************************************************************/
{
global $SF_modulesdrivepath;
global $sfdebug;
global $SF_sitewebpath;
global $siteconfigarray;
global $menuitemidentifier;
global $menudataarray;
global $menutoplevelidentifier;
$breadcrumbs=array();


#take a copy of this global
$tempmii=$menuitemidentifier;

#do sublevels eg 1.1 10.3.1 etc
while(preg_match("/\./",$tempmii))
     {
      foreach($menudataarray as $item)
       {
        $subitem=explode(',',trim($item));
        if(count($subitem) <= 1)
          continue; #skip incomplete lines
        if(!strcmp($tempmii,$subitem[3]))
          {
           $breadcrumbs[$subitem[3]]=$subitem;
          }
       }

     /* strip numbers from the right if any ie 1.1 becomes 1*/
     for($x=strlen($tempmii)-1; $tempmii[$x] != '.' and $x>=0; $x--)
        {
         $tempmii[$x]=' ';
        }
      $tempmii[$x++]=' '; #strip decimal ie 1. becomes 1
      $tempmii = trim($tempmii); #trim off spaces
     } /* end of while() */

#do top level e.g 1-xx
foreach($menudataarray as $item)
       {
        $subitem=explode(',',trim($item));
        if(count($subitem) <= 1)continue; #skip incomplete lines
        if(!strcmp($tempmii,$subitem[3]))
          {
          $breadcrumbs[$subitem[3]]=$subitem;
          }
       }

#do home e.g 0
foreach($menudataarray as $item)
       {
        $subitem=explode(',',trim($item));
        if(count($subitem) <= 1)
          continue; #skip incomplete lines
        if(!strcmp('0',$subitem[3]))
          {
          $breadcrumbs[$subitem[3]]=$subitem;
          }
       }

#sort the breadcrumbs back into order
ksort($breadcrumbs,SORT_STRING);

#print out the menu in the newly sorted order
if($output)
{ 
  if($sfdebug >=3){SF_DebugMsg('SF_GenerateBreadCrumbLine(mi:'.$menuitemidentifier.')'); }
  echo('<div id="SF_breadcrumbarea" class="SF_breadcrumbarea"><p class="SF_breadcrumb_line">');
  echo('<span class="SF_breadcrumb_title">'.$breadcrumbleadtext.'</span>');
  $x=1; $charcount=0;
  foreach($breadcrumbs as $key=>$item)
         {
           if($x > 1 and $x <= count($breadcrumbs))
              {echo $breadcrumbseparator;}
           if($key == 0 and count($breadcrumbs) > 1 and $displayhome == false)
              continue;
           $charcount = $charcount + strlen(htmlspecialchars($item[2]));
           if($charcount > $limitchars) 
              {echo " ..."; break;}
           echo('<span class="SF_breadcrumb_item">');
           echo('<a href="'.$SF_sitewebpath.$siteconfigarray['dirconfigpath'].$item[4].'">'.htmlspecialchars($item[2]).'</a>');
           echo('</span>');
          $x++;
         }
  echo('</p></div>');
}
else /* if the $output was FALSE */
{
  $breadcrumbstring='';
  $x=1;
  foreach($breadcrumbs as $key=>$item)
        {
         $breadcrumbstring = $breadcrumbstring.htmlspecialchars($item[2]);
         if($x++ < count($breadcrumbs))
            {$breadcrumbstring = $breadcrumbstring.$breadcrumbseparator;}
        }
  return $breadcrumbstring;
}
}


/**
* returns a string representing the current page
*
* Argument is following types:
*
* GPT_PAGE = Current Page Title (as determined via menu data)
*
* GPT_SITEnPAGE = $SFsitetitle + Current Page Title 
*
* GPT_BREADCRUMB = current breadcrumb (as determined via menu data) line separated by '|'s
*
* GPT_SITE+BREADCRUMB = $SFsitetitle + current breadcrumb
*
* @param integer GPT_PAGE, GPT_SITEnPAGE, GPT_BREADCRUMB, GPT_SITE+BREADCRUMB
*/
function SF_GetPageTitle($titletype=GPT_BREADCRUMB)
/****************************************************************************/
{
global $menuitemtitle;
global $SF_sitetitle;

switch($titletype)
      {
      case GPT_PAGE:
                    return $menuitemtitle;
                    break;
      case GPT_SITEnPAGE:
                    return $SF_sitetitle." : ".$menuitemtitle;
                    break;
      case GPT_BREADCRUMB:
                    return SF_GenerateBreadCrumbLine(""," | ",false);
                    break;
      case GPT_SITEnBREADCRUMB:
                    return $SF_sitetitle.': '.SF_GenerateBreadCrumbLine(""," | ",false); 
                    break;
      case GPT_SITE:
                    return $SF_sitetitle; 
                    break;                    
           default:
                    return '(no page title)';
                    break;
      }
}


/**
* Returns CSS path and file name based on 'config_dir' settings
*
*/
function SF_GetCSSFilename()
/****************************************************************************/
{
global $sfdebug;
global $dirconfigarray;
#get the css value from dir config array */
if($sfdebug >= 1)
  {SF_DebugMsg('SF_GetCSSFilename('.$dirconfigarray['cssfile'].')'); }

return $dirconfigarray['cssfile']; 
}


/**
* Returns CSS path and file name of SF's default CSS file
*
*/
function SF_GetDefaultCSSFilename()
/****************************************************************************/
{
global $defaultcssfile;
return $defaultcssfile;
}


/**
* Output site map html from current menu data
*
* [tobedone] description
*
* @param bool turns on (true) or off (false) showing of level identifiers in the outputted HTML
* 
* @param int  specifies how many levels to show (1-6) e.g. two will show sitemap level 1 and 2
* 
* @param string provides a regex match for what menulevels to display
*/
function SF_GenerateSiteMap($showlevels=false,$levelstoshow=6,$displaylevelmatch=".*")
/*****************************************************************************/
{
global $SF_modulesdrivepath;
global $sfdebug;
global $SF_sitewebpath;
global $menuitemidentifier;
global $menudataarray;
global $menutoplevelidentifier;
global $siteconfigarray;
$sitemaparray=array();

#tidy up inbound
if($levelstoshow == null) $levelstoshow = 6;
if($displaylevelmatch == null) $displaylevelmatch=".*";


if($sfdebug>=3)
  {SF_DebugMsg('SF_GenerateSiteMap()');}

#take a copy of this global
$tempmii=$menuitemidentifier;

foreach($menudataarray as $item)
       {
        $subitem=explode(',',trim($item));
        if(count($subitem) <= 1)continue; #skip incomplete lines
        $sitemaparray[$subitem[3]]=$item;
       }

ksort($sitemaparray,SORT_STRING);

echo('<div id="SF_sitemaparea" class="SF_sitemaparea">');
foreach($sitemaparray as $item)
       {
        $subitem=explode(",",trim($item));
        switch(preg_match_all("/\./",$subitem[3],$dontcarearray))
              {
                case 0:
                       $cssclass='SF_map_level_1';
                       $currentlevel=1;
                       break;
                case 1:
                       $cssclass='SF_map_level_2';
                       $currentlevel=2;
                       break;
                case 2:
                       $cssclass='SF_map_level_3';
                       $currentlevel=3;
                       break;
                case 3:
                       $cssclass='SF_map_level_4';
                       $currentlevel=4;
                       break;
                case 4:
                       $cssclass='SF_map_level_5';
                       $currentlevel=5;
                       break;
                case 5:
                       $cssclass='SF_map_level_6';
                       $currentlevel=6;
                       break;
              }
        /* now output this item classed correctly */
        if($currentlevel <= $levelstoshow)
          {
          if(preg_match("@$displaylevelmatch@",$subitem[3]))
            {
            echo '<p class="'.$cssclass.'">';
            if(preg_match("@^http:\/\/.*@",$subitem[4]))
              {echo '<a href="'.$subitem[4].'">';}
            else
              {echo '<a href="'.$SF_sitewebpath.$siteconfigarray['dirconfigpath'].$subitem[4].'">';}  
            echo $subitem[2].'</a>';
            if($showlevels == true)
              {echo ' ('.$subitem[3].')';}
            echo "</p>\n";
            }
         }
      } /* end of foreach() */
echo('</div>');
}


/**
* Global error exit function for Siteframework
*
* Outputs CSS link, Error text supplied and does a hard exit
*
* @param string intended to identify who called the exit, file or function
* @param string error message you want to output with the exit
*/
function SF_ErrorExit($caller='nocaller', $msg='nomsg')
/****************************************************************************/
{
global $SF_moduleswebpath;
echo '<link href="'.SF_GetDefaultCSSFilename().'" rel="stylesheet" type="text/css">';
echo '<br/><p class="SF_error_text">SF Fatal Error: from=['.$caller.']<br/>error=['.$msg.']</p><br/>';
exit;
}


/**
* Global debug message function for Siteframework
*
* Outputs CSS link, and debug text you supply
*
* @param string debug message you want to output
*/
function SF_DebugMsg($msg='nomsg')
/****************************************************************************/
{
global $SF_moduleswebpath;
global $defaultcssfile;
echo '<link href="'.SF_GetDefaultCSSFilename().'" rel="stylesheet" type="text/css">';
echo '<span class="SF_debug_text">SF_debug: '.$msg.'</span></br>';
flush();
}


/**
* Gets the contents of named file/URL and output it.
*
* Passed a filename/URL it determines to:
*
* http:// just get it
*
* anything else assume some sort of relative path so convert it
* to an absolute file reference starting at webserver DOCUMENT_ROOT 
*
* so these should all be ok:
*
* file.html (reference in current dir)
*
* ../about/index.html (some sort of relative reference)
*
* /about/index.html (absolute reference (from root) on this server)
*
*
* @param string URL you want to get
*/
function SF_GenerateContentFromURL($url)
/****************************************************************************/
{
global $SF_sitedrivepath;

/* figure out if this is a http (get it) or fix the path up for getting off the local filesystem */
$url=sfnormaliseurl($url,'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);

$contents=file_get_contents($url);
if(!$contents)
  {SF_ErrorExit('SF_generateContentFromURL','Failed to open file ['.$url.']');}
if(preg_match("/^http:\/\//",$url)) # is this a url call?
  {
    if(preg_match("@<!-- SF_Command:content:begins -->@",$contents)) # does it contain framework content?
      {
       $b=preg_match("@<!-- SF_Command:content:begins -->.*<!-- SF_Command:content:ends -->@s",$contents,$body); #get just the body
       if($b ==1) $contents = $body[0]; # if we got a good body, copy it into contents and thats what will be returned
      } 
  }  

echo $contents;

return;
}



/**
* Gets the MARKDOWN contents and FRONT MATTER named file/URL and output it.
*
* Passed a filename/URL it determines to:
*
* http:// just get it
*
* anything else assume some sort of relative path so convert it
* to an absolute file reference starting at webserver DOCUMENT_ROOT 
*
* so these should all be ok:
*
* file.md (reference in current dir)
*
* ../about/index.md (some sort of relative reference)
*
* /about/index.md (absolute reference (from root) on this server)
*
*
* @param string URL you want to get
* @param output title as header1 true=yes, false=no
* @param output only $summaryonly number of characters false=0 characters, 0=zero characters, any integer number of characters to output as summary
* @param output a 'Read More' link false=no, true=yes
* @param return value is contents of url, else return true;
*/
function SF_GeneratefromMarkdownURL($url,$title=true,$summaryonly=false,$returnreadmorelink=false,$returncontents=false)
/****************************************************************************/
{
global $SF_sitedrivepath;
global $SF_modulesdrivepath;
global $SF_moduleswebpath;
global $SF_parsedownpath;
global $SF_commands;
require_once ($SF_parsedownpath);

/* figure out if this is a http (get it) or fix the path up for getting off the local filesystem */
//$url=sfnormaliseurl($url,'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
$output ="";
$contents=file_get_contents($url);
if(!$contents)
  {return false;} 

$parts = preg_split('/[\n]*[-]{3}[\n]/', $contents, 3, PREG_SPLIT_NO_EMPTY);

$SF_commands['refurl']="";
if(count($parts) > 1)
  {
    $yaml = simpleyaml(explode("\n",$parts[0]));
    foreach($yaml as $key=>$value)
    {
      $SF_commands[$key]=$value;
      //var_dump($SF_commands);
    }
    $md = $parts[1];
  }
else
  {
   $md = $parts[0];
  }

if($title and array_key_exists('title',$SF_commands))
  {
  $output = $output."<h1>".$SF_commands['title']."</h1>";
  }
if(array_key_exists('date',$SF_commands) )
  {
  $output = $output.'<div class="dateby"> &#8880;  '.$SF_commands['date'];
  if(array_key_exists('author',$SF_commands))
    {
      $output = $output." by ".$SF_commands['author'];
    }
    $output = $output.'   &#8881; </div>';
  }
$Parsedown = new Parsedown();
if($summaryonly >=1)
  {
   $snippet = $Parsedown->text($md);
   $snippet = preg_replace("/<a.[^<]*>/","",$snippet); // get rid of image links in summaries
   $snippet = preg_replace("/<img.[^<]*>/","",$snippet);  // get rid of hrefs in summaries
   $snippet = substr($snippet,0,$summaryonly);
   $output = $output.$snippet.'...';
   if(!$returnreadmorelink){
       $output = $output.'<p>[<a href="'.$_SERVER['PHP_SELF'].'?p='.substr($url,5,strlen($url)).'"">Read more..</a>]</p>';
   }
   else
   {
    $SF_commands['readmorelink']='<p>[<a href="'.$url.'"">Read more..</a>]</p>';
   }

  }
else
  {
  $output = $output.'<div class="SF_flex_box">';
  $output = $output.'<div>';    
  /*if(array_key_exists('title',$SF_commands) and $title)
    {     
     $output = $output.'<h1>'.$SF_commands['title'].'</h1>';
    }*/
  $output = $output.$Parsedown->text($md);
  $output = $output.'</div><div style="padding: 10px; margin-top: 25px;">';
  if($SF_commands['refurl'])
                  {
                    include_once($SF_modulesdrivepath.'extras/urlmetapreview.php');
                    $output = $output."\n".'<div class="linkcard">';
                  if(($res = SF_GenerateMetapreview($SF_commands['refurl'],false)) == false)
                     {
                        $output = $output.'<p style="color: #FF0000;">Error:[Preview Meta lookup failed]</p>';
                        $output = $output.'<p><a href="'.$SF_moduleswebpath.'extras/urlmetapreview.php?'.$SF_commands['refurl'].'">[Check]</a><p>';
                     }
                  else
                     {
                        $output = $output.'<p><img src="'.$res['image'].'" width="200"/></p>';
                        $output = $output.'<p>'.$res['title']."</p>";
                        $output = $output.'<p>[<a href="'.$SF_commands['refurl'].'">Original</a>]<p>';
                     }
                    $output = $output.'</div>'."\n";
                  }
   
  $output = $output.'</div></div>';

  }

if($returncontents)
  {
    return (string) $output;
  }
else
  {
    echo $output;
    return true;
  }

}

/*
* given array of yaml strings containing 'field: value' yaml pairs
* return array keyed by 'field' containing 'value'
*/
function simpleyaml($inarray)
{
foreach($inarray as $key=>$value)
        {
        $item = explode(":",$value);

        $yaml[trim($item[0])]=trim($item[1]);
        if(array_key_exists(2,$item))
          {
          $yaml[trim($item[0])]=trim($item[1]).":".trim($item[2]);
          }
        }

return $yaml;
}




/**
* Generate a javascript encoded mailto link or a normal mailto link in the event scripting is turned off
*
* @param string emailaddress a valid email address
* 
* @param string the link text you want to appear in the 'a href'
* 
* @param string any classing (css) you may wish to apply to the a href
*/
function SF_GenerateEmailLink($emailaddress,$linktext,$class)
/****************************************************************************/
{
/* get the name - pre @ */
$result = preg_match("/^.*\@/",$emailaddress,$matches);
if(!$result)
{echo "[invalid email address]";return;}
$name = $matches[0];
$name=preg_replace("/\@/","",$name);
/* get the domain - pre @ */
$result = preg_match("/\@.*$/",$emailaddress,$matches);
if(!$result)
{echo "[invalid email address]";return;}
$domain = $matches[0];
$domain=preg_replace("/\@/","",$domain);

/* encode for name, domain and mailto: into html hex entity numbers */
$encname = str_convert_htmlentities($name);
$encdomain = str_convert_htmlentities($domain);
$mailto = str_convert_htmlentities("mailto:");

echo "
<SCRIPT LANGUAGE=\"javascript\">
// <!-- 
  var first = 'ma';
  var second = 'il';
  var third = 'to:';
  var fourth = '$encname';
  var fifth = '$encdomain';
  document.write('<a href=\"');
  document.write(first+second+third);
  document.write(fourth);
  document.write('&#64;');
  document.write(fifth);
  document.write('\"');
  "; 
if($class) echo "document.write('class=\"".$class."\"');";
echo "document.write('>'); 
  document.write('".$linktext."</a>');
// -->
</script>";
echo "<noscript><a href=\"".$mailto.$encname."&#64;".$encdomain."\"";
if($class) echo "class=\"".$class."\"";
echo ">".$linktext."</a></noscript>";
}


/**
* Converts a string to hex html entities
* 
* template desc
*
* @access private
*
*/
function str_convert_htmlentities ($str)
/****************************************************************************/
{
  $str = mb_convert_encoding($str , 'UTF-32', 'UTF-8');
  $t = unpack("N*", $str);
  $t = array_map(function($n) { return "&#$n;"; }, $t);
  return implode("", $t);
}


/**
* Gets the Section Title (if any) as per directory config (or fallback)
*
* template desc
*
* @access public
*
*/
function SF_GetSectionTitle()
/****************************************************************************/
{
global $dirconfigarray;

if(array_key_exists('sectionheading',$dirconfigarray))
  {
  return $dirconfigarray['sectionheading'];
  }
else
  {
  return;
  }
}



/**
* template title
*
* template desc
*
* @access public
*/
function SF_GetPageModifiedDate($filename='',$dateformat='jMY h:i')
/****************************************************************************/
{
global $SF_documentroot;
if($filename == '')
  {
   $filename=$SF_documentroot.$_SERVER['PHP_SELF'];
  }
return date($dateformat, filemtime($filename));
}



/**
* template title
*
* template desc
*
* @access public
*/
function SF_GetTextOnlyURL()
/****************************************************************************/
{
  global $textonlyqs;
  if(preg_match("@\?@",$_SERVER['REQUEST_URI']))
    {$sep='&amp;';}
  else
    {$sep='?';}
  return $_SERVER['REQUEST_URI'].$sep.$textonlyqs;
}



/**
* template title
*
* template desc
*
* @access public
*/
function SF_GetPrintURL()
/****************************************************************************/
{
global $printlayoutqs;
if(preg_match("@\?@",$_SERVER['REQUEST_URI']))
  {$sep='&amp;';}
else
  {$sep='?';}
return $_SERVER['REQUEST_URI'].$sep.$printlayoutqs;
}



/**
* template title
*
* template desc
*
* @access private
*/
function getcurrentpath()
/****************************************************************************/
{
global $sfdebug;
$currentpath = getpath($_SERVER['PHP_SELF']);
if($sfdebug)
  {SF_DebugMsg('getcurrentpath('.$currentpath.')'); }
return $currentpath;
}



/**
* template title
*
* template desc
*
* @access private
*/
function getpath($urlstring)
/****************************************************************************/
{
  $urlstring=trim($urlstring); 
  /*if we are at the root just return with root */
  if(!strcmp('/',$urlstring))
    {return $urlstring;}
  /* search from right of string and remove all characters back to the next right-most '/' - effect is clipping to path*/
  for($x=strlen($urlstring)-1; $x>=0 and $urlstring[$x] != '/'; $x--)
    {$urlstring[$x]=' ';}
  //if(strlen($urlstring) < 1){$urlstring='/';}
  return trim($urlstring);
}



/**
* template title
*
* template desc
*
* @access private
*/
function removetrailingslash($pathstring)
/****************************************************************************/
{
  $pathstringlength=strlen($pathstring)-1;
  if($pathstring[$pathstringlength] == '/')
    {
    $pathstring[$pathstringlength]=' ';
    }
 return trim($pathstring);
}



/**
* template title
*
* template desc
*
* @access private
*/
function removeleadingslash($pathstring)
/****************************************************************************/
{
  if($pathstring[0] == '/')
    {
    $pathstring[0]=' ';
    }
 return trim($pathstring);
}



/**
* template title
*
* template desc
*
* @access private
*/
function previousdir($pathstring)
/****************************************************************************/
{
  global $sfdebug;
  $pathstring=trim($pathstring); 
  /*if we are at the root just return with root */
  if(!strcmp('/',$pathstring))
    {return $pathstring;}
  /*if we are at the root , e.g. just a filename, just return with root */
  if(!stristr($pathstring,'/'))
    {return '/';}  
  /*remove initial trailing slash */
  $pathstring=removetrailingslash($pathstring);
  /* search from right of string and remove all characters back to the next right-most '/' - effect is clipping the path to previous directory*/
  for($x=strlen($pathstring)-1; $x>=0 and $pathstring[$x] != '/'; $x--)
    {
      $pathstring[$x]=' ';
    }
  $pathstring=trim($pathstring); /* remove whitepspace */
  return $pathstring;
}



/**
* template title
*
* template desc
*
* @access private
*/
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
     {
      $urlstring[$x]=' ';
     }
  return trim($urlstring);
}



/**
*  Create a useable file or HTTP reference from whatever we are passed
*
*
* @access private
* @param string the reference we want to normalise
* @param string the url we are currently at
*/
function sfnormaliseurl($url_ref,$url)
/****************************************************************************/
{
global $SF_defaultindexfile;
global $SF_documentroot;
global $SF_sitedrivepath;
$adjusted_url="";

$url=preg_replace("@$SF_defaultindexfile$@","",$url);
if(preg_match("@^http@",$url_ref))
  {
  $adjusted_url=$url_ref;
  }
else
  {
    if(preg_match("@^\/@",$url_ref))
    {  
      $adjusted_url=$SF_sitedrivepath.$url_ref;
    }
    else
    {
      if(preg_match("@^[0-9a-z]@i",$url_ref))
      {
        $adjusted_url=$SF_documentroot.getpath(preg_replace("@http:\/\/".$_SERVER['HTTP_HOST']."@","",$url)).$url_ref;
      }
      else
      {
      $thttphost='http://'.$_SERVER['HTTP_HOST'];
      $turl=preg_replace("@$thttphost@",'',getpath($url));
      $texurl=$url_ref;
        while(preg_match("@^\.\.\/@",$texurl))
            {
            $texurl=preg_replace("@^\.\.\/@","",$texurl);
            $turl=getpreviouspath($turl);
            }
      $adjusted_url=$SF_documentroot.$turl.$texurl;
      }
    }
  } /* end of else */

 $adjusted_url=preg_replace("@#.*$@","",$adjusted_url); /* remove anything on end of url after a # */
 $adjusted_url=preg_replace("@\?.*$@","",$adjusted_url); /* remove anything on end of url after a ? */

 return $adjusted_url; 
}



/**
* template title
*
* template desc
*
* @access private
*/
function configdataisincomplete()
/****************************************************************************/
{
  global $sfdebug;
  global $dirconfigarray;
  if($sfdebug >=3)
    {SF_DebugMsg('configdataisincomplete() - config currently is: '.print_r($dirconfigarray,true)); }
  if(array_key_exists('menudatafile',$dirconfigarray) and array_key_exists('cssfile',$dirconfigarray) and array_key_exists('headerfile',$dirconfigarray) and array_key_exists('footerfile',$dirconfigarray))
    {return false;}
  else
    {return true;}
  
}




/**
* template title
*
* template desc
*
* @access private
*/
function rearrangepagefortextonly($inputhtml)
/****************************************************************************/
{
$h=preg_match("@^.*<!-- SF_Command:content:begins -->@s",$inputhtml,$header);
$b=preg_match("@<!-- SF_Command:content:begins -->.*<!-- SF_Command:content:ends -->@s",$inputhtml,$body);
$f=preg_match("@<!-- SF_Command:content:ends -->.*$@s",$inputhtml,$footer);

/* if we get a sucessful transformation return it else return what we got in */
if($h==1 and $b==1 and $f==1)
  {return $body[0].$header[0].$footer[0];}
else
  {return $inputhtml;}  
}


/**
* Generate text only html from input html
*
* removes tables, images and css refs
*
* @access public
*/
function SF_GenerateTextOnlyHTML($url,$output=true)
/****************************************************************************/
{
  global $defaulttextonlycssfile;

  $search = array(
                  "@<table.*?>|</table>|<tr.*?>|</tr>|<td.*?>|</td>|<hr.*?>|<link.*?>|<style.*?>|</style>|<center.*?>|</center>@i",
                  "@(<img.+alt=)(\"[^<].+?\")([^<].*?>)@i", /* replace img's with alt text first */
                  "@(<img[^<].+?>)@i" /* then those without (which wouldn't have s&r'd by previous) */
                 );
  $replace = array(
                   "",
                   "\nImage[$2]<br/>\n",
                   "\nImage[no alt text]<br/>\n"
                   );

 if(!$contents = @file_get_contents($url))
 {
    $resulthtml = 'ERROR: URL File Open not allowed (allow_url_fopen=0)';
    if($output)
      {echo $resulthtml;}
    else
      {return $resulthtml;}
  }
  $resulthtml=preg_replace($search,$replace,$contents);
  /* this is all a bit of a kludge but put a CSS back into the html */
  $resulthtml=preg_replace("/<head>/",'<head><link href="'.$defaulttextonlycssfile.'" rel="stylesheet" title="SF_CSS" type="text/css">',$resulthtml);
  $resulthtml=rewriteurlsfortextonly($resulthtml);
  $resulthtml=rearrangepagefortextonly($resulthtml);

  if($output)
   {echo $resulthtml;}
  else
   {return $resulthtml;}
}


/**
* template title
*
* template desc
*
* @access private
*/
function rewriteurlsfortextonly($inputhtml)
/****************************************************************************/
{
global $textonlyqs;

$search = array(
                "@<a href=@",   
                "@(<a href=\")([^\"][0-9\.\/a-zA-Z]+?)(\".*>)@i", /*except those beginning with h(ttp) */
                );
$replace = array(
                 "\n<a href=",   /* force each new href to be on a newline */
                 "$1$2?$textonlyqs$3",    /* now append onto our urls */
                 );
$resulthtml=preg_replace($search,$replace,$inputhtml);
return $resulthtml;
}

?>
