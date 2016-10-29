<?php
/**
	Extensible Atom 1.0 compatible class for Chevereto Image Hosting Script.

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

		if( isset($this->category) ) {
			$description = _s( 'From %a by %u', array(
				'%a' => $this->category['name'],
				'%u' => $this->user['name']
			));
		} else {
			$description = _s( 'From %a by %u', array(
				'%a' => _s("%s's images", $this->user['name_short']),
				'%u' => $this->user['name']
			));
		}

		$this->overrideElements(array(
				'title' => $this->user['name'],
				'subtitle' => $description
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
		$entry->appendNode('title', $image['name']);
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