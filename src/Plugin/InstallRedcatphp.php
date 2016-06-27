<?php
namespace RedCat\Artist\Plugin;
use RedCat\Artist\ArtistPlugin;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use RedCat\Artist\TokenTree;
class InstallRedcatphp extends ArtistPlugin{
	protected $description = "Install redcatphp package from vendor dir to top level of application";
	protected $args = [];
	protected $opts = ['force'];
	protected $gitEmailDefault = "";
	protected $gitNameDefault = "";
	protected function exec(){
		
		$this->runCmd('asset:jsalias');
		
		if(is_file($f=$this->cwd.'packages/.redcat-installed')){
			return;
		}
		
		
		if(!file_exists($this->cwd.'artist')){
			symlink('packages/bin/artist','artist');
		}
		
		if(is_dir($this->cwd.'packages/redcatphp/redcatphp')){
			if($this->recursiveCopy($this->cwd.'packages/redcatphp/redcatphp',$this->cwd)){
				$this->output->writeln('redcatphp bootstrap installed');
			}
			else{
				$this->output->writeln('redcatphp bootstrap failed to install');
			}
		}
		
		$dirs = ['.tmp','.data','content'];
		array_walk($dirs,function($dir){
			if(!is_dir($this->cwd.$dir)){
				if(mkdir($this->cwd.$dir)){
					$this->output->writeln($dir.' directory created');
				}
				else{
					$this->output->writeln($dir.' directory creation failed');
				}
			}
			chmod($dir,0777);
		});
		if(!is_file($this->cwd.'.config.env.php')){
			if(	copy($this->cwd.'.config.env.phps',$this->cwd.'.config.env.php') ){
				$this->output->writeln('.config.env.php created');
			}
			else{
				$this->output->writeln('.config.env.php creation failed');
			}
			$this->mergeSubPackagesConfig();
		}
		
		$this->setDbConfig();
		$this->runGitConfig();
		
		touch($f);
	}
	protected function recursiveCopy($source,$dest){
		$r = true;
		if(!is_dir($dest)){
			$r = mkdir($dest, 0755);
			if($r===false) return false;
		}
		$force = $this->input->getOption('force');
		
		
		$rdirectory = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
		$iterator = new RecursiveIteratorIterator($rdirectory,RecursiveIteratorIterator::SELF_FIRST);
		foreach($iterator as $item){
			$sub = $iterator->getSubPathName();
			if(substr($sub,0,5)=='.git/') continue;
			if($item->isDir()){
				$d = $dest. $iterator->getSubPathName();
				if(!is_dir($d)){
					$r = mkdir($d);
					if($r) $this->output->writeln("directory $d created");
					else $this->output->writeln("directory $d failed to create");
				}
				else{
					$this->output->writeln("directory $d allready exists");
				}
			}
			else{
				$f = $dest.$sub;
				if(!is_file($f)){
					$r = copy($item, $f);
					if($r) $this->output->writeln("file $f copied");
					else $this->output->writeln("file $f failed to copy");
				}
				elseif($force){
					unlink($f);
					$r = copy($item, $f);
					if($r) $this->output->writeln("file $f copied (overwrite)");
					else $this->output->writeln("file $f failed to copy (overwrite)");
				}
				else{
					$this->output->writeln("file $f failed to copy, file allready exists (use --force option to overwrite it)");
				}
			}
			if($r===false) return false;
		}
		return true;
	}
	
	protected function mergeSubPackagesConfig(){
		$modified = false;
		$path = $this->cwd.'.config.php';
		$config = new TokenTree($path);
		$source = $this->cwd.'packages';
		foreach(glob($source.'/*',GLOB_ONLYDIR) as $p){
			if(is_file($f=$p.'/redcat.config.php')){
				self::merge_recursive($config,new TokenTree($f));
				$modified = true;
			}
		}
		if($modified){
			file_put_contents($path,(string)$config);
		}
	}
	
	protected static function merge_recursive(&$a,$b){
		foreach($b as $key=>$value){
			if(is_array($value)&&isset($a[$key])&&is_array($a[$key])){
				$a[$key] = self::merge_recursive($a[$key],$value);
			}
			else{
				$a[$key] = $value;
			}
		}
		return $a;
	}
	
	protected function runGitConfig(){
		if(!is_dir($this->cwd.'.git')) return;
		$ini = parse_ini_file($this->cwd.'.git/config',true);
		$defaultEmail = $this->gitEmailDefault;
		$defaultName = $this->gitNameDefault;
		if(strtoupper(substr(PHP_OS, 0, 3))!='WIN'){
			$defaultUser = exec('grep 1000 /etc/passwd | cut -f1 -d:');
			$iniGlobalFile = '/home/'.$defaultUser.'/.gitconfig';
			if(is_file($iniGlobalFile)){
				$iniGlobal = parse_ini_file($iniGlobalFile,true);
				if(isset($iniGlobal['user'])){
					if(isset($iniGlobal['user']['email']))
						$defaultEmail = $iniGlobal['user']['email'];
					if(isset($iniGlobal['user']['name']))
						$defaultName = $iniGlobal['user']['name'];
				}
			}
		}
		$email = $this->askQuestion("Email for git commit ($defaultEmail): ",$defaultEmail);
		$name = $this->askQuestion("Name for git commit ($defaultName): ",$defaultName);
		passthru("git config user.email $email");
		passthru("git config user.name $name");
	}
	protected function setDbConfig(){
		$modified = false;
		$path = $this->cwd.'.config.env.php';
		$config = new TokenTree($path);
		$configDb = &$config['$']['db'];
		$configDb['host'] = '"'.$this->askQuestion("Main database host (localhost): ","localhost").'"';
		$name = $configDb['name'];
		$name = trim($name,'"');
		$name = trim($name,"'");
		$configDb['name'] = '"'.$this->askQuestion("Main database name ({$name}): ",$name).'"';
		$configDb['user'] = '"'.$this->askQuestion("Main database user (root): ","root").'"';
		$configDb['password'] = '"'.$this->askQuestion("Main database password (root): ","root").'"';
		file_put_contents($path,(string)$config);
	}
}