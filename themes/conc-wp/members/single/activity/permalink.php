<?php

/**
 * BuddyPress - Members Directory
 *
 * @package BuddyPress
 * @subpackage bp-default
 */

get_header( 'buddypress' ); ?>

<?php do_action( 'template_notices' ); ?>

	<div id="content">
		<div class="padder">

            <div class="activity no-ajax" role="main">
                <?php if ( bp_has_activities( 'display_comments=threaded&show_hidden=true&include=' . bp_current_action() ) ) : ?>
            
                    <ul id="activity-stream" class="activity-list item-list">
                    <?php while ( bp_activities() ) : bp_the_activity(); ?>
            
                        <?php locate_template( array( 'activity/entry.php' ), true ); ?>
            
                    <?php endwhile; ?>
                    </ul>
            
                <?php endif; ?>
            </div>

		</div><!-- .padder -->
	</div><!-- #content -->



<?php get_sidebar( 'buddypress' ); ?>
<?php get_footer( 'buddypress' ); ?>