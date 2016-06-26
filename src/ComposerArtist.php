<?php
namespace RedCat\Artist;
class ComposerArtist{
	static function __callStatic($func,$args){
		$event = array_shift($args);
		$GLOBALS['ioDialogRedCat'] = $event->getIO();
		$php = 'packages/bin/artist';
		$_SERVER['argv'] = $GLOBALS['argv'] = [$php,$func];
		ob_start();
		include $php;
	}
}