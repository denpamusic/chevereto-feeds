<?php if(!defined('access') or !access) die('This file cannot be directly accessed.'); ?>
<link rel="alternate" href="<?php echo G\get_base_url('feeds/rss'); ?>" title="Recent Images RSS Feed" type="application/rss+xml">
<link rel="alternate" href="<?php echo G\get_base_url('feeds/atom'); ?>" title="Recent Images Atom Feed" type="application/atom+xml">
<?php if(G\get_route_name() == 'user') { ?>
<link rel="alternate" href="<?php echo G\get_base_url( 'feeds/rss/?user=' . get_user()['username'] ); ?>" title="<?php echo get_user()['name'] . '\'s RSS Feed' ?>" type="application/rss+xml">
<link rel="alternate" href="<?php echo G\get_base_url( 'feeds/atom/?user=' . get_user()['username'] ); ?>" title="<?php echo get_user()['name'] . '\'s Atom Feed' ?>" type="application/atom+xml">
<?php } ?>