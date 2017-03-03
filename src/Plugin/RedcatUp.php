<?php
namespace RedCat\Artist\Plugin;
use RedCat\Artist\ArtistPlugin;
class RedcatUp extends ArtistPlugin{
	protected $description = "Turn off maintenance mode";
	protected $args = [];
	protected $opts = [];
	protected function exec(){
		if(!is_file($this->cwd.'index-up.phps')){
			$this->output->writeln('Application is not in maintenance');
			return;
		}
		copy($this->cwd.'index-up.phps',$this->cwd.'index.php');
		unlink($this->cwd.'index-up.phps');
		$this->output->writeln('Application is not anymore in maintenance from now');
	}
}