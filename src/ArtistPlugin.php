<?php
namespace RedCat\Artist;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Question\Question;
use RuntimeException;
use Composer\Console\Application as ComposerConsoleApplication;
abstract class ArtistPlugin extends Command{
	protected $description;
	
	protected $args = [];
	protected $requiredArgs = [];
	
	protected $opts = [];
	protected $requiredOpts = [];
	protected $shortOpts = [];
	protected $boolOpts = [];
	
	protected $loadOptsProperties = ['opts'];
	protected $loadArgsProperties = ['args'];
	
	protected $cwd;
	protected $input;
	protected $output;
	protected $ioHelper;
	protected function execute(InputInterface $input, OutputInterface $output){
		$this->input = $input;
		$this->output = $output;
		if(isset($GLOBALS['ioDialogRedCat'])){
			$this->ioHelper = $GLOBALS['ioDialogRedCat'];
		}
		$this->exec();
	}
	abstract protected function exec();
	protected function configure(){
		$this->cwd = defined('REDCAT_CWD')?REDCAT_CWD:getcwd().'/';
		$c = explode('\\', get_class($this));
		$c = array_pop($c);
		$c = strtolower(preg_replace('/([^A-Z])([A-Z])/', '$1:$2', $c));
		$this->setName($c);
		if(isset($this->description))
			$this->setDescription($this->description);
		
		$requiredArgs = [];
		foreach($this->loadArgsProperties as $k){
			$uk = ucfirst($k);
			if(property_exists($this,'required'.$uk)){
				$requiredArgs = $this->{'required'.$uk}+$requiredArgs;
			}
		}
		foreach($this->loadArgsProperties as $k){
			if(property_exists($this,$k)){
				$this->loadArgs($this->$k,$requiredArgs);
			}
		}
		
		$requiredOpts = [];
		$shortOpts = [];
		$boolOpts = [];
		foreach($this->loadOptsProperties as $k){
			$uk = ucfirst($k);
			if(property_exists($this,'required'.$uk)){
				$requiredOpts = array_merge($this->{'required'.$uk},$requiredOpts);
			}
			if(property_exists($this,'short'.$uk)){
				$shortOpts = array_merge($this->{'short'.$uk},$shortOpts);
			}
			if(property_exists($this,'bool'.$uk)){
				$boolOpts = array_merge($this->{'bool'.$uk},$boolOpts);
			}
		}
		foreach($this->loadOptsProperties as $k){
			if(property_exists($this,$k)){
				$this->loadOptions($this->$k,$shortOpts,$boolOpts,$requiredOpts);
			}
		}
	}
	protected function loadArgs($args,$requiredArgs=[]){
		foreach($args as $k=>$v){
			if(is_integer($k)){
				$arg = $v;
				$description = '';
			}
			else{
				$arg = $k;
				$description = $v;
			}
			$mode = in_array($arg,$requiredArgs)?InputArgument::REQUIRED:InputArgument::OPTIONAL;
			$this->addArgument($arg,$mode,$description);
		}
	}
	protected function loadOptions($opts,$shortOpts=[],$boolOpts=[],$requiredOpts=[]){
		foreach($opts as $k=>$v){
			if(is_integer($k)){
				$opt = $v;
				$description = '';
			}
			else{
				$opt = $k;
				$description = $v;
			}
			if(in_array($opt,$requiredOpts)){
				$mode = InputOption::VALUE_REQUIRED;
			}
			elseif(in_array($opt,$boolOpts)){
				$mode = InputOption::VALUE_NONE;
			}
			else{
				$mode = InputOption::VALUE_OPTIONAL;
			}
			$short = isset($shortOpts[$opt])?$shortOpts[$opt]:null;
			$this->addOption($opt,$short,$mode,$description);
		}
	}
	protected function runCmd($cmd,$input=[],$output=null){
		if(!($input instanceof InputInterface)){
			$input = new ArrayInput((array)$input);
		}
		if(!($output instanceof OutputInterface)){
			$output = $this->output;
		}
		$run = $this->getApplication()->find($cmd);
		if(!$run){
			throw new RuntimeException($cmd.': command not found');
		}
		return $run->run($input, $output);
	}
	protected function askQuestion($sentence,$default=null){
		if($this->ioHelper){
			$helper = $this->ioHelper;
			return $helper->ask($sentence, $default);
		}
		else{
			$helper = $this->getHelper('question');
			$question = new Question($sentence, $default);
			return $helper->ask($this->input, $this->output, $question);
		}
	}
	protected function cmd($command,$output=true){
		if($output){
			echo "$command\n";
		}
		$command .= ' 2>&1';
		passthru($command);
		//$desc = [
			//0 => ['file', 'php://stdin', 'r'],
			//1 => ['file', 'php://stdout', 'w'],
			//2 => ['file', 'php://stderr', 'w'],
		//];
		//$proc = proc_open( $command, $desc, $pipes );
//
		//do {
			//sleep(1);
			//$status = proc_get_status($proc);
		//} while ($status['running']);
	}
	
	static function cleanDotInUrl($url){
		$x = explode('/',$url);
		$l = count($x);
		$r = [];
		for($i=0; $i<$l; $i++){
			if($x[$i]=='..'&&!empty($r)){
				array_pop($r);
			}
			elseif($x[$i]!='.'){
				$r[] = $x[$i];
			}
		}
		$url = implode('/',$r);
		return $url;
	}
}