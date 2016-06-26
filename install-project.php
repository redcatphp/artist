<?php
$lines = file('bin/create-project');
$x = explode(' ',str_replace('+',' ',$_REQUEST['q']));
foreach($x as $i=>$v){
	$args[] = '$argv['.($i+1).'] = "'.str_replace('"','\"',$v).'";';
}
array_shift($lines);
array_shift($lines);
foreach(array_reverse($args) as $i=>$v){
	array_unshift($lines,$v);
}
echo implode("\n",$lines);