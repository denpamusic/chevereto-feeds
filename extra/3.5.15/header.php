<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?><!DOCTYPE HTML>
<html <?php echo CHV\Render\get_lang_html_tags(); ?> class="tone-<?php echo CHV\getSetting('theme_tone'); ?>">
<head>
<meta charset="utf-8">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no, maximum-scale=1">
<meta name="description" content="<?php echo get_meta_description(); ?>">
<meta name="keywords" content="<?php echo get_meta_keywords(); ?>">

<title><?php echo get_doctitle(); ?></title>

<?php CHV\Render\include_peafowl_head(); ?>

<link rel="stylesheet" href="<?php echo CHV\Render\versionize_src(CHV\Render\get_theme_file_url('style.css')); ?>">
<link rel="stylesheet" href="<?php echo CHV\Render\versionize_src(CHV\Render\get_theme_file_url('custom_hooks/style.css')); ?>">
<link rel="shortcut icon" href="<?php echo CHV\get_system_image_url(CHV\getSetting('favicon_image')); ?>">
<link rel="apple-touch-icon" href="<?php echo CHV\get_system_image_url(CHV\getSetting('favicon_image')); ?>" sizes="114x114">

<link rel="alternate" href="<?php echo G\get_base_url() . 'feeds/rss'; ?>" title="Recent Images RSS Feed" type="application/rss+xml">
<link rel="alternate" href="<?php echo G\get_base_url() . 'feeds/atom'; ?>" title="Recent Images Atom Feed" type="application/atom+xml">
<?php if(G\get_route_name() == 'user') { ?>
<link rel="alternate" href="<?php echo G\get_base_url() . 'feeds/rss/?user=' . get_user()['username'] ?>" title="<?php echo get_user()['name'] . '\'s RSS Feed' ?>" type="application/rss+xml">
<link rel="alternate" href="<?php echo G\get_base_url() . 'feeds/atom/?user=' . get_user()['username'] ?>" title="<?php echo get_user()['name'] . '\'s Atom Feed' ?>" type="application/atom+xml">
<?php } ?>

<?php
if((int) CHV\getSetting('theme_logo_height') > 0) {
	$logo_height = (int) CHV\getSetting('theme_logo_height');
	echo '<style type="text/css">.top-bar-logo, .top-bar-logo img { height: '.$logo_height.'px; } .top-bar-logo { margin-top: -'.(int) ($logo_height/2).'px; } </style>';
}
?>

<?php
$open_graph = [
	'type'			=> 'website',
	'url'			=> G\get_current_url(),
	'title'			=> CHV\getSetting('website_doctitle', true),
	'site_name' 	=> CHV\getSetting('website_name', true),
	'description'	=> CHV\getSetting('website_description', true)
];

switch(true) {
	case function_exists('get_image') and G\is_route("image"):
		$open_graph_extend = [
			'title'			=> get_pre_doctitle(),
			'description'	=> get_image()['description'],
			'image'			=> get_image()['display_url'],
			'image:width'	=> get_image()['width'],
			'image:height'	=> get_image()['height']
		];
	break;
	case function_exists('get_user') and G\is_route("user"):
		$open_graph_extend = [
			'type'			=> 'profile',
			'title'			=> get_user()['name'],
			'description'	=> sprintf(is_user_images() ? _s("%s's Images") : _s("%s's Albums"), get_user()["name_short"]),
			'image'			=> get_user()['avatar']['url'],
		];
	break;
	case function_exists('get_album') and G\is_route("album"):
		$open_graph_extend = [
			'title'			=> get_album()['name'],
			'description'	=> get_album()['description'],
		];
	break;
}
if($open_graph_extend) {
	$open_graph = array_merge($open_graph, $open_graph_extend);
}
foreach($open_graph  as $k => $v) {
	if(!$v) continue;
	echo '<meta property="og:'.$k.'" content="'.$v.'">'."\n";
}
?>

<?php if(function_exists('get_image') && G\is_route("image")) { ?>
<link rel="image_src" href="<?php echo get_image()['url']; ?>">
<?php } ?>
<?php if(CHV\getSetting('theme_custom_css_code')) { ?>
<style><?php echo CHV\getSetting('theme_custom_css_code'); ?></style>
<?php } ?>
<?php if(CHV\getSetting('theme_custom_js_code')) { ?>
<script><?php echo CHV\getSetting('theme_custom_js_code'); ?></script>
<?php } ?>

</head>

<?php
	G\Render\include_theme_file('custom_hooks/header');
	if(!G\isPreventedRoute() and in_array(G\get_route_name(), ['user', 'image']) && !is_404()) {	
		$body_class = (G\is_route("image") or (G\is_route("user") and get_user()["background"]) or is_owner() or is_admin()) ? " no-margin-top" : "";
	}
	if(G\Handler::getRoute() == 'index') {
		$body_class = CHV\Settings::get('homepage_style');
	}
?>
<?php if(G\Handler::getRoute() == 'index' and in_array($body_class, ['landing', 'split']) ) { ?>
<style>
#home-cover {
	background-image: url(<?php echo CHV\get_system_image_url(CHV\Settings::get('homepage_cover_image')); ?>);
}
</style>
<?php } ?>

<body id="<?php echo G\getTemplateUsed(); ?>" class="<?php echo $body_class; ?>">

<?php echo CHV\getSetting('analytics_code'); ?>

<?php if(!is_maintenance()) {; ?>
<header id="top-bar" class="top-bar<?php if(in_array($body_class, ['landing', 'split'])) { echo ' transparent'; } ?>">
	<?php if(is_private_gate()) { ?>
	<div class="c24 center-box content-width">
	<?php } ?>
    <div class="content-width">
    
        <div id="logo" class="top-bar-logo<?php if(is_private_gate()) { ?> text-align-left<?php } ?>"><a href="<?php echo CHV\Login::isLoggedUser() ? (CHV\Settings::get('logged_user_logo_link') == 'user_profile' ? CHV\Login::getUser()['url'] : G\get_base_url()) : G\get_base_url(); ?>"><img class="replace-svg" src="<?php echo CHV\get_system_image_url(CHV\getSetting(CHV\getSetting('logo_vector_enable') ? 'logo_vector' : 'logo_image')); ?>" alt="<?php echo CHV\getSetting('website_name'); ?>"></a></div>
        
		<?php if(CHV\getSetting('website_mode') == 'public' or (CHV\getSetting('website_mode') == 'private' and CHV\Login::getUser())) { ?>
        <ul class="top-bar-left float-left">
			
			<li data-action="top-bar-menu-full" data-nav="mobile-menu" class="top-btn-el phone-show hidden">
				<span class="top-btn-text"><span class="icon icon-menu3"></span></span>
			</li>
			
			<?php
				if(CHV\getSetting('website_explore_page')) {
					// Category selector
					$categories = get_categories();
					
					if(count($categories) > 0) {
						array_unshift($categories, [
							'id'		=> NULL,
							'name'		=> _s('All'),
							'url_key'	=> NULL,
							'url'		=> G\get_base_url('explore')
						]);
						
						$cols = min(6, ceil(count($categories) / 6));
			?>
			<li id="top-bar-explore" data-nav="explore" class="phone-hide pop-btn pop-btn-delayed pop-btn-show<?php if(G\get_route_name() == 'explore') { ?> current<?php } ?>">
				<?php
					
				?>
                <span class="top-btn-text"><span class="icon icon-images2"></span><span class="btn-text phone-hide phablet-hide"><?php _se('Explore'); ?></span></span>
                <div class="pop-box <?php if($cols > 1) { echo sprintf('pbcols%d ', $cols); } ?>arrow-box arrow-box-top anchor-left">
                    <div class="pop-box-inner pop-box-menu<?php if($cols > 1) { ?> pop-box-menucols<?php } ?>">
                        <ul>
						<?php
							foreach($categories as $k => $v){
								echo '<li data-content="category" data-category-id="' . $v['id'] . '"><a data-content="category-name" data-link="category-url" href="' . $v['url'] . '">' . $v["name"] . '</a></li>'."\n";
								$count++;
							}
						?>
                        </ul>
                    </div>
                </div>
			</li>
            <?php
            	} else {
            ?>
            <li id="top-bar-explore" data-nav="explore" class="phone-hide top-btn-el<?php if(G\get_route_name() == 'explore') { ?> current<?php } ?>">
            	<a href="<?php echo G\get_base_url('explore'); ?>"><span class="top-btn-text"><span class="icon icon-images2"></span><span class="btn-text phone-hide phablet-hide"><?php _se('Explore'); ?></span></span></a>
            </li>
            <?php
            	}	
            }
			?>
			
			<?php if(CHV\getSetting('website_search')) { ?>
            <li data-action="top-bar-search"  data-nav="search" class="phone-hide pop-btn">
                <span class="top-btn-text"><span class="icon icon-search"></span><span class="btn-text phone-hide phablet-hide"><?php _se('Search'); ?></span></span>
            </li>
            <li data-action="top-bar-search-input" class="top-bar-search-input phone-hide pop-btn pop-keep-click hidden">
                <div class="input-search">
                	<form action="<?php echo G\get_base_url("search/images"); ?>" method="get">
                    	<input class="search" type="text" placeholder="<?php _se('Search'); ?>" autocomplete="off" spellcheck="false" name="q">
                    </form>
                    <span class="icon-search"></span><span class="icon close icon-close" data-action="clear-search" title="<?php _se('Close'); ?>"></span><span class="icon settings icon-edit" data-modal="form" data-target="advanced-search" title="<?php _se('Advanced search'); ?>"></span>
                </div>
            </li>
			<div class="hidden" data-modal="advanced-search">
				<span class="modal-box-title"><?php _se('Advanced search'); ?></span>
				<?php G\Render\include_theme_file('snippets/form_advanced_search'); ?>
			</div>
			<?php } ?>
			
			<?php if(CHV\getSetting('website_random')) { ?>
			<li id="top-bar-random"  data-nav="random" class="phone-hide top-btn-el">
                <a href="<?php echo G\get_base_url("?random"); ?>"><span class="top-btn-text"><span class="icon icon-shuffle"></span><span class="btn-text phone-hide phablet-hide"><?php _se('Random'); ?></span></span></a>
            </li>
			<?php } ?>
            
        </ul>
		<?php } ?>
        <ul class="top-bar-right float-right keep-visible">
			
			<?php if(get_system_notices()) { ?>
				<li data-nav="notices" class="phone-hide pop-btn pop-keep-click">
                <span class="top-btn-text"><span class="icon icon-notification color-red"></span><span class="btn-text phone-hide phablet-hide"><?php _se('Notices (%s)', count(get_system_notices())); ?></span></span>
				<div class="pop-box anchor-center c8 arrow-box arrow-box-top anchor-center">
					<div class="pop-box-inner padding-20">
						<ul class="list-style-type-disc list-style-position-inside">
						<?php foreach(get_system_notices() as $notice) { ?>
							<li><?php echo $notice; ?></li>
						<?php } ?>
						</ul>
					</div>
				</div>
            </li>
			<?php } ?>
			
			<?php if(CHV\Login::getUser()['is_admin'] or (CHV\getSetting('enable_uploads') and !is_private_gate())) { ?>
            <li data-action="top-bar-upload" data-nav="upload" class="phone-hide pop-btn"<?php if(!CHV\getSetting('guest_uploads')) { ?> data-login-needed="true"<?php } ?>>
                <span class="top-btn-text"><span class="icon icon-cloud-upload"></span><span class="btn-text phone-hide phablet-hide"><?php _se('Upload'); ?></span></span>
            </li>
			<?php } ?>
			
        	<?php
				if(!CHV\Login::isLoggedUser()) {
			?>
            <?php
					if(is_captcha_needed()) {
			?>
			<li id="top-bar-signin" data-nav="signin" class="<?php if(G\is_route("login")) echo "current "; ?>top-btn-el">
				<a href="<?php echo G\get_base_url('login'); ?>" class="top-btn-text"><span class="icon icon-login tablet-hide laptop-hide desktop-hide"></span><span class="text phone-hide phablet-hide"><?php _se('Sign in'); ?></span></a>
			</li>
			<?php
					} else {
			?>
			<li id="top-bar-signin" data-nav="signin" class="<?php if(G\is_route("login")) echo "current "; ?>pop-btn pop-btn-delayed pop-account pop-keep-click">
				<span class="top-btn-text"><span class="icon icon-login tablet-hide laptop-hide desktop-hide"></span><span class="text phone-hide phablet-hide"><?php _se('Sign in'); ?></span></span>
                <div id="top-signin-menu" class="pop-box anchor-center c8 arrow-box arrow-box-top anchor-center">
                    <div class="pop-box-inner">
                    	<?php
                        	if(CHV\getSetting('social_signin')) {
						?>
                        <span class="title"><?php _se('Sign in with another account'); ?></span>
                   		<ul class="sign-services text-align-center">
                            <?php G\Render\include_theme_file('snippets/sign_services_buttons'); ?>
                        </ul>
                        <div class="or-separator"><span><?php _se('or'); ?></span></div>
                        <?php
							}
						?>
                        <form method="post" action="<?php echo G\get_base_url("login"); ?>" autocomplete="off">
							<?php echo G\Render\get_input_auth_token(); ?>
                        	<div class="input"><input type="text" class="text-input" name="login-subject" placeholder="<?php _se('Username or Email address'); ?>" autocomplete="off" required></div>
                            <div class="input"><input type="password" class="text-input" name="password" placeholder="<?php _se('Password'); ?>" autocomplete="off" required><button type="submit" class="icon-input-submit"></button></div>
                            <div class="input margin-bottom-0 overflow-auto">
                            	<div class="checkbox-label"><input type="checkbox" name="keep-login" id="keep-login" value="1"><label for="keep-login"><?php _se('Keep me logged in'); ?></label></div>
                                <div class="float-right"><a href="<?php echo G\get_base_url("account/password-forgot"); ?>"><?php _se('Forgot your password?'); ?></a></div>
                            </div>
                        </form>
						<?php
							if(CHV\getSetting('enable_signups')) {
						?>
						<div class="input text-align-center margin-top-10 margin-bottom-0"><?php _se("Don't have an account? <a href='%s'>Sign up</a> now.", G\get_base_url('signup')); ?></div>
						<?php
							}
						?>
                    </div>
                </div>
            </li>
			<?php
					}
			?>
			<?php
					if(CHV\getSetting('enable_signups')) {
						if(is_captcha_needed()) {
			?>
			<li id="top-bar-signup" data-nav="signup" class="<?php if(G\is_route("signup")) echo "current "; ?>phone-hide top-btn-el">
				<a href="<?php echo G\get_base_url('signup'); ?>" class="top-btn-text top-btn-create-account btn blue text"><span class="icon icon-user phablet-hide tablet-hide laptop-hide desktop-hide"></span><?php _se('Create account'); ?></a>
			</li>
			<?php
						} else {		
			?>
			<li id="top-bar-signup" data-nav="signup" class="<?php if(G\is_route("signup")) echo "current "; ?>phone-hide pop-btn pop-btn-delayed pop-account pop-keep-click">
            	<span class="top-btn-text top-btn-create-account btn blue text"><span class="icon icon-user phablet-hide tablet-hide laptop-hide desktop-hide"></span><?php _se('Create account'); ?></span>
                <div id="top-signup-menu" class="pop-box anchor-center c8 arrow-box arrow-box-top">
                    <div class="pop-box-inner">
                    	<?php
                        	if(CHV\getSetting('social_signin')) {
						?>
                        <span class="title"><?php _se('Sign up with another account'); ?></span>
                   		<ul class="sign-services text-align-center">
                        	<?php G\Render\include_theme_file('snippets/sign_services_buttons'); ?>
                        </ul>
                        <div class="or-separator"><span><?php _se('or'); ?></span></div>
                        <?php
							}
						?>
                        <form method="post" action="<?php echo G\get_base_url("signup"); ?>" autocomplete="off">
							<?php echo G\Render\get_input_auth_token(); ?>
                        	<div class="input"><input type="email" class="text-input" name="email" placeholder="<?php _se('Email address'); ?>" autocomplete="off" required></div>
                        	<div class="input"><input type="text" class="text-input" name="username" placeholder="<?php _se('Username'); ?>" autocomplete="off" required></div>
                            <div class="input"><input type="password" class="text-input" name="password" placeholder="<?php _se('Password'); ?>" autocomplete="off" required><button type="submit" class="icon-input-submit"></button></div>
                            <div class="input text-align-center margin-bottom-0"><?php _se('By signing up you agree to our <a href="%s">Terms of service</a>', G\get_base_url('page/tos')); ?></div>
                        </form>
                    </div>
                </div>
            </li>
			<?php
						} 
					} // signups
			?>
            
			<?php
				} else {
			?>
            <li id="top-bar-user" data-nav="user" class="pop-btn pop-btn-delayed">
                <span class="top-btn-text">
					<?php if(CHV\Login::getUser()["avatar"]["url"]) { ?>
					<img src="<?php echo CHV\Login::getUser()["avatar"]["url"]; ?>" alt="" class="user-image">
					<?php } else { ?>
					<img src="" alt="" class="user-image hidden">
					<?php } ?>
					<span class="user-image default-user-image<?php echo (CHV\Login::getUser()["avatar"]["url"] ? ' hidden' : ''); ?>"><span class="icon icon-user"></span></span>
					<span class="text phone-hide"><?php echo CHV\Login::getUser()["name"]; ?></span><span class="arrow-down"></span>
				</span>
                <div class="pop-box arrow-box arrow-box-top anchor-right">
                    <div class="pop-box-inner pop-box-menu">
                        <ul>
                            <li><a href="<?php echo CHV\Login::getUser()["url"]; ?>"><?php _se('My Profile'); ?></a></li>
							<li><a href="<?php echo CHV\Login::getUser()["url_albums"]; ?>"><?php _se('Albums'); ?></a></li>
                            <li><a href="<?php echo G\get_base_url("settings"); ?>"><?php _se('Settings'); ?></a></li>
							<?php if(is_admin()) { ?>
							<li><a href="<?php echo G\get_base_url("dashboard"); ?>"><?php _se('Dashboard'); ?></a></li>
							<?php } ?>
                            <li><a href="<?php echo G\get_base_url(sprintf("logout?auth_token=%s", get_auth_token())); ?>"><?php _se('Sign out'); ?></a></li>
                        </ul>
                    </div>
                </div>
            </li>
			<?php
				}
			?>
			<?php 
				if(CHV\getSetting('website_mode') == 'public' or (CHV\getSetting('website_mode') == 'private' and CHV\Login::getUser())) {
			?>
            <li data-nav="about" class="phone-hide pop-btn pop-btn-delayed">
                <span class="top-btn-text"><span class="icon icon-info tablet-hide laptop-hide desktop-hide"></span><span class="text phone-hide phablet-hide"><?php _se('About'); ?></span><span class="arrow-down"></span></span>
                <div class="pop-box arrow-box arrow-box-top anchor-right">
                    <div class="pop-box-inner pop-box-menu">
                        <ul>
                            <li><a href="<?php echo G\get_base_url('page/tos'); ?>"><?php _se('Terms of service'); ?></a></li>
                            <li><a href="<?php echo G\get_base_url('page/privacy'); ?>"><?php _se('Privacy'); ?></a></li>
                            <li><a href="<?php echo G\get_base_url('page/contact'); ?>" rel="contact"><?php _se('Contact'); ?></a></li>
                        </ul>
                    </div>
                </div>
            </li>
            
			<?php
					if(!CHV\Login::isLoggedUser()) {
			?>
            <li data-nav="language" class="phablet-hide phone-hide pop-btn">
				<?php
					// Language selector
					$available_langs = CHV\get_available_languages();
					$cols = min(6, ceil(count($available_langs) / 6));
				?>
                <span class="top-btn-text"><span class="text"><?php echo CHV\get_lang_used()['short_name']; ?></span><span class="arrow-down"></span></span>
                <div class="pop-box <?php if($cols > 1) { echo sprintf('pbcols%d ', $cols); } ?>arrow-box arrow-box-top anchor-right">
                    <div class="pop-box-inner pop-box-menu<?php if($cols > 1) { ?> pop-box-menucols<?php } ?>">
                        <ul>
						<?php
							foreach($available_langs as $k => $v){
								echo '<li' . (CHV\get_lang_used()['code'] == $k ? ' class="current"' : '') . '><a href="' . G\get_base_url('?lang=' . $k) . '">' . $v["name"] . '</a></li>'."\n";
								$count++;
							}
						?>
                        </ul>
                    </div>
                </div>
            </li>
			<?php
					}
			?>
			<?php
				}
			?>
			
        </ul>
        
    </div>
	<?php if(is_private_gate()) { ?>
	</div>
	<?php } ?>
</header>
<?php } ?>