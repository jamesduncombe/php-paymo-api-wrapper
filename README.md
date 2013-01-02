## PHP wrapper for Paymo's API

This is a wrapper for Paymo's API. Please feel free to fork it and make it better!

### What's included?

- Caching class - this is used to cache up the returned responses from Paymo's API.

- The main Paymo class - this includes all the methods 

- The Paymo and Cache classes have both been commented with [Doxygen](http://www.stack.nl/~dimitri/doxygen/) format comments (the Doxygen config file is here too).

### Example

Below is a very short example of how this can be used:

```php
<?php
	
	/**
	 * Require both our Paymo and Cache classes
	 */
	 
	require_once 'class-cache.php';
	require_once 'class-paymo.php';
	
	/**
	 * Create a new instance of Paymo
	 * Here we want the API to return it's response in JSON format
	 * We're also asking it to cache the authentication token and cache the API response
	 */
	
	$api_response_format = 'json';
	$use_auth_cache = true;
	$use_data_cache = true;
	
	$paymo = new Paymo( 'your_api_key', 'your_username', 'your_password', $use_auth_cache, $use_data_cache, $api_response_format );
	
	/**
	 * Print out a list of all our clients
	 */
	 
	print_r( $paymo->clients_getList() );

	/**
	 * Print out a list of invoices between April and August of 2012
	 * For date format see: http://api.paymo.biz/docs/misc.dates.html
	 */

	$client_id = null;
	$start = '2012-04-01';
	$end = '2012-08-01';
	$status = 'paid';

	print_r( $paymo->invoices_find( $client_id, $start, $end, $status ) );
	
?>
```