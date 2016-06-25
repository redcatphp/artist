<?php
$downlad = function($url){
	$fp = fopen($url,'r');
	if(!$fp){
		echo "error: $url unreachable";
		exit;
	}
	echo "Downloading from $url...\n";
	$filename = basename($url);
	file_put_contents($filename,$fp);
	echo "Downloaded in $filename\n";
};
$downlad('https://raw.githubusercontent.com/redcatphp/artist/master/artist.phar');
$downlad('https://raw.githubusercontent.com/redcatphp/artist/master/artist.phar.pubkey');
echo "Make artist.phar executable\n";
chmod('artist.phar',0755);
echo "Move artist and artist.pubkey to /usr/local/bin/\n";
if(is_file('/usr/local/bin/artist.phar')) passthru('sudo rm /usr/local/bin/artist');
if(is_file('/usr/local/bin/artist.phar.pubkey')) passthru('sudo rm /usr/local/bin/artist.pubkey');
passthru('sudo mv artist.phar /usr/local/bin/artist');
passthru('sudo mv artist.phar.pubkey /usr/local/bin/artist.pubkey');
if(is_file('/usr/local/bin/artist')){
	echo "Done !\n";
	echo "You can now call artist in the console from everywhere just simply typing \"artist\"\n";
}
else{
	echo "There were some trouble to install artist in the bin path\n";
	echo "You can try to move the files to the bin path by yourself\n";
	echo "or type these commands manually:\n";
	echo "sudo mv artist.phar /usr/local/bin/artist\n";
	echo "sudo mv artist.phar.pubkey /usr/local/bin/artist.pubkey\n";
}