<?php
/**
	Feed interface for use with feed modules for Chevereto Image Hosting Script.

	@author	Denis Hoshino (denpa) <denpa@netfleet.space>

	Copyright (c) 2016, Denis Hoshino
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	* Redistributions of source code must retain the above copyright notice, this
	  list of conditions and the following disclaimer.

	* Redistributions in binary form must reproduce the above copyright notice,
	  this list of conditions and the following disclaimer in the documentation
	  and/or other materials provided with the distribution.

	* Neither the name of the copyright holder nor the names of its
	  contributors may be used to endorse or promote products derived from
	  this software without specific prior written permission.

	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
	AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
	IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
	FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
	DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
	SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
	CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
	OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
	OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

namespace CHV;

interface FeedModule {
	/*
		Mod version constant. Used in generation of generator tag(pun intended :P).
	*/
	const modVersion = '3.8.6-2';

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