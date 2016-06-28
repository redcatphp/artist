<?php
namespace RedCat\Artist;
use Symfony\Component\Console\Application;
use FilesystemIterator;
use ReflectionClass;
use Phar;
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
			if(!$redcat&&is_file($this->cwd.'redcat.php')&&is_file($this->cwd.'packages/autoload.php')){
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
		}
		foreach($this->commandPaths as $dir=>$ns){
			$fileSystemIterator = new FilesystemIterator($dir);
			foreach($fileSystemIterator as $fileInfo){
				if($fileInfo->getExtension()!='php') continue;
				$class = $ns.'\\'.pathinfo($fileInfo->getFilename(),PATHINFO_FILENAME);
				if(!class_exists($class)) continue;
				$reflectionClass = new ReflectionClass($class);
				if($reflectionClass->IsInstantiable()){
					if($this->redcat){
						$o = $this->redcat->create($class);
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
}