<?php
$lines = file('bin/create-project');
$tmp = [];
$tmp[] = array_shift($lines);
$tmp[] = array_shift($lines);
$i = 1;
foreach(array_reverse($_REQUEST) as $k=>$v){
	$val = $v?$k.'='.$v:$k;
	array_unshift($lines,'$argv['.$i.'] = "'.str_replace('"','\"',$val).'";');
	$i++;
}
array_unshift($lines,array_pop($tmp));
array_unshift($lines,array_pop($tmp));
echo implode("\n",$lines);