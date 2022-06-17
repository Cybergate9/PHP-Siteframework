<?php
/**
* This file is Site Framework's global auto prepend file
*
* This is what 'kicks it all off' for any given page
*
* @package SiteFramework
* @author Shaun Osborne (smo30@cam.ac.uk)
* @link http://www.fitzmuseum.cam.ac.uk/projects/phpsiteframework/
* @access public 
* @copyright The Fitzwilliam Museum, University of Cambridge, UK
* @license http://www.fitzmuseum.cam.ac.uk/projects/phpsiteframework/licences.html GPL
*/

/**
* Global 'soft' debug 'on' variable used by various functions in the framework
*
* 0=off
*
* 1=show main config files etc
*
* 2=show directory configuration processing
*
* 3=show info re breadcrumb and menu function calls
*
* -1 = cannot be controlled via querystring
*
* when called it creates a link to $defaultcssfile for the browser so
* some CSS stuff is likely to be affected when this is turned on 
*
* @global integer $sfdebug
* @access private
*/
$sfdebug=0;

/* these are only required in this module */
$SF_starttime = microtimefloat();
$SF_qc=array(); /*holds values for any query strings*/

require_once('SF_cacheconfig.php');

if(array_key_exists('QUERY_STRING',$_SERVER) and $_SERVER['QUERY_STRING'])
{
  $qsitems = explode('&',$_SERVER['QUERY_STRING']);
  foreach($qsitems as $item)
  {
    $qscomponents = explode('=',$item);
    if(!strncasecmp('sf_f',$qscomponents[0],4) & array_key_exists('1',$qscomponents))
      {
        switch(strtolower($qscomponents[1]))
              {
              case 'p':
              case 'print': /* we are doing print layout so over-write values we have for header, footer and css config */
                  $SF_qc['print']='y';

                  break;
              case 't':
              case 'textonly': /* we are text_only version so transform it, output it and exit */  
                  #$url = preg_replace("/[\?|\&]sf_function=textonly$/","",$_SERVER['REQUEST_URI']);
                  $SF_qc['textonly']='y';
                  break;
              case 'nosf':
                  $SF_qc['nosf']='y';
                  break;
              case 'time':
                  $SF_qc['time']='y'; 
                  break;
              case 'force':
                  $SF_forcecache=true;
                  $SF_qc['time']='y'; /* forcing also turns on time display */ 
                  break; 
              }         
       } 
     if(!strcasecmp('debug',$qscomponents[0]))
       {
         if($sfdebug != -1)
           {
             if(array_key_exists('1',$qscomponents))
             {
             $sfdebug=intval($qscomponents[1]);
             $SF_caching=false;
             $SF_qc['time']='y';
             }
           }
       }
  }
}

/* first if we are caching figure out if we should exclude */
if($SF_caching==true)
{
  foreach($SF_cacheexcludes as $compare)
  {
    if(preg_match("@$compare@",$_SERVER['REQUEST_URI']))
      {
      $SF_caching=false;
      $SF_fromcache='CacheExcluded';
      }
  }
}
else
{
$SF_fromcache='CachingOff';
}

/* if caching is still on (config or excludes may have turned it off)- check cache for this, if not start caching it */
if($SF_caching==true)
{
  $SF_fromcache='NotCached';
  require_once('Cache/Lite/Output.php');
  /* cleanse the URI for a cachekey which does not generate unique cache files for sf_f options sf_f=time or sf_f=force or debug=x */
  $cachekey = preg_replace("@[\?\&]sf_f=time@",'',$_SERVER['REQUEST_URI']);
  $cachekey = preg_replace("@[\?\&]sf_f=force@",'',$cachekey);
  $cachekey = preg_replace("@[\?\&]debug=[0-9]@",'',$cachekey);
  
  $cache = new Cache_Lite_Output($SF_cacheoptions);
  if($SF_forcecache==false)
    {
    if($data=$cache->get($cachekey))
      {
      echo $data;
      $SF_fromcache='FromCache';
      apexit();
      }
    }
  else
   {
   $SF_fromcache='CacheUpdateForced';
   $cache->remove($cachekey);
   }  
  $cache->start($cachekey);
}

/* ok we're not getting it from the cache so do it normally */
require_once('SF_mainmodule.php');


/* if we got sf_function=print then change header, footer and css */
if(array_key_exists('print',$SF_qc))
{
$dirconfigarray['headerfile']=$defaultprintheaderfile;
$dirconfigarray['footerfile']=$defaultprintfooterfile;
$dirconfigarray['cssfile']=$defaultprintcssfile;
}
/* if we got sf_function=textonly then do that, end the cache and exit*/
if(array_key_exists('textonly',$SF_qc))
{
SF_GenerateTextOnlyHTML('http://'.$_SERVER['HTTP_HOST'].preg_replace("/[\?|\&]sf_f.*=t.*$/","",$_SERVER['REQUEST_URI']));
/* end caching capture if its turned on */
if($SF_caching==true)
  {$cache->end();}
apexit();
}

/**
* this if block does pre-processing of content looking for SF_Commands if they exist
*
* in the form of <!-- SF_Command:command1:value1 value2 --> or YAML front matter
*
* and loading them into global array $SF_commands['command1'] etc
*/
if(array_key_exists('contentpp',$dirconfigarray)  and !strcmp("yes",$dirconfigarray['contentpp']))
{
  global $SF_phpselfdrivepath;
  global $SF_commands;
  global $sfdebug;
  
  if($sfdebug >= 1){SF_DebugMsg($SF_modulesdrivepath.'SF_autopreprend.php: pre-processing file '.$SF_phpselfdrivepath);}  
  $contents=file_get_contents($SF_phpselfdrivepath);
  if(!$contents)
  {SF_ErrorExit('SF_autoprepend.php','Failed to open "content" file "'.$SF_selfdrivepath.'" for pre-processing');}
  
  preg_match_all("@<\!-- SF_Command(.*?) -->@i",$contents,$matches);
  #print_r($matches);
  foreach($matches[1] as $procentries)
  {
    $command=explode(":",trim($procentries));
    $SF_commands[$command[1]]=$command[2];
  }

  // process YAML front matter if any 
  $parts = preg_split('/[\n]*[-]{3}[\n]/', $contents, 3, PREG_SPLIT_NO_EMPTY);
  //print_r($contents);
  if(count($parts) > 1)
  {
    $yaml = simpleyaml(explode("\n",$parts[0]));
    foreach($yaml as $key=>$value)
    {
      $SF_commands[$key]=$value;
    }
  }

}
/* if we got sf_function=nosf or a <!-- SF_Command:nosf:anything --> then do that, end the cache and exit*/
if(array_key_exists('nosf',$SF_commands) or array_key_exists('nosf',$SF_qc))
{
echo file_get_contents($SF_phpselfdrivepath);
if($SF_caching==true)
  {$cache->end();}
 apexit();
}

/**
* This gets the configured header file (config'd via the current 'config_dir' file)
*/
if(!preg_match('@none$@i',$dirconfigarray['headerfile']))
{
  $ret=include($dirconfigarray['headerfile']);
  if(!$ret)
  {SF_ErrorExit('SF_autoprepend.php','Failed to open "header" file "'.$dirconfigarray['headerfile'].'"');}
}
/**
* This gets the actual content file via PHP_SELF
*/
$ext = pathinfo($SF_phpselfdrivepath, PATHINFO_EXTENSION);
if($ext == "md")
 { 
  $ret=SF_GeneratefromMarkdownURL($SF_phpselfdrivepath);
  if(!$ret)
  {SF_ErrorExit('SF_autoprepend.php','Failed to open Markdown "content" file "'.$SF_phpselfdrivepath.'"');}
 }
else
{
  $ret=include($SF_phpselfdrivepath);
  if(!$ret)
  {SF_ErrorExit('SF_autoprepend.php','Failed to open "content" file "'.$SF_phpselfdrivepath.'"');}
}


/**
* This gets the configured footer file (config'd via the current 'config_dir' file)
*/
if(!preg_match('@none$@i',$dirconfigarray['headerfile']))
{
  $ret=include($dirconfigarray['footerfile']);
  if(!$ret)
  {SF_ErrorExit('SF_autoprepend.php','Failed to open "footer" file "'.$dirconfigarray['footerfile'].'"');}
}
/* end caching capture if its turned on */
if($SF_caching==true)
  {$cache->end();}


apexit();

/**
* apexit - Auto Prepend Exit function
*
* Exit module which can display time, cache state etc
*
* @access private
*/
function apexit()
{
  global $SF_qc;
  global $SF_starttime;
  global $SF_caching;
  global $SF_fromcache;
 
  if(array_key_exists('time',$SF_qc))
    {
      $SF_endtime = microtimefloat();
      $SF_time=round($SF_endtime-$SF_starttime,4);
      echo '<p class="SF_timing_text">Page rendered in '.$SF_time.' seconds ('.$SF_fromcache.')<p>';
    } 
  exit;
}

/**
* microtimefloat
*
* straight from php.net
*
* @access private
* @see http://uk.php.net/manual/en/function.microtime.php
*/
function microtimefloat()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}

?>
