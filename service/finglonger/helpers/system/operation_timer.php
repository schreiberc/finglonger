<?php
/**
 * Simple timer class
 *
 * @author Colin Sharp
 * @version 1.0.0
 * @copyright 2017 Finglonger Inc.
 */

class OperationTimer{
	
	private $startTime;
	private $endTime;
	private $totalTime;
	private $isRunning;

	/**
	 * Contstructor, initilize timing variables
	 *
	 * @return void.
	 */
	public function  __construct(){
		
		$this->startTime = 0;
		$this->endTime = 0;
		$this->totalTime = null;
		$this->isRunning = false;
	
	}
	
	/**
	 * Start the timer
	 *
	 * @return void.
	 */
	public function startTimer(){
		
		$mtime = microtime();
		$mtime = explode(" ",$mtime);
		$mtime = $mtime[1] + $mtime[0];
		$this->startTime = $mtime;
		$this->isRunning = true;
	
	}
	
	/**
	 * Stop the timer
	 *
	 * @return void.
	 */
	public function stopTimer(){
		
		$mtime = microtime();
		$mtime = explode(" ",$mtime);
		$mtime = $mtime[1] + $mtime[0];
		$this->endTime = $mtime;
		$this->totalTime = ($this->endTime - $this->startTime);
	
	}
	
	/**
	 * Return running status of current timer
	 *
	 * @return bool - true if timer is running
	 * @return bool - false if timer is not running
	 */
	public function isTimerRunning(){
		
		return $this->isRunning;
	
	}
	
	/**
	 * Return current timer value, stops the timer if it is currently running.
	 *
	 * @return microtime - running time of the timer
	 */
	public function getTime(){
		
		if($this->isRunning){
			$this->stopTimer();
		}
		
		return $this->totalTime;
	}
		
}