<?PHP
/**
 * Simple Stack Class
 * 
 * @author Colin Sharp
 * @version 1.0.0
 * @copyright 2017 Finglonger Inc.
 */

class Stack{

	private $elements;
	
	/**
	 * Constructor
	 *
	 */
	public function stack(){

		$this->elements = array();

    }
	
    /**
     * Push an item on the stack
     *
     * @param $item - item to be pushed onto the stack
     * @return void
     */
	public function push($item){

		array_unshift($this->elements, $item);
		
	}
	
	/**
	 * Pop an item off the stack
	 *
	 * @return Boolean False if stack is Empty
	 * @return Mixed item on top of stack if item not empty
	 */
	public function pop(){
		
		if ($this->isEmpty()) {
			return false;
		} else {
			return array_shift($this->elements);
		}
	
	}
	
	/**
	 * Determine if Stack is empty
	 *
	 * @return Boolean true if stack is Empty
	 * @return Boolean false if stack is not Empty
	 */
	public function isEmpty() {

		return empty($this->elements);
	
	}
	
	/**
	 * Return the stack elements
	 *
	 * @return Array 
	 */
	public function returnArray(){

		return $this->elements;

	}
	
	/**
	 * Determine if stack has null values
	 *
	 * @return Boolean false if there is a null value
	 * @return Boolean true if there are no null values
	 */
	public function isNull(){

		foreach($this->elements as $value){
			
			if(!is_null($value)){
				return false;
			}
		
		}

		return true;

	}
	
	/**
	 * Determine if stack is valid if its not empty and contains no null values
	 *
	 * @return Boolean false if the stack is not valid
	 * @return Boolean true if the stack is valid
	 */
	public function isValid(){

		return(!($this->isNull() && $this->isEmpty()));

	}
	
	/**
	 * Get the size of the stack
	 *
	 * @return Integer size of the stack
	 */
	public function getSize(){
		
		return(sizeof($this->elements));
	
	}

	/**
	 * Return an element at a given index.
	 *
	 * @param Integer index of element to return
	 * @return Mixed element at given index.
	 */
	public function get($_index){

		return($this->elements[$_index]);

	}
}

?>
