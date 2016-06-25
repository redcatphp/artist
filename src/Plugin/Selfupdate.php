<?php
namespace RedCat\Artist\Plugin;
use RedCat\Artist\ArtistPlugin;
use Humbug\SelfUpdate\Updater;
class Selfupdate extends ArtistPlugin{
	protected $description = "Update Artist";
	protected $args = [];
	protected $opts = [];
	protected $boolOpts = [];
	protected $shortOpts = [];
	function exec(){
		$updater = new Updater();
		
		//$updater->setStrategy(Updater::STRATEGY_GITHUB); //use packagist.org
		//$updater->getStrategy()->setStability('any');
		//$updater->getStrategy()->setPackageName('redcatphp/artist');
		//$updater->getStrategy()->setPharName('artist.phar');
		//$updater->getStrategy()->setCurrentLocalVersion('@package_version@');
		
		$updater->getStrategy()->setPharUrl('https://raw.githubusercontent.com/redcatphp/artist/master/artist.phar');
		$updater->getStrategy()->setVersionUrl('https://raw.githubusercontent.com/redcatphp/artist/master/artist.phar.version');
		
		$result = $updater->update();
		if(!$result){
			$this->output->writeln('allready up to date');
			return;
		}
		$new = $updater->getNewVersion();
		$old = $updater->getOldVersion();
		printf('Updated from %s to %s', $old, $new);
	}
}