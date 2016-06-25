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
		
		//$updater->getStrategy()->setPharUrl($urlToGithubPagesPharFile);
		//$updater->getStrategy()->setVersionUrl($urlToGithubPagesVersionFile);
		
		$updater->setStrategy(Updater::STRATEGY_GITHUB); //use packagist.org
		$updater->getStrategy()->setStability('any');
		$updater->getStrategy()->setPackageName('redcatphp/artist');
		$updater->getStrategy()->setPharName('artist.phar');
		$updater->getStrategy()->setCurrentLocalVersion('@package_version@');
		
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