<?php
namespace RedCat\Artist\Plugin;
use RedCat\Artist\ArtistPlugin;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
class AssetJsalias extends ArtistPlugin{
	use AssetTrait;
	protected $description = 'Register navigator main javascript from bower vendor directory in $js.alias config';
	protected $args = ['jsconfigfile'=>'The $js config file definition to store alias key'];
	protected $opts = ['force'];
	
	protected $exclude = ['js'];
	protected function exec(){
		$this->loadAssetInstallerPaths();
		$force = $this->input->getOption('force');
		$source = $this->cwd.$this->bowerAssetDir;
		$mapFile = $this->input->getArgument('jsconfigfile')?:$this->cwd.'js/js.config.js';
		$start = '$js.config(';
		$end = ');';
		if(is_file($mapFile)){
			$mapFileContent = file_get_contents($mapFile);
			$mapFileContent = trim($mapFileContent);
			$mapFileContent = substr($mapFileContent,strlen($start),-1*strlen($end));
			$mapFileContent = self::removeTrailingCommas($mapFileContent);
			$map = json_decode($mapFileContent,true);
			if(!is_array($map)){
				$this->output->writeln('json parse error in '.$mapFile);
				return;
			}
		}
		else{
			$map = [];
		}
		if(!isset($map['alias'])) $map['alias'] = [];
		$alias = &$map['alias'];
		foreach(glob($source.'/*',GLOB_ONLYDIR) as $p){
			$packageName = basename($p);
			if(in_array($packageName,$this->exclude)) continue;
			if(isset($alias[$packageName])&&!$force) continue;
			if(is_file($jsonFile=$p.'/bower.json')||is_file($jsonFile=$p.'/component.json')){
				$json = json_decode(file_get_contents($jsonFile),true);
				if(!isset($json['main'])) continue;
				$mainJson = $json['main'];
			}
			elseif(is_file($jsonFile=$p.'/composer.json')){
				$json = json_decode(file_get_contents($jsonFile),true);
				if(!isset($json['extra']['component']['scripts'])) continue;
				$mainJson = $json['extra']['component']['scripts'];
			}
			else{
				continue;
			}
			$mainJs = [];
			foreach((array)$mainJson as $main){
				if(strtolower(pathinfo($main,PATHINFO_EXTENSION))=='js'){
					$mainJs[] = self::cleanDotInUrl($this->bowerAssetDir.'/'.$packageName.'/'.substr($main,0,-3));
				}
			}
			if(empty($mainJs)) continue;
			if(count($mainJs)===1){
				$alias[$packageName] = $mainJs[0];
			}
			else{
				$alias[$packageName] = $mainJs;
			}
		}
		$jsonEncode = json_encode($map,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
		$jsonEncode = str_replace('    ',"\t",$jsonEncode);
		file_put_contents($mapFile,$start.$jsonEncode.$end);
		$this->output->writeln('bower packages alias registered for $js in '.$mapFile);
	}
	static function removeTrailingCommas($json){
		$json = preg_replace('/,\s*([\]}])/m', '$1', $json);
		return $json;
	}
}