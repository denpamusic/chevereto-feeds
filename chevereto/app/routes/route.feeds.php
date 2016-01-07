<?php
$route = function($handler) {
	try {
		if($handler->isRequestLevel(3)) return $handler->issue404();

		/* Max images to output regardless of presence of chevereto api v1 key. */
		$softLimit = 25;
		/* Max images to output in presence of chevereto api v1 key. */
		$hardLimit = 100;
		/* Available feed types. Format: 'url-key' => 'classname' */
		$feedTypes = ['atom' => 'CHV\Atom', 'rss' => 'CHV\Rss'];
		/* Built-in GZip Compression */
		$gzip = false;

		/* Remove or comment out this entire block if you don't want a hello page with version info */
		if( isset($_REQUEST['hello']) ) {
			$c = '';
			foreach($feedTypes as $type => $classname) {
				$classList .= isset( class_implements($classname)['CHV\FeedModule'] ) ? $c . "$classname v" . $classname::getVersion() : '';
				$c = ', ';
			}
			echo 'Welcome to CHV\Feeds v' . CHV\FeedModule::modVersion . ' with XMLight v' . CHV\XMLight::getVersion() .  '<br />'.'Modules: ' . $classList . '<br /> Have a nice day!';
			die();
		}
		/* End of hello block. */

		$limit = (integer)$_REQUEST['limit'];

		if(!$limit || $limit < 0) {
			$limit = $softLimit;
		}

		if($limit > $softLimit) {
			if($limit > $hardLimit) return $handler->issue404();
			if( is_null(CHV\getSetting('api_v1_key')) || !G\timing_safe_compare(CHV\getSetting('api_v1_key'), $_REQUEST['key']) ) {
				return $handler->issue404();
			}
		}

		$username = $_REQUEST['user'] ?: null;
		$category_url_key = $_REQUEST['category'] ?: null;
		$categories = $handler::getVar('categories');

		$request = strtolower($handler->request[0]);
		if( !array_key_exists($request, $feedTypes) ) {
			return $handler->issue404();
		}
		$type = $feedTypes[$request];

		if($category_url_key) {
			foreach($categories as $v) {
				if($v['url_key'] == $category_url_key) {
					$category = $v;
					break;
				}
			}
			if(!$category) {
				return $handler->issue404();
			}
		}

		if($username) {
			$user = CHV\User::getSingle($username, 'username');
			/* Check that user with this username exists and has a valid status. */
			if(!$user || $user['status'] !== 'valid') {
				return $handler->issue404();
			}
		}

		date_default_timezone_set('UTC');
		try {
			$list = new CHV\Listing;
			$list->setType('images');
			$list->setOffset(0);
			$list->setLimit($limit); // how many results?
			$list->setSortType('date'); // date | size | views
			$list->setSortOrder('desc'); // asc | desc
			$list->setRequester( CHV\Login::getUser() );

			/* Select feed type */
			$feed = new $type();
			if( !($feed instanceof CHV\FeedModule) ) {
				throw new Exception("$type is not a valid feed module!");
			}

			/* Check for category */
			if($category) {
					$list->setCategory($category['id']);
					$feed->setCategory($category);
			}

			/* Check for username */
			if($user) {
					$list->setWhere('WHERE user_username = :u');
					$list->bind( ':u', $user['username'] );
					$feed->setUser($user);
			}

			$list->exec();
		} catch(Exception $e) {
			G\exception_to_error($e);
		}

		$list_size = count($list->output);
		for ($i = 0; $i < $list_size; $i++) {
			$image = CHV\Image::formatArray($list->output[$i], true);
			$image['rating'] = ($image['nsfw'] == 0) ? 'nonadult' : 'adult';
			$image['description'] = $image['description'] ?: 'No description.';
			if ( $image['category_id'] != 0 ) {
				$image['category'] = get_categories()[ $image['category_id'] ]['name'];
			}

			$feed->addImage($image);
		}
		if($gzip) ob_start('ob_gzhandler') || ob_start();
		$feed->sendXML();
		die();
	} catch(Exception $e) {
		G\exception_to_error($e);
	}
};