<?php
/**
	Feed interface for use with feed modules for Chevereto Image Hosting Script.

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

interface FeedModule {
	/*
		Mod version constant. Used in generation of generator tag(pun intended :P).
	*/
	const modVersion = '0.7.1';

	/*
		Class version function.
		Should return class version string in any format.
	*/
	public static function getVersion();

	/*
		Formats given time string according to the respective feed standards.
		Accepts either unix timestamp or date string.
	*/
	public static function formatDate($timestamp);

	/*
		Class constructor.
		Must be public with empty args!
		Don't try to pull a singleton or an object factory on me! >.<
	*/
	public function __construct();

	/*
		Appends xmlns attributes to the root tag.
		Needed for feed extensions(MRSS, DC, etc.)
	*/
	public function registerNamespaces(Array $namespaces);

	/*
		Sets user from the 'user' request value.
	*/
	public function setUser(Array $user);

	/*
		Sets user from the 'category' request value.
	*/
	public function setCategory(Array $category);

	/*
		Sets feed logo.
		Param must be and array in 'tag' => 'value' format.
	*/
	public function setLogo(Array $logo);

	/*
		Overrides multiple elements.
		Params are array in 'tag' => 'value' format and optional numerical index.
		By default index is 0, so first elements specified by 'tag' are overrided.
	*/
	public function overrideElements(Array $override, $index);

	/*
		Sets attributes for single element.
		Params: Name of element, array in 'attribute' => 'value' format and optional numerical index.
		By default index is 0, so attributes are added to the first element specified by 'name'.
	*/
	public function setElementAttributes($name, Array $attributes, $index);

	/*
		Adds custom feed item.
		Accepts array in 'tag' => 'value' format.
	*/
	public function addItem(Array $item);

	/*
		Adds image entry to the feed.
		Accepts CHV\Image formatted array.
	*/
	public function addImage(Array $image);

	/*
		Sets correct mime type for respective feed format and sends it to the client.
	*/
	public function sendXML();
}