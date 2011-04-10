<?php

namespace GoGreat\SymfonyWrapper;

/**
 * @author Jochem Maas
 * @author Ruben de Vries
 *
 */
class SymfonyApp
{	
	/**
	 * returns the mode of the app,
	 * the method makes the assumption that the app.ini defines a valid mode.
	 *
	 * @return	string
	 */
	static function getMode()
	{
		return SymfonyCnf::getTargetMode();
	}
	
	/**
	 * returns the debug mode of the app,
	 * the method makes the assumption that the app.ini defines a valid debug mode.
	 *
	 * @return	string
	 */
	static function getDebugMode()
	{
		return SymfonyCnf::getor('symfony.debug', 6);
	}
	
	/**
	 * returns whether the current process is running the cli SAPI.
	 *
	 * @return 	bool
	 */
	static function isCLI()
	{
		return PHP_SAPI === 'cli';
	}
	
	/**
	 * Returns the full path of the root dir
	 * 
	 * @return 		string
	 * 
	 */
	static function getRootDir()
	{
		static $dir = null;
		
		if (is_null($dir))
			$dir = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
			
		return $dir;
	}
	
	/**
	 * Returns the full path of this installations defined 'user data' dir
	 * 
	 * @return 		string
	 */
	static function getUserDataDir()
	{
		static $dir = null;
				
		if (is_null($dir)) 
			$dir = SymfonyCnf::get('symfony.dir.usrdata');
				
		return $dir;
	}
	
	/**
	 * Returns the full path of this installations defined 'backup' dir
	 * 
	 * @return 		string
	 */	
	static function getBackupDir()
	{
		static $dir = null;
				
		if (is_null($dir)) 
			$dir = SymfonyCnf::get('symfony.dir.backup');
				
		return $dir;
	
	}
	
	/**
	 * Returns the full path of this installations defined 'tmp' dir
	 * 
	 * @return 		string
	 */
	static function getTmpDir()
	{
		static $dir = null;
				
		if (is_null($dir)) 
			$dir = SymfonyCnf::get('symfony.dir.tmp');
				
		return $dir;
	}
	
	/**
	 * Returns the full path of this installations defined 'logs' dir
	 * 
	 * @return 		string
	 */
	static function getLogDir()
	{
		static $dir = null;
				
		if (is_null($dir)) 
			$dir = SymfonyCnf::get('symfony.dir.logs');
				
		return $dir;
	}
	
	/**
	 * 
	 * Returns the full path of the current targets webroot dir
	 * 
	 * @return 		string
	 * 
	 */	
	static function getWebrootDir()
	{
		return self::getRootDir().'/web';
	}
}