<?php
namespace RedCat\Artist\Plugin;
use RedCat\Artist\ArtistPlugin;
use RedCat\DataMap\Bases;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Helper\ProgressBar;
class CsvImport extends ArtistPlugin{
	protected $description = "Import from csv lines format to a database";

	protected $args = [
		'db'=>'The key of database to save from config map',
		'dir'=>'The storage directory',
		'separator'=>'The splitter character, default: ;',
		'lowercase'=>'Normalize columns to lowercase, default: true',
	];
	protected $opts = [
	];
	
	protected $defaultDir = '.data/csv/';
	protected $defaultSeparator = ';';
	protected $defaultLowercase = true;
	protected $bases;
	function __construct($name = null,Bases $bases=null){
		parent::__construct($name);
		$this->bases = $bases;
	}
	
	protected function exec(){
		$db = $this->input->getArgument('db');
		if(is_null($db))
			$db = 0;
		
		$dir = $this->input->getArgument('dir');
		if(!$dir)
			$dir = $this->defaultDir;
		$dir = rtrim($dir,'/').'/';
		
		$separator = $this->input->getArgument('separator');
		if(!$separator)
			$separator = $this->defaultSeparator;
			
		$lowercase = $this->input->getArgument('lowercase');
		if(!$lowercase)
			$lowercase = $this->defaultLowercase;
		
		$b = $this->bases[$db];
		
		
		foreach(glob($dir.'*.csv') as $rowsFile){
			$type = substr(basename($rowsFile),0,-4);
			$table = $b[$type];
			$fp = fopen($rowsFile,'r');
			$i = 0;
			$this->output->writeln('importing '.$type.'');
			
			$linecount = 0;
			while(!feof($fp)){
				$line = fgets($fp, 4096);
				if(trim($line)){
					$linecount = $linecount + substr_count($line, PHP_EOL);
				}
			}
			$progress = new ProgressBar($this->output, $linecount);
			rewind($fp);
			
			while (($line = fgetcsv($fp, 0, $separator)) !== FALSE) {
				if($i==0){
					if(end($line)=='')
						array_pop($line);
					if($lowercase){
						$line = array_map('strtolower',$line);
					}
					$columns = $line;
				}
				else{
					$row = [];
					foreach($columns as $i=>$field){
						$row[$field] = isset($line[$i])?$line[$i]:null;
					}
					$row['_forcePK'] = true;
					$table[] = $row;
				}
				$i++;
				$progress->advance();
			}
			$progress->finish();
			$this->output->writeln('Rows of table '.$type.' imported');
			fclose($fp);
		}
		$this->output->writeln('CSV directory '.$dir.' imported into  DB '.$db);
	}
}