<?php
/**
	Extensible RSS 2.0 compatible class for Chevereto Image Hosting Script.

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
		$this->overrideElements(array(
				'title' => $this->user['name'],
				'description' => _s('From %s', ['%s' => '"' . ( isset($this->category) ? $this->category['name'] : _s("%s's images", $this->user['name_short']) )]) . '" ' . _s('by %u', ['%u' => $this->user['name']]),
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
		$item->appendNode('title', $image['title']);
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