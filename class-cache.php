<?php
/**
 * Cache - Class to cache things into a file.
 *
 * @package paymo-api
 */
 
/**
 * Main caching class
 * @package paymo-api
 */
abstract class Cache {

	public $cache_file;
	public $cache_time;
	public $mod_time;
	
	/**
	 * Class construct for the cache class
	 * @param string $cache_file
	 * @param string $cache_time
	 */
	public function __construct($cache_file, $cache_time) {
		$this->cache_file = $cache_file;
		$this->cache_time = $cache_time;
	}
	
	/**
	 * Create new cache directory
	 * @return bool
	 */
	public function createCacheDirectory() {
		if (!file_exists(dirname($this->cache_file))) {
			if (!@mkdir(dirname($this->cache_file))) {
				die('Please create the cache directory: '.dirname($this->cache_file).' and make sure it\'s writeable by this script.');
			} else {
				return true;
			}
		} elseif (file_exists(dirname($this->cache_file)) && !is_writable(dirname($this->cache_file))) {
			die('Cannot write to cache directory. Please make it writeable.');
		} else {
			return true;
		}
	}

	/**
	 * Get the cache file contents
	 * @return string|bool The cache content or false if we get it
	 */
	public function getCache() {
		if (file_exists($this->cache_file)) {
			$cache_contents = file_get_contents($this->cache_file);
			return $cache_contents;
		} else {
			return false;
		}
	}
	
	/**
	 * Set the cache file contents
	 * @param string $new_data	The new data to add to the cache file
	 * @return bool
	 */
	public function setCache($new_data) {
		if (file_exists(dirname($this->cache_file))) {
			if (is_writeable(dirname($this->cache_file))) {
				if (file_put_contents($this->cache_file, $new_data)) {
				return true;
				}
			} else {
				die('Can\'t write cache file, please check the cache directory permissions. Is it writeable by this script?');
			}
		} else {
			die('DEAD');
			echo 'HERE';
			// first create the cache
			$this->createCacheDirectory();
			// then fill the cache
			$this->setCache($new_data);
		}
	}

    /**
     * Check for cached file
     * @return bool
     */
    public function checkCache() {
    	if (file_exists(dirname($this->cache_file)) || !is_writable(dirname($this->cache_file))) {
    		$this->createCacheDirectory();
    	}
    	if (file_exists($this->cache_file)) {
    		$this->mod_time = filemtime($this->cache_file) + $this->cache_time;
    		if ($this->mod_time > time()) {
    			return true;
    		} else {
    			return false;
    		}
    	} else {
    		return false;
    	}
    
    }
}

?>