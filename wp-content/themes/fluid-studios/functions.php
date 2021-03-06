<?php

// Deregister jQuery that ships with WordPress, Infusion ships with it's own copy
if( !is_admin()){
	wp_deregister_script('jquery');
}

// The constant definitions
// The number of characters for content excerpt on the index page 
if ( ! defined("NUM_OF_CHARS_IN_SUMMARY") ) define("NUM_OF_CHARS_IN_SUMMARY_CONTENT", 20);

// The size of the featured image on the index page
if ( ! defined("THUMBNAIL_WIDTH") ) define("THUMBNAIL_WIDTH", 240);
if ( ! defined("THUMBNAIL_HEIGHT") ) define("THUMBNAIL_HEIGHT", 160);

// The maximum number of characters in the "new post" page, "title" field
if ( ! defined("MAX_CHARS_IN_POST_TITLE") ) define("MAX_CHARS_IN_POST_TITLE", 80);

// Max num of chars in tag list on summary pages
if ( ! defined("MAX_CHARS_IN_SUMMARY_TAG_LIST") ) define("MAX_CHARS_IN_SUMMARY_TAG_LIST", 50);

// Enable Post Thumbnail selection UI
if ( function_exists( 'add_theme_support' ) ) {
	add_theme_support( 'post-thumbnails' );
	set_post_thumbnail_size( THUMBNAIL_WIDTH, THUMBNAIL_HEIGHT, true ); // 50 pixels wide by 50 pixels tall, hard crop mode
}

// Customize the excerpt length
function new_excerpt_length($length) {
	return NUM_OF_CHARS_IN_SUMMARY_CONTENT;
}
add_filter('excerpt_length', 'new_excerpt_length');

// Define the excerpt "more" string
function new_excerpt_more($more) {
	global $post;
	return '&nbsp;<a href="'. get_permalink($post->ID) . '" rel="bookmark" title="Continue reading ' . the_title('', '', false) . '">(...more)</a>';
}
add_filter('excerpt_more', 'new_excerpt_more');

// Custom comments
if ( ! function_exists( 'Studios_comment' ) ) :
function Studios_comment( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment;
	switch ( $comment->comment_type ) :
		case '' :
	?>
	<li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>">
		<div id="comment-<?php comment_ID(); ?>">
			<header class="comment-header comment-author vcard">
				<p><?php echo get_avatar( $comment, '60', '', 'Comment authors avatar' ); ?>
				<?php printf( __( '%s says:', 'Studios' ), sprintf( '<cite class="fn">%s</cite>', get_comment_author_link() ) ); ?></p>
			</header><!-- /.comment-author /.vcard -->
			<?php if ( $comment->comment_approved == '0' ) : ?>
				<em class="comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation...', 'Studios' ); ?></em>
			<?php endif; ?>

		<section class="comment-content"><?php comment_text(); ?></section>

		<footer class="comment-utility">
			<ul>
				<li>Comment posted <time datetime="<?php the_time('Y-m-d') ?>" pubdate="pubdate"><?php printf( __( '%1$s', 'Studios' ), get_comment_date() ); ?></time><?php edit_comment_link( __( 'Edit', 'Studios' ), ' ' ); ?><?php comment_reply_link( array_merge( $args, array( 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?> &mdash; <span class="top"><a href="#nav:page-top" title="Return to the TOP of this page">TOP</a></span></li>
			</ul>
		</footer><!-- /.comment-utility -->

	</div><!-- /#comment-##  -->

	<?php
			break;
		case 'pingback'  :
		case 'trackback' :
	?>
	<li class="post pingback">
		<p><?php _e( 'Pingback:', 'Studios' ); ?> <?php comment_author_link(); ?><?php edit_comment_link( __( '(Edit)', 'Studios' ), ' ' ); ?></p>
	<?php
			break;
	endswitch;
}
endif;

// Customized comment reply link
function my_replylink($c='',$post=null) {
  global $comment;
  // bypass
  if (!comments_open() || $comment->comment_type == "trackback" || $comment->comment_type == "pingback") return $c;
  // patch
  $id = $comment->comment_ID;
  $reply = 'Reply to this comment...';
  $o = '<span class="comment-reply"><a class="comment-reply-link" href="'.get_permalink().'?replytocom='.$id.'#respond">'.$reply.'</a></span>';
  return $o;
}
add_filter('comment_reply_link', 'my_replylink');

// remove WordPress version info from head and feeds
	function complete_version_removal() {
		return '';
	}
	add_filter('the_generator', 'complete_version_removal');

// register main navigation
	add_action( 'init', 'register_main_nav_menu' );

	function register_main_nav_menu() {
		register_nav_menu( 'main_nav', __( 'Main Navigation Menu' ) );
	}

// register sidebar widget
	if (function_exists('register_sidebar')) {
		register_sidebar(array(
			'before_widget' => '<li class="fl-clearfix fl-widget %2$s">',
			'after_widget' => '</li>',
			'before_title' => '<h2 class="widget-title">',
			'after_title' => '</h2>',
		));
	}

// prevent duplicate content for comments
	function noDuplicateContentforComments() {
		global $cpage, $post;
		if($cpage > 1) {
		echo "\n".'<link rel="canonical" href="'.get_permalink($post->ID).'" />'."\n";
		}
	}
	add_action('wp_head', 'noDuplicateContentforComments');

// Remove the Login Error Message
add_filter('login_errors',create_function('$a', "return null;"));

// Credit
	function custom_admin_footer() {
		echo 'Fluid Studios is developed by <a href="http://fluidproject.org/">The Fluid Project</a>.';
	} 
	add_filter('admin_footer_text', 'custom_admin_footer');

// add Twitter handle in user profiles
	function Studios_contactmethods($contactmethods) {
		$contactmethods['twitter'] = 'Twitter Handle';
		return $contactmethods;
	}
	
	add_filter('user_contactmethods', 'Studios_contactmethods', 10, 1);

// enable threaded comments
	function enable_threaded_comments(){
		if (!is_admin()) {
			if (is_singular() AND comments_open() AND (get_option('thread_comments') == 1))
				wp_enqueue_script('comment-reply');
			}
	}
	add_action('get_header', 'enable_threaded_comments');


// build an HTML string for a tag
	function build_link_for_tag($aTag) {
		$tag_link = get_tag_link($aTag->term_id);
		$html .= "<a rel='tag' href='{$tag_link}' title='{$aTag->name} Tag' class='{$aTag->slug}'>";
		$html .= "{$aTag->name}</a>";
		return $html;
	}

// Build a list of post tags limited to a maximum character length
	function get_tags_summary($tagList) {
		$html = '';
		if ($tagList) {
			$html = '<div class="fs-tags post_tags">';
			// always display at least the first tag
			$firsttag = array_shift($tagList);
			$html .= build_link_for_tag($firsttag);
			$display = "{$firsttag->name}";
			foreach($tagList as $tag) {
				$newlen = strlen($display) + strlen($tag->name);
				// only add next tag if it fits within the limit
				if ($newlen < MAX_CHARS_IN_SUMMARY_TAG_LIST) {
					$display .= ", {$tag->name}";
					$html .= ", ".build_link_for_tag($tag);
				} else {
					// if there are undisplay tags, show ellipses
					$html .= "...";
					break;
				}
			}
			$html .= '</div>';
		}
		return $html;
	}

?>