<?php
namespace RedCat\Artist\Plugin;
trait AssetTrait{
	protected $bowerAssetDir = 'vendor/bower-asset';
	protected $npmAssetDir = 'vendor/npm-asset';
	function loadAssetInstallerPaths(){
		$cwd = property_exists($this,'cwd')?$this->cwd:getcwd();
		if(is_file($cwd.'composer.json')){
			$json = json_decode(file_get_contents($cwd.'composer.json'),true);
			if(is_array($json)){
				if(isset($json['config']['vendor-dir'])){
					$this->bowerAssetDir = $json['config']['vendor-dir'].'/bower-asset';
					$this->npmAssetDir = $json['config']['vendor-dir'].'/npm-asset';
				}
				if(isset($json['extra']['asset-installer-paths']['bower-asset-library'])){
					$this->bowerAssetDir = $json['extra']['asset-installer-paths']['bower-asset-library'];
				}
				if(isset($json['extra']['asset-installer-paths']['npm-asset-library'])){
					$this->npmAssetDir = $json['extra']['asset-installer-paths']['npm-asset-library'];
				}
			}
		}
	}
}