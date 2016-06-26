<?php
namespace RedCat\Artist;
class ComposerEvents{
	static function __callStatic($func,$args){
		$json = json_decode(file_get_contents('composer.json'),true);
		if(isset($json['extra']['artist']['scripts']['class'])){
			$c = $json['extra']['artist']['scripts']['class'];
		}
		else{
			$c = 'MyApp\\Artist\Setup';
		}
		if(class_exists($c)&&is_callable([$c,$func])){
			call_user_func_array([$c,$func],$args);
		}
	}
}