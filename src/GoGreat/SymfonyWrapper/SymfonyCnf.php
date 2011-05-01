<?php

namespace GoGreat\SymfonyWrapper;

/**
 * @author Jochem Maas
 * @author Ruben de Vries
 *
 */
abstract class SymfonyCnf
{	
	/**
	 * list of accepted 'app modes' - which also correspond to the names of directory in which 
	 * indiviual 'targets' are defined (within cnf/targets)
	 * 
	 * @var 	array[string]
	 */
	static private $validTargetModes	= array(
		'dev', 'test', 'stage', 'prod',
	);
	
	/**
	 * full path to the app.ini file that contains the application level settings
	 * for this installation
	 * 
	 * @var 	array[string=>string]
	 */
	static private $cnfFiles 			= array(
		'target'		=> null,
		'target_shared'	=> null,
		'shared'		=> null,
	);
	
	/**
	 * storage for the actual config values - simple array of key:value pairs
	 * 
	 * @var 	array[string=>string]
	 */	
	static private $config;
		
	/**
	 * storage for the actual config values, whereby the values are grouped as per the 
	 * group headers defined in the ini file
	 * 
	 * @var 	array[string=>array]
	 */
	static private $config_section;
	
	/**
	 * defines weither we've done an succesfull init
	 * 
	 * @var 	boolean
	 */	
	static private $init = false;

		
	/**
	 * initializer for the static interface, only needs to be called once per request
	 * 
	 * @return 	void
	 */
	static function init()
	{		
		if(!self::$init)		
		{
			// order here is important ... defines the order in which they are (over)loaded
			self::$cnfFiles			= array(
				'shared'		=> self::getSharedCnfFile(),
				'target_shared'	=> self::getTargetSharedCnfFile(),
				'target'		=> self::getTargetCnfFile(),
			);
		
			self::load();
			self::$init = true;
		}
	}

	/**
	 * getter method for retrieve config values,
	 * this method accepts a single key as parameter OR an array of keys,
	 * if an array is given then an array is returned, the returned array will
	 * have keys set for keys that actually reference valid config items. 
	 * 
	 * NB: the value of $key MUST START WITH 'symfony.' - if it does not that prefix will automatically be add before 
	 * the lookup is performed. 
	 * 
	 * @param 	array|string		$key			- a valid key (or an array of 1 or more of them) used in the app.ini
	 * @param	bool				$shortenKeys	- whether to return shortened keys in the array (parameter is ignored if $key is scalar) 
	 * @return	array|string
	 */
	static function get($key, $shortenKeys = false)
	{
		self::init();
		
		switch (gettype($key)) {
			case 'string':
				return $key && isset(self::$config[ $key ]) ? self::$config[ $key ] : null;
			case 'array':
				$data = array();
				foreach ($key as $k) if (isset(self::$config[ $k ]))
					$data[ ($shortenKeys && ($p = (int)strrpos($k, '.')) ? substr($k, $p + 1) : $k) ] = self::$config[ $k ];
					
				return $data;
		}
		
		throw new Exception(__METHOD__.': bad arg!');
	}
	
	/**
	 * returns a section of the config as defined by the given section name,
	 * the section must exist otherwise an empty array is returned
	 * 
	 * @param 	string		$s_key	- config section name  
	 * @return	array
	 */
	static function getSection($s_key)
	{
		self::init();
		
		return  isset(self::$config_section[$s_key]) ? self::$config_section[$s_key] : null;
	}
		
	/**
	 * a variation of {self::get()}, whereby the second argument is a return value that 
	 * should be returned in the event the given key(s) dids not match [any] config items 
	 * 
	 * @param 	array|string	$key				- a valid key (or an array of 1 or more of them) used in the app.ini
	 * @param 	mixed			$default			- the value to return if the given $key (or keys) were not found. 
	 * @param 	bool			$shortenKeys		- whether to return shortened keys in the array (parameter is ignored if $key is scalar)
	 */
	static function getor($key, $default, $shortenKeys = false)
	{		
		$v = self::get($key, $shortenKeys);
		
		switch (gettype($key)) {
			case 'string': 	if (!is_null($v)) 	return $v; break;
			case 'array':	if (count($v) > 0) 	return $v; break;
		}
		
		return $default;
	}
	
	/**
	 * 
	 * Returns the current target
	 * 
	 * @return 		string
	 */
	static function getTarget()
	{		
		static $target = null;
		
		if(is_null($target))
		{			
			$target = self::getConfigRootDir().'/target';

			if (!file_exists($target))
				die("Target file ({$target}) doesn't exist!\n"); // @TODO: better error handling 
			
			$target = trim(file_get_contents($target));
		}
		
		return $target;
	}
		
	/**
	 * retrieve the application mode that is associated with the current target
	 * 
	 * @return 		string
	 */
	static function getTargetMode()
	{
		static $mode = null;
		
		if (is_null($mode)) 
		{			
			$mode = explode('/', self::getTarget());
			$mode = $mode[0];
			
			if (!in_array($mode, self::$validTargetModes))
				die('target does not have valid application mode!');
		}
		
		return $mode;
	}

	/**
	 * 
	 * Returns the full path of the config root dir
	 * 
	 * @return 		string
	 * 
	 */
	static function getConfigRootDir()
	{		
		return SymfonyApp::getRootDir().'/cnf';
	}

	/**
	 * 
	 * Returns the full path of the [target agnostic] shared config dir
	 * 
	 * @return 		string
	 * 
	 */
	static function getSharedConfigDir()
	{
		return self::getConfigRootDir().'/shared';
	}
	
	/**
	 * 
	 * Returns the full path of the current target's config dir
	 * 
	 * @return 		string
	 * 
	 */
	
	static function getTargetConfigDir()
	{
		return self::getConfigRootDir().'/targets/'.self::getTarget();
	}	

	/**
	 * 
	 * Returns the full path of the target' shared (shared between targets with the same 'app mode') config dir
	 * 
	 * @return 		string
	 * 
	 */
	static function getTargetSharedConfigDir()
	{
		return self::getConfigRootDir().'/targets/'.self::getTargetMode().'/shared';		
	}	

	/**
	 * retrieve the location of the current install's php.ini as either a file or
	 * directory path. a type can be given as the first param where the value
	 * equates to a file check on "some/path/php.ini"
	 *
	 * @param 	bool			$asfile		whether to return the path as a dir or a file location, defaults to FALSE.
	 * @param 	bool			$usedefs	whether to filter down the list of fallback/default locations
	 * 										or not when trying to find a file, defaults to FALSE
	 *
	 * @return	string|null					NULL on failure otherwise the path in question
	 */
	static function getPhpIniLoc($asfile = false, $usedefs = false)
	{
		if (!SymfonyApp::isCLI())
			throw new Exception('Not for use via http');
			
		$DS		= DIRECTORY_SEPARATOR;
		$Tdir 	= self::getTargetConfigDir();			// target (most specific)
		$Sdir 	= self::getTargetSharedConfigDir();		// target, shared on the basis of 'app mode'
		$Cdir 	= self::getSharedConfigDir();			// shared, by all targets (least specific)
		
		if (!$usedefs)
		{
			$path = $Tdir.$DS.'php.d'.$DS.'php.ini';
			
			if (!file_exists($path))
				$path = null;
			
			return $path;
		}
		
		$paths = array();
		
		foreach (array($Tdir, $Sdir, $Cdir) as $dir)
			if ($dir) 
				$paths[] = $dir.$DS.'php.d'.$DS.'php.ini';
				
		while ($path = array_shift($paths))
			if (file_exists($path))
				return $path;
				
		return null;
	}	
	
	/**
	 * 
	 * Returns the Database Source Name (for the given 'named' database) 
	 * of the current target
	 * 
	 * @param	string		- named database - must be valid! defaults to 'master' (default is used if any 'empty' name is passed in) 
	 * @return	string
	 */
	static function getDSN($name = null)
	{
		$cnf = self::getSection('database');
		
		if (empty($name))
			$name = 'master';
		
		if (!isset($cnf["symfony.db.{$name}.host"]))
			die('invalid named database requested!');	
			
		$host = $cnf["symfony.db.{$name}.host"];

		if (isset($cnf["symfony.db.{$name}.port"]) && $cnf["symfony.db.{$name}.port"] && $cnf["symfony.db.{$name}.port"] != 3306)
			$host .= ":{$cnf["symfony.db.{$name}.port"]}";
			
		return "mysql://{$cnf["symfony.db.{$name}.user"]}:{$cnf["symfony.db.{$name}.pass"]}@{$host}/{$cnf["symfony.db.{$name}.name"]}";		
	}
	
	/**
	 * determine the target app.ini file for the current install
	 * 
	 * @return 		string|null
	 */
	static private function getTargetCnfFile()
	{		
		$f = self::getTargetConfigDir() . '/app.ini';
		
		if (is_file($f) && is_readable($f))
			return $f;
			
		return null;
	}
	
	/**
	 * determine the target app.ini file for the current install
	 * 
	 * @return 		string|null
	 */
	static private function getSharedCnfFile()
	{		
		$f = self::getSharedConfigDir() . '/app.ini';
		
		if (is_file($f) && is_readable($f))
			return $f;
			
		return null;
	}
	
	/**
	 * determine the shared (shared between target with the same 'app mode') app.ini file for the current install
	 * 
	 * @return 		string|null
	 */
	static private function getTargetSharedCnfFile()
	{
		$f = self::getTargetSharedConfigDir().'/app.ini';
		
		if (is_file($f) && is_readable($f))
			return $f;
			
		return null;
	}	
	
	/**
	 * tries to load the app.ini data.
	 * 
	 * @return 	void
	 */
	static private function load()
	{			
		self::$config 			= array();
		self::$config_section 	= array();		

		foreach (self::$cnfFiles as $cnfFile) 
			if (strlen($cnfFile)) self::mergeSectionedConfigData( parse_ini_file($cnfFile, true) );
	}
	
	/**
	 * ...
	 * 
	 * @param 	array 	$cnfSections
	 * @param 	bool	$overwrite
	 */
	static private function mergeSectionedConfigData($cnfSections, $overwrite = true)
	{
		if (!is_array($cnfSections))
			return;

		$replacement 	= array();	
		$search			= array();
		$replace		= array();
		
		foreach ($cnfSections as $key => $section)
		{
			if($key == 'import') {
				foreach($section as $imports)
					foreach($imports as $importFile)
						if($importFile = SymfonyApp::getRootDir() .'/'. $importFile)
							if(file_exists($importFile))
								$replacement = array_merge($replacement, parse_ini_file($importFile, true));
											
				foreach($replacement as $k => $v) {
					$search[] 	= "%{$k}%";					
					$replace[] 	= $v;
				}
			}
			else 
			{				
				// do the replacements from the import
				$section = self::parseConfigReplacements($section, $search, $replace);
				
				if (is_array($section)) { 
					// an actual section - so concatenate
					self::$config = $overwrite ? array_merge(self::$config, $section) : array_merge($section, self::$config);
					
					if (!isset(self::$config_section[ $key ])) {
						self::$config_section[ $key ] = $section;
					} else {
						self::$config_section[ $key ] = $overwrite	? array_merge(self::$config_section[ $key ], $section)
																	: array_merge($section, self::$config_section[ $key ])
																	;
					}
					
					
				} else if (!is_numeric($key) && is_scalar($section)) {
					// an ini setting (that falls outside any section) - add to array an remove from sectionsd
					if ($overwrite || !isset(self::$config[$key]))				
						self::$config[$key] = $section;				
				}
			}
		}	
	}
	
	static private function parseConfigReplacements($data, $search, $replace)
	{
		$array = is_array($data);
		
		if(!$array)
			$data = array($data);
		
		if(empty($search) || empty($replace))
			return $data;
			
		foreach($data as &$value)
			$value = str_replace($search, $replace, $value);
		
		return ($array ? $data : reset($data));
	}
}