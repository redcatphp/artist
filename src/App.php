<?php
namespace RedCat\Artist;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use FilesystemIterator;
use ReflectionClass;
use Phar;

use RedCat\Autoload\LowerCasePSR4;

class App{
	protected $commandPaths = [];
	protected $registeredCommands = [];
	protected $loader;
	protected $redcat;
	protected $cwd;
	protected $application;
	protected static $app;
	static function getInstance(){
		if(!isset(self::$app)){
			self::$app = new self();
		}
		return self::$app;
	}
	static function run(){
		return self::getInstance()->runApp();
	}
	function __construct(){
		global $loader;
		$this->loader = $loader;
		$this->loadRedcat();
		$this->cwd = getcwd().'/';
		$this->application = new Application('Artist the RedCatPHP CLI');
		if(isset($GLOBALS['autoExitArtistRedcat'])){
			$this->application->setAutoExit($GLOBALS['autoExitArtistRedcat']);
		}
		$this->commandPaths[dirname(dirname(__FILE__)).'/src/Plugin'] =	'RedCat\\Artist\\Plugin';
	}
	function getApplication(){
		return $this->application;
	}
	function loadRedcat(){
		if(!$this->redcat){
			global $redcat;
			if(!$redcat&&is_file($this->cwd.'redcat.php')&&is_file($this->cwd.'vendor/autoload.php')){
				require_once $this->cwd.'redcat.php';
			}
			$this->redcat = $redcat;
			if($this->redcat['artist.pluginDirsMap']){
				$this->commandPaths = array_merge($this->commandPaths,$this->redcat['artist.pluginDirsMap']);
			}
		}
		return $this;
	}
	function runApp(){
		$this->lookupCommands();
		$this->application->run();
		return $this;
	}
	function lookupCommands(){
		foreach($this->commandPaths as $dir=>$ns){
			$reg = [$dir,$ns];
			if(in_array($reg,$this->registeredCommands)) continue;
			$this->registeredCommands[] = $reg;
			$this->loader->addPsr4($ns.'\\',$dir);
			if(class_exists(LowerCasePSR4::class)){
				LowerCasePSR4::getInstance()->addNamespace($ns,$dir)->splRegister();
			}
		}
		foreach($this->commandPaths as $dir=>$ns){
			$fileSystemIterator = new FilesystemIterator($dir);
			foreach($fileSystemIterator as $fileInfo){
				if($fileInfo->getExtension()!='php') continue;
				$class = $ns.'\\'.$this->toClassName(pathinfo($fileInfo->getFilename(),PATHINFO_FILENAME));
				if(!class_exists($class)) continue;
				$reflectionClass = new ReflectionClass($class);
				if(($class==Command::class||$reflectionClass->isSubclassOf(Command::class))&&$reflectionClass->isInstantiable()){
					if($this->redcat){
						$o = $this->redcat->get($class);
					}
					else{
						$o = new $class;
					}
					$this->application->add($o);
				}
			}
		}
		return $this;
	}
	protected function toClassName($word) {
		return ucfirst(str_replace(' ', '', ucwords(strtr($word, '-', ' '))));
	}
}
