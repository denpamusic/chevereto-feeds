<?php
/**
	Extensible Atom 1.0 compatible class for Chevereto Image Hosting Script.

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
use G, Exception;

class Atom implements FeedModule {
	private $feed;
	private $user;
	private $category;

	/*
		%p => user profile link,
		%u => username,
		%d => image description,
		%v => viewer url,
		%t => thumbnail url
	*/
	private static $contentTemplate = '<p><a href="%p">%u</a><br>%d</p><p><a href="%v"><img src="%t" alt="%d" /></a>';

	public static $version = FeedModule::modVersion;

	public static function getVersion() {
		return self::$version;
	}

	public static function formatDate($timestamp) {
		if( !is_numeric($timestamp) ) {
			$timestamp = strtotime($timestamp);
		}
		return date(DATE_ATOM, $timestamp);
	}

	public function __construct() {
		$this->feed = new XMLight('feed');
		$this->feed->registerNamespaces([
				'http://www.w3.org/2005/Atom',
//				'dc' => 'http://purl.org/dc/elements/1.1/'
		]);

		/* Add generic atom head with feed logo */
		$this->feed->importArray(array(
			'title' => getSetting('website_doctitle', true),
			'subtitle' => getSetting('website_description', true),
			'updated' => Atom::formatDate( time() ),
			'logo' => get_system_image_url( getSetting('favicon_image') ),
			'id' => 'tag:' . $_SERVER['SERVER_NAME'] . ',2005:' . G\url_to_relative( G\get_current_url() ),
			'generator' => 'Chevereto Feeds::Atom v' . Atom::getVersion() . '(with XMLight v' . XMLight::getVersion() . ')',
		));

		/* Add self atom link */
		$this->feed->appendNode('link', '', array(
			'href' => G\get_current_url(),
			'rel' => 'self'
		));

		$this->feed->appendNode('link', '', array(
			'href' => G\get_base_url(),
		));
	}

	public function registerNamespaces( Array $namespaces = array() ) {
		$this->feed->registerNamespaces($namespaces);
	}

	public function overrideElements(Array $override, $index = XMLight::firstChild) {
		$this->feed->setChildrenValues($index, $override);
	}

	public function setElementAttributes($name, Array $attributes, $index = XMLight::firstChild) {
		$this->feed->{$name}[$index]->setAttributes($attributes);
	}

	public function setLogo(Array $logo) {
		$this->feed->setChildrenValues( XMLight::firstChild, $logo );
	}

	public function setUser(Array $user) {
		$this->user = $user;
		$this->overrideElements(array(
				'title' => $this->user['name'],
				'subtitle' => _s('From %s', ['%s' => '"' . ( isset($this->category) ? $this->category['name'] : _s("%s's images", $this->user['name_short']) )]) . '" ' . _s('by %u', ['%u' => $this->user['name']]),
		));
		$this->setElementAttributes( 'link', array( 'href' => $this->user['url'] ) );
		$this->setLogo( array('logo' => $this->user['avatar']['url']) );
	}

	public function setCategory(Array $category) {
		$this->category = $category;
		if( !isset($this->user) ) {
			$this->overrideElements(array(
				'title' => $this->category['name'],
				'subtitle' => $this->category['description'] ?: 'No description'
			));
			$this->setElementAttributes( 'link', array( 'href' => get_category()['url'] ) );
		}
	}

	public function addItem(Array $item) {
		$this->feed->importArray($item);
	}

	public function addImage(Array $image) {
		$entry = $this->feed->appendNode('entry');
		$entry->appendNode('title', $image['title']);
		$entry->appendNode('link', '', array(
			'rel' => 'alternate',
			'type' => 'text/html',
			'href' => $image['url_viewer']
		));

		$entry->appendNode('id', 'tag:' . $_SERVER['SERVER_NAME'] . ',2005:' . G\url_to_relative($image['url_viewer']));

		$uploader = $image['user'] ?: array( 'name' => _s('Guest'), 'url' => G\get_base_url() );

		$author = $entry->appendNode('author');
		$author->appendNode('name', $uploader['name']);
		$author->appendNode('uri', $uploader['url']);

		$entry->appendNode( 'updated', Atom::formatDate( $image['date_gmt'] ) );
/*
		if ( isset($image['original_exifdata']) ) {
			$exif = json_decode( html_entity_decode($image['original_exifdata']) );
			if( isset($exif->DateTimeOriginal) ) {
				$entry->appendNode('date.Taken',
					Atom::formatDate( $exif->DateTimeOriginal ), array(), 'dc'
				);
			}
		}
*/
		if ( isset($image['category']) && !is_null( $image['category']['id'] ) && !is_null( $image['category']['name'] ) ) {
			$entry->appendNode('category', array(
				'term' => $image['category']['id'],
				'label' => $image['category']['name']
			));
		}

		if ( (bool)($image['chain'] & 1) ) { // mask 0001 check for thumbnail
			$html = strtr( Atom::$contentTemplate, array(
				'%p' => $uploader['url'],
				'%u' => $uploader['name'],
				'%d' => $image['description'],
				'%v' => $image['url_viewer'],
				'%t' => $image['thumb']['url']
			));
			$entry->appendNode('content', $html, ['type' => 'html']);
		}
/*
		if ( (bool)($image['chain'] & 2) ) { // mask 0010 check for medium
			$entry->appendNode('link', '', array(
				'rel' => 'enclosure',
				'type' => $image['medium']['mime'],
				'href' => $image['medium']['url']
			));
		}
*/
		if ( (bool)($image['chain'] & 12) ) { // mask 1100 check for large and original
			$entry->appendNode('link', '', array(
				'rel' => 'enclosure',
				'type' => $image['mime'],
				'href' => $image['url']
			));
		}
	}

	public function __toString() {
		return (string)$this->feed;
	}

	public function sendXML() {
		header('Content-Type: application/atom+xml; charset=utf-8');
		echo (string)$this;
	}
}

class AtomException extends Exception {}