<?php
/**
	Lightweight XML class for Chevereto Image Hosting Script.

	@author	Denis Hoshino (denpa) <denpa@swrn.net>

	Copyright (c) 2014, Denis Hoshino

	Permission is hereby granted, free of charge, to any person obtaining a copy
	of this software and associated documentation files (the "Software"), to deal
	in the Software without restriction, including without limitation the rights
	to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the Software is
	furnished to do so, subject to the following conditions:

	The above copyright notice and this permission notice shall be included in
	all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
	THE SOFTWARE.
*/

namespace CHV;
use G, Exception;

class XMLight {
	public $name = 'XMLight';
	public $value = '';
	public $attributes = array();
	public $parent = null;
	public $children = array();
	public $index = 0;
	protected $xml = '';

	public static $version = '1.0.6';	// Class version.

	public static function getVersion() {
		return self::$version;
	}

	/* %t => tag, %a => attributes, %v - value */
	protected static $elementTemplate = '<%t%a>%v</%t>';
	protected static $emptyElementTemplate = '<%t%a />';

	protected static $declaration = '<?xml version="1.0" encoding="utf-8"?>';

	const firstChild = 0;		// First index constant. Used for improved readability.

	protected function createXML() {
		foreach($this->children as $group) {
			foreach($group as $element) {
				$element->createXML();
			}
		}

		$attributes = '';
		if ( !empty($this->attributes) ) {
			foreach ($this->attributes as $name => $attribute) {
				$attributes .= " $name=\"$attribute\"";
			}
		}

		$this->xml = strtr( XMLight::$elementTemplate, array('%t' => $this->name, '%a' => $attributes, '%v' => $this->xml) );
		return $this->xml;
	}

	protected function sanitizeValue($string) {
		return G\safe_html($string);
	}

	public function __construct( $root = '', Array $attributes = array() ) {
		if($root != '') {
			$this->name = $root;
			$this->setAttributes($attributes);
		}
	}

	public function __toString() {
			return XMLight::$declaration . $this->createXML();
	}

	/* Register XML namespaces. */
	public function registerNamespaces( Array $namespaces = array() ) {
		foreach($namespaces as $ns => $url) {
			$this->setAttribute(is_numeric($ns) ? "xmlns" : "xmlns:$ns", $url);
		}
	}

	/* Appends new element to the end of element stack */
	public function appendNode($name, $value = '', Array $attributes = array(), $namespace = '') {
		if( !isset($this->children[$name]) ) {
			$this->children[$name] = array();
		}
		$xmlElement = new XMLElement();
		$xmlElement->name = $name;
		$xmlElement->value = $this->sanitizeValue($value);
		$xmlElement->index = count($this->children[$name]);
		$xmlElement->setNamespace($namespace);
		$xmlElement->setAttributes($attributes);
		$xmlElement->parent = $this;

		$this->children[$name][] = $xmlElement;
		return $xmlElement;
	}

	/* Appends new element to the beginning of element stack */
	public function prependNode($name, $value = '', Array $attributes = array(), $namespace = '') {
		if( !isset($this->children[$name]) ) {
			$this->children[$name] = array();
		}
		$xmlElement = new XMLElement();
		$xmlElement->name = $name;
		$xmlElement->value = $this->sanitizeValue($value);
		$xmlElement->index = XMLight::firstChild;
		$xmlElement->setNamespace($namespace);
		$xmlElement->setAttributes($attributes);
		$xmlElement->parent = $this;

		array_unshift($this->children[$name], $xmlElement);
		return $xmlElement;
	}

	public function importArray(Array $array, $method = 'appendNode') {
		foreach($array as $key => $value) {
			if( is_array($value) ) {
				$xmlElement = $this->{$method}($key);
				$this->{$key}[$xmlElement->index]->importArray($value, $method);
			} else {
				$xmlElement = $this->{$method}($key, $value);
			}
		}
		return $xmlElement;
	}

	public function findNode($name, $index = XMLight::firstChild) {
		foreach($this->children as $group) {
			foreach($group as $element) {
				if( $node = $element->find($name, $index) ) {
					return $node;
				}
			}
		}
		return array_key_exists($name, $this->children) ? $this->children[$name][$index] : false;
	}

	public function setValue($value = '') {
		$this->value = $value;
	}

	/* Sets a value for a single child element */
	public function setChildValue($name, $value = '', $index = XMLight::firstChild) {
			if( isset($this->children[$name][$index]) ) {
				$this->children[$name][$index]->setValue($value);
			}
	}

	/* Sets values for multiple children element with single index */
	public function setChildrenValues( $index = XMLight::firstChild, Array $elements = array() ) {
		foreach($elements as $key => $value) {
			if( is_array($value) ) {
				$this->{$key}[$index]->setChildrenValues($index, $value);
			} else {
				$this->setChildValue($key, $value, $index);
			}
		}
	}

	public function setAttribute($name, $attribute = '') {
		$this->attributes[$name] = $this->sanitizeValue($attribute);
	}

	public function setAttributes( Array $attributes = array() ) {
		$this->attributes = array_merge($this->sanitizeValue($attributes), $this->attributes);
	}

	public function setNamespace($namespace) {
		if($namespace != '') {
			$this->name = "$namespace:" . $this->name;
		}
	}

	public function __set($name, $value = '') {
		$this->children[$name][] = $value;
	}

	public function __get($name) {
		if( array_key_exists($name, $this->children) ) {
			return $this->children[$name];
		}
	}
}

/* Do not instance XMLElement directly! Use the main XMLight class instead. */
class XMLElement extends XMLight {
	public $name;
	public $value;
	public $attributes = array();
	public $parent;

	public function __construct() {}

	protected function createXML() {
		$attributes = '';

		if ( !empty($this->children) ) {
			foreach ($this->children as $child) {
				foreach($child as $element) {
					$element->createXML();
				}
			}
		}

		if ( !empty($this->attributes) ) {
			foreach ($this->attributes as $name => $attribute) {
				$attributes .= " $name=\"$attribute\"";
			}
		}

		switch(true) {
			case empty($this->parent):
				/* Orphaned element p(>.<)/ Possibly a result of direct XMLElement instantiation. */
				break;
			case !empty($this->value):
				/* Element has an assigned value */
				$this->parent->xml .= strtr( XMLight::$elementTemplate, array('%t' => $this->name, '%a' => $attributes, '%v' => $this->value) );
				break;
			case !empty($this->xml):
				/* Element has an inner xml elements */
				$this->parent->xml .= strtr( XMLight::$elementTemplate, array('%t' => $this->name, '%a' => $attributes, '%v' => $this->xml) );
				break;
			default:
				/* Empty element */
				$this->parent->xml .= strtr( XMLight::$emptyElementTemplate, array('%t' => $this->name, '%a' => $attributes) );
				break;
		}
	}
}

class XMLightException extends Exception {}