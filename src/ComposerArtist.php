<?php
namespace RedCat\Artist;
class ComposerArtist{
	static function __callStatic($func,$args){
		$php = 'bin/artist.phar';
		$_SERVER['argv'] = $GLOBALS['argv'] = [$php,$func];
		ob_start();
		include $php;
	}
}