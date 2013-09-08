<?php

// Exit if accessed directly
if ( !defined('ABSPATH')) exit;

/**
 * Single Posts Template
 *
 *
 * @file           single.php
 * @package        Responsive 
 * @author         Emil Uzelac 
 * @copyright      2003 - 2013 ThemeID
 * @license        license.txt
 * @version        Release: 1.0
 * @filesource     wp-content/themes/responsive/single.php
 * @link           http://codex.wordpress.org/Theme_Development#Single_Post_.28single.php.29
 * @since          available since Release 1.0
 */

get_header(); ?>

<div id="content" class="<?php echo implode( ' ', responsive_get_content_classes() ); ?>">
  <?php get_template_part( 'loop-header' ); ?>
  <?php if (have_posts()) : ?>
  <?php while (have_posts()) : the_post(); ?>
  <?php responsive_entry_before(); ?>
  <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <?php responsive_entry_top(); ?>
    <?php get_template_part( 'post-meta' ); ?>
    <div class="post-entry">
      <?php the_content(__('Read more &#8250;', 'responsive')); ?>
      <?php if ( get_the_author_meta('description') != '' ) : ?>
      <div id="author-meta">
        <?php if (function_exists('get_avatar')) { echo get_avatar( get_the_author_meta('email'), '80' ); }?>
        <div class="about-author">
          <?php _e('About','responsive'); ?>
          <?php the_author_posts_link(); ?>
        </div>
        <p>
          <?php the_author_meta('description') ?>
        </p>
      </div>
      <!-- end of #author-meta -->
      
      <?php endif; // no description, no author's meta ?>
      <div id="buddypress">
        <?php $post = get_post(get_the_ID()); 
			Conferencer::add_meta($post); 
			$atts = array( 'slug'=> $post->post_name);
            echo bowe_codes_group_tag($atts); 
			echo bowe_codes_group_users_tag(array( 'size' => 20, 'avatar' => "1", 'slug' => $post->post_name));
			?>
        <?php if ( bp_has_groups($atts) ) : while ( bp_groups() ) : bp_the_group(); ?>
        
        <?php do_action( 'bp_before_group_plugin_template' ); ?>

			<div id="item-header">
				<?php locate_template( array( 'groups/single/group-header.php' ), true ); ?>
			</div><!-- #item-header -->
<?php bp_group_description(); ?>
			<div id="item-nav">
				<div class="item-list-tabs no-ajax" id="object-nav" role="navigation">
					<ul>
						<?php bp_get_options_nav(); ?>

						<?php do_action( 'bp_group_plugin_options_nav' ); ?>
					</ul>
				</div>
			</div><!-- #item-nav -->
   
		
        <div id="item-nav">
          <div class="item-list-tabs no-ajax" id="object-nav" role="navigation">
            <ul>
              <li id="../../sessions/tel-crisis-groups-li"  class="current selected"><a href="#">Description</a></li>
              <li id="home-groups-li"><a id="home" href="http://altc2013.alt.ac.uk/groups/tel-crisis/">Activity</a></li>
              <li id="members-groups-li"><a id="members" href="http://altc2013.alt.ac.uk/groups/tel-crisis/members/">Members <span>2</span></a></li>
              <li id="invite-groups-li"><a id="invite" href="http://altc2013.alt.ac.uk/groups/tel-crisis/send-invites/">Send Invites</a></li>
              <li id="nav-notifications-groups-li"><a id="nav-notifications" href="http://altc2013.alt.ac.uk/groups/tel-crisis/notifications/">Email Options</a></li>
            </ul>
          </div>
        </div>
        <div id="item-header" role="complementary">
          <div id="item-header-content">
            <div id="item-meta">
              <div id="item-buttons">
                <?php do_action( 'bp_group_header_actions' ); ?>
              </div>
              <!-- #item-buttons --> 
              
            </div>
          </div>
          <!-- #item-header-content --> 
          
        </div>
        <?php $group_users_arg = array( 
			'exclude_admins_mods' => 0,
			'group_id' => groups_get_id($post->post_name) 
			);
			
	$group_users_arg = apply_filters( 'bowe_codes_group_users_tag_args', $group_users_arg, $args );
	?>
        <?php if ( bp_group_has_members( $group_users_arg ) ) : ?>
        <?php do_action( 'bp_before_group_members_content' ); ?>
        <div class="item-list-tabs" id="subnav" role="navigation">
          <ul>
            <?php do_action( 'bp_members_directory_member_sub_types' ); ?>
          </ul>
        </div>
        <div id="pag-top" class="pagination no-ajax">
          <div class="pag-count" id="member-count-top">
            <?php bp_members_pagination_count(); ?>
          </div>
          <div class="pagination-links" id="member-pag-top">
            <?php bp_members_pagination_links(); ?>
          </div>
        </div>
        <?php do_action( 'bp_before_group_members_list' ); ?>
        <ul id="member-list" class="item-list" role="main">
          <?php while ( bp_group_members() ) : bp_group_the_member(); ?>
          <li> <a href="<?php bp_group_member_domain(); ?>">
            <?php bp_group_member_avatar_thumb(); ?>
            </a>
            <h5>
              <?php bp_group_member_link(); ?>
            </h5>
            <span class="activity">
            <?php bp_group_member_joined_since(); ?>
            </span>
            <?php do_action( 'bp_group_members_list_item' ); ?>
            <?php if ( bp_is_active( 'friends' ) ) : ?>
            <div class="action">
              <?php bp_add_friend_button( bp_get_group_member_id(), bp_get_group_member_is_friend() ); ?>
              <?php do_action( 'bp_group_members_list_item_action' ); ?>
            </div>
            <?php endif; ?>
          </li>
          <?php endwhile; ?>
        </ul>
        <?php do_action( 'bp_after_group_members_list' ); ?>
        <div id="pag-bottom" class="pagination no-ajax">
          <div class="pag-count" id="member-count-bottom">
            <?php bp_members_pagination_count(); ?>
          </div>
          <div class="pagination-links" id="member-pag-bottom">
            <?php bp_members_pagination_links(); ?>
          </div>
        </div>
        <?php do_action( 'bp_after_group_members_content' ); ?>
        <?php else: ?>
        <div id="message" class="info">
          <p>
            <?php _e( 'This group has no members.', 'buddypress' ); ?>
          </p>
        </div>
        <?php endif; ?>
        <?php endwhile; endif; ?>
        <?php echo BD_Activity_Stream_Shortcodes_Helper::generate_activity_stream(array( 'primary_id' => groups_get_id($post->post_name) ));?> </div>
      <?php wp_link_pages(array('before' => '<div class="pagination">' . __('Pages:', 'responsive'), 'after' => '</div>')); ?>
    </div>
    <!-- end of .post-entry -->
    
    <div class="navigation">
      <div class="previous">
        <?php previous_post_link( '&#8249; %link' ); ?>
      </div>
      <div class="next">
        <?php next_post_link( '%link &#8250;' ); ?>
      </div>
    </div>
    <!-- end of .navigation -->
    
    <?php get_template_part( 'post-data' ); ?>
    <?php responsive_entry_bottom(); ?>
  </div>
  <!-- end of #post-<?php the_ID(); ?> -->
  <?php responsive_entry_after(); ?>
  <?php responsive_comments_before(); ?>
  <?php comments_template( '', true ); ?>
  <?php responsive_comments_after(); ?>
  <?php 
		endwhile; 

		get_template_part( 'loop-nav' ); 

	else : 

		get_template_part( 'loop-no-posts' ); 

	endif; 
	?>
</div>
<!-- end of #content -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
