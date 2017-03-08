<?php

define('PLOG',dirname(__FILE__).'/system.log');

// set to the user defined error handler
$old_error_handler = set_error_handler('myErrorHandler',E_ALL & ~E_NOTICE | E_STRICT);

function myErrorHandler ($errno, $errstr, $errfile, $errline, $vars)
{
  // Only handle the errors specified by the error_reporting directive or function
  // Ensure that we should be displaying and/or logging errors
  //if ( ! ($errno & error_reporting ()) || ! (ini_get ('display_errors') || ini_get ('log_errors'))) return;
  if(($errno & (E_NOTICE | E_STRICT)) OR error_reporting()==0) return;

  // define an assoc array of error string
  // in reality the only entries we should
  // consider are 2,8,256,512 and 1024
  $errortype = array (
    1   =>  'Error',
    2   =>  'Warning',
    4   =>  'Parsing Error',
    8   =>  'Notice',
    16  =>  'Core Error',
    32  =>  'Core Warning',
    64  =>  'Compile Error',
    128 =>  'Compile Warning',
    256 =>  'User Error',
    512 =>  'User Warning',
    1024=>  'User Notice',
    2048=>  'Strict Mode',
    4096=>  'Recoverable Error'
  );
  $s = '{"error": { "type": "'.$errortype[$errno].'", "text": "'.addslashes($errstr).'", "line": '.$errline.', "file": "'.addslashes($errfile).'", "trace": [';
	$MAXSTRLEN = 300;
	$a = debug_backtrace();
	$traceArr = array_reverse($a);
	if(count($traceArr)) foreach($traceArr as $arr)
	{
		if($arr['function']=='myErrorHandler') continue;
		$Line = (isset($arr['line'])? $arr['line'] : "unknown");
		$File = (isset($arr['file'])? str_replace($_GLOBALS['tmpdir'],'',$arr['file']) : "unknown");
		$s.= '{"line": '.$Line.', "file": "'.addslashes($File).'"';
    if (isset($arr['class']))
		  $s .= ', "class": "'.$arr['class'].'"';
		$args = array();
		if(!empty($arr['args'])) foreach($arr['args'] as $v)
		{
			if (is_null($v)) $args[] = 'NULL';
			elseif (is_array($v)) $args[] = '"Array['.sizeof($v).']'.(sizeof($v)<=5 ? ' '.addslashes(substr(serialize($v),0,$MAXSTRLEN)) : '').'"';
			elseif (is_object($v)) $args[] = '"Object: '.get_class($v).'"';
			elseif (is_bool($v)) $args[] = $v ? 'true' : 'false';
			else
      {
				$v = (string) @$v;
				$str = substr($v,0,$MAXSTRLEN);
				if (strlen($v) > $MAXSTRLEN) $str .= '...';
				$args[] = '"'.addslashes($str).'"';
			}
		}
    if(isset($arr['function']))
		{
		  $s .= ', "function": { "name": "'.$arr['function'].'", "args": ['.implode(', ',$args).'] }';
		}
    else
		{
		  $s .= ', "kernel": ['.implode(', ',$args).']';
		}
	}
	$s.= '] } }';
	echo $s;
  die;
}

function loger($x)
{
global $tmpdir;

  error_log(date('[d-m-Y] (H:i:s) {'.$_SERVER['REMOTE_ADDR'].($_SERVER["HTTP_X_FORWARDED_FOR"]!='' ? ','.$_SERVER["HTTP_X_FORWARDED_FOR"] : '').($_SERVER["HTTP_X_ORIGINAL"]!='' ? ','.$_SERVER["HTTP_X_ORIGINAL"] : '').'} -> ').$x.chr(13).chr(10),3,PLOG);
}

?>
