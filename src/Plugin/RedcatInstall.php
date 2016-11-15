<?php
namespace RedCat\Artist\Plugin;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use RedCat\Artist\ArtistPlugin;
use RedCat\Artist\TokenTree;
use RedCat\Artist\App as ArtistApp;
class RedcatInstall extends ArtistPlugin{
	protected $description = "Install redcatphp package from vendor dir to top level of application";
	protected $args = [];
	protected $opts = ['force'];
	protected $gitEmailDefault = "";
	protected $gitNameDefault = "";
	protected $overwrite = [
		'redcat.php',
		'config/default.php'
	];
	protected function exec(){
		
		$this->runCmd('asset:jsalias');
		if(is_file($f=$this->cwd.'vendor/.redcat-installed')){
			return;
		}
		
		file_put_contents('vendor/.htaccess','Deny from All');
		file_put_contents('vendor/bower-asset/.htaccess','Allow from All');
		file_put_contents('vendor/npm-asset/.htaccess','Allow from All');
		
		
		if(!file_exists($this->cwd.'artist')){
			symlink('vendor/bin/artist','artist');
		}
		
		if(is_dir($this->cwd.'vendor/redcatphp/redcatphp')){
			if($this->recursiveCopy($this->cwd.'vendor/redcatphp/redcatphp',$this->cwd)){
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
		if(!is_file($this->cwd.'config/env.php')){
			if(	copy($this->cwd.'config/env.phps',$this->cwd.'config/env.php') ){
				$this->output->writeln('config/env.php created');
			}
			else{
				$this->output->writeln('config/env.php creation failed');
			}
			$this->mergeSubPackagesConfig();
		}
		
		$this->setDbConfig();
		$this->runGitConfig();
		
		touch($f);
		
		ArtistApp::getInstance()->loadRedcat()->lookupCommands();
	}
	protected function recursiveCopy($source,$dest){
		$r = true;
		if(!is_dir($dest)){
			$r = mkdir($dest, 0755);
			if($r===false) return false;
		}
		
		$rdirectory = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
		$iterator = new RecursiveIteratorIterator($rdirectory,RecursiveIteratorIterator::SELF_FIRST);
		foreach($iterator as $item){
			$force = $this->input->getOption('force');
			$sub = $iterator->getSubPathName();
			if(in_array(substr($dest.$sub,strlen($this->cwd)),$this->overwrite)) $force = true;
			if($item->isDir()){
				if(substr($sub,0,4)=='.git') continue;
				$d = $dest.$sub;
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
				if(substr($sub,0,5)=='.git'.DIRECTORY_SEPARATOR) continue;
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
		$path = $this->cwd.'config/app.php';
		$config = new TokenTree($path);
		$source = $this->cwd.'vendor';
		foreach(glob($source.DIRECTORY_SEPARATOR.'*',GLOB_ONLYDIR) as $p){
			if(is_file($f=$p.DIRECTORY_SEPARATOR.'redcat.config.php')){
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
		$path = $this->cwd.'config/env.php';
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