<?php 
/* * * * *
 * 
 * Copyright 2011,2012 Florin-Tiberiu Iacob.
 * 
 * This file is part of HTMLRes.
 * 
 * [Note]
 * 		HTMLRes is a Kohana module.
 * 		Kohana is a PHP HMVC framework.
 * 		See http://kohanaframework.org .
 * 		Kohana is free software, and copyrighted by it's authors.
 * [End Note]
 * 
 * HTMLres is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * HTMLRes is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with HTMLRes.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * The GNU Lesser General Public License should be in a file named "LICENSE".
 * 
 * */
 
defined('SYSPATH') or die('NO Direct Script Acccess !');

class HTMLRes_HTMLNodeCollector implements ArrayAccess,Countable {
	/* * * * *
	 * [Node: In the explanation below, it is supposed you already know the workings of Kohana]
	 * 
	 * This class is the object front-end of this module.
	 * Objects of this class have the role of aiding in adding an element to the registry easily.
	 * [Note: To learn about the registry see HTMLRes_HTMLNodeReg ]
	 * 
	 * The objects are ment to be created by the static front-end HTMLRes.
	 * 
	 * Getting or creating a collector:
	 * <code>
	 * 		HTMLRes::collector('my_colector');
	 * </code>
	 * this will create and return a new collector invoking the factory,
	 * or return the existing one if it already exists.
	 * 
	 * You can pass options to the registry of the collector as a second parameter.
	 * It is recommended to store the collector in the controller somewhere,
	 * although you can get them at any time using HTMLRes::collector(<name>);
	 * <code>
	 * class Controller_Main extends Controller_Template {
	 * 		public $template='main';
	 * 		public function before()
	 *   	{
	 * 			// in the default Controller_Template this will create $this->template as a View object
	 * 			parent::before();
	 * 			// easy access to our collector
	 * 			$this->template->bind('scripts',$this->scripts);
	 * 			$this->scripts = HTMLRes::collector(
	 * 				'scripts',
	 * 				array(
	 * 					'prefix' => 'application/scripts/',
	 * 					'extension' => '.js',
	 * 					'render_node_callback' => array('HTMLRes','script_node_link')
	 * 				)
	 * 			);
	 * 			// see below for this one
	 * 			$this->_add_main_scripts_to($this->scripts);
	 * 		}
	 * }
	 * </code>
	 * this will set a new collector that will print script links when echoed using 
	 * the conveniance HTMLRes::script_node_link($node) static function, that can be
	 * used as a render function for nodes (unses HTML::script()).
	 * [Note: For more on rendering nodes and registries , see HTMLRes_HTMLNodeReg class]
	 * 
	 * Afterwads you can start adding scripts to your collector, and there are a few ways
	 * to do that (called forms from now on):
	 * [Note: further examples are in the context of the above controller class definition]
	 * <code>
	 * 		protected function _add_main_scripts_to( $coll ) // scripts collector, no reference needed
	 * 		{ 
	 * 			// This would add jquery from CDN, the first argument of this form is the source
	 * 			// $coll->{'libs/jquery-1.7'}('http://code.jquery.com/jquery-latest.pack.js')
	 * 			
	 * 			
	 * 			$coll
	 * 				// This will create the src attribute from prefix + name + extension
	 * 				// resulting src="/application/scripts/libs/jquery-1.7.js"
	 * 				
	 * 				->{'libs/jquery-1.7'}()
	 * 				
	 * 				// In this context, where jquery is already loaded, we can add something that depends on it
	 * 				// [ i should probably do something about the versioning, but i don't see the point yet ]
	 * 				
	 * 				->{'libs/jqueryUI-1.8-custom.pack'}() 
	 * 				
	 * 				// If no illegal characters you can pass the name as symbol (not expression as above)
	 * 				
	 * 				->main_functions()		// will add node with name "main_functions" and default parameters
	 * 				->forms();				// same as above
	 * 			
	 * 			
	 * 			// You can set scripts as properties, where the value assigned, if it's not an array,
	 * 			// it will be passed as <code> array( $value ) </code> as the first argument to the collector
	 * 			// function, otherwise, if it's an array, it is passed as is;
	 * 			
	 * 			$coll->ajax = true;
	 * 				// same as $coll->ajax(true); // true is actually there by default, so it's unneeded
	 * 				// same as $coll->{'ajax'}();
	 * 				// same as $coll->___collect('ajax');
	 * 				// same as $coll->___collect_assoc(array('name'=>'ajax'));
	 * 				// same as $coll('ajax');
	 * 				// same as $coll->ajax = array('ajax');
	 * 				// same as $coll->ajax = array('name' => 'ajax'); 
	 * 					// this above is overrinding the name , you can actually do $coll->add = array('name'=>'ajax');
	 * 				// same as $coll['ajax'] = true;
	 * 				// same as $coll->{'ajax'} = true;
	 * 				// :)
	 * 			
	 * 			
	 * 			// If one is from another folder 
	 * 			
	 * 			$coll->response_decoder = array('prefix'=>'application/vendor/extra/scripts/');
	 * 			
	 * 			
	 * 			// since it implements ArrayAcccess, you can also use this method
	 * 			
	 * 			$coll['notices'] = array('prefix'=>'application/vendor/extra/scripts/');
	 * 			
	 * 			
	 * 			// You can set options individually or bulk.
	 * 			// In case you use your server hosted scripts , and that is, the src parameter is set to true and
	 * 			// it is built form prefix + name + extension, you can load more than one script at
	 * 			// a time from a specific directory
	 * 			
	 * 			$coll['main|widgets|animations'] = array('prefix'=>'application/vendor/ads/scripts/');
	 * 			
	 * 			// this above, will explode the name by "|" and add each one with the parameters passed;
	 * 			// this feature is available in all forms where the language permits it ( all except symbol calls);
	 * 		}
	 * </code>
	 * 
	 * Rendereing the collector return the result of the registry's __toString
	 * (and that is $registry->render())
	 * In your view can do 
	 * <code> <?php echo $scripts; ?> </code>
	 * 
	 * The class has some utility functions named in a way that it is very unlikely to 
	 * conflict with one of your node names, and that is with 3 underscores appended "___".
	 * These will be called "supermagic" methods or functions from now on in this doc.
	 * 
	 * */
	
	
	/* * 
	 * Holds the name of the collector
	 * 
	 * */
	protected
	$_name = NULL;
	
	/* * 
	 * Holds the registry
	 * 
	 * */
	protected
	$_registry = NULL;

	
	/* * 
	 * Too obvious
	 * 
	 * */
	protected function __construct(){ }
	
	
	
	/* * 
	 * Factory of the collector.
	 * It is not ment to be used directly.
	 * 
	 * */
	public static function factory($name, array $options = NULL)
	{
		if (HTMLRes::collector_exists($name)) return HTMLRes::collector($name)->___options((array)$options);
		$new = new static;
		$new->_name = $name;
		$new->_registry = HTMLNodeReg::factory();
		return $new;
	}
	
	
	
	/* * 
	 * Supermagic. sets or gets the options of the collector's registry.
	 * <code>
	 * 		$test = HTMLRes::collector('test');
	 * 		$options = $test->___options();
	 * 		$options['extension'] = '.css';
	 * 		$test->___options($options);
	 * </code>
	 * 
	 * @param <array> $options % the options to be set, <void> to get
	 * @return <mixed> 
	 * 		-- <object> $this if used as setter
	 * 		-- <array> $options if used as getter
	 * 
	 * */
	public function ___options(array $options = NULL)
	{
		if ($options === NULL) return $this->_registry->options();
		$this->_registry->options($options);
		return $this;
	}
	
	
	
	/* * 
	 * Supermagic. Main collector function.
	 * Gets an array of argumets that is passed to the import method of the registry.
	 * Options can be set temporarily for a single import, if given as a second argument.
	 * If the third bool argument is supplied and true, the new options will be permanent.
	 * Parses the name for a pipe sign "|", and if it finds one, it explodes and imports
	 * as many nodes as the explosion produces, with the name from the explosion, and the
	 * rest of the arguments.
	 * <code>
	 * 		// permanently set extension to ".css3" for this import and after
	 * 		$collector->___collect_assoc(array('shadows'),array('extension'=>'.css3'),true);
	 * 		// change options temporarily on these ones
	 * 		$collector->___collect_assoc(array('main|basic|default'),array('extension'=>'.css'));
	 * </code>
	 * 
	 * @param <array> $args % the arguments to pass to registry import (see HTMLRes_HTMLNodeReg)
	 * @return <object> $this
	 * 
	 * */
	public function ___collect_assoc(array $args, array $options = NULL, $make_permanent = false)
	{
		$old_options = $this->___options();
		if ($options !== NULL) $this->___options($options);
		$name = isset($args['name']) ? $args['name'] : $args[0];
		if ((strlen($name) > 3) and (strpos($name,'|') !== false)) {
			$multi = explode('|',$name);
			foreach($multi as $name) {
				$newargs = $args;
				if (isset($newargs['name'])) $newargs['name'] = $name;
				else { array_shift($newargs); array_unshift($newargs,$name); }
				$this->_registry->import($newargs);
			}
		} else $this->_registry->import($args);
		if (($options !== NULL) and ( ! $make_permanent)) $this->___options($old_options);
		return $this;
	}
	
	
	
	/* * 
	 * Supermagic. Wrapper for ___collect_assoc() that calls this latter with func_get_args,
	 * meaning that parameters can be passed directly as function arguments instead of them
	 * being passed wrapped in an array
	 * 
	 * */
	public function ___collect()
	{
		return $this->___collect_assoc(func_get_args());
	}
	
	
	
	/* * 
	 * Supermagic. Returns the registry.
	 * ( why !? i have no ideea ... it's not used anywhere)
	 * 
	 * @params none
	 * @return <object#HTMLNodeReg> $this->_registry
	 * 
	 * */
	public function ___getreg()
	{
		return $this->_registry;
	}
	
	
	
	/* * 
	 * Supermagic. Wrapper for the registry's ordered_list() method.
	 * 
	 * @params none
	 * @return <array> $nodes % array with the nodes in the registry
	 * 
	 * */
	public function ___getnodes()
	{
		return $this->_registry->ordered_list();
	}
	
	
	
	/* * 
	 * Supermagic. Chainable collector switcher
	 * 
	 * @params <string> $name % name of the collector to switch to or create
	 * @return <object#HTMLNodeCollector> $collector % the collector
	 * 
	 * */
	public function ___switch($name)
	{
		return HTMLRes::collector($name);
	}
	
	
	
	/* * 
	 * Supermagic. Wrapper for ___switch()
	 * 
	 * @params <string> $name % name of the collector to switch to or create
	 * @return <object#HTMLNodeCollector> $collector % the collector
	 * 
	 * */
	public function ___collector($name)
	{
		return $this->___switch($name);
	}
	
	
	
	/* * 
	 * MagicMethod.
	 * Checks the registry for a node with specified name;
	 * 
	 * */
	public function __isset($name)
	{
		return $this->_registry->has($name);
	}
	
	
	
	/* * 
	 * MagicMethod.
	 * Forces unregistration of a node in the registry if it exists.
	 * 
	 * */
	public function __unset($name)
	{
		$this->_registry->unregister($name,true);
	}
	
	
	
	/* * 
	 * MagicMethod.
	 * Collect Form: Function Call (symbol/expression)
	 * Collects an element by joining the elements recieved as parameters of the function,
	 * and adding the name of the function as the first element in the array.
	 * Passes everythisg to the main collector.
	 * 
	 * If the element is in an subdirectory or the file has dot "." in his name,
	 * you can call the function name as an expresiion that evaluates to a string.
	 * 
	 * */
	public function __call($name,$args)
	{
		array_unshift($args,$name);
		return $this->___collect_assoc($args);
	}
	
	
	/* * 
	 * MagicMethod.
	 * Collect Form: Property set (symbol/expression)
	 * Collects an element by joining the value recieved to be set with the name of 
	 * property to be set. If the value is not an array, than it is considered to be the
	 * second parameter, and the array is created form the name and the value in this order.
	 * Passes everythisg to the main collector.
	 * 
	 * If the element is in an subdirectory or the file has dot "." in his name,
	 * you can write the name of the propery to be set as an expresiion that evaluates to a string.
	 * 
	 * */
	public function __set($name,$value)
	{
		$args = is_array($value) ? $value : array($value);
		array_unshift($args,$name);
		$this->___collect_assoc($args);
		return $value;
	}
	
	
	
	/* * 
	 * MagicMethod.
	 * Same as ___collect()
	 * 
	 * */
	public function __invoke()
	{
		return $this->___collect_assoc(func_get_args());
	}
	
	
	
	/* * 
	 * MagicMethod.
	 * Renders the registry.
	 * 
	 * */
	public function __toString()
	{
		return (string)$this->_registry;
	}
	
	
	
	/* * 
	 * ArrayAccess implementation
	 * 
	 * */
	public function offsetExists($offset) { return isset($offset); }
	public function offsetUnset($offset) { unset($this->{$offset}); }
	public function offsetGet($offset) { return $this->{$offset} ; }
	public function offsetSet($offset,$value) { $this->{$offset} = $value; }
	
	/* * 
	 * Countable implementation
	 * 
	 * */
	public function count() { return count($this->_registy); }
	
}
?>
