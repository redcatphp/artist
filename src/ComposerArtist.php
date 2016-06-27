<?php
namespace RedCat\Artist;
class ComposerArtist{
	static function __callStatic($func,$args){
		$event = array_shift($args);
		$GLOBALS['ioDialogRedCat'] = $event->getIO();
		$php = 'packages/bin/artist';
		$func = self::snakeCase($func);
		$_SERVER['argv'] = $GLOBALS['argv'] = [$php,$func];
		ob_start();
		include $php;
	}
	static function snakeCase($str){
		return str_replace(' ', ':', strtolower(preg_replace('/([a-z])([A-Z])/', '$1 $2', $str)));
	}
}