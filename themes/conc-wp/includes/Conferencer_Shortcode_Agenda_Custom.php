<?php
add_action('wp_ajax_get_joinleave_buttons_array', 'get_joinleave_buttons_array');           // for logged in user  
add_action('wp_ajax_nopriv_get_joinleave_buttons_array', 'get_joinleave_buttons_array');

function get_joinleave_buttons_array(){
	$user_id = get_current_user_id();
	if(!$user_id){
		echo('false');
		die();
		return;
	} 
	$gids = explode(",",$_POST['gids']);
	$buts = array();
	foreach($gids as &$gid){
		$buts[$gid] = bp_group_join_button_from_id($gid);
	}
	echo json_encode($buts);
	die();
}

function bp_group_join_button_from_id($group_id){
		global $bp;
		$group = groups_get_group( array( 'group_id' => $group_id ) );
		
		if (empty($group))
			return false;
		if ( !is_user_logged_in() || bp_group_is_user_banned( $group ) )
			return false;

		// Group creation was not completed or status is unknown
		if ( !$group->status )
			return false;

		// Already a member
		if ( isset( $group->is_member ) && $group->is_member ) {

			// Stop sole admins from abandoning their group
	 		$group_admins = groups_get_group_admins( $group->id );
		 	if ( 1 == count( $group_admins ) && $group_admins[0]->user_id == bp_loggedin_user_id() )
				return false;

			$button = array(
				'id'                => 'leave_group',
				'component'         => 'groups',
				'must_be_logged_in' => true,
				'block_self'        => false,
				'wrapper_class'     => 'group-button prog ' . $group->status,
				'wrapper_id'        => 'groupbutton-' . $group->id,
				'link_href'         => wp_nonce_url( bp_get_group_permalink( $group ) . 'leave-group', 'groups_leave_group' ),
				'link_text'         => '–',
				'link_title'        => __( 'Leave Group', 'buddypress' ),
				'link_class'        => 'group-button leave-group',
			);

		// Not a member
		} else {

			// Show different buttons based on group status
			switch ( $group->status ) {
				case 'hidden' :
					return false;
					break;

				case 'public':
					$button = array(
						'id'                => 'join_group',
						'component'         => 'groups',
						'must_be_logged_in' => true,
						'block_self'        => false,
						'wrapper_class'     => 'group-button prog ' . $group->status,
						'wrapper_id'        => 'groupbutton-' . $group->id,
						'link_href'         => wp_nonce_url( bp_get_group_permalink( $group ) . 'join', 'groups_join_group' ),
						'link_text'         => '+',
						'link_title'        => __( 'Join Group', 'buddypress' ),
						'link_class'        => 'group-button join-group',
					);
					break;

				case 'private' :
					return false;
					break;

			}
		}
	return (bp_get_button( apply_filters( 'bp_get_group_join_button', $button ) ));
}

new Conferencer_Shortcode_Agenda_Custom();
class Conferencer_Shortcode_Agenda_Custom extends Conferencer_Shortcode {
	var $shortcode = 'agendacustom';
	var $defaults = array(
		'column_type' => 'track',
		'session_tooltips' => true,
		'show_empty_rows' => true,
		'show_empty_columns' => true,
		'show_empty_cells' => null,
		'show_unassigned_column' => false,
		'tabs' => 'days',
		'tab_day_format' => 'M. j, Y',
		'row_day_format' => 'l, F j, Y',
		'row_time_format' => 'g:ia',
		'show_row_ends' => false,
		'keynote_spans_tracks' => true,
		'link_sessions' => true,
		'link_speakers' => true,
		'link_rooms' => true,
		'link_time_slots' => true,
		'link_columns' => true,
		'unassigned_column_header_text' => 'N/A',
		'unscheduled_row_text' => 'Unscheduled',
	);
	
	var $buttons = array('agenda');
	
	function prep_options() {
		parent::prep_options();
		
		if (!in_array($this->options['column_type'], array('track', 'room'))) {
			$this->options['column_type'] = false;
		}
		
		if ($this->options['show_empty_cells'] != null) {
			$this->options['show_empty_rows'] = $this->options['show_empty_cells'];
			$this->options['show_empty_columns'] = $this->options['show_empty_cells'];
		}
	}

	function content() {
		extract($this->options);
		$conferencer_options = get_option('conferencer_options');

		// Define main agenda variable

		$agenda = array();
	
		// Fill agenda with empty time slot rows
	
		foreach (Conferencer::get_posts('time_slot', false, 'start_time_sort') as $time_slot_id => $time_slot) {
			$agenda[$time_slot_id] = array();
		}
		$agenda[0] = array(); // for unscheduled time slots
	
		// If the agenda is split into columns, fill rows with empty "cell" arrays
	
		if ($column_type) {
			$column_post_counts = array(
				-1 => 0, // keynotes
				0 => 0, // unscheduled
			);
			$column_posts = Conferencer::get_posts($column_type);
		
			foreach ($agenda as $time_slot_id => $time_slot) {
				foreach ($column_posts as $column_post_id => $column_post) {
					$column_post_counts[$column_post_id] = 0;
					$agenda[$time_slot_id][$column_post_id] = array();
				}
				$agenda[$time_slot_id][0] = array();
			}
		}
	
		// Get all session information
	
		$sessions = Conferencer::get_posts('session', false, 'order_sort');
		foreach (array_keys($sessions) as $id) {
			Conferencer::add_meta($sessions[$id]);
		}
	
		// Put sessions into agenda variable
	
		foreach ($sessions as $session) {
			$time_slot_id = $session->time_slot ? $session->time_slot : 0;

			if ($column_type) {
				$column_id = $session->$column_type ? $session->$column_type : 0;
				if ($keynote_spans_tracks && $session->keynote) $column_id = -1;
				$agenda[$time_slot_id][$column_id][$session->ID] = $session;
				$column_post_counts[$column_id]++;
			} else {
				$agenda[$time_slot_id][$session->ID] = $session;
			}
		}
		
		// Remove empty unscheduled rows
		
		if (deep_empty($agenda[0])) unset($agenda[0]);
	
		// Conditionally remove empty rows and columns
	
		if (!$show_empty_rows) {
			foreach ($agenda as $time_slot_id => $cells) {
				$non_session = get_post_meta($time_slot_id, '_conferencer_non_session', true);
				if (!$non_session && deep_empty($cells)) unset($agenda[$time_slot_id]);
			}
		}
	
		if (!$show_empty_columns) {
			$empty_column_post_ids = array();
			foreach ($column_posts as $column_post_id => $column_post) {
				if (!$column_post_counts[$column_post_id]) $empty_column_post_ids[] = $column_post_id;
			}
		
			foreach ($agenda as $time_slot_id => $cells) {
				foreach ($empty_column_post_ids as $empty_column_post_id) {
					unset($agenda[$time_slot_id][$empty_column_post_id]);
				}
			}
		}

		// Set up tabs
	
		if ($tabs) {
			$tab_headers = array();
		
			foreach ($agenda as $time_slot_id => $cells) {
				if ($tabs == 'days') {
					if ($starts = get_post_meta($time_slot_id, '_conferencer_starts', true)) {
						$tab_headers[] = get_day($starts);
					} else $tab_headers[] = 0;
				}
			}
		
			$tab_headers = array_unique($tab_headers);
			
			if (count($tab_headers) < 2) $tabs = false;
		}
		
		// Set up column headers
	
		if ($column_type) {
			$column_headers = array();
		
			// post column headers
			foreach ($column_posts as $column_post) {
				if (!$show_empty_columns && in_array($column_post->ID, $empty_column_post_ids)) continue;
			
				$column_headers[] = array(
					'title' => $column_post->post_title,
					'class' => 'column_'.$column_post->post_name,
					'link' => $link_columns ? get_permalink($column_post->ID) : false,
				);
			}
		
			if ($show_unassigned_column && count($column_post_counts[0])) {
				// extra column header for sessions not assigned to a column
				$column_headers[] = array(
					'title' => $unassigned_column_header_text,
					'class' => 'column_not_applicable',
					'link' => false,
				);
			} else {
				// remove cells if no un-assigned sessions
				foreach ($agenda as $time_slot_id => $cells) {
					unset($agenda[$time_slot_id][0]);
				}
			}
		}
	
		// Remove unscheduled time slot, if without sessions
		if (deep_empty($agenda[0])) unset($agenda[0]);

		// Start buffering output

		ob_start();
	
		?>
	
		<div class="conferencer_agenda">
			<?php if ($conferencer_options['details_toggle']) { ?>
				<a href="#" class="conferencer_session_detail_toggle">
					<span class="show">display session details</span>
					<span class="hide">hide session details</span>
				</a>
			<?php } ?>
<?php 
$column_posts = Conferencer::get_posts('track');
$track_array = array();
//$out = "<!-- ";
?>
<?php  echo '<script src="'.get_stylesheet_directory_uri().'/js/confprog.js"></script>'; ?>
<div class="agenda-filter">
<h2>Theme Filter (<a href="#" class="check">Uncheck All</a>)</h2>
<?php
foreach ($column_posts as $column_post) {
	$track_array[$column_post->ID] = $column_post->post_title;
	//$out .= ".track-".$column_post->post_name."{\n\n}\n\n"; ?>
	<div class="track track-<?php echo $column_post->post_name;?>" id="track-<?php echo $column_post->post_name;?>">
    	<label><input id="track-<?php echo $column_post->post_name;?>" name="track-<?php echo $column_post->ID;?>" type="checkbox" checked/>
    	<?php echo $column_post->post_title;?></label>
    </div> 
    <?php 
}

//echo $out." -->";
?>
<h2>Joined Sessions Filter</h2>
<p><strong><a href="#" class="mysessions"><img src="<?php echo get_stylesheet_directory_uri();?>/icons/loading.gif" /></a></strong></p>
</div>
			<?php if ($tabs) { ?>
				<div class="conferencer_tabs">
				<ul class="tabs">
					<?php foreach ($tab_headers as $tab_header) { ?>
						<li>
							<?php if ($tabs == 'days') { ?>
								<a href="#conferencer_agenda_tab_<?php echo $tab_header; ?>">
									<?php echo $tab_header ? date($tab_day_format, $tab_header) : $unscheduled_row_text; ?>
								</a>
							<?php } ?>
						</li>
					<?php } ?>
				</ul>
			<?php } else { ?>
				<table class="grid">
					<?php if ($column_type) $this->display_headers($column_headers); ?>
					<tbody>
			<?php } ?>
			
					<?php $row_starts = $last_row_starts = $second_table = false; ?>
					<?php foreach ($agenda as $time_slot_id => $cells) { ?>
				
						<?php
							// Set up row information
					
							$last_row_starts = $row_starts;
							$row_starts = get_post_meta($time_slot_id, '_conferencer_starts', true);
							$row_ends = get_post_meta($time_slot_id, '_conferencer_ends', true);
							$non_session = get_post_meta($time_slot_id, '_conferencer_non_session', true);
							$no_sessions = deep_empty($cells);
						
							// Show day seperators
							$show_next_day = $row_day_format !== false && date('w', $row_starts) != date('w', $last_row_starts);
						
							if ($show_next_day) { ?>
								
								<?php if ($tabs) { ?>

									<?php if ($second_table) { ?>
											</tbody>
										</table>
										 <!-- #conferencer_agenda_tab_xxx --> </div>
									<?php } else $second_table = true; ?>

									<div id="conferencer_agenda_tab_<?php echo get_day($row_starts); ?>">
									<table class="grid">
										<?php if ($column_type) $this->display_headers($column_headers); ?>
										<tbody>
								<?php } else { ?>
									<tr class="day">
										<td colspan="<?php echo $column_type ? count($column_headers) + 1 : 2; ?>">
											<?php echo $row_starts ? date($row_day_format, $row_starts) : $unscheduled_row_text; ?>
										</td>
									</tr>
								<?php } ?>
								
							<?php }
							// Set row classes

							$classes = array();
							if ($non_session) $classes[] = 'non-session';
							else if ($no_sessions) $classes[] = 'no-sessions';
						?>
				
						<tr<?php output_classes($classes); ?>>
					
							<?php // Time slot column -------------------------- ?>
					
							<td class="time_slot">
								<?php
									if ($time_slot_id) {
										$time_slot_link = get_post_meta($time_slot_id, '_conferencer_link', true)
											OR $time_slot_link = get_permalink($time_slot_id);
										$html = date($row_time_format, $row_starts);
										if ($show_row_ends) $html .= " &ndash; ".date($row_time_format, $row_ends);
										if ($link_time_slots) $html = "<a href='$time_slot_link'>$html</a>";
										echo $html;
									}
								?>
							</td>
						
							<?php // Display session cells --------------------- ?>
							
							<?php $colspan = $column_type ? count($column_headers) : 1; ?>

							<?php if ($non_session) { // display a non-sessioned time slot ?>

								<td class="sessions" colspan="<?php echo $colspan; ?>">
									<p>
										<?php
											$html = get_the_title($time_slot_id);
											if ($link_time_slots) $html = "<a href='$time_slot_link'>$html</a>";
											echo $html;
										?>
									</p>
								</td>
								
							<?php } else if (isset($cells[-1])) { ?>
								
								<td class="sessions keynote-sessions" colspan="<?php echo $colspan; ?>">
									<?php
										foreach ($cells[-1] as $session) {
											$this->display_session($session);
										}
									?>
								</td>

							<?php } else if ($column_type) { // if split into columns, multiple cells  ?>

								<?php foreach ($cells as $cell_sessions) { ?>
									<td class="sessions <?php if (empty($cell_sessions)) echo 'no-sessions'; ?>">
										<?php
											foreach ($cell_sessions as $session) {
												$this->display_session($session);
											}
										?>
									</td>
								<?php } ?>

							<?php } else { // all sessions in one cell ?>
							
								<td class="sessions <?php if (empty($cells)) echo 'no-sessions'; ?>">
									<?php
										foreach ($cells as $session) {
											$this->display_session($session);
										}
									?>
								</td>
							
							<?php } ?>
						</tr>
					<?php } ?>
				</tbody>
			</table>
			
			<?php if ($tabs) { ?>
				 <!-- #conferencer_agenda_tab_xxx --> </div>
				</div> <!-- .conferencer_agenda_tabs -->
			<?php } ?>
	
		</div> <!-- .conferencer_agenda -->
	
		<?php
	
		// Retrieve and return buffer
	
		return ob_get_clean();
	}
	
	function display_headers($column_headers) { ?>
		<thead>
			<tr>
				<th class="column_time_slot"></th>
				<?php foreach ($column_headers as $column_header) { ?>
					<th class="<?php echo $column_header['class']; ?>">
						<?php
							$html = $column_header['title'];
							if ($column_header['link']) $html = "<a href='".$column_header['link']."'>$html</a>";
							echo $html;
						?>
					</th>
				<?php } ?>
			</tr>
		</thead>
	<?php }
	
	function display_session($session) {
		if (function_exists('conferencer_agenda_display_session')) {
			conferencer_agenda_display_session($session, $this->options);
			return;
		}

		extract($this->options);
		$group_id = get_post_meta($session->ID, 'con_group', true);
		?>

		<div class="session <?php if ($session->track) echo "track-".get_the_slug($session->track); ?>" group-id="<?php echo $group_id ;?>">
        <div class="generic-button group-button prog public" id="groupbutton-<?php echo $group_id ;?>"></div>
            <?php $islive = get_post_meta($session->ID, 'conc_wp_live', true);
				if ($islive) echo '<div class="islive">LIVE</div>'; ?>
			<?php echo do_shortcode("
				[session_meta
					post_id='$session->ID'
					show='title,speakers,room'
					speakers_prefix='with '
					room_prefix='in ',
					track_prefix='In theme ',
					link_title=".($link_sessions ? 'true' : 'false')."
					link_speakers=".($link_speakers ? 'true' : 'false')."
					link_room=".($link_rooms ? 'true' : 'false')."
				]
			");	?>
			<?php if ($session_tooltips) { ?>
				<div class="session-tooltip">
					<?php echo do_shortcode("
						[session_meta
							post_id='$session->ID'
							show='title,speakers,room,track',
							track_prefix='In theme ',
							link_all=false
						]
					"); ?>
					
					<p class="excerpt"><?php echo generate_excerpt($session); ?></p>
					<div class="arrow"></div><div class="inner-arrow"></div>
				</div>
			<?php } ?>
		
		</div>

	<?php }
	
}
?>