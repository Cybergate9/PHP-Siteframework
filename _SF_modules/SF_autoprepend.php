<?php
/**
* This file is Site Framework's global auto prepend file
*
* This is what 'kicks it all off' for any given page
*
* @package PHP-SiteFramework
* @author Shaun Osborne (webmaster@cybergate9.net)
* @link https://github.com/Cybergate9/PHP-Siteframework
* @access public 
* @copyright Shaun Osborne, 2005-present
* @license https://github.com/Cybergate9/PHP-Siteframework/blob/master/LICENSE
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
$SF_caching=0;
$SF_cacheforced=false;


/* these are only required in this module */
$SF_starttime = microtimefloat();
$SF_qc=array(); /*holds values for any query strings*/

include('SF_localconfig.php');

if($SF_caching == true)
  {
  require_once('SF_cache.php');
  }

if(false){  // hard debug set to true if we need it 
  print("SF_autoprepend.php<br/>");
  echo get_include_path();  
  echo("<br/>SF_Caching=[" .$SF_caching. "]<br/>");
}

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
               $SF_forcecache=true;  
               $SF_qc['time']='y';
               }
           }
       }
    if(!strcasecmp('cache',$qscomponents[0]) or !strcasecmp('c',$qscomponents[0]))
       {
         if(array_key_exists('1',$qscomponents))
           {
           $cacheoption=intval($qscomponents[1]);
           if((($cacheoption <=> 'force') == 0) or (($cacheoption <=> 'force') == 0))
             {
              echo "qsfc".$cacheoption."<br/>";
              $SF_forcecache=true;
              $SF_qc['time']='y';
             }
           }
       } 
  }
}



/* first if we are caching figure out if we should exclude */
if($SF_caching == true)
  {
  $SF_fromcache = SF_cachestart();
  }
else
{
  $SF_fromcache = 'notcaching';
}

if($sfdebug >=2)
  {
    echo("<br/>SF_Caching=[" .$SF_caching. "], SF_fromcache=[".$SF_fromcache."] SF_cacheforced=[".$SF_forcecache."]<br/>");
  }

/* if caching is still on (config or excludes may have turned it off)- check cache for this, if not start caching it */


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
  /* end caching capture if its turned on 
  if($SF_caching==true)
    {
      $SF_fromcache = SF_cacheend();*/
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
  {
    SF_ErrorExit('SF_autoprepend.php','Failed to open "content" file "'.$SF_phpselfdrivepath.'" for pre-processing');
  }
  
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
      $yaml = simpleyaml(explode("\n",$parts[0])); // simpleyaml() from SF_mainmodule.php
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
/* end caching capture if its turned on */
if($SF_caching==true)
  {
    $SF_fromcache=SF_cacheend();
    apexit();
  }
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
  $ret=SF_GeneratefromMarkdownURL($SF_phpselfdrivepath,true);
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
  {
    $SF_fromcache = SF_cacheend();
  }

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
