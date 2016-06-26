<?php
$lines = file('bin/create-project');
$tmp = [];
$tmp[] = array_shift($lines);
$tmp[] = array_shift($lines);
$i = count($_REQUEST);
foreach(array_reverse($_REQUEST) as $k=>$v){
	if($v){
		$val = is_integer($k)?$v:$k.'='.$v;
	}
	else{
		$val = $k;
	}
	array_unshift($lines,'$argv['.$i.'] = "'.str_replace('"','\"',$val).'";');
	$i--;
}
array_unshift($lines,array_pop($tmp));
array_unshift($lines,array_pop($tmp));
echo implode("\n",$lines);