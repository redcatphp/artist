<?php
namespace RedCat\Artist\Plugin;
use RedCat\Artist\ArtistPlugin;
abstract class Composer extends ArtistPlugin{
	protected $loadOptsProperties = ['globalComposerOpts','composerOpts','opts'];
	protected $loadArgsProperties = ['globalComposerArgs','composerArgs','args'];
	protected $globalComposerOpts = [
		'working-dir'=>'If specified, use the given directory as working directory.',
		'profile'=>'Display timing and memory usage information',
	];
	protected $boolGlobalComposerOpts = [
		'profile',
	];
	protected $shortGlobalComposerOpts = [
		'working-dir'=>'d',
	];
	protected function getComposerBin(){
		if(strtoupper(substr(PHP_OS, 0, 3))==='WIN'){
			if(file_exists($b='C:\\bin\\composer.bat'))
				return $b;
			if(file_exists($b='C:\\bin\\composer.phar'))
				return $b;
		}
		else{
			if(file_exists($b='/usr/local/bin/composer'))
				return $b;
			if(file_exists($b='/usr/local/bin/composer.phar'))
				return $b;
		}
		if(file_exists($b='composer'))
			return "php $b";
		if(file_exists($b='composer.phar'))
			return "php $b";
	}
}