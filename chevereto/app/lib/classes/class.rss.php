<?php
/**
	Extensible RSS 2.0 compatible class for Chevereto Image Hosting Script.

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

class Rss implements FeedModule {
	private $feed;
	private $channel;
	private $user;
	private $category;

	public static $version = FeedModule::modVersion;

	public static function getVersion() {
		return self::$version;
	}

	public static function formatDate($timestamp) {
		if( !is_numeric($timestamp) ) {
			$timestamp = strtotime($timestamp);
		}
		return date(DATE_RSS, $timestamp);
	}

	public function __construct() {
		$this->feed = new XMLight('rss', array('version' => '2.0'));
		$this->feed->registerNamespaces([
			'atom' => 'http://www.w3.org/2005/Atom',
			'media' => 'http://search.yahoo.com/mrss/'
		]);
		$this->channel = $this->feed->appendNode('channel');

		$now = Rss::formatDate( time() );

		$website_description = getSetting('website_description', true);
		$website_doctitle = getSetting('website_doctitle', true);
		$website_url = G\get_base_url();
		$website_logo = get_system_image_url( getSetting('favicon_image') );

		/* Add generic RSS 2.0 channel head with feed logo */
		$this->channel->importArray(array(
			'title' => $website_doctitle,
			'link' => $website_url,
			'description' => $website_description,
			'pubDate' => $now,
			'lastBuildDate' => $now,
			'generator' => 'Chevereto Feeds::MRSS v' . Rss::getVersion() . '(with XMLight v' . XMLight::getVersion() . ')',
			'ttl' => 60,
			'image' => array(
				'url' => $website_logo,
				'title' => $website_doctitle,
				'link' => $website_url
			)
		));

		/* Add self atom link */
		$this->channel->appendNode('link', '', array(
			'href' => G\get_current_url(),
			'rel' => 'self',
			'type' => 'application/rss+xml'
		), 'atom');
	}

	/* Register XML namespaces. Necessary for RSS extensions. */
	public function registerNamespaces( Array $namespaces = array() ) {
		$this->feed->registerNamespaces($namespaces);
	}

	public function overrideElements(Array $override, $index = XMLight::firstChild) {
		$this->channel->setChildrenValues($index, $override);
	}

	public function setElementAttributes($name, Array $attributes, $index = XMLight::firstChild) {
		$this->channel->{$name}[$index]->setAttributes($attributes);
	}

	public function setLogo(Array $logo) {
		$this->channel->setChildrenValues( XMLight::firstChild, array('image' => $logo) );
	}

	public function setUser(Array $user) {
		$this->user = $user;

		if( isset($this->category) ) {
			$description = _s( 'From %a by %u', array('%a' => $this->category['name'], '%u' => $this->user['name']) );
		} else {
			$description = _s( 'From %a by %u', array(
				'%a' => _s("%s's images", $this->user['name_short']),
				'%u' => $this->user['name']
			));
		}

		$this->overrideElements(array(
				'title' => $this->user['name'],
				'description' => $description,
				'url' => $this->user['url']
		));
		$this->setLogo(array(
			'url' => $this->user['avatar']['url'],
			'title' => $this->user['name'],
			'link' => $this->user['url']
		));
	}

	public function setCategory(Array $category) {
		$this->category = $category;
		if( !isset($this->user) ) {
			$this->overrideElements(array(
				'title' => $this->category['name'],
				'description' => $this->category['description'] ?: 'No description',
				'url' => $this->category['url']
			));
			$this->setLogo(array(
				'url' => CHV\get_system_image_url( CHV\getSetting('logo_image') ),
				'title' => $this->category['name'],
				'link' => $this->category['url']
			));
		}
	}

	public function addItem(Array $item) {
		$this->feed->importArray($item);
	}

	public function addImage(Array $image) {
		$item = $this->channel->appendNode('item');
		$item->appendNode('title', $image['name']);
		$item->appendNode('link', $image['url_viewer']);
		$item->appendNode('description', $image['description']);
		$item->appendNode('description', $image['description'], array('type' => 'plain'), 'media');
		$item->appendNode('rating', $image['rating'], array('scheme' => 'urn:simple'), 'media');

		if ( isset($image['category']) ) {
			$item->appendNode('category', $image['category']);
		}

		if ( isset($image['user']) ) {
			$item->appendNode('credit', $image['user']['name'], array(), 'media');
		}

		$group = $item->appendNode('group', '', array(), 'media');
		if ( (bool)($image['chain'] & 1) ) { // mask 0001  check for thumbnail
			$item->appendNode('thumbnail', '', array(
				'url' => $image['thumb']['url'],
				'width' => getSetting('upload_thumb_width'),
				'height' => getSetting('upload_thumb_height')
			), 'media');
		}

		if ( (bool)($image['chain'] & 2) ) { // mask 0010  check for medium
			$medium_size = getSetting('upload_medium_size');
			$medium_fixed_dimension = getSetting('upload_medium_fixed_dimension');

			$image_medium_options = [];
			$image_medium_options[$medium_fixed_dimension] = $medium_size;
			$image_ratio = $medium_fixed_dimension == 'width' ? (double)($image['height']/$image['width']) : (double)($image['width']/$image['height']);

			$group->appendNode('content', '', array(
				'url' => $image['medium']['url'],
				'fileSize' => $image['medium']['size'],
				'type' => $image['mime'],
				'medium' => 'image',
				'isDefault' => 'false',
				'width' => getSetting('upload_medium_size'),
				'height' => round( $image[$medium_fixed_dimension] * $image_ratio ),
			), 'media');
		}

		if ( (bool)($image['chain'] & 12) ) { // mask 1100 check for large and original
			$item->appendNode('enclosure', '', array(
				'url' => $image['url'],
				'length' => $image['size'],
				'type' => $image['mime']
			));
			$group->appendNode('content', '', array(
				'url' => $image['url'],
				'fileSize' => $image['size'],
				'type' => $image['mime'],
				'medium' => 'image',
				'isDefault' => 'true',
				'width' => $image['width'],
				'height' => $image['height'],
			), 'media');
		}

		$item->appendNode('guid', $image['url']);
		$item->appendNode('pubDate', Rss::formatDate( $image['date_gmt'] ));
	}

	public function __toString() {
		return (string)$this->feed;
	}

	public function sendXML() {
		header('Content-Type: application/rss+xml; charset=utf-8');
		echo (string)$this;
	}
}

class RssException extends Exception {}