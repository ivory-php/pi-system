<?php

namespace Ivory;


/**
 * @author Ben Eckenroed, ben@eckenroed.net
 * @license MIT
 * 
 * Provides native PHP access to many Raspberry Pi Systems. 
 * NOTE: Some of these commands will require the user running the script to have
 * root or sudo access. 
 */
class System {

	/**
	 * Get the CPU Temp in C
	 * @return float
	 */
	public function cpuTemp()
	{
		/**
		 * The temp returns a string like "temp=46.7'C". So we'll
		 * explode it at the equal sign and return the second entry
		 * in the array up to the apostrophe
		 */
		$temp = system('/opt/vc/bin/vcgencmd measure_temp');
		$temp = explode('='. $temp);

		return substr($temp[1], 0, strpos($temp[1], "'"));
	}

	/**
	 * Retrieve the Hostname of the system
	 * @return string
	 */
	public function hostname()
	{
		return system('hostname');
	}

	/**
	 * Sets a new hostname for the Pi. NOTE: USER EXECUTING APPLICATION MUST HAVE SUDO 
	 * RIGHTS.
	 * @param string $newHostname 
	 * @return boolean Success or Failure
	 */
	public function setHostname($newHostname)
	{
		return !! system('hostname ' . $newHostname);
	}

	/**
	 * Determine whether or not the ehternet cable is connected and receiving 
	 * a network address.
	 * @return boolean 
	 */
	public function ethernetIsConnected()
	{
		return !! system('ifconfig eth0 | grep "inet addr');
	}

	/**
	 * Determine whether or not the wireless land is connected and receiving
	 * a network address.
	 * @return boolean 
	 */
	public function wifiIsConnected()
	{
		return !! system('ifconfig wlan0 | grep "inet addr');
	}

	/**
	 * Return the first found IP address. This function searhces first 
	 * ethernet (wired), then wireless. 
	 * 
	 * @return mixed
	 */
	public function ipAddress()
	{
		if( $this->ethernetIsConnected() ) :
			$result = system('ifconfig eth0 | grep "inet addr');
			$result = substr($result, 10); // Remove the 'inet addr:' from the string
			$ipAddress = explode(' ', $result); // Explode into array by spaces
			return $ipAddress;
		elseif( $this->wifiIsConnected() ) :
			$result = system('ifconfig wlan0 | grep "inet addr');
			$result = substr($result, 10); // Remove the 'inet addr:' from the string
			$ipAddress = explode(' ', $result); // Explode into array by spaces
			return $ipAddress;
		endif;

		return false;
	}

	/**
	 * Return whether or not there is an internect connection
	 * @return boolean 
	 */
	public function hasInternetConnection()
	{
		/**
		 * The ping command, if there is no internet, will not pass the results onto 
		 * grep. However, if there is internet, it will. The word "ping:" does not appear
		 * in any results where a ping is succesful, but does appear if there is a failure.
		 *
		 * So, we'll check for an "error" on the ping, if there isn't one, then there
		 * is internet, if there is an error, then there is no internet. 
		 *
		 * The -c flag says how many pings to do. The -t flag indicates the 
		 * timeout one failure (1 second). In other words, if there is no internet, 
		 * the script will take 1 second to check, if there is internet, the script
		 * will know immediately. 
		 */
		if( !system('ping -c 1 -t 1 www.google.com | grep ping:') ) return true;

		return false;
	}

	/**
	 * Get the memory values for the Pi
	 * @param  string $outputSize What size? b = bytes, k = kilobytes, m = megabytes, g = gigabytes
	 * @return array
	 */
	public function getMemory($outputSize = 'm')
	{
		// Get the output from the command
		$memory = system("free -{$outputSize} | grep 'Mem:'");

		// Replace the multiple spaces between each value with a single space
		$memory = preg_replace('!\s+!', ' ', $memory);

		// Explode the string by the spaces into an array
		$memory = explode(" ", $memory);
		
		return [
			'total'		=> $memory[1],
			'used'		=> $memory[2],
			'free'		=> $memory[3],
			'shared'	=> $memory[4],
			'buffers'	=> $memory[5],
			'cached'	=> $memory[6],
		];
	}

	/**
	 * Get the Total Memory for the system
	 * @param string $outputSize What size calculation do you want the output in?
	 * @return int 
	 */
	public function totalMemory($outputSize = 'm')
	{
		return $this->getMemory($outputSize)['total'];
	}

	/**
	 * Get the amount of used memory
	 * @param  string $outputSize 
	 */
	public function usedMemory($outputSize = 'm')
	{
		return $this->getMemory($outputSize)['used'];
	}

	/**
	 * Get the amount of free memory
	 * @param  string $outputSize 
	 */
	public function freeMemory($outputSize = 'm')
	{
		return $this->getMemory($outputSize)['free'];
	}

	/**
	 * Get the percentage of memory that is free
	 */
	public function freeMemoryPercent()
	{
		return ( $this->freeMemory() / $this->totalMemory() ) * 100;
	}

	/**
	 * Get the percentage of memory that is used
	 */
	public function usedMemoryPercent()
	{
		return ( $this->usedMemory() / $this->totalMemory() ) * 100;
	}

	/**
	 * Reboot the Pi
	 */
	public function reboot()
	{
		system('sudo reboot');
	}

	/**
	 * Retreives information about disk usage
	 * @return array
	 */
	public function diskInformation()
	{
		// Get the result
		$info = system("df -h / | grep '/dev/root'");

		// Replace the multiple spaces with a single space
		$info = preg_replace('!\s+!', ' ', $info);

		// Explode the string into an array using the spaces
		$info = explode(" ", $info);

		return [
			'size'			=> $info[1],
			'used'			=> $info[2],
			'available'		=> $info[3],
			'percent_used'	=> $info[4]
		];
	}

	public function up()
	{
		$up = system('uptime');
		$up = explode(",", $up);

		return [
			'1' => 		substr($up[2], 16), // load time over last 1 minute
			'5' => 		$up[3], // load time over last 5 minutes
			'15' => 	$up[4] // load time over last 15 minutes
		];
	}
	
	public function uptime()
	{
		return system('uptime -p');

	}
}
