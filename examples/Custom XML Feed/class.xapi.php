<?php
/**
	Example of custom XML feed class for Chevereto Image Hosting Script.

	To see this module in action, copy it to /app/lib/classes/, append it to $feedTypes
	variable in /app/routes/route.feeds.php:
		$feedTypes = ['atom' => 'CHV\Atom', 'rss' => 'CHV\Rss', 'xapi' => 'CHV\Xapi'];
	and navigate to http://example.com/feeds/xapi.

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

class Xapi implements FeedModule {
	/* Used for storing the XMLight object containg the whole XML tree */
	private $feed;
	/* Used for storing chevereto user array */
	private $user;
	/* Used for storing chevereto category array */
	private $category;

	public static $version = '0.1.0';

	public static function getVersion() {
		return self::$version;
	}

	/*
		Formats given time string according to the respective feed standarts.
		Accepts either unix timestamp or date string.
		For format reference please read
			PHP manual on date() function: http://php.net/manual/function.date.php
			and Predefined DateTime Constants: http://php.net/manual/class.datetime.php#datetime.constants.types
	*/
	public static function formatDate($timestamp) {
		if( !is_numeric($timestamp) ) {
			$timestamp = strtotime($timestamp);
		}
		return date(DATE_RFC850, $timestamp);
	}

	/*
		In class constructor we create XMLight object with first element of the tree
		as an argument(in this case 'chevereto') and store it in $this->feed variable.

		Then we already can use this object to set some values that we're know or
		to add xml namespaces to the root element.

		For example, XML created by this class constructor will be like
		<chevereto xmlns="http://dev.swrn.net/chv/xapi">
			<website>
				<title>Example.COM</title>
				<description>DOGFOOD FOR DAYS!</description>
				<logo>https://example.com/content/images/system/favicon.png</logo>
				<url>https://example.com/</url>
				<id>hash:md5:09f966a7161a6faf151292c2816686c9</id>
				<feed>
					<url>https://example.com/feeds/xapi</url>
					<generated>Tuesday, 04-Nov-14 11:01:47 UTC</generated>
					<generator>Chevereto Feeds::XAPI v0.7.1(with XMLight v1.0.6)</generator>
				</feed>
			</website>
		</chevereto>
	*/
	public function __construct() {
		$this->feed = new XMLight('chevereto');
		/* By xml standard namespace must be url, although not necessary an existing one. */
		$this->feed->registerNamespaces(['http://dev.swrn.net/chv/xapi']);

		/* Add generic head */
		$this->feed->importArray(array(
			'website' => array(
				'title' => getSetting('website_doctitle', true),
				'description' => getSetting('website_description', true),
				'logo' => get_system_image_url( getSetting('favicon_image') ),
				'url' => G\get_base_url(),
				'id' => 'hash:md5:' . md5( G\get_current_url() ),
				'feed' => array(
					'url' => G\get_current_url(),
					'updated' => Xapi::formatDate( time() ),
					'generator' => 'Chevereto Feeds::XAPI v' . Xapi::getVersion() . '(with XMLight v' . XMLight::getVersion() . ')'
				)
			)
		));
	}

	/*
		A shortcut to add a namespace(xmlns attribute) for the first(root) element of the feed.
	*/
	public function registerNamespaces( Array $namespaces = array() ) {
		$this->feed->registerNamespaces($namespaces);
	}

	/*
		Used to override elements that are already set.
		By xml standard, tags are not unique so we're also need to provide an index
		to differentiate between two tags with the same name.
		Notice that indexes start from 0(XMLight::firstChild), so element with index 1 is actually second element.

		For example if we have some xml
			<user>
				<group>Keyboardists</group>
				<group>Suck</group>
			</user>
		and call
			overrideElements(array( 'user' => array( 'group' => 'Rock' ) ), 1);
		we'll get
			<user>
				<group>Keyboardists</group>
				<group>Rock</group>
			</user>
	*/
	public function overrideElements(Array $override, $index = XMLight::firstChild) {
		$this->feed->setChildrenValues($index, $override);
	}

	/*
		Sets attributes for XML element identified by tag and index.
		After calling this function as setElementAttributes('group', array('parent' => 'Musicians'), XMLight::firstChild)
		on example above we'll get

		<user>
			<group parent="Musicians">Keyboardists</group>
			<group>Rock</group>
		</user>
	*/
	public function setElementAttributes($name, Array $attributes, $index = XMLight::firstChild) {
		$this->feed->{$name}[$index]->setAttributes($attributes);
	}

	/*
		Optional shortcut for setting up feed logo.
		Used to imporove readability.
		Isn't used in this example.
	*/
	public function setLogo(Array $logo) {
		$this->feed->setChildrenValues( XMLight::firstChild, $logo );
	}

	/*
		Function to sets user for current feed and can be used to change some feed elements
		if username is present in request.
		Accepts array containing user information.

		In this example we add user xml element to our feed:
		<user>
			<username>Dude</username>
			<fullname>Do You Even Code</fullname>
			<image>http://example.com/content/images/users/D/av_1408034123.png</image>
			<profile>http://example.com/user/dude</profile>
		</user>
	*/
	public function setUser(Array $user) {
		$this->user = $user;
		$this->addItem(array(
			'user' => array(
				'username' => $user['username'],
				'fullname' => $user['name'],
				'image' => $user['avatar']['url'],
				'profile' => $user['url']
			)
		));
	}

	/*
		Sets category for current feed and can be used to change some feed elements
		if category is present in request.
		Accepts array containing category information.

		In this example we add category xml element to our feed:
		<category>
			<name>Flangers</name>
			<description>Kinda like your mama's toaster on drugs.</description>
			<url>http://example.com/category/flangers</url>
		</category>
	*/
	public function setCategory(Array $category) {
		$this->category = $category;
		$this->addItem(array(
			'category' => array(
				'name' => $category['name'],
				'description' => $category['description'],
				'url' => $category['url'],
			)
		));
	}

	/*
		Publicaly visible shortcut for adding new element to our feed from array.
		Accept regular or multidimensional arrays with 'tag' => 'value' format.
	*/
	public function addItem(Array $item) {
		$this->feed->importArray($item);
	}

	/*
		Function to import image array generated by CHV\Listing and formated by CHV\Image::formatArray.
		Contents of this array can be seen in "Full info" tab of any chevereto image.

		Note that this function will run multiple times for all images returned by CHV\Listing,
		so avoid cluttering it with resource intensive stuff or you'll get poor performance.

		Here's an example of XML element generated by function below:
		<image>
			<id>Io</id>
			<title>101% AWESOME</title>
			<description>Overflow</description>
			<url>http://example.com/image/Io</url>
			<updated>Tuesday, 04-Nov-14 11:01:47 UTC</updated>
			<thumb width="160" height="160" type="image/png" bytesize="12452">https://example.com/images/2014/09/03/overflow.th.png</thumb>
			<medium width="500" height="708" type="image/png" bytesize="110521">https://example.com/images/2014/09/03/overflow.md.png</medium>
			<large width="848" height="1200" type="image/png" bytesize="445560">https://example.com/images/2014/09/03/overflow.png</large>
			<uploader>
				<name>The God</name>
				<uri>https://example.com/user/teh_dog</uri>
			</uploader>
		</image>
	*/
	public function addImage(Array $image) {
		$entry = $this->feed->appendNode('image');
		$entry->appendNode('id', $image['id_encoded']);
		$entry->appendNode('title', $image['name']);
		$entry->appendNode('description', $image['description']);
		$entry->appendNode('url', $image['url_viewer']);
		$entry->appendNode( 'updated', Xapi::formatDate($image['date_gmt']) );

		/*
			Check for available image sizes by utilizing a chain property.
			see https://chevereto.com/src/img/misc/image-chain.png
		*/
		if ( (bool)($image['chain'] & 1) ) { // 1 is 0001 in binary :/, so we get only last bit to check for thumbnail.
			$entry->appendNode('thumb', $image['thumb']['url'], array(
				'width' => $image['thumb']['width'],
				'height' => $image['thumb']['height'],
				'type' => $image['thumb']['mime'],
				'bytesize' => $image['thumb']['size']
			));
		}

		if ( (bool)($image['chain'] & 2) ) { // 2 is 0010 in binary, so we get only second bit to check for medium.
			$entry->appendNode('medium', $image['medium']['url'], array(
				'width' => $image['medium']['width'],
				'height' => $image['medium']['height'],
				'type' => $image['medium']['mime'],
				'bytesize' => $image['medium']['size']
			));
		}

		if ( (bool)($image['chain'] & 12) ) { // 12 is 1100 in binary, so we get third and fourth bits to check for large and original.
			$entry->appendNode('large', $image['url'], array(
				'width' => $image['width'],
				'height' => $image['height'],
				'type' => $image['mime'],
				'bytesize' => $image['size']
			));
		}

		/* Check if uploader is registred user. */
		$by = $image['user'] ?: array( 'name' => _s('Guest'), 'url' => G\get_base_url() );

		$uploader = $entry->appendNode('uploader');
		$uploader->appendNode('name', $by['name']);
		$uploader->appendNode('url', $by['url']);

		if ( isset($image['category']) ) {
			$entry->appendNode('category', array(
				'id' => $image['category_id'],
				'name' => $image['category']
			));
		}
	}

	/*
		This function is used when we try to
		convert this feed object to a string by either using constructions that
		require it to be a string(echo, sprintf, etc.) or typecasting it as such with (string)$object.

		Here it's returning XMLight object as string which forces it to generate XML document.
	*/
	public function __toString() {
		return (string)$this->feed;
	}

	/*
		Sets correct Content-Type header for our feed and sends it to the client.
	*/
	public function sendXML() {
		header('Content-Type: application/xml; charset=utf-8');
		echo (string)$this;
	}
}

class XapiException extends Exception {}