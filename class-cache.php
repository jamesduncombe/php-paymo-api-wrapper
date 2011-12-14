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
	 * Get the cache file contents
	 * @return bool
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
		if (is_writeable(dirname($this->cache_file))) {
			if (file_put_contents($this->cache_file, $new_data)) {
			return true;
			}
		} else {
			return false;
		}
	}

    /**
     * Check for cached file
     * @return bool
     */
    public function checkCache() {
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