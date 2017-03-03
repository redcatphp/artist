<?php
namespace RedCat\Artist\Plugin;
use RedCat\Artist\ArtistPlugin;
class RedcatDown extends ArtistPlugin{
	protected $description = "Turn on maintenance mode";
	protected $args = [
		'ip' => 'Allow an IP to access site in maintenance, separe them by space to add multiple',
	];
	protected $opts = [];
	protected function exec(){
		$ip = $this->input->getArgument('ip');
		if(is_array($ip))
			$ip = implode(' ',$ip);
		$ip = explode(' ',$ip);
		if(is_file($this->cwd.'index-up.phps')){
			$this->output->writeln('Application is allready in maintenance');
			return;
		}
		copy($this->cwd.'index.php',$this->cwd.'index-up.phps');
		copy(__DIR__.'/sources/maintenance.php',$this->cwd.'index.php');
		$this->output->writeln('Application is in maintenance from now');
	}
}