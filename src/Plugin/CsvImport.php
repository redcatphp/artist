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
		
		
		$config = [];
		foreach(glob($dir.'*.ini') as $file){
			$type = substr(basename($file),0,-4);
			$config[$type] = parse_ini_file($file,true);
			if(isset($config[$type]['protect']))
				$config[$type]['protect'] = explode(',',$config[$type]['protect']);
		}
		
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
			$this->output->writeln('');
			rewind($fp);
			
			if(isset($config[$type]['separator'])&&$config[$type]['separator'])
				$separator = $config[$type]['separator'];
			while (($line = fgetcsv($fp, 0, $separator)) !== FALSE) {
				if($i==0){
					if(end($line)=='')
						array_pop($line);
					$columns = [];
					foreach($line as $col){
						$columns[] = isset($config[$type]['remap'][$col])?$config[$type]['remap'][$col]:$col;
					}
					if(isset($config[$type]['protect'])){
						foreach($config[$type]['protect'] as $col){
							if(false!==$index=array_search($col,$columns)){
								$y = 2;
								$oldcol = $columns[$index];
								do{
									$newcol = $oldcol.$y;
									$y++;
								}
								while(in_array($newcol,$columns));
								$columns[$index] = $newcol;
							}
						}
					}
					if($lowercase){
						$columns = array_map('strtolower',$columns);
					}
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