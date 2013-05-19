<?php
	/**
	 * Attribution-ShareAlike 3.0 Unported (CC BY-SA 3.0)
	 * http://creativecommons.org/licenses/by-sa/3.0/legalcode
	 *
	 * Heavily inspired by PHP i18n (https://github.com/Philipp15b/php-i18n)
	 */

	include_once('Spyc.php');


	/**
	 * php18 - easier internationalization
	 * @author iamanders <anders@iamanders.com>
	 */
	class php18 {

		/**
		 * Languages array - last value is the highest prioritized
		 * @var array
		 */
		private $languages;

		/**
		 * Language file
		 * @var string
		 */
		private $language_file;

		/**
		 * Path to langauge files
		 * @var string
		 */
		public $language_path;

		/**
		 * Path to cahced language files
		 * @var string
		 */
		public $cache_path;

		/**
		 * "flatted array" level seperator
		 * @var string
		 */
		public $separator;

		/**
		 * Class name of the compiled language class
		 * @var string
		 */
		public $class_name;

		/**
		 * Is class inited?
		 * @var boolean
		 */
		private $is_inited = false;



		/**
		 * Constructor
		 * @param string  $language
		 * @param string  $language_fallback
		 * @param string  $language_file
		 * @param string  $language_path
		 * @param string  $cache_path
		 * @param boolean $auto_init
		 */
		public function __construct($language, $language_fallback, $language_file, $language_path = 'language/', $cache_path = 'cache/', $auto_init = true) {
			//Set up paths
			$this->language_path = $language_path;
			$this->cache_path = $cache_path;

			//Set up languages array
			$this->languages = array($language_fallback, $language); //Load fallback first, then the prefered language
			$this->languages = array_unique($this->languages); //Only unique languages

			$this->language_file = $language_file;

			$this->separator = '_';
			$this->class_name = 'lc';

			//Init!
			$this->is_inited = false;
			if($auto_init) { $this->init(); }
		}


		/**
		 * Init the language file(s), do caching stuff
		 * and include the translation class to the global scope
		 * @return void
		 */
		public function init() {
			if(!file_exists($this->language_path)) { throw new Exception('Language path does not exist'); }
			if(!file_exists($this->cache_path)) { throw new Exception('Cache path does not exist'); }

			$this->is_inited = true;

			$compiled_filename = sprintf('php18_%s_%s.php', $this->language_file, implode('_', $this->languages) );
			$write_compiled = true;

			//Check cached compiled file
			if(file_exists($this->cache_path . $compiled_filename)) { //Compiled file exists
				$timestamp_cached = filemtime($this->cache_path . $compiled_filename);
				$timestamp_language = filemtime(sprintf('%s%s_%s.yml', $this->language_path, $this->language_file, $this->languages[0]));
				if($timestamp_language2 = @filemtime(sprintf('%s%s_%s.yml', $this->language_path, $this->language_file, $this->languages[1]))) {
					if($timestamp_language2 > $timestamp_language) { $timestamp_language = $timestamp_language2; }
				}

				//Is the compiled file old?
				if($timestamp_cached > $timestamp_language) {
					$write_compiled = false;
				}
			}


			if($write_compiled) {
				//Generate from yml
				$lists = array();
				foreach($this->languages as $l) {
					$filename = sprintf('%s%s_%s.yml', $this->language_path, $this->language_file, $l);
					$lists[] = $this->tree_walk($this->array_from_yml($filename));
				}

				if(isset($this->languages[1])) {
					$lists = array_merge($lists[0], $lists[1]);
				} else {
					$lists = $this->languages[0];
				}

				//Compile!
				$compiled = "<?php\n\tclass " . $this->class_name . " {\n";
				foreach($lists as $l_key => $l_value) {
					$compiled .= sprintf("\t\tconst %s = '%s';\n", $l_key, $l_value);
				}
				$compiled .= "\t}\n";

				//Save compiled file
				file_put_contents($this->cache_path . $compiled_filename, $compiled);
			}

			//Include the class
			include $this->cache_path . $compiled_filename;
		}



		/**
		 * Get array from an yml file
		 * Uses the Spyc class (http://code.google.com/p/spyc/)
		 * @return array
		 */
		private function array_from_yml($filename) {
			//TODO, should probably do some sort of file_exists check here..
			return Spyc::YAMLLoad($filename);
		}



		/**
		 * "Flatten" mutli level array
		 * @return array
		 */
		private function tree_walk($tree, $level = array()) {
			$to_return = array();

			foreach($tree as $tree_key => $tree_item) {
				$level[] = $tree_key;
				if(is_array($tree_item)) {
					$to_return = $to_return + $this->tree_walk($tree_item, $level);
				} else {
					$to_return[(string)implode($this->separator, $level)] = $tree_item;
				}
				array_pop($level);
			}

			return $to_return;
		}


		/**
		 * Get first language
		 * @return string
		 */
		public function language() {
			return $this->languages[count($this->languages) - 1];
		}


		/**
		 * Get fallback language
		 * @return string
		 */
		public function language_fallback() {
			return $this->languages[0];
		}


		/**
		 * Get language stack
		 * @return array
		 */
		public function languages() {
			return $this->languages;
		}


	}

