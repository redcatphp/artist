<?php
namespace RedCat\Artist\Plugin;
use RedCat\Artist\ArtistPlugin;
use Symfony\Component\Console\Output\OutputInterface;
class ComposerUpdate extends Composer{
	
	protected $description = "Update composer and git project";
	protected $args = [];
	protected $composerOpts = [	
		'prefer-source'=> 'There are two ways of downloading a package: source and dist. For stable versions Composer will use the dist by default. The source is a version control repository. If --prefer-source is enabled, Composer will install from source if there is one. This is useful if you want to make a bugfix to a project and get a local git clone of the dependency directly.',
		'prefer-dist'=> 'Reverse of --prefer-source, Composer will install from dist if possible. This can speed up installs substantially on build servers and other use cases where you typically do not run updates of the vendors. It is also a way to circumvent problems with git if you do not have a proper setup.',
		'ignore-platform-reqs'=> 'ignore php, hhvm, lib-* and ext-* requirements and force the installation even if the local machine does not fulfill these. See also the platform config option.',
		'dry-run'=> 'If you want to run through an installation without actually installing a package, you can use --dry-run. This will simulate the installation and show you what would happen.',
		'dev'=> 'Install packages listed in require-dev (this is the default behavior).',
		'no-dev'=> 'Skip installing packages listed in require-dev. The autoloader generation skips the autoload-dev rules.',
		'no-autoloader'=> 'Skips autoloader generation.',
		'no-scripts'=> 'Skips execution of scripts defined in composer.json.',
		'no-progress'=> 'Removes the progress display that can mess with some terminals or scripts which don\'t handle backspace characters.',
		'optimize-autoloader'=> 'Convert PSR-0/4 autoloading to classmap to get a faster autoloader. This is recommended especially for production, but can take a bit of time to run so it is currently not done by default.',
		'classmap-authoritative'=> 'Autoload classes from the classmap only. Implicitly enables --optimize-autoloader',
	];
	protected $boolComposerOpts = [
		'prefer-source',
		'prefer-dist',
		'ignore-platform-reqs',
		'dry-run',
		'dev',
		'no-dev',
		'no-autoloader',
		'no-scripts',
		'no-progress',
		'optimize-autoloader',
		'classmap-authoritative',
	];
	protected $shortComposerOpts = [
		'optimize-autoloader'=>'o',
		'classmap-authoritative'=>'a',
	];
	protected $opts = [
		'progress'=>'Show the progress display',
		'composer-verbose'=>'Increase verbosity to debug',
	];
	protected $boolOpts = [
	];
	protected $shortOpts = [
	];
	protected $composerJson;
	function exec(){
		ignore_user_abort(false);
		set_time_limit(0);
		
		if(is_dir($this->cwd.'.git')){
			$this->cmd("git pull");
		}

		$composer = $this->getComposerBin();
		$paramsUpdate = [];
		$verbosity = $this->input->getOption('composer-verbose');
		if($verbosity){
			if(is_integer($verbosity)) $verbosity = str_repeat('v',$verbosity);
			$paramsUpdate[] = '-'.$verbosity;
		}
		else{
			switch($this->output->getVerbosity()){
				case OutputInterface::VERBOSITY_QUIET:
					$paramsUpdate[] = '-q';
				break;
				case OutputInterface::VERBOSITY_VERBOSE:
					$paramsUpdate[] = '-v';
				break;
				case OutputInterface::VERBOSITY_VERY_VERBOSE:
					$paramsUpdate[] = '-vv';
				break;
				case OutputInterface::VERBOSITY_DEBUG:
					$paramsUpdate[] = '-vvv';
				break;
				case OutputInterface::VERBOSITY_NORMAL:
				default:
				break;
			}
		}
		foreach(array_keys($this->globalComposerOpts) as $opt){
			if(in_array($opt,$this->boolGlobalComposerOpts)){
				if($this->input->getOption($opt)){
					$paramsUpdate[] = '--'.$opt;
				}
			}
			else{
				if(null !== $option = $this->input->getOption($opt)){
					$paramsUpdate[] = '--'.$opt.'='.$option;
				}
			}
		}
		foreach(array_keys($this->composerOpts) as $opt){
			switch($opt){
				case 'no-autoloader':
				case 'no-dev':
				case 'dry-run':
					if($this->input->getOption($opt)){
						$paramsUpdate[] = '--'.$opt;
					}
				break;
				case 'no-progress':
					if($this->input->getOption($opt)||!$this->input->getOption('progress')){
						$paramsUpdate[] = '--'.$opt;
					}
				break;
				//case 'prefer-dist':					
					//if($this->input->getOption($opt)||!$this->input->getOption('prefer-source')){
						//$paramsUpdate[] = '--'.$opt;
					//}
				//break;
				default:
					if(in_array($opt,$this->boolComposerOpts)){
						if($this->input->getOption($opt)){
							$paramsUpdate[] = '--'.$opt;
						}
					}
					else{
						if(null !== $option = $this->input->getOption($opt)){
							$paramsUpdate[] = '--'.$opt.'='.$option;
						}
					}
				break;
			}
		}
		
		$paramsUpdate = implode(' ',$paramsUpdate);
		$this->cmd("$composer update $paramsUpdate");
	}
}