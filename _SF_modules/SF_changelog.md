---
author: Shaun Osborne (webmaster@cybergate9.net)
link: https://github.com/Cybergate9/PHP-Siteframework
copyright: Shaun Osborne, 2005-present
license: https://github.com/Cybergate9/PHP-Siteframework/blob/master/LICENSE
---

CHANGE HISTORY

2022.08.16:
*       change to date based versioning
*       default header and footer cleanups
*       refactor urlpreviews to be more robust, fix issue with bloomberg curls 
*       added extra front matter directives 'summary' can control summary view lengths
*       added logic to generate better og:image refs (site, or refurl if any)
*       corrections to robots.txt
*       performance profiling undertaken (corrected stand-out inefficiencies)
*       put phpdocs back into docs/

2.2     (29Jul2022)  bug fixes esp. urlpreviews.php, prepare for 2.2 git release

2.1     (24Jul2022)  bug fixes esp. to shortcode processor, caching, and previews
                     cleanup code for new git release
                     implemented shortcodes for f:vb, f:img f:lbimg f:lbgallery
                     et book added to extras

2.03    (11Jul2022)  corrected refurl error causing multi-caching in urlmetadatapreview.php
                     moved requires in mainconfig.php
                     moved urlmetadatapreview.php in SF_urlpreview.php

2.02    (10Jul2022)  tweaks to SF_cache.php to fix small issues

2.01    (10Jul2022)  improved {{shortcode}} processor (SF_ShortCodeProcessor()), removed changelog from mainmodule into its own changelog file
 
 2.00    (05Jul2022)  added {{shortcode}} processor (SF_ShortCodeProcessor())

 1.92    (02Jul2022)  SF_default.css cleanup, /extras/SF_urlmetapreview.php replaced by /extras/urlmetadatapreview.php which now stores metadata lookups

 1.91    (29Jun2022)  SF_GeneratefromMarkdownURL() updates to fine tune outputs, deal with metadata better (titles, data, author etc)
                      SF_GeneratefromMarkdownURL() now requires and is using ParsedownExtra
 1.9     (22Jun2022)  clean implementation of pure php caching without Cache_Lite (cacheconfig.php removed and replaced with SF_cache.php)
                      functionality remains similar:
                      1) caching into single directory, or multiple subdirs (if hash value > 0, only 1 or 2 recommended),
                      2) timeouts in secs (3600 = 1 hour)
                      3) top level caching config in SF_localconfig.php, details in SF_cache.php
 1.83    (18Jun2022)  decided on parsedown config, composer install into _SF_modules, configure via mainconfig.php
                      decided to split mainconfig.php in two adding a localconfig.php as in practice over-writing mainconfig.php on remote
                      installs is a pain
                      tidies up meta, header, footer and accessibility pages including accesskeys
                      SF_GeneratefromMarkdownURL() can do short summaries now as well as full output
                      SF_GenerateTextOnlyHTML($url,$output=true) added check on file_get_contents to catch allow_url_fopen = false in server config
                      
 1.7    (15Jun2022)  fixed wrong references in, and added meta via SF_commands values to dublin core defaultmetadata.html
                     added SF_GeneratefromMarkdownURL() and simpleyaml() as first implementation for Markdown content
                     modified SF_autoprepend.php to sense if ext is .md and process it as Markdown if so

 1.6    (13Jun2022)  fixes for php8, split() deprecated, replaced with explode()'s
                     fixed 'forever while' bug in SF_LoadMenuData()
                     added check for duplicate menuid's in SF_LoadMenuData, will put out 'warnings' if ?debug=1
                     fixed sfdebug warning if we're not called via autoprepend
                     created str_convert_htmlentities() for email encodes in SF_GenerateEmailLink() due to deprecated preg_replace feature
                     SF_defaultheader.html now html5, utf8

 1.51    (31May2006) SF_LoadMenuData() and SF_GenerateNavigationMenu() updated so both level 1 and level 2 are highlighted when a level 2 is active
                     and menuhighlighting is on (true)

 1.50    (27May2006) SF_GenerateNavigationMenu() updated to output different div's if different levels are chosen

 1.49    (25may2006) added $displayhome =true,$limitchars=200 paramater to SF_GenerateBreadcrumbLine()

 1.48    (23may2006) SF_autoprepend UPDATED can now use <!-- SF_Command:httpredirect:url -->
                     (if content pp is on in directory) to generate a hhtp 302 class redirect back to browser for this page

 1.47   (22may2006) Added $displaylevelmatch parameter to SF_GenerateSiteMap

 1.46   (17may2006) SF_GenerateEmailLink() added. SF_GenerateSiteMap() updated to be able to restrict levels to show

 1.45   (15may2006) SF_autoprepend UPDATED can now use sf_function=nosf or <!-- SF_Command:nosf:anything -->
                     (if content pp is on in directory) to turn off the framework for this file

 1.44  (14May2006) removed logic put in with V1.5 (_fallback etc) and built in proper fallback ability so logic now runs
         find exact match, find variation match (e.g. index.2.html), find previous dir match and keeping falling back on ddir's unless we hit 'root'
         so upshot is menu can be defined as /gallery/something/ and this will match

                   /gallery/something/anypage

                   /gallery/something/dirone/anypage

                   /gallery/something/dirone/dirtwo/anypage etc


 1.43   (12May2006) SF_GenerateContentsFromURL() can now take into account if one calls in content via http from a framework
                    delivered page the header and footer will automatically be removed
                    (based on SF_Command:content:begins and SF_Command:content:ends tags)
                     doing what is expected - ie get just the content

 1.42  (11may2006) fixed SF_LoadSiteConfigData(), and SF_LoadDirConfigData() and SF_LoadMenuData() to skip incomplete of blank lines in config files

 1.41  (3may2006) fixed bug in LoadMenuData() that wouldnt mark right menu item for directory levels deeper than
                  those named in menu config (ie paths it couldnt recognise), first two passes remain the same, third and forth are:

                  3) choose the item marked as _toplevel_fallback, or

                  4) choose the first item (ordered) from the subset we are working in

 1.4b  (17Jan05) SF_GenerateTextOnlyHTML now also removes <style></style> & <center></center> tags

 1.4a  (16Jan06) added SF_GetSectionTitle() function and support to SF_LoadDirConfigData(); for it

 1.4   (10Jan06) text only now applies a CSS

 1.3e  (2Dec05)  fixes in SF_mainmodule for SF_documentroot

 1.3c (23nov05)  added SF_documentroot as a gloabl in SF_mainconfig.php and fixed SF_GetPageModifiedDate() to use it

 1.3b (28Oct05)  fine tune interaction between querystrings and caching, debug=x now turns caching off

 1.3a (27Oct05)  added ability in SF_autoprepend.php to respond to 'none' dir config settings for header and footer

                 fixed pre-processing configuration 'yes' to be case insensitive

 1.3  (26Oct05)  cleaned up querystring logic in SF_autoappend.php, added debug=x querystring ability and made changes
                 required for that to work

 1.2c  (25Oct2005) if no order information now given in menu config they will still display (just in no particular order)

                   fixed bug in SF_GenerateContentFromURL where it wasn't cleaning http path properly

 1.2b (24Oct2005) fixed sf_f=force in autoprepend.php to properly handle updating cached copy of page

                  fixed caching so sf_f=time|force themselves do not create new cache copies

 1.2a (23Oct2005) textonly rearrangement commands changed to SF_command:content:begins and SF_command:content:ends

 1.2 (22Oct2005) removed autoappend.php altogether moving all functionality into autoprepend.php. Has not only benefit of simplifying configuration but allowing pre-processing of 'commands' from the 'content' file (not sure about efficiency of this but we'll see).

                 new gloabls $SF_phpselfdrivepath and array $SF_commands

                 implemented ability to turn pre-processing on and off via config_dir

                 implemented caching using Cache_Lite (can be turned on/off, config'd in SF_cacheconfig.php)

                 some minor cleanup in SF_LoadMenuData() loops

 1.1e (20Oct05) query strings for SF now case-insentive

 1.1d (19Oct05) breadcrumb lines were not being htmlspecialchar'd - fixed

                'print' link wasn't working from text only - fixed

 1.1c (18Oct05) minor changes - global $SF_sitetitle, GPT_ constants introduced

 1.1 (18Oct05) added SF_LoadSiteConfigData() and made adjustments throughout framework to cope with this.
 This allows all the configuration for a directory running under SF to be delegated.
 Functionally it means the directory is declared and its 'config_dir' file named and then all configuration
 for that directory is contained in that 'config_dir' file and its associated 'config_menu' files

 1.0b fixed SF_GenerateContentFromURL() so it fixes no http:// relative references properly

 1.0a added a few trim's to SF_LoadMenuData() so config file formatting is more forgiving



