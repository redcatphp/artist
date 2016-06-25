<?php
namespace RedCat\Artist\Plugin;
use RedCat\Artist\ArtistPlugin;
use Symfony\Component\Console\Output\OutputInterface;
class ComposerInstall extends Composer{
	
	protected $description = "Enhanced installer for composer project";
	protected $args = [
		
	];
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
		'composer-asset-plugin'=>'Pre-install the fxp/composer-asset-plugin',
		'no-prestissimo'=>'Dont\'t install the hirak/prestissimo plugin',
		'progress'=>'Show the progress display',
	];
	protected $boolOpts = [
		'composer-asset-plugin',
		'no-prestissimo',
	];
	protected $shortOpts = [
		
	];
	protected $composerJson;
	function exec(){
		ignore_user_abort(false);
		set_time_limit(0);
		$this->tmpDir = getcwd().'/.tmp/artist/';
		if(!is_dir($this->tmpDir)&&!@mkdir($this->tmpDir)){
			$this->tmpDir = sys_get_temp_dir().'/.artist/';
		}
		
		if(!is_dir($d=$this->tmpDir.'composer'))
			mkdir($d,0777,true);
		putenv("COMPOSER_HOME=".$d);
		
		if(!$this->getComposerBin()){
			$this->installComposer();
		}
		
		$this->install();
	}
	function install(){
		$composer = $this->getComposerBin();		
		$vendorDir = $this->getVendorDir();
		if(is_dir($vendorDir)){
			if(!is_writable($vendorDir)){
				echo "vendor-dir '$vendorDir' is not writeable\n\n";
				return;
			}
		}
		else{
			if(!is_writable(getcwd())){
				echo "current dir is not writeable\n\n";
				return;
			}
		}
		
		$json = $this->getComposerJson();
		$assetPlugin = $this->input->getOption('composer-asset-plugin');
		$prestissimo = !$this->input->getOption('no-prestissimo');
		
		$paramsRequire = [];
		$paramsUpdate = [];
		$paramsInstall = [];
		switch($this->output->getVerbosity()){
			case OutputInterface::VERBOSITY_QUIET:
				$paramsRequire[] = $paramsUpdate[] = $paramsInstall[] = '-q';
			break;
			case OutputInterface::VERBOSITY_VERBOSE:
				$paramsRequire[] = $paramsUpdate[] = $paramsInstall[] = '-v';
			break;
			case OutputInterface::VERBOSITY_VERY_VERBOSE:
				$paramsRequire[] = $paramsUpdate[] = $paramsInstall[] = '-vv';
			break;
			case OutputInterface::VERBOSITY_DEBUG:
				$paramsRequire[] = $paramsUpdate[] = $paramsInstall[] = '-vvv';
			break;
			case OutputInterface::VERBOSITY_NORMAL:
			default:
			break;
		}
		foreach(array_keys($this->globalComposerOpts) as $opt){
			if(in_array($opt,$this->boolGlobalComposerOpts)){
				if($this->input->getOption($opt)){
					$paramsRequire[] = $paramsUpdate[] = $paramsInstall[] = '--'.$opt;
				}
			}
			else{
				if(null !== $option = $this->input->getOption($opt)){
					$paramsRequire[] = $paramsUpdate[] = $paramsInstall[] = '--'.$opt.'='.$option;
				}
			}
		}
		foreach(array_keys($this->composerOpts) as $opt){
			switch($opt){
				case 'no-autoloader':
				case 'no-dev':
				case 'dry-run':
					if($this->input->getOption($opt)){
						$paramsUpdate[] = $paramsInstall[] = '--'.$opt;
					}
				break;
				case 'no-progress':
					if($this->input->getOption($opt)||!$this->input->getOption('progress')){
						$paramsRequire[] = $paramsUpdate[] = $paramsInstall[] = '--'.$opt;
					}
				break;
				case 'prefer-dist':					
					if($this->input->getOption($opt)||!$this->input->getOption('prefer-source')){
						$paramsRequire[] = $paramsUpdate[] = $paramsInstall[] = '--'.$opt;
					}
				break;
				default:
					if(in_array($opt,$this->boolComposerOpts)){
						if($this->input->getOption($opt)){
							$paramsRequire[] = $paramsUpdate[] = $paramsInstall[] = '--'.$opt;
						}
					}
					else{
						if(null !== $option = $this->input->getOption($opt)){
							$paramsRequire[] = $paramsUpdate[] = $paramsInstall[] = '--'.$opt.'='.$option;
						}
					}
				break;
			}
		}
		
		
		
		
		$paramsInstall = implode(' ',$paramsInstall);
		$paramsRequire = implode(' ',$paramsRequire);
		$paramsUpdate = implode(' ',$paramsUpdate);
		
		if($assetPlugin||$prestissimo){
			if(isset($json['require'])){
				if(isset($json['extra']['artist']['tmp-require'])){
					$json['extra']['artist']['tmp-require'] = $json['require']+$json['extra']['artist']['tmp-require'];
				}
				else{
					$json['extra']['artist']['tmp-require'] = $json['require'];
				}
				$json['require'] = (object)[];
				file_put_contents($this->cwd.'composer.json',json_encode($json,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
				
				if($prestissimo){
					$this->cmd("$composer require hirak/prestissimo $paramsRequire");
				}
				if($assetPlugin){
					$this->cmd("$composer require fxp/composer-asset-plugin $paramsRequire");
				}

				$json = json_decode(file_get_contents($this->cwd.'composer.json'),true);
				$json['require'] += $json['extra']['artist']['tmp-require'];
				unset($json['extra']['artist']['tmp-require']);
				if(empty($json['extra']['artist'])){
					unset($json['extra']['artist']);
				}
				file_put_contents($this->cwd.'composer.json',json_encode($json,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
				
				$this->cmd("$composer update $paramsUpdate");
			}
			else{
				if($prestissimo){
					$this->cmd("$composer require hirak/prestissimo $paramsRequire");
				}
				if($assetPlugin){
					$this->cmd("$composer require fxp/composer-asset-plugin $paramsRequire");
				}
			}
		}
		else{
			$this->cmd("$composer install $paramsInstall");
		}
		
		
		if(is_file($installFile='install.php')&&!rename($installFile,$installFile.'s')){
			echo 'Unable to rename installation script, you should rename or remove it manually';
		}
	}
	protected function installComposer(){
		$composerPhar = 'composer.phar';
		$composerSetup = 'composer-setup.php';
		echo "Downloading composer installer\n";
		$composerSetupContent = fopen('https://getcomposer.org/installer','r');
		if(!$composerSetupContent){
			echo "An error occured, unable to download composer installer\n";
			return;
		}
		file_put_contents($composerSetup,$composerSetupContent);
		if(!file_exists($composerSetup)){
			echo "An error occured, unable to write composer installer, it\'s probably a rights problem\n";
			return;
		}
		
		$this->cmd('php '.$composerSetup.'');
		
		unlink($composerSetup);
		if(!file_exists($composerPhar)){
			echo "An error occured, unable to install a local composer\n";
			return;
		}

		echo "Local composer installed, you can use it from the root path of your application\n";
		rename($composerPhar,'composer');
	}
	
	protected function getComposerJson(){
		if($this->composerJson)
			return $this->composerJson;
		if(is_file($this->cwd.'composer.json')){
			$json = json_decode(file_get_contents($this->cwd.'composer.json'),true);
		}
		else{
			$json = [
				"minimum-stability" => "dev",
				"config" => [
					"vendor-dir" => "packages",
					"bin-dir" => "packages/bin"
				]
			];
		}
		return $this->composerJson = $json;
	}
	protected function getVendorDir(){
		$json = $this->getComposerJson();
		$vendorDir = isset($json['config']['vendor-dir'])?$json['config']['vendor-dir']:'vendor';
		return $vendorDir;
	}
}