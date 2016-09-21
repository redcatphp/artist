<?php
namespace RedCat\Artist\Plugin;
use RedCat\Artist\ArtistPlugin;
use BadMethodCallException;
class Require2 extends ArtistPlugin{
	protected $description = "Install a subset of packages";
	protected $args = [];
	protected $opts = [];
	protected function exec(){
		$json = json_decode(file_get_contents($this->cwd.'composer.json'),true);
		
		$dependencies = [ 'letudiant/composer-shared-package-plugin' ];
		foreach($dependencies as $dependency){
			if(!isset($json['require'][$dependency])){
				throw new BadMethodCallException('Require2 need the package "'.$dependency.'"');
			}
		}
		
		if(isset($json['extra']['require2'])){
			$maindir = $this->cwd.'.tmp/composer-require2/';
			foreach($json['extra']['require2'] as $key=>$require2){
				
				if(isset($require2['require'])){
					$require = $require2['require'];
					unset($require2['require']);
				}
				else{
					$require = $require2;
					$require2 = [];
				}
				
				$dir = $maindir.$key.'/';
				if(!is_dir($dir)){
					mkdir($dir,0777,true);
				}
				$composer = [];
				$composer['require']['letudiant/composer-shared-package-plugin'] = $json['require']['letudiant/composer-shared-package-plugin'];
				foreach($require as $k=>$v){
					$composer['require'][$k] = $v;
				}
				
				if(isset($json['repositories'])) $composer['repositories'] = $json['repositories'];
				if(isset($json['config']['vendor-dir'])) $composer['config']['vendor-dir'] = $json['config']['vendor-dir'];
				if(isset($json['config']['bin-dir'])) $composer['config']['bin-dir'] = $json['config']['bin-dir'];
				if(isset($json['config']['github-oauth'])) $composer['config']['github-oauth'] = $json['config']['github-oauth'];
				if(isset($json['minimum-stability'])) $composer['minimum-stability'] = $json['minimum-stability'];
				
				if(isset($json['extra']['shared-package'])) $composer['extra']['shared-package'] = $json['extra']['shared-package'];
				$symlinkDir = isset($json['extra']['shared-package']['symlink-dir'])?$json['extra']['shared-package']['symlink-dir'] : 'vendor-shared';
				$composer['extra']['shared-package']['symlink-dir'] = $symlinkDir;
				$composer['extra']['shared-package']['vendor-dir'] = $json['extra']['shared-package']['vendor-dir'];
				
				$composer['extra']['shared-package']['vendor-dir'] = '../../../'.$composer['extra']['shared-package']['vendor-dir'];
				
				$composer = array_merge_recursive($composer,$require2);
				
				file_put_contents($dir.'composer.json',json_encode($composer));
				
				chdir($dir);
				if(file_exists($dir.'composer.lock')){
					passthru('composer update');
				}
				else{
					passthru('composer install');
				}
				
				foreach(glob($dir.$symlinkDir.'/*',GLOB_ONLYDIR) as $p){
					$vendor = basename($p);
					if($vendor=='composer') continue;
					foreach(glob($p.'/*',GLOB_ONLYDIR) as $path){
						$package = basename($path);
						if($vendor=='letudiant'&&$package=='composer-shared-package-plugin') continue;
						
						$rp = realpath($path);
						
						$version = basename($rp);
						
						$linkDir = $this->cwd.$symlinkDir.'/'.$vendor;
						if(!is_dir($linkDir)) mkdir($linkDir,0777,true);
						$link = $linkDir.'/'.$package.'.'.$version;

						if(file_exists($link)){
							unlink($link);
						}
						symlink($rp, $link);
						$this->output->writeln('Link created "'.$vendor.'/'.$package.'.'.$version.'"');
					}
				}
			}
				
			chdir($this->cwd);
			
			$this->rmdirSkippingSymlink($maindir);
		}
		
	}
	private function rmdirSkippingSymlink($dir){
		if(is_link($dir)){
			unlink($dir);
			return;
		}
		if(is_dir($dir)){
			$dh = opendir($dir);
			if($dh){
				while(false!==($file=readdir($dh))){
					if($file!='.'&&$file!='..'){
						$fullpath = $dir.'/'.$file;
						if(is_file($fullpath)){
							unlink($fullpath);
						}
						else{
							$this->rmdirSkippingSymlink($fullpath);
						}
					}
				}
				closedir($dh);
			}
			rmdir($dir);
		}
	}
}