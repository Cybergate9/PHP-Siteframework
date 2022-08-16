<?php
/**
* This file is Siteframework's main module
* !!!Important!!!
* this module should never output anything itself when in production
* Note: Shouldn't need to change values in here, default settings for SF in:
* 1) SF_mainconfig.php - for SF directory paths etc
* 2) SF_localconfig.php - intended to be the file changed on a per installation/host basis
* 3) SF_config_site.csv (csv text file) for defining site and subsite 'config_dir' files
* 4) SF_config_dir.csv (csv text file) for per directory configuration (menu, css, header, footer)
* 5) SF_config_menu.csv (csv text file) for menu configuration data
*
* CHANGELOG in SF_changelog.md
*
* @author Shaun Osborne (webmaster@cybergate9.net)
*
* @link https://github.com/Cybergate9/PHP-Siteframework
*
* @copyright Shaun Osborne, 2005-present
* @license https://github.com/Cybergate9/PHP-Siteframework/blob/master/LICENSE
*
* @version as per $sfversion in SF_mainconfig.php
*/

// bring in global paths and default values for variables, order is important
if (! isset($sfdebug)) {
    $sfdebug = 0;
}
require 'SF_localconfig.php';
require 'SF_mainconfig.php';

/* inclusions for markdown and previews */
require $SF_modulesdrivepath.'vendor/erusev/parsedown/Parsedown.php';
require $SF_modulesdrivepath.'vendor/erusev/parsedown-extra/ParsedownExtra.php';
require $SF_modulesdrivepath.'SF_urlpreview.php';

/****************************************************************************
Global variables */
$currentmenuarray = [];
$menudataarray = [];
$menuitemidentifier = '0';  /*don't change this starting value*/
$menuitemparent = ' ';      /*don't change this starting value*/
$menuitemtitle = '';
$dirconfigarray = [];
$siteconfigarray = [];
$SF_commands = [];
$textonlyqs = 'sf_function=textonly'; /* query string to append to get textonly version */
$printlayoutqs = 'sf_function=print'; /* query string to append to get print layout version */
/* Global Constants */
/*GetPageTitle (GPT) constants */
define('GPT_PAGE', 0);
define('GPT_SITEnPAGE', 1);
define('GPT_BREADCRUMB', 2);
define('GPT_SITEnBREADCRUMB', 3);
define('GPT_SITE', 4);

/*************  BEGINNING OF SF_mainmodule.php 'main()  ****/

if ($sfdebug >= 1) {
    SF_DebugMsg($SF_modulesdrivepath.'SF_mainmodule.php, Version: ['.$sfversion.'] loaded');
}
SF_PageInitialise();

/*************  END OF SF_mainmodule.php 'main()'   *****/

/**
 * This (based on the page which called it) initialises everything for Siteframework
 * It calls:
 * SF_LoadSiteConfigData() (which determines file for SF_LoadDirConfigData())
 * SF_LoadDirConfigData() (which determines menudata, css, header and footer to load)
 * SF_LoadMenuData() to initialise everything for this page (menu wise)
 * Also
 *
 * @see SF_LoadMenuData
 * @see SF_LoadDirConfigData
 * @see SF_LoadSiteConfigData
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
    $filelinesarray = [];
    $datafile = $defaultsiteconfigfile;
    if (count($siteconfigarray) >= 1) {
        if ($sfdebug >= 1) {
            SF_DebugMsg('WARNING: SF_LoadSiteConfigData() has already run - skipping');
        }

        return;
    }
    $adjustedSFsitewebpath = removeleadingslash($SF_sitewebpath);
    $currentpath = getpath(preg_replace("~$adjustedSFsitewebpath~i", '', $_SERVER['PHP_SELF']));
    $SF_phpselfpathdrivepath = $currentpath;
    if ($sfdebug >= 1) {
        SF_DebugMsg('SF_LoadSiteConfigData(SF_documentroot:['.$SF_documentroot.'])');
        SF_DebugMsg('SF_LoadSiteConfigData(SF_sitewebpath:['.$SF_sitewebpath.'])');
        SF_DebugMsg('SF_LoadSiteConfigData(currentpath:'.$currentpath.' (will match '.$currentpath.' or '.removeleadingslash($currentpath).'), DATAFILE:'.$datafile.')');
    }
    $filelinesarray = file($datafile);
    if (! $filelinesarray) {
        SF_ErrorExit('SF_LoadSiteConfigData()', 'no data from file '.$datafile);
    }
    $atroot = false;
    while (! isset($siteconfigarray['dirconfigfile']) and $atroot == false) {
        foreach ($filelinesarray as $line) {
            $line = str_replace('"', '', $line);  /* remove any "'s from data */
            $values = explode(',', $line);
            if (count($values) <= 1) {
                continue;
            } //skip incomplete lines
            if ((('/' <=> $values[0][0]) == 0)) /* if path begins with a forward slash */
                  {$comparepath = $values[0]; }      /* just use it */
            else {
                $comparepath = '/'.$values[0];
            }  /* else add the forward slash */
            if ($sfdebug >= 3) {
                SF_DebugMsg('SF_LoadSiteConfigData(COMPARE: Config:['.$comparepath.'] Current Path:['.$currentpath.']');
            }
            if ((($comparepath <=> $currentpath) == 0)) {
                if ($sfdebug >= 2) {
                    SF_DebugMsg('SF_LoadSiteConfigData(MATCHED: Config:['.$comparepath.'] Current Path:['.$currentpath.']');
                }
                if (trim($values[1]) != '') {
                    if ($values[1][0] == '/') {
                        $siteconfigarray['dirconfigfile'] = $SF_sitedrivepath.trim($values[1]);
                        $siteconfigarray['dirconfigpath'] = '';
                    } else {
                        $siteconfigarray['dirconfigpath'] = removeleadingslash($currentpath);
                        $siteconfigarray['dirconfigfile'] = $SF_sitedrivepath.$siteconfigarray['dirconfigpath'].removeleadingslash($values[1]);
                    }
                }
                break;
            }
        } /* end of foreach */
        if ($currentpath == '/') {
            $atroot = true;
        }
        $currentpath = previousdir($currentpath);
    } /* end of while */

    /* if we've got back to root (i.e. we're here) and some values have not been set then use global defaults */
    if (! isset($siteconfigarray['dirconfigfile'])) {
        $siteconfigarray['dirconfigfile'] = $defaultdirconfigfile;
        $siteconfigarray['dirconfigpath'] = '';
    }
    $SF_subsitewebpath = $SF_sitewebpath.$siteconfigarray['dirconfigpath'];
    $SF_subsitedrivepath = $SF_sitedrivepath.$siteconfigarray['dirconfigpath'];
    if ($sfdebug >= 1) {
        SF_DebugMsg('SF_LoadSiteConfigData('.print_r($siteconfigarray, true).')');
    }
}

/**
 * Gets the settings for menu, css, header and footer files
 *
 * Load the dir_config file (determined by SF_LoadSiteConfigData()) and based
 * on our current path extracts the appropriate config_menu, css , header and
 * footer filenames.
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
    $filelinesarray = [];

    if (count($dirconfigarray) >= 1) {
        if ($sfdebug >= 1) {
            SF_DebugMsg('WARNING: SF_LoadDirConfigData() has already run - skipping');
        }

        return;
    }
    if (isset($siteconfigarray['dirconfigfile'])) {
        $datafile = $siteconfigarray['dirconfigfile'];
    } else {
        $datafile = $defaultdirconfigfile;
    }
    $adjustedSFsitewebpath = removeleadingslash($SF_sitewebpath);
    $currentpath = getpath(preg_replace("~$adjustedSFsitewebpath~i", '', $_SERVER['PHP_SELF']));
    if ($sfdebug >= 1) {
        SF_DebugMsg('SF_LoadDirConfigData(currentpath:'.$currentpath.' (will match '.$currentpath.' or '.removeleadingslash($currentpath).'), DATAFILE:'.$datafile.')');
    }
    $filelinesarray = file($datafile);
    if (! $filelinesarray) {
        SF_ErrorExit('SF_LoadDirConfigData()', 'no data from file '.$datafile);
    }
    $atroot = false;
    while (configdataisincomplete() and $atroot == false) {
        foreach ($filelinesarray as $line) {
            $line = str_replace('"', '', $line);  /* remove any "'s from data */
            $values = explode(',', $line);
            if (count($values) <= 1) {
                continue;
            } //skip incomplete lines
            foreach ($values as $key=>$junk) {
                $values[$key] = trim($values[$key]);
            }
            if ((('/' <=> $values[0][0]) == 0)) /* if path begins with a forward slash */
                  {$comparepath = $values[0]; }      /* just use it */
            else {  /* else add the forward slash */
                $comparepath = '/'.$siteconfigarray['dirconfigpath'].$values[0];
            }
            if ($sfdebug >= 3) {
                SF_DebugMsg('SF_LoadDirConfigData(COMPARE: currentpath:['.$currentpath.'] comparepath:['.$comparepath.']');
            }
            if ((($comparepath <=> $currentpath) == 0)) {
                if ($sfdebug >= 2) {
                    SF_DebugMsg('SF_LoadConfigData(MATCHED: Config:['.$comparepath.'] Current Path:['.$currentpath.']');
                }
                if ($values[1] != '' and ! isset($dirconfigarray['menudatafile'])) {
                    if ($values[1][0] == '/') {
                        $dirconfigarray['menudatafile'] = $SF_sitedrivepath.$values[1];
                    } else {
                        $dirconfigarray['menudatafile'] = $SF_sitedrivepath.$siteconfigarray['dirconfigpath'].removeleadingslash($values[1]);
                    }
                }
                if ($values[2] != '' and ! isset($dirconfigarray['menu'])) {
                    $dirconfigarray['menu'] = $values[2];
                }
                if (trim($values[3]) != '' and ! isset($dirconfigarray['menukey'])) {
                    $dirconfigarray['menukey'] = $values[3];
                }
                if (trim($values[4]) != '' and ! isset($dirconfigarray['cssfile'])) {
                    if ($values[4][0] == '/') {
                        $dirconfigarray['cssfile'] = $SF_sitedrivepath.$values[4];
                    } else {
                        $dirconfigarray['cssfile'] = $SF_sitewebpath.$siteconfigarray['dirconfigpath'].removeleadingslash($values[4]);
                    }
                }
                if ($values[5] != '' and ! isset($dirconfigarray['headerfile'])) {
                    if ($values[5][0] == '/') {
                        $dirconfigarray['headerfile'] = $SF_sitedrivepath.$values[5];
                    } else {
                        $dirconfigarray['headerfile'] = $SF_sitedrivepath.$siteconfigarray['dirconfigpath'].removeleadingslash($values[5]);
                    }
                }
                if ($values[6] != '' and ! isset($dirconfigarray['footerfile'])) {
                    if ($values[6][0] == '/') {
                        $dirconfigarray['footerfile'] = $SF_sitedrivepath.$values[6];
                    } else {
                        $dirconfigarray['footerfile'] = $SF_sitedrivepath.$siteconfigarray['dirconfigpath'].removeleadingslash($values[6]);
                    }
                }
                if ($values[7] != '' and ! isset($dirconfigarray['contentpp'])) {
                    $dirconfigarray['contentpp'] = strtolower($values[7]);
                }
                if ($values[8] != '' and ! isset($dirconfigarray['sectionheadingttext'])) {
                    $dirconfigarray['sectionheading'] = $values[8];
                }
                if ($values[9] != '' and ! isset($dirconfigarray['custom1'])) {
                    $dirconfigarray['custom1'] = $values[9];
                }
                if ($values[10] != '' and ! isset($dirconfigarray['custom2'])) {
                    $dirconfigarray['custom2'] = $values[10];
                }
                if ($values[11] != '' and ! isset($dirconfigarray['custom3'])) {
                    $dirconfigarray['custom3'] = $values[11];
                }
                break;
            }
        }
        if ($currentpath == '/') {
            $atroot = true;
        }
        $currentpath = previousdir($currentpath);
    }
    /* if weve got back to root (i.e here) and some values have not been set then use global defaults */
    if (! isset($dirconfigarray['menudatafile'])) {
        $dirconfigarray['menudatafile'] = $defaultmenudatafile;
    }
    if (! isset($dirconfigarray['cssfile'])) {
        $dirconfigarray['cssfile'] = $defaultcssfile;
    }
    if (! isset($dirconfigarray['headerfile'])) {
        $dirconfigarray['headerfile'] = $defaultheaderfile;
    }
    if (! isset($dirconfigarray['footerfile'])) {
        $dirconfigarray['footerfile'] = $defaultfooterfile;
    }

    if ($sfdebug >= 1) {
        SF_DebugMsg('SF_LoadDirConfigData('.print_r($dirconfigarray, true).')');
    }
}

/**
 * Loads the menu data for the page we are on
 *
 * Load the menu_config file (determined by SF_LoadDirConfigData()) and based
 * on our current path figure out what menu item we are on.  Popoulates two
 * globals arrays; $menudataarray (contains the whole menu data file) and
 * contains a sort sorted list of current menu items to display
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

    $datafile = $dirconfigarray['menudatafile'];
    $menu = $dirconfigarray['menu'];
    $menukey = $dirconfigarray['menukey'];

    if (count($menudataarray) >= 1) {
        if ($sfdebug >= 1) {
            SF_DebugMsg('WARNING: SF_LoadMenuData() has already run - skipping');
        }

        return;
    }

    if ($sfdebug >= 1) {
        SF_DebugMsg("SF_LoadMenuData($datafile,$menu,$menukey)");
    }

    $filelinesarray = file($datafile);
    if (! $filelinesarray) {
        SF_ErrorExit('SF_LoadMenuData()', 'no data from menu file '.$datafile);
    }

    /*get the toplevel and requested level values into new array keyed by order value from csv file*/
    $filelinesarray = str_replace('"', '', $filelinesarray);  /* remove any "'s from csv data */
    $menudataarray = $filelinesarray;
    array_shift($menudataarray); /* remove the header line from array*/

    foreach ($menudataarray as $key=>$item) {
        $item = str_replace(',/', ',', $item); /* this is a bit of a cludge to remove leading slashes in paths */
        if ($sfdebug >= 2) {
            SF_DebugMsg("SF_LoadMenuData($key, $item)");
        }
        $subitem = explode(',', trim($item));
        if (count($subitem) <= 1) {
            continue;
        } //skip incomplete lines
        $sit0 = trim($subitem[0]);
        $sit1 = trim($subitem[1]);
        if ((($menu <=> $sit0) == 0) and ((($menutoplevelidentifier <=> $sit1) == 0) or (($menukey <=> $sit1) == 0))) {
            if ((('' <=> $subitem[3]) == 0)) {
                $cmkey = $key;
            } else {
                $cmkey = $subitem[3];
            } /* this covers if no ordering info given */
            $currentmenuarray[$cmkey] = $subitem;
        }
    }

    /*sort the currentmenuarray on keys (order value)*/
    ksort($currentmenuarray, SORT_STRING);

    $tpath = $SF_sitewebpath.$siteconfigarray['dirconfigpath'];
    $currentpath = preg_replace("@^$tpath@i", '', $_SERVER['PHP_SELF']);
    if ($sfdebug >= 2) {
        SF_DebugMsg('SF_LoadMenuData(currentpath:'.$currentpath.')');
    }
    /*find what order number we are at e.g. 1, 1.1, 1.3.1
    first pass, this will match exacts or dir+index.htm(l)'s or dir+filename+.anything.htm(l)*/
    foreach ($currentmenuarray as $item) {
        /* if we get a direct match, or match on value+index.html, or match on altered path
        e.g gettinghere.2.html becomes gettinghere.html
        then remember it as the current item */
        $item[4] = trim($item[4]);
        if (! strcasecmp($currentpath, $item[4]) or ! strcasecmp($currentpath, $item[4].$SF_defaultindexfile) or ! strcasecmp(preg_replace("/\.[0-9a-zA-Z].*\.htm/", '.htm', $currentpath), $item[4])) {
            $menuitemidentifier = $item[3];
            $menuitemtitle = $item[2];
        }
    }

    /*second pass if nothing from first - on just paths and keeping walking back until we will either get a match, find root, of have exhausted possibilities, cx provides the safety on while() loop */
    $tpath = getpath($currentpath);
    $cx = 0;
    while ($menuitemidentifier == '0' and (($tpath <=> $SF_sitewebpath) != 0) and $cx <= count($menudataarray)) {
        if ($sfdebug >= 3) {
            SF_DebugMsg('SF_LoadMenuData(comparing with tpath='.$tpath.', & $SF_sitewebpath='.$SF_sitewebpath.')');
        }
        foreach ($currentmenuarray as $item) {
            if ($sfdebug >= 3) {
                SF_DebugMsg('SF_LoadMenuData(tpath:'.$tpath.', getpath(menudataitem):'.getpath(trim($item[4])).')');
            }
            if (($tpath <=> (getpath(trim($item[4])))) == 0) {
                $menuitemidentifier = trim($item[3]);
                $menuitemtitle = trim($item[2]);
                break; /*stop when we find the first one*/
            }
        }
        $tpath = previousdir($tpath);
        $cx++;
        if ($sfdebug >= 3) {
            SF_DebugMsg('SF_LoadMenuData(count($menudataarray):'.count($menudataarray).', $cx:'.$cx.')');
        }
    }

    $tempmii = $menuitemidentifier;
    if (preg_match("/\./", $tempmii)) {
        /* strip numbers from the right if any ie 1.1 becomes 1*/
        for ($x = strlen($tempmii) - 1; $tempmii[$x] != '.' and $x >= 0; $x--) {
            $tempmii[$x] = ' ';
        }
        $tempmii[$x++] = ' '; //strip decimal ie 1. becomes 1
        $menuitemparent = trim($tempmii); //trim off spaces
    }

    if ($sfdebug >= 1) {
        SF_DebugMsg("SF_LoadMenuData(has chosen MENUID:$menuitemidentifier, TITLE:$menuitemtitle PARENTID: $menuitemparent)");
        foreach ($menudataarray as $key=>$item) {
            $itemvals = explode(',', $item);
            foreach ($menudataarray as $ckey=>$compareitem) {
                $comparevals = explode(',', $compareitem);
                if ($ckey != $key and (($comparevals[3] <=> $itemvals[3]) == 0)) {
                    SF_DebugMsg("SF_LoadMenuData(<b>***WARNING***</b>: Duplicate menu ID: [$itemvals[3]] (line:$key) and $comparevals[3](line:$ckey)");
                }
            }
        }
    }
}

/**
 * Ouput the HTML for the current menu
 *
 * Use global array $currentmenuarray to ouput the currently selected menu set
 * as was determined by SF_LoadMenuData().
 * Menu block is surrounded by a <div id="SF_menuarea" class="SF_menuarea">
 * Menu level 1's are tagged as <p class="SF_menu_level_1">
 * Menu level 2's are tagged as <p class="SF_menu_level_2">
 *
 * @see SF_LoadMenuData()
 *
 * @param bool mixed controls whether we tag what menuitem is selected true=on [default], false=off (CSS=SF_menu_level_1_highlight and SF_menu_level_2_highlight)
 * @param bool controls whether we tag items that are off site links (start with http://), true=on [default], false=off (CSS=SF_offsite_link)
 * @param int control whether to show all menu levels (0), only menu level 1's (1) or only menu level 2's (2)
 */
function SF_GenerateNavigationMenu($menuhighlight = true, $dooffsitelinktags = true, $showonlylevel = 0, $separator = '')
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

    //get the values we want from global config
    foreach ($dirconfigarray as $key=>$value) {
        switch ($key) {
                  case 'menukey':
                                $menukey = $value;
                                 break;
                  case 'menu':
                                $menu = $value;
                                 break;
                  default:
                                break;
                }
    }

    if ($sfdebug >= 3) {
        SF_DebugMsg("SF_GenerateNavigationMenu($menu,$menukey,hl:$menuhighlight,mi:$menuitemidentifier)<br/>");
    }

    // create navigation menu div
    if ($showonlylevel == 0) {
        echo '<div id="SF_menuarea" class="SF_menuarea">';
    } elseif ($showonlylevel == 1) {
        echo '<div id="SF_menuarea1" class="SF_menuarea1">';
    } elseif ($showonlylevel == 2) {
        echo '<div id="SF_menuarea2" class="SF_menuarea2">';
    }

    //print out the menu from the global array
    foreach ($currentmenuarray as $key=>$item) {
        if (($menutoplevelidentifier <=> $item[1]) == 0) {
            if ((! strcmp($menuitemidentifier, $item[3]) or ! strcmp($menuitemparent, $item[3])) and $menuhighlight) {
                $cssclass = 'SF_menu_level_1_highlight';
            } else {
                $cssclass = 'SF_menu_level_1';
            }
        } else {
            if ((($menuitemidentifier <=> $item[3]) == 0) and $menuhighlight) {
                $cssclass = 'SF_menu_level_2_highlight';
            } else {
                $cssclass = 'SF_menu_level_2';
            }
        }
        if (preg_match("/^http:\/\/.*/", $item[4]) and $dooffsitelinktags) {
            $menuitemhtml = '<p class="'.$cssclass.'"><span class="SF_offsite_link"><a href="'.$item[4].'" target="_blank">'.htmlspecialchars($item[2]).'</a></span>'.$separator."</p>\n";
        } else {
            $menuitemhtml = '<p class="'.$cssclass.'">';
            if ($key != 0) {
                $menuitemhtml = $menuitemhtml.$separator;
            }
            $menuitemhtml = $menuitemhtml.'<a href="'.$SF_sitewebpath.$siteconfigarray['dirconfigpath'].$item[4].'">'.htmlspecialchars($item[2])."</a></p>\n";
        }
        /* deal with $showonlylevels */
        if ($showonlylevel == 0) {
            echo $menuitemhtml;
        } elseif ($showonlylevel == 1 and (($menutoplevelidentifier <=> $item[1]) == 0)) {
            echo $menuitemhtml;
        } elseif ($showonlylevel == 2 and (($menutoplevelidentifier <=> $item[1]) != 0)) {
            echo $menuitemhtml;
        }
    } /* end of foreach() */
    /* end SF_menuarea div */
    echo '</div>';
}

/**
 * Output breadcrumb html for current page
 * CSS styles are: SF_breadcrumbarea, SF_breadcrumb_line, SF_breadcrumb_title and SF_breadcrumb_item
 *
 * @param  string  $breadcrumbleadtext the lead text for the breadcrumbline
 * @param  string  $breadcrumbseparator  the separator between each breadcrumb item
 * @param  bool  $output output (true) html or return result from function as string (false)
 */
function SF_GenerateBreadCrumbLine($breadcrumbleadtext = 'You are in: ', $breadcrumbseparator = ' > ', $output = true, $displayhome = true, $limitchars = 200)
/****************************************************************************/
{
    global $SF_modulesdrivepath;
    global $sfdebug;
    global $SF_sitewebpath;
    global $siteconfigarray;
    global $menuitemidentifier;
    global $menudataarray;
    global $menutoplevelidentifier;
    $breadcrumbs = [];

    //take a copy of this global
    $tempmii = $menuitemidentifier;

    //do sublevels eg 1.1 10.3.1 etc
    while (strstr('.', $tempmii)) {
        foreach ($menudataarray as $item) {
            $subitem = explode(',', trim($item));
            if (count($subitem) <= 1) {
                continue;
            } //skip incomplete lines
            if (($tempmii <=> $subitem[3]) == 0) {
                $breadcrumbs[$subitem[3]] = $subitem;
            }
        }
        /* strip numbers from the right if any ie 1.1 becomes 1*/
        for ($x = strlen($tempmii) - 1; $tempmii[$x] != '.' and $x >= 0; $x--) {
            $tempmii[$x] = ' ';
        }
        $tempmii[$x++] = ' '; //strip decimal ie 1. becomes 1
      $tempmii = trim($tempmii); //trim off spaces
    } /* end of while() */

    //do top level e.g 1-xx
    foreach ($menudataarray as $item) {
        $subitem = explode(',', trim($item));
        if (count($subitem) <= 1) {
            continue;
        } //skip incomplete lines
        if (($tempmii <=> $subitem[3]) == 0) {
            $breadcrumbs[$subitem[3]] = $subitem;
        }
    }

    //do home e.g 0
    foreach ($menudataarray as $item) {
        $subitem = explode(',', trim($item));
        if (count($subitem) <= 1) {
            continue;
        } //skip incomplete lines
        if (('0' <=> $subitem[3]) == 0) {
            $breadcrumbs[$subitem[3]] = $subitem;
        }
    }

    //sort the breadcrumbs back into order
    ksort($breadcrumbs, SORT_STRING);

    //print out the menu in the newly sorted order
    if ($output) {
        if ($sfdebug >= 3) {
            SF_DebugMsg('SF_GenerateBreadCrumbLine(mi:'.$menuitemidentifier.')');
        }
        echo '<div id="SF_breadcrumbarea" class="SF_breadcrumbarea"><p class="SF_breadcrumb_line">';
        echo '<span class="SF_breadcrumb_title">'.$breadcrumbleadtext.'</span>';
        $x = 1;
        $charcount = 0;
        foreach ($breadcrumbs as $key=>$item) {
            if ($x > 1 and $x <= count($breadcrumbs)) {
                echo $breadcrumbseparator;
            }
            if ($key == 0 and count($breadcrumbs) > 1 and $displayhome == false) {
                continue;
            }
            $charcount = $charcount + strlen(htmlspecialchars($item[2]));
            if ($charcount > $limitchars) {
                echo ' ...';
                break;
            }
            echo '<span class="SF_breadcrumb_item">';
            echo '<a href="'.$SF_sitewebpath.$siteconfigarray['dirconfigpath'].$item[4].'">'.htmlspecialchars($item[2]).'</a>';
            echo '</span>';
            $x++;
        }
        echo '</p></div>';
    } else /* if the $output was FALSE */
    {
      $breadcrumbstring = '';
      $x = 1;
      foreach ($breadcrumbs as $key=>$item) {
          $breadcrumbstring = $breadcrumbstring.htmlspecialchars($item[2]);
          if ($x++ < count($breadcrumbs)) {
              $breadcrumbstring = $breadcrumbstring.$breadcrumbseparator;
          }
      }

    return $breadcrumbstring;
    }
}

/**
 * returns a string representing the current page
 * Argument is following types:
 * GPT_PAGE = Current Page Title (as determined via menu data)
 * GPT_SITEnPAGE = $SFsitetitle + Current Page Title
 * GPT_BREADCRUMB = current breadcrumb (as determined via menu data) line separated by '|'s
 * GPT_SITE+BREADCRUMB = $SFsitetitle + current breadcrumb
 *
 * @param int GPT_PAGE, GPT_SITEnPAGE, GPT_BREADCRUMB, GPT_SITE+BREADCRUMB
 */
function SF_GetPageTitle($titletype = GPT_BREADCRUMB)
/****************************************************************************/
{
    global $menuitemtitle;
    global $SF_sitetitle;
    global $SF_commands;
    $pagetitle = '';
    if (isset($SF_commands['title'])) {
        $pagetitle = $SF_commands['title'];
    }
    switch ($titletype) {
      case GPT_PAGE:
                    return $menuitemtitle;
                    break;
      case GPT_SITEnPAGE:
                    return $SF_sitetitle.': '.$menuitemtitle.': '.$pagetitle;
                    break;
      case GPT_BREADCRUMB:
                    return SF_GenerateBreadCrumbLine('', ' | ', false);
                    break;
      case GPT_SITEnBREADCRUMB:
                    return $SF_sitetitle.': '.SF_GenerateBreadCrumbLine('', ' | ', false);
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
 */
function SF_GetCSSFilename()
/****************************************************************************/
{
    global $sfdebug;
    global $dirconfigarray;
    //get the css value from dir config array */
    if ($sfdebug >= 1) {
        SF_DebugMsg('SF_GetCSSFilename('.$dirconfigarray['cssfile'].')');
    }

    return $dirconfigarray['cssfile'];
}

/**
 * Returns CSS path and file name of SF's default CSS file
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
 * @param int  specifies how many levels to show (1-6) e.g. two will show sitemap level 1 and 2
 * @param string provides a regex match for what menulevels to display
 */
function SF_GenerateSiteMap($showlevels = false, $levelstoshow = 6, $displaylevelmatch = '.*')
/*****************************************************************************/
{
    global $SF_modulesdrivepath;
    global $sfdebug;
    global $SF_sitewebpath;
    global $menuitemidentifier;
    global $menudataarray;
    global $menutoplevelidentifier;
    global $siteconfigarray;
    $sitemaparray = [];
    //tidy up inbound
    if ($levelstoshow == null) {
        $levelstoshow = 6;
    }
    if ($displaylevelmatch == null) {
        $displaylevelmatch = '.*';
    }
    if ($sfdebug >= 3) {
        SF_DebugMsg('SF_GenerateSiteMap()');
    }
    //take a copy of this global
    $tempmii = $menuitemidentifier;
    foreach ($menudataarray as $item) {
        $subitem = explode(',', trim($item));
        if (count($subitem) <= 1) {
            continue;
        } //skip incomplete lines
        $sitemaparray[$subitem[3]] = $item;
    }
    ksort($sitemaparray, SORT_STRING);
    echo '<div id="SF_sitemaparea" class="SF_sitemaparea">';
    foreach ($sitemaparray as $item) {
        $subitem = explode(',', trim($item));
        switch (preg_match_all("/\./", $subitem[3], $dontcarearray)) {
                case 0:
                       $cssclass = 'SF_map_level_1';
                       $currentlevel = 1;
                       break;
                case 1:
                       $cssclass = 'SF_map_level_2';
                       $currentlevel = 2;
                       break;
                case 2:
                       $cssclass = 'SF_map_level_3';
                       $currentlevel = 3;
                       break;
                case 3:
                       $cssclass = 'SF_map_level_4';
                       $currentlevel = 4;
                       break;
                case 4:
                       $cssclass = 'SF_map_level_5';
                       $currentlevel = 5;
                       break;
                case 5:
                       $cssclass = 'SF_map_level_6';
                       $currentlevel = 6;
                       break;
              }
        /* now output this item classed correctly */
        if ($currentlevel <= $levelstoshow) {
            if (preg_match("@$displaylevelmatch@", $subitem[3])) {
                echo '<p class="'.$cssclass.'">';
                if (preg_match("@^http:\/\/.*@", $subitem[4])) {
                    echo '<a href="'.$subitem[4].'">';
                } else {
                    echo '<a href="'.$SF_sitewebpath.$siteconfigarray['dirconfigpath'].$subitem[4].'">';
                }
                echo $subitem[2].'</a>';
                if ($showlevels == true) {
                    echo ' ('.$subitem[3].')';
                }
                echo "</p>\n";
            }
        }
    } /* end of foreach() */
    echo '</div>';
}

/**
 * Global error exit function for Siteframework
 * Outputs CSS link, Error text supplied and does a hard exit
 *
 * @param string intended to identify who called the exit, file or function
 * @param string error message you want to output with the exit
 */
function SF_ErrorExit($caller = 'nocaller', $msg = 'nomsg')
/****************************************************************************/
{
    global $SF_moduleswebpath;
    echo '<link href="'.SF_GetDefaultCSSFilename().'" rel="stylesheet" type="text/css">';
    echo '<br/><p class="SF_error_text">SF Fatal Error: from=['.$caller.']<br/>error=['.$msg.']</p><br/>';
    exit;
}

/**
 * Global debug message function for Siteframework
 * Outputs CSS link, and debug text you supply
 *
 * @param string debug message you want to output
 */
function SF_DebugMsg($msg = 'nomsg')
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
 * http:// just get it
 * anything else assume some sort of relative path so convert it
 * to an absolute file reference starting at webserver DOCUMENT_ROOT
 * so these should all be ok:
 * file.html (reference in current dir)
 * ../about/index.html (some sort of relative reference)
 * /about/index.html (absolute reference (from root) on this server)
 *
 * @param string URL you want to get
 */
function SF_GenerateContentFromURL($url, $returncontents = false)
/****************************************************************************/
{
    global $SF_sitedrivepath;
    global $SF_commands;

    /* figure out if this is a http (get it) or fix the path up for getting off the local filesystem */
    $url = sfnormaliseurl($url, 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);

    $contents = file_get_contents($url);
    if (! $contents) {
        SF_ErrorExit('SF_generateContentFromURL', 'Failed to open file ['.$url.']');
    }
    /* process $SF_commands we find in $url */
    preg_match_all("@<\!-- SF_Command(.*?) -->@i", $contents, $matches);
    foreach ($matches[1] as $procentries) {
        $command = explode(':', trim($procentries));
        $SF_commands[$command[1]] = $command[2];
    }
    if (preg_match("/^http:\/\//", $url)) { // is this a url call?
        if (preg_match('@<!-- SF_Command:content:begins -->@', $contents)) { // does it contain framework content?
           $b = preg_match('@<!-- SF_Command:content:begins -->.*<!-- SF_Command:content:ends -->@s', $contents, $body); //get just the body
           if ($b == 1) {
               $contents = $body[0];
           } // if we got a good body, copy it into contents and that's what will be returned
        }
    }
    if ($returncontents) {
        return $contents;
    } else {
        echo $contents;
    }
}

/**
 * Gets the MARKDOWN contents and FRONT MATTER named file/URL and output it.
 * Passed a filename/URL it determines to:
 * http:// just get it
 * anything else assume some sort of relative path so convert it
 * to an absolute file reference starting at webserver DOCUMENT_ROOT
 * so these should all be ok:
 * file.md (reference in current dir)
 * ../about/index.md (some sort of relative reference)
 * /about/index.md (absolute reference (from root) on this server)
 *
 * @param string URL you want to get
 * @param output title as header1 true=yes, false=no
 * @param output only $summaryonly number of characters false=0 characters, 0=zero characters, any integer number of characters to output as summary
 * @param output a 'Read More' link false=no, true=yes
 * @param return value is contents of url, else return true;
 */
function SF_GeneratefromMarkdownURL($url, $title = true, $summaryonly = false, /*$returnreadmorelink = false,*/ $returncontents = false)
/****************************************************************************/
{
    global $SF_sitedrivepath;
    global $SF_modulesdrivepath;
    global $SF_moduleswebpath;
    global $SF_parsedownpath;
    global $SF_parsedownextrapath;
    global $SF_commands;

    $contents = $output = '';
    $contents = file_get_contents($url);
    if (! $contents) {
        return false;
    }
    // cut up *.md file into two parts based on front matter separators '---'
    $parts = preg_split('/[\n\r]*[-]{3}[\n\r]/', $contents, 3, PREG_SPLIT_NO_EMPTY);

    unset($SF_commands['refurl']);
    unset($SF_commands['summary']);
    if (count($parts) > 1) { // if we have front matter, process into $SF_commands
        $yaml = simpleyaml(explode("\n", $parts[0]));
        foreach ($yaml as $key=>$value) {
            $SF_commands[$key] = $value;
        }
        $md = $parts[1]; // and put remaining into markdown
    } else {
        $md = $parts[0];
    }
    //print_r($SF_commands);
    if (isset($SF_commands['summary']) and intval($SF_commands['summary']) < $summaryonly) {
        $summaryonly = intval($SF_commands['summary']);
    }
    $contents = $parts = '';  // free memory
    if (isset($SF_commands['shortcodes']) and ! ((($SF_commands['shortcodes'] <=> 'off') == 0) or (($SF_commands['shortcodes'] <=> 'no') == 0))) {
        $md = SF_ShortCodeProcessor($md);
    }
    if ($title and isset($SF_commands['title'])) {
        $output = $output.'<div class="card"><div class="card-content">';
        $output = $output.'<h1>'.$SF_commands['title'].'</h1>';
    }
    if (isset($SF_commands['date'])) {
        $output = $output.'<div class="dateby"> &#8880;  '.$SF_commands['date'];
        if (isset($SF_commands['author'])) {
            $output = $output.' by '.$SF_commands['author'];
        }
        $output = $output.'   &#8881; </div>';
    }
    $Parsedown = new ParsedownExtra();
    //$Parsedown->setMarkupEscaped(true);
    if ($summaryonly >= 1) {
        $snippet = $md;
        $snippet = preg_replace('/<a.[^<]*>/', '', $snippet); // get rid of image links in summaries
        $snippet = preg_replace('/<img.[^<]*>/', '', $snippet);  // get rid of hrefs in summaries
        $snippet = preg_replace('/<script.[^<]*>/', '', $snippet);  // get rid of script in summaries
        $snippet = preg_replace('/<figure.[^<]*>/', '', $snippet);  // get rid of figure refs in summaries
        if (strlen($snippet) <= $summaryonly) { //if summary is shorter than what weve got just use what weve got
            $summaryonly = strlen($snippet) - 1;
        }
        $snippet = substr($snippet, 0, $summaryonly);
        $output = $output.'<p>'.$snippet.'...</p>';
    } else {
        $output = $output.$Parsedown->text($md);
        if ($title and isset($SF_commands['title'])) { // close out card divs if we're doing a full page with title etc
            $output = $output.'</div>';
        }
        if ($SF_commands['refurl']) {
            $output = $output."\n".'<div class="card-urlpreview">';
            if (($res = SF_GenerateMetadataPreview($SF_commands['refurl'], false)) == false) {
                $urlparts = parse_url($SF_commands['refurl']);
                $output = $output.'<p>Error:[Preview lookup on '.$urlparts['host'].' failed]</p>';
                $output = $output.'<p><a href="'.$SF_moduleswebpath.'/SF_urlpreview.php?'.$SF_commands['refurl'].'">[Check]</a><p>';
            } else {
                $output = $output.'<p><img src="'.$res['image'].'" width="200"/></p>';
                $output = $output.'<p>'.$res['title'].'</p>';
                $output = $output.'<p>[<a href="'.$SF_commands['refurl'].'">Original</a>]<p>';
            }
            $output = $output.'</div>'."\n";
        }
    }
    if ($title and isset($SF_commands['title'])) { // close out card divs if we're doing a full page with title etc
        $output = $output.'</div>';
    }
    $contents = $parts = $snippet = $Parsedown = $md = ''; //clear memory explicitly
    if ($returncontents) {
        return (string) $output;
    } else {
        echo $output;

        return true;
    }
}

function SF_ShortCodeProcessor($string)
{
    global $sfdebug;
    global $SF_sitewebpath;
    global $SF_commands;
    preg_match_all('/{{(.*)}}/u', $string, $matches, PREG_SET_ORDER);
    if ($sfdebug >= 2) {
        echo '<pre>';
        print_r($matches);
        echo '</pre>';
    }
    //echo '<br/>$matches: ';var_dump($matches);
    foreach ($matches as $command) {
        //echo '<br/>$command: ';var_dump($command);
        $parts_array = []; // empty array each cycle
        $convertto = ''; // empty create string each cycle
        $commandparts = explode(';', $command[1]);
        foreach ($commandparts as $part) {
            $part = preg_replace("/https\:\/\//", '__HTTPS__', $part); /* protect URLs */
            $part_split = explode(':', $part);
            if (isset($part_split['1'])) {
                $parts_array[trim($part_split[0])] = preg_replace('/__HTTPS__/', 'https://', trim($part_split[1])); /* undo URL protect */
            } else {
                if (($part_split[0] <=> '') != 0) {
                    $parts_array[$part_split[0]] = '';
                }
            }
        }
        //echo '<br/>';var_dump($parts_array);
        if (isset($parts_array['func']) or isset($parts_array['f'])) {
            if (isset($parts_array['f'])) {
                $callfunc = 'scf_'.$parts_array['f'];
            } else {
                $callfunc = 'scf_'.$parts_array['func'];
            }
            if (function_exists($callfunc)) {
                $convertto = $callfunc($parts_array);
                $string = str_replace($command[0], $convertto, $string);
            } else {
                if (($SF_commands['shortcodes'] <=> 'quiet') != 0) {
                    echo '<br/>Warning: SF_ShortCodeProcessor(): no callable $callfunc for ('.str_replace('scf_', '', $callfunc).') e.g. no '.$callfunc.'();';
                }
            }
        }
    }
    $command = $parts_array = $matches = $commandparts = $convertto = ''; //clear memory

    return $string;
}

/************** these are callable 'shortcode' functions *****************************/

/* verbatim - wrap contents in htmlspecialchars() */
function scf_vb($inarray)
{
    $convertto = '';
    if (isset($inarray['text'])) {
        $convertto = htmlspecialchars($inarray['text']);
    }

    return $convertto;
}

function scf_img($inarray)
{
    global $SF_sitewebpath;
    /* set defaults if not set */
    if (! isset($inarray['srcsize'])) {
        $inarray['srcsize'] = '500';
    }
    if (isset($inarray['dir'])) {
        $inarray['srcsize'] = '';
    }
    if (! isset($inarray['dir'])) {
        $inarray['dir'] = 'images/web500';
    }
    if (! isset($inarray['bigdir'])) {
        $inarray['bigdir'] = 'images/web2000';
    }
    if (! isset($inarray['caption'])) {
        $inarray['caption'] = '';
    }
    if (isset($inarray['w'])) { // w is a synonym for width
        $inarray['width'] = $inarray['w'];
    }
    $convertto = '';
    if (isset($inarray['src'])) {
        $convertto = $convertto.'<figure><a href="'.$SF_sitewebpath.$inarray['bigdir'].'/'.$inarray['src'].'" title="'.$inarray['caption'].'">';
        $convertto = $convertto.'<img src="'.$SF_sitewebpath.$inarray['dir'].'/'.$inarray['src'].'" ';
        if (isset($inarray['width'])) {
            $convertto = $convertto.' width="'.$inarray['width'].'"';
        }
        $convertto = $convertto.' /></a>';
        $convertto = $convertto.'<figcaption>'.$inarray['caption'].'</figcaption></figure>';
    }

    return $convertto;
}

function scf_lbimg($inarray)
{
    global $SF_sitewebpath;
    $convertto = '';

    if (! isset($inarray['dir'])) {
        $inarray['dir'] = 'images/web500';
    }

    if (isset($inarray['src'])) {
        $imgs = explode(',', $inarray['src']);
        $caps = explode(',', $inarray['caption']);
        //echo '<br/>';var_dump($imgs);
        $dclass = 'lbg-container';
        $convertto = $convertto.'<div class="'.$dclass.'">'."\n";
        foreach ($imgs as $key=>$img) {
            $exif = SF_getexif('storedexifmetadata.json', $img);
            if ($exif) {
                $exifstring = '['.$exif['make'].', '.$exif['mm'].'mm, f:'.$exif['fstop'].', s:'.$exif['shutter'].', iso:'.$exif['iso'].']';
            } else {
                $exifstring = '';
            }
            $convertto = $convertto.'<div class="lbg-image"><a href="'.$SF_sitewebpath.'images/web2000/'.$img.'" class="venobox-lbgall" data-gall="gallery-'.count($imgs) + count($caps).'" title="'.$caps[$key].'<br/>'.$exifstring.'"><img src="'.$SF_sitewebpath.$inarray['dir'].'/'.$img.'" />';
            $convertto = $convertto.'<div class="lbg-caption">'.$caps[$key].'</div>'."\n";
            $convertto = $convertto.'</a></div>'."\n";
        }
        $convertto = $convertto.'</div>'."\n";
        $convertto = $convertto.'<script type="text/javascript">'."new VenoBox({selector: '.venobox-lbgall', border: '2px', bgcolor: '#666666', maxWidth: '95%', numeration: true, infinigall: true, share: false,});</script>";
    }

    return $convertto;
}

function scf_lbgallery($inarray)
{
    global $SF_sitewebpath;
    $convertto = '';
    $dclass = '';
    if (isset($inarray['div'])) {
        $dclass = $inarray['div'];
    } else {
        $dclass = 'lbg-container';
    }
    $imgclass = 'lbg-image';
    if (isset($inarray['src'])) {
        $convertto = $convertto.'<div class="'.$dclass.'">'."\n";
        $imgs = explode(',', $inarray['src']);
        $caps = str_getcsv($inarray['caption']);
        foreach ($imgs as $key=>$img) {
            $exif = SF_getexif('storedexifmetadata.json', $img);
            if ($exif) {
                $exifstring = '['.$exif['make'].', '.$exif['mm'].'mm, f:'.$exif['fstop'].', s:'.$exif['shutter'].', iso:'.$exif['iso'].']';
            } else {
                $exifstring = '';
            }
            $convertto = $convertto.'<div class="lbg-image"><a href="'.$SF_sitewebpath.'images/web2000/'.$img.'" class="venobox-lbgall" data-gall="gallery-'.count($imgs) + count($caps).'" title="'.$caps[$key].'<br/>'.$exifstring.'">'."\n";
            $convertto = $convertto.'<img src="'.$SF_sitewebpath.'images/web500/'.$img.'"/>'."\n";
            $convertto = $convertto.'<div class="lbg-caption">'.$caps[$key].'</div>'."\n";
            $convertto = $convertto.'</a></div>'."\n";
        }

        $convertto = $convertto.'</div>'."\n";
        $convertto = $convertto.'<script type="text/javascript">'."new VenoBox({selector: '.venobox-lbgall', border: '2px', bgcolor: '#666666', maxWidth: '97%', numeration: true, infinigall: true, share: false,});</script>";
    }

    return $convertto;
}
/*
* getexif 'summary' fields from named exif metadata (json) file
* return array keyed by 'field' containing 'values'
*/
function SF_Getexif($metadatafile, $file)
{
    global $SF_sitedrivepath;
    $exifjson = file_get_contents($SF_sitedrivepath.$metadatafile);
    $exif = json_decode($exifjson, true);
    if (isset($exif[$file]['Summary'])) {
        return $exif[$file]['Summary'];
    } else {
        return false;
    }
}
/*
* given array of yaml strings containing 'field: value' yaml pairs
* return array keyed by 'field' containing 'value'
*/
function simpleyaml($inarray)
{
    //var_dump($inarray);
    $yaml = [];
    foreach ($inarray as $key=>$value) {
        if (! (($key <=> '') == 0) and ! (($value <=> '') == 0)) {
            $item = explode(':', $value);
            $yaml[trim($item[0])] = trim($item[1]);
            if (isset($item[2])) {
                $yaml[trim($item[0])] = trim($item[1]).':'.trim($item[2]);
            }
        }
    }

    return $yaml;
}

/**
 * Generate a javascript encoded mailto link or a normal mailto link in the event scripting is turned off
 *
 * @param string emailaddress a valid email address
 * @param string the link text you want to appear in the 'a href'
 * @param string any classing (css) you may wish to apply to the a href
 */
function SF_GenerateEmailLink($emailaddress, $linktext, $class)
/****************************************************************************/
{
    /* get the name - pre @ */
    $result = preg_match("/^.*\@/", $emailaddress, $matches);
    if (! $result) {
        echo '[invalid email address]';

        return;
    }
    $name = $matches[0];
    $name = preg_replace("/\@/", '', $name);
    /* get the domain - pre @ */
    $result = preg_match("/\@.*$/", $emailaddress, $matches);
    if (! $result) {
        echo '[invalid email address]';

        return;
    }
    $domain = $matches[0];
    $domain = preg_replace("/\@/", '', $domain);
    /* encode for name, domain and mailto: into html hex entity numbers */
    $encname = str_convert_htmlentities($name);
    $encdomain = str_convert_htmlentities($domain);
    $mailto = str_convert_htmlentities('mailto:');
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
    if ($class) {
        echo "document.write('class=\"".$class."\"');";
    }
    echo "document.write('>'); 
  document.write('".$linktext."</a>');
// -->
</script>";
    echo '<noscript><a href="'.$mailto.$encname.'&#64;'.$encdomain.'"';
    if ($class) {
        echo 'class="'.$class.'"';
    }
    echo '>'.$linktext.'</a></noscript>';
}

/**
 * Converts a string to hex html entities
 */
function str_convert_htmlentities($str)
/****************************************************************************/
{
    $str = mb_convert_encoding($str, 'UTF-32', 'UTF-8');
    $t = unpack('N*', $str);
    $t = array_map(function ($n) { return "&#$n;"; }, $t);

    return implode('', $t);
}

/**
 * Gets the Section Title (if any) as per directory config (or fallback)
 */
function SF_GetSectionTitle()
/****************************************************************************/
{
    global $dirconfigarray;
    if (isset($dirconfigarray['sectionheading'])) {
        return $dirconfigarray['sectionheading'];
    } else {
        return;
    }
}

/**
 * Get filesystem modified date for filename
 */
function SF_GetPageModifiedDate($filename = '', $dateformat = 'jMY h:i')
/****************************************************************************/
{
    global $SF_documentroot;
    if ($filename == '') {
        $filename = $SF_documentroot.$_SERVER['PHP_SELF'];
    }

    return date($dateformat, filemtime($filename));
}

/**
 * Get cureent year as string from system time
 */
function SF_GetCurrentYear()
/****************************************************************************/
{
    return date('Y', time());
}

/**
 * determine separator required and add text-only querystring to end of url
 */
function SF_GetTextOnlyURL()
/****************************************************************************/
{
    global $textonlyqs;
    if ((('?' <=> $_SERVER['REQUEST_URI']) == 0)) {
        $sep = '&amp;';
    } else {
        $sep = '?';
    }

    return $_SERVER['REQUEST_URI'].$sep.$textonlyqs;
}

/**
 * determine separator required and add text-only querystring to end of url
 */
function SF_GetPrintURL()
/****************************************************************************/
{
    global $printlayoutqs;
    if ((('?' <=> $_SERVER['REQUEST_URI']) == 0)) {
        $sep = '&amp;';
    } else {
        $sep = '?';
    }

    return $_SERVER['REQUEST_URI'].$sep.$printlayoutqs;
}

/**
 * based on where we are get the current path
 */
function getcurrentpath()
/****************************************************************************/
{
    global $sfdebug;
    $currentpath = getpath($_SERVER['PHP_SELF']);
    if ($sfdebug) {
        SF_DebugMsg('getcurrentpath('.$currentpath.')');
    }

    return $currentpath;
}

/**
 * using a full url, strip down to just the path portion
 */
function getpath($urlstring)
/****************************************************************************/
{
    $urlstring = trim($urlstring);
    /*if we are at the root just return with root */
    if (! strcmp('/', $urlstring)) {
        return $urlstring;
    }
    /* search from right of string and remove all characters back to the next right-most '/' - effect is clipping to path*/
    for ($x = strlen($urlstring) - 1; $x >= 0 and $urlstring[$x] != '/'; $x--) {
        $urlstring[$x] = ' ';
    }

    return trim($urlstring);
}

/**
 * remove trailing slash, if any
 */
function removetrailingslash($pathstring)
/****************************************************************************/
{
    $pathstringlength = strlen($pathstring) - 1;
    if ($pathstring[$pathstringlength] == '/') {
        $pathstring[$pathstringlength] = ' ';
    }

    return trim($pathstring);
}

/**
 * remove leading slash, if any
 */
function removeleadingslash($pathstring)
/****************************************************************************/
{
    if ($pathstring[0] == '/') {
        $pathstring[0] = ' ';
    }

    return trim($pathstring);
}

/**
 * given a path, return previous directory from path
 */
function previousdir($pathstring)
/****************************************************************************/
{
    global $sfdebug;
    $pathstring = trim($pathstring);
    /*if we are at the root just return with root */
    if (! strcmp('/', $pathstring)) {
        return $pathstring;
    }
    /*if we are at the root , e.g. just a filename, just return with root */
    if (! stristr($pathstring, '/')) {
        return '/';
    }
    /*remove initial trailing slash */
    $pathstring = removetrailingslash($pathstring);
    /* search from right of string and remove all characters back to the next right-most '/' - effect is clipping the path to previous directory*/
    for ($x = strlen($pathstring) - 1; $x >= 0 and $pathstring[$x] != '/'; $x--) {
        $pathstring[$x] = ' ';
    }
    $pathstring = trim($pathstring); /* remove whitepspace */

    return $pathstring;
}

/**
 * given a url, get previous directory in url path
 */
function getpreviouspath($urlstring)
/*************************************************************************/
{
    $urlstring = trim($urlstring);
    /*if we are at the root just return with root */
    if (! strcmp('/', $urlstring)) {
        return $urlstring;
    }
    /* always knock of the first / if there is one */
    if ($urlstring[(strlen($urlstring) - 1)] == '/') {
        $urlstring[strlen($urlstring) - 1] = ' ';
    }
    /* search from right of string and remove all characters back to the next right-most '/' - effect is clipping to path*/
    for ($x = strlen($urlstring) - 1; $urlstring[$x] != '/' and $x >= 0; $x--) {
        $urlstring[$x] = ' ';
    }

    return trim($urlstring);
}

/**
 * Create a useable file or HTTP reference from whatever we are passed
 *
 * @param string the reference we want to normalise
 * @param string the url we are currently at
 */
function sfnormaliseurl($url_ref, $url)
/****************************************************************************/
{
    global $SF_defaultindexfile;
    global $SF_documentroot;
    global $SF_sitedrivepath;
    $adjusted_url = '';
    $url = preg_replace("@$SF_defaultindexfile$@", '', $url);
    if (preg_match('@^http@', $url_ref)) {
        $adjusted_url = $url_ref;
    } else {
        if (preg_match("@^\/@", $url_ref)) {
            $adjusted_url = $SF_sitedrivepath.$url_ref;
        } else {
            if (preg_match('@^[0-9a-z]@i', $url_ref)) {
                $adjusted_url = $SF_documentroot.getpath(preg_replace("@http:\/\/".$_SERVER['HTTP_HOST'].'@', '', $url)).$url_ref;
            } else {
                $thttphost = 'http://'.$_SERVER['HTTP_HOST'];
                $turl = preg_replace("@$thttphost@", '', getpath($url));
                $texurl = $url_ref;
                while (preg_match("@^\.\.\/@", $texurl)) {
                    $texurl = preg_replace("@^\.\.\/@", '', $texurl);
                    $turl = getpreviouspath($turl);
                }
                $adjusted_url = $SF_documentroot.$turl.$texurl;
            }
        }
    } /* end of else */
    $adjusted_url = preg_replace('@#.*$@', '', $adjusted_url); /* remove anything on end of url after a # */
    $adjusted_url = preg_replace("@\?.*$@", '', $adjusted_url); /* remove anything on end of url after a ? */

    return $adjusted_url;
}

/**
 * look at config data and return true or false depending on state
 */
function configdataisincomplete()
/****************************************************************************/
{
    global $sfdebug;
    global $dirconfigarray;
    if ($sfdebug >= 3) {
        SF_DebugMsg('configdataisincomplete() - config currently is: '.print_r($dirconfigarray, true));
    }
    if (isset($dirconfigarray['menudatafile']) and isset($dirconfigarray['cssfile']) and isset($dirconfigarray['headerfile']) and isset($dirconfigarray['footerfile'])) {
        return false;
    } else {
        return true;
    }
}

/**
 * cut up input file for text only and return text only contents
 */
function rearrangepagefortextonly($inputhtml)
/****************************************************************************/
{
    $h = preg_match('@^.*<!-- SF_Command:content:begins -->@s', $inputhtml, $header);
    $b = preg_match('@<!-- SF_Command:content:begins -->.*<!-- SF_Command:content:ends -->@s', $inputhtml, $body);
    $f = preg_match('@<!-- SF_Command:content:ends -->.*$@s', $inputhtml, $footer);

    /* if we get a sucessful transformation return it else return what we got in */
    if ($h == 1 and $b == 1 and $f == 1) {
        return $body[0].$header[0].$footer[0];
    } else {
        return $inputhtml;
    }
}

/**
 * Generate text only html from input html
 * removes tables, images and css refs
 */
function SF_GenerateTextOnlyHTML($url, $output = true)
/****************************************************************************/
{
    global $defaulttextonlycssfile;
    $search = [
        '@<table.*?>|</table>|<tr.*?>|</tr>|<td.*?>|</td>|<hr.*?>|<link.*?>|<style.*?>|</style>|<center.*?>|</center>@i',
        '@(<img.+alt=)("[^<].+?")([^<].*?>)@i', /* replace img's with alt text first */
        '@(<img[^<].+?>)@i', /* then those without (which wouldn't have s&r'd by previous) */
    ];
    $replace = [
        '',
        "\nImage[$2]<br/>\n",
        "\nImage[no alt text]<br/>\n",
    ];
    if (! $contents = @file_get_contents($url)) {
        $resulthtml = 'ERROR: URL File Open not allowed (allow_url_fopen=0)';
        if ($output) {
            echo $resulthtml;
        } else {
            return $resulthtml;
        }
    }
    $resulthtml = preg_replace($search, $replace, $contents);
    /* this is all a bit of a kludge but put a CSS back into the html */
    $resulthtml = preg_replace('/<head>/', '<head><link href="'.$defaulttextonlycssfile.'" rel="stylesheet" title="SF_CSS" type="text/css">', $resulthtml);
    $resulthtml = rewriteurlsfortextonly($resulthtml);
    $resulthtml = rearrangepagefortextonly($resulthtml);
    if ($output) {
        echo $resulthtml;
    } else {
        return $resulthtml;
    }
}

/**
 * rewrite 'follow' url for text only pages
 */
function rewriteurlsfortextonly($inputhtml)
/****************************************************************************/
{
    global $textonlyqs;
    $search = [
        '@<a href=@',
        "@(<a href=\")([^\"][0-9\.\/a-zA-Z]+?)(\".*>)@i", /*except those beginning with h(ttp) */
    ];
    $replace = [
        "\n<a href=",   /* force each new href to be on a newline */
        "$1$2?$textonlyqs$3",    /* now append onto our urls */
    ];
    $resulthtml = preg_replace($search, $replace, $inputhtml);

    return $resulthtml;
}
