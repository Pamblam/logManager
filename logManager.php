<?php
/**
 * A class for creating logfiles which archives files when they get too long
 * and creates new a new archive when the current one reaches a given length
 */
class logManager {
	
	/**
	 * The name of the logfile. If set to false it will default to "log" in the 
	 * current directory
	 * @var boolean|string
	 */
	public static $logFile = false;
	
	/**
	 * maximum filesize of the log file in Bytes
	 * @var int
	 */
	public static $maxLogFileSize = 64000;
	
	/**
	 * maximum filesize of the archive file in Bytes
	 * @var int
	 */
	public static $maxArchiveFileSize = 64000;
	
	/**
	 * maximum log a string to the logfile
	 * @throws Exception
	 * @var int
	 */
	public static function log($message){
		self::validateLogFile();
		$fh = fopen(self::$logFile,"a+");
		if(false === $fh) throw new Exception("Can't open logfile");
		if(!fwrite($fh,date("c")." - $message\n")) throw new Exception("Can't write to logfile");
		fclose($fh);
	}
	
	/**
	 * Test all functions required by the class to make sure it works 
	 * in it's required location
	 * @return string
	 */
	public static function testLogManager(){
		try{
			$string = "TestEntry".implode("0",array_fill(0, 100, "1"));
			$testFileName = realpath(self::$logFile)."/logtest";
			if(!file_exists($testFileName)) touch($testFileName);
			if(!file_exists($testFileName)) throw new Exception("Could not create testlogfile.");
			if(!is_readable($testFileName)) throw new Exception("testlogfile is not readable.");
			if(!is_writable($testFileName)) throw new Exception("testlogfile is not writable.");
			$fh = fopen($testFileName,"a+");
			if(false === $fh) throw new Exception("Can't open testlogfile");
			if(!fwrite($fh,date("c")." - $string\n")) throw new Exception("Can't write to testlogfile");
			fclose($fh);
			$zip = new ZipArchive();
			$zip->open($testFileName.".zip",ZIPARCHIVE::CREATE);
			$zip->addFile($testFileName, "log-".date("m-d-y-g:i:s-a"));
			$zip->close();
			$fh = fopen($testFileName, 'r+');
			$t = ftruncate($fh, 0);
			fclose($fh);
			if($t === false) throw new Exception("Can't truncate testlogfile.");
			$u = unlink($testFileName);
			if($t === false) throw new Exception("Can't delete testlogfile.");
			unlink($testFileName.".zip");
		}catch(Exception $e){
			return  "Status: Error - {$e->message}";
		}
		return "Status: Ok";
	}
	
	/**
	 * Private - Checks to see if the file needs to be archived
	 * @throws Exception
	 */
	private static function checkLogfile(){
		if(filesize(self::$logFile) >= self::$maxLogFileSize){
			$i=0; while($i++) if(!file_exists(self::$logFile."_archive_$i.zip")) break;
			$i--; $archFileName = self::$logFile."_archive_$i.zip"; $i++;
			if(file_exists($archFileName) && filesize($archFileName) >= self::$maxArchiveFileSize) $archFileName = self::$logFile."_archive_$i.zip";
			$zip = new ZipArchive();
			$zip->open($archFileName,ZIPARCHIVE::CREATE);
			$zip->addFile(self::$logFile, "log-".date("m-d-y-g:i:s-a"));
			$zip->close();
			$fh = fopen(self::$logFile, 'r+');
			$t = ftruncate($fh, 0);
			fclose($fh);
			if($t === false) throw new Exception("Can't truncate file.");
		}
	}
	
	/**
	 * Private - Makes sure the logfile exists and is writable
	 * @throws Exception
	 */
	private static function validateLogFile(){
		if(self::$logFile === false) self::$logFile = realpath(dirname(__FILE__))."/log";
		if(!file_exists(self::$logFile)) touch(self::$logFile);
		if(!file_exists(self::$logFile)) throw new Exception("Could not create logfile.");
		if(!is_readable(self::$logFile)) throw new Exception("Logfile is not readable.");
		if(!is_writable(self::$logFile)) throw new Exception("Logfile is not writable.");
		self::checkLogfile();
	}
}
