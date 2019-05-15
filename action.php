<?php
require_once( '../../php/rtorrent.php' );
require_once( '../../php/Torrent.php' );
require_once( "../../php/xmlrpc.php" );

// Update conf.php with your parameters before running !

eval( getPluginConf( 'webseedsource' ) );

class WeebSeedTorrent extends Torrent {
	protected $pointer = 0;

	public function __construct($torrent, $dlpath, $webseedurl) 
	{
		$torrentdata = get_object_vars($torrent);
		foreach ($torrentdata as $key => $value){
			$this->{$key} = $value;
		}
		$url = $webseedurl;
		if ($dlpath != "") {
			$url = $url . "/" . $dlpath . "/";
		}
		$this->{"url-list"} = [$url];
		$this->{"announce-list"} = [];
		$this->{"announce"} = null;
		$this->{"private"} = 1;
		return;
	}

	public function getName($default) {
		if(is_null($this->name)){
			return $default;
		}
		else{
			return $this->name;	
		}
	}
}

function make_webseed($webseedbase, $webseedurl, $hash) {
	$torrent = rTorrent::getSource($hash);
	if ($torrent) {
		$req = new rXMLRPCRequest();
		$req->addCommand(new rXMLRPCCommand( "d.get_directory", $hash));
		$req->run();
		$basepath = $req->val[0];

		$req = new rXMLRPCRequest();
		$req->addCommand(new rXMLRPCCommand( "d.get_base_filename", $hash));
		$req->run();
		$filename = $req->val[0];

		$basepath = str_replace($webseedbase, "", $basepath);
		$basepath = trim(str_replace($filename, "", $basepath), "/");

		$newtorrent = new WeebSeedTorrent($torrent, $basepath, $webseedurl);

		toLog("Generating webseed torrent for ".$hash." with url_list:" . implode(",", $newtorrent->{"url-list"}));

		return $newtorrent;
	} else {
		return null;
	}
}

function serve_file($webseedurl, $webseedbase){
	if(isset($_REQUEST['result'])) {
		cachedEcho('noty(theUILang.cantFindTorrent,"error");',"text/html");
	}
	if(!isset($_REQUEST['hash'])) {
		return null;
	}
	$hashes = explode(" ", urldecode($_REQUEST['hash']));
	if (count($hashes) == 1) {
		$torrent = make_webseed($webseedbase, $webseedurl, $hashes[0]);
		if ($torrent) {
			$torrent->send();
		}
	} else {
		if (!class_exists('ZipArchive')) {
			cachedEcho('noty("PHP module \'zip\' is not installed.","error");',"text/html");
		}
		ignore_user_abort(true);
		set_time_limit(0);
		$zippath = getTempFilename('webseedsource','zip');
		$zip = new ZipArchive;
		$zip->open($zippath, ZipArchive::CREATE);
		foreach($hashes as $hash)
		{
			$torrent = make_webseed($webseedbase, $webseedurl, $hash);
			if ($torrent) {
				$tmppath = getTempFilename($hash);
				$remove[] = $tmppath;
				$torrent->save($tmppath);
				if (!$zip->addFile($tmppath, $hash. "_webseed.torrent")) {
					toLog("error adding ".$tmppath." to zip");
				}
			}
		}
		if (!$zip->close()) {
			toLog("error closing zip at ".$zippath);
		}
		if (sendFile($zippath, "application/zip", null, false)) {
			unlink($zippath);
		}
		foreach ($remove as $tmppath) {
			unlink($tmppath);
		}
	}
}

serve_file($webseedurl, $webseedbase);
header("HTTP/1.0 302 Moved Temporarily");
header("Location: ".$_SERVER['PHP_SELF'].'?result=0');
