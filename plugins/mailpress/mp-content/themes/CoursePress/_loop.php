<?php 
global $wp_query;
print_r($wp_query->parse_query_vars());
$oldquery['showposts'] = 100;
$oldquery = $wp_query->query_vars; 
?>
<?php $args = array_merge( $wp_query->query_vars, array( 'category_name' => 'reader' ) ); ?>
<?php $my_query = new WP_Query($args); ?>
<?php while ($my_query->have_posts()) : $my_query->the_post(); ?>
<?php
 $cats = get_the_category(); 
 foreach($cats as $c) {
	 //print_r($c);
	 if ($c->category_parent > 0) {
	 	$catcount[$c->cat_name] +=1;
		$catreplace[$c->cat_name.'</a>'] = $c->cat_name. '</a> (<strong>'. $catcount[$c->cat_name].'</strong>)';
	 }
 }
 ?>
<?php endwhile; ?>
<?php
 $catlist = wp_list_categories('echo=0&show_count=0&title_li=&exclude=1');
 $catlist = strtr($catlist, $catreplace);
 $catlist = preg_replace('/<li[^>]*>/','<li style="margin:0">',$catlist);
 $catlist = preg_replace('/<ul[^>]*>/','<ul style="padding-left:20px;">',$catlist);
?>

<table <?php $this->classes('nopmb cp_ctable'); ?>>
  <?php query_posts(array_merge( $oldquery, array( 'category_name' => 'conference-information' ) )); ?>
  <tr>
    <td <?php $this->classes('cp_section_head'); ?>>Conference Information <small>(<a href="<?php echo site_url(); ?>/category/conference-information">Visit on site</a>)</small></td>
  </tr>
  <?php $info = false; ?>
  <tr>
    <td <?php $this->classes('nopmb cp_ctd'); ?>><div <?php $this->classes('cp_recent_act'); ?>><strong>Recent Activity</strong> (numbers in brackets indicate new posts)
        <ul>
          <?php  echo $catlist; ?>
        </ul>
      </div>
      <?php while (have_posts()) : the_post(); ?>
      <?php if (in_category('conference-information')): ?>
      <?php $info = true; ?>
      <div <?php $this->classes('cp_cdiv'); ?>>
        <h2 <?php $this->classes('cp_ch2'); ?>> <a <?php $this->classes('cp_clink'); ?> href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>">
          <?php the_title(); ?>
          </a> </h2>
        <small <?php $this->classes('nopmb cdate'); ?>>
        <?php the_time('F j, Y') ?>
        </small>
        <div <?php $this->classes('nopmb cp_div'); ?>>
          <?php $this->the_content( __( 'Read more >>' ) ); ?>
        </div>
      </div>
      <?php endif; ?>
      <?php endwhile; ?>
      <?php if (!$info):?>
      <div <?php $this->classes('cp_cdiv'); ?>>
        <div <?php $this->classes('nopmb'); ?>>
          <p <?php $this->classes('nopmb noinfo'); ?>>No new conference information in this newsletter</p>
        </div>
      </div>
      <?php endif; ?></td>
  </tr>
  <tr>
    <td <?php $this->classes('nopmb cp_section_head'); ?>>Activity Stream <small>(<a href="<?php echo site_url(); ?>/activity">Visit on site</a>)</small></td>
  </tr>
  <tr>
    <td <?php $this->classes('nopmb cp_ctd'); ?>>
      <table <?php $this->classes('nopmb cp_twotable'); ?>>
        <tr>
          <td <?php $this->classes('nopmb cp_onetd'); ?>>
            <div <?php $this->classes('cp_onecell'); ?>>
              <? $object = 'status';
			  	 $max = 10; ?>
				<h2 <?php $this->classes('cp_ch2'); ?>>Recent Activity (last <?php echo $max; ?>)</h2>
                  <?php include('bp-activity.php'); ?>
			</div>
          </td>
          <td <?php $this->classes('nopmb cp_onetd'); ?>>
          	<div <?php $this->classes('cp_onecell'); ?>>
              <? $type = 'active';
			  	 $max = 10; ?>
              <h2 <?php $this->classes('cp_ch2'); ?>>Recent Active Sessions (last <?php echo $max; ?>)</h2>
              <?php include('bp-groups.php'); ?></td>
            </div>
        </tr>
      </table>
      </td>
  </tr>
  <?php query_posts(array_merge( $oldquery, array( 'category_name' => 'blog-posts' ) )); ?>
  <tr>
    <td <?php $this->classes('cp_section_head'); ?>>Participant Blog Posts <small>(<a href="<?php echo site_url(); ?>/category/blog-posts">Visit on site</a>)</small></td>
  </tr>
  <tr><td>
  <table <?php $this->classes('nopmb cp_twotable'); ?>>
  <?php $info = false; ?>
  <?php while (have_posts()) : the_post(); ?>
  <?php if (in_category('blog-posts')): ?>
  <?php $info = true; ?>
  <?php $col = ( 'left' != $col ) ? 'left' : 'right';
	  $content = get_the_excerpt();
	  $excerpt = explode(' ',$content); 
      if ($col == "left"){ ?>
  <tr>
    <?php } ?>
    <td <?php $this->classes('nopmb cp_onetd'); ?>><div <?php $this->classes('cp_onecell'); ?>>
        <h2 <?php $this->classes('cp_ch2'); ?>> <a <?php $this->classes('cp_clink'); ?> href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>">
          <?php the_title(); ?>
          </a> </h2>
        <small <?php $this->classes('nopmb cp_cdate'); ?>>
        <?php the_time('F j, Y') ?>
        | <a href="<?php the_syndication_source_link(); ?>" target="_blank"><?php echo html_entity_decode(get_syndication_source(),ENT_QUOTES,'UTF-8') ?></a> </small>
        <div <?php $this->classes('nopmb cp_extext'); ?>>
          <?php //$this->the_content( __( '(more...)' ) ); ?>
          <?php $content = strip_tags(get_the_excerpt());
							  $content = str_replace(array("Read more &#8250;","[...]"),"",$content);
							  $words = array_slice(explode(' ', $content), 0, 50);
							  $new_ex = "";
							  foreach ($words as $word){
								if (strlen($word)<28){
									$new_ex .= $word .' ';
								} else {
									$new_ex .= substr($word,0,28) ."... ";
								}
							  }
							  
							  echo $new_ex; ?>
          <a href="<?php the_permalink() ?>">Read more &raquo;</a> </div>
      </div></td>
    <?php  if ($col != "left"){ ?>
  </tr>
  <?php } ?>
  <?php endif; ?>
  <?php endwhile; ?>
  <?php  if ($col == "left"){ ?>
  
    <td></td>
  </tr>
  <?php } ?>
  <?php if (!$info):?>
  <tr><td>
   <div <?php $this->classes('cp_cdiv'); ?>>
        <div <?php $this->classes('nopmb'); ?>>
          <p <?php $this->classes('nopmb noinfo'); ?>>No new participant posts in this newsletter</p>
        </div>
   </div>
   </td></tr>
  <?php endif; ?>
  <?php //rewind_posts(); ?>
  </table>
  </td></tr>
  <tr>
    <td <?php $this->classes('cp_section_head'); ?>>Bookmarks <small>(<a href="<?php echo site_url(); ?>/category/bookmarks">Visit on site</a>)</small></td>
  </tr>
  <?php 
$info = false; 
$bookDelicious = ""; 
$bookDiigo = ""; 
query_posts(array_merge( $oldquery, array( 'category_name' => 'bookmarks' ) ));
?>
  <?php while (have_posts()) : the_post(); ?>
  <?php if (in_category('bookmarks')): ?>
  <?php $info = true; ?>
  <?php 
 if (in_category('delicious'))
	$bookDelicious .= '<li style="font-size:0.8em"><a href="'. get_permalink() . '" rel="bookmark" title="Permanent Link to '.get_the_title().'">' . html_entity_decode(get_the_title(),ENT_QUOTES,'UTF-8') .'</a></li>';
 if (in_category('diigo'))
	$bookDiigo .= '<li style="font-size:0.8em"><a href="'. get_permalink() . '" rel="bookmark" title="Permanent Link to '.get_the_title().'">' . html_entity_decode(get_the_title(),ENT_QUOTES,'UTF-8') .'</a></li>';
?>
  <?php endif; ?>
  <?php endwhile; ?>
  <?php if ($bookDelicious || $bookDiigo): ?>
  <tr>
    <td <?php $this->classes('nopmb cp_ctd'); ?>><div <?php $this->classes('cp_cdiv'); ?>>
        <?php if ($bookDelicious): ?>
        <strong>Delicious</strong>
        <ul>
          <?php echo $bookDelicious; ?>
        </ul>
        <?php endif; ?>
        <?php if ($bookDiigo): ?>
        <strong>Diigo</strong>
        <ul>
          <?php echo $bookDiigo; ?>
        </ul>
        <?php endif; ?>
      </div></td>
  </tr>
  <?php endif; ?>
  <?php if (!$info):?>
  <tr>
    <td <?php $this->classes('nopmb cp_ctd'); ?> colspan="2"><div <?php $this->classes('cp_cdiv'); ?>>
        <div <?php $this->classes('nopmb'); ?>>
          <p <?php $this->classes('nopmb cp_noinfo'); ?>>No new bookmarks in this newsletter</p>
        </div>
      </div></td>
  </tr>
  <?php endif; ?>
  <tr><td <?php $this->classes('nopmb cp_ctd'); ?> colspan="2"><div <?php $this->classes('cp_cdiv'); ?>>
        <div <?php $this->classes('nopmb'); ?>><?php
/* Call function to render the table. */
$data = get_google_csv("https://spreadsheets.google.com/tq?tqx=out:csv&tq=select+B+where+B+%3C%3E+''+OFFSET+1&key=0AqGkLMU9sHmLdEYzbzB0WldvMWVCbjJDNDhNMFVEYXc&gid=123");

foreach($data as $row){
	if (strlen($row[0])>2){
	//	echo "<p>".$row[0]."</p>";
	}
}
?>        </div>
      </div></td>
  </tr>
</table>
<?php 
function groups_render($type, $count) {
   if ( bp_has_groups( 'type=' . $type . '&max=' . $count ) ) : ?>
			<ul id="groups-list" <?php $this->classes('nopmb item-list'); ?>>
				<?php while ( bp_groups() ) : bp_the_group(); ?>
					<li>
						<div class="item-avatar" <?php $this->classes('nopmb item-avatar'); ?>>
							<a href="<?php bp_group_permalink() ?>" title="<?php bp_group_name() ?>"><?php bp_group_avatar_thumb() ?></a>
						</div>

						<div class="item">
							<div class="item-title"><a href="<?php bp_group_permalink() ?>" title="<?php bp_group_name() ?>"><?php bp_group_name() ?></a></div>
							<div class="item-meta">
								<span class="activity" <?php $this->classes('nopmb activity'); ?>>
								<?php
									if ( 'newest' == $type )
										printf( __( 'created %s', 'buddypress' ), bp_get_group_date_created() );
									if ( 'active' == $type )
										printf( __( 'active %s', 'buddypress' ), bp_get_group_last_active() );
									else if ( 'popular' == $type )
										bp_group_member_count();
								?>
								</span>
							</div>
						</div>
					</li>

				<?php endwhile; ?>
			</ul>
	<?php endif; ?>
<?php }


function get_bbp_query($topics_array){
	//if(function_exists('bp_is_active')):
	$widget_query = new WP_Query( $topics_array );	
			if ( $widget_query->have_posts() ) :  
			$count = 0; ?>
<ul style="padding-left:0px;">
  <?php while ( $widget_query->have_posts() ) :
					
						if ($count < 5):
							$count++;
							$widget_query->the_post();
							$topic_id    = bbp_get_topic_id( $widget_query->post->ID ); 
							$author_link = bbp_get_topic_author_link( array( 'post_id' => $topic_id, 'type' => 'both', 'size' => 15 ) ); ?>
  <li style="margin-bottom:5px;"> <a class="bbp-forum-title" href="<?php bbp_topic_permalink( $topic_id ); ?>" title="<?php bbp_topic_title( $topic_id ); ?>">
    <?php bbp_topic_title( $topic_id ); ?>
    </a> -
    <?php bbp_topic_last_active_time( $topic_id ); ?>
    <?php printf( _x( 'by %1$s', 'widgets', 'bbpress' ), '<span class="topic-author">' . $author_link . '</span>' ); ?> </li>
  <?php else:
							break;
						endif; ?>
  <?php endwhile; ?>
</ul>
<?php 
			//wp_reset_postdata();

		endif;
		//wp_reset_query();
	//endif;
}
function get_bbp_recent_topics(){
	$topics_query = array(
					'author'         => 0,
					'post_type'      => bbp_get_topic_post_type(),
					'post_parent'    => 'any',
					'posts_per_page' => 5,
					'post_status'    => join( ',', array( bbp_get_public_status_id(), bbp_get_closed_status_id() ) ),
					'show_stickes'   => false,
					'order'          => 'DESC',
					'meta_query'     => array( bbp_exclude_forum_ids( 'meta_query' ) )
				);	
	get_bbp_query($topics_query);
}
function get_bbp_recent_replies(){
	$replies_query = array(
					'post_type'      => array( bbp_get_reply_post_type() ),
					'post_status'    => join( ',', array( bbp_get_public_status_id(), bbp_get_closed_status_id() ) ),
					'posts_per_page' => 5,
					'meta_query'     => array( bbp_exclude_forum_ids( 'meta_query' ) )
				) ;
	get_bbp_query($replies_query);
}



function get_google_csv($url, $mapRow = false){
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_HEADER, 0 );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
	curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
	curl_setopt( $ch, CURLOPT_URL, $url );
	$csv_data = curl_exec( $ch ) or die( 'CURL ERROR: '.curl_error( $ch ) );
	curl_close( $ch );
	
	/* Call function to parse .CSV data string into an indexed array. */
	$csv = parse_gcsv( $csv_data );
	if ($mapRow){
		$header =  array_shift($csv);
		$output = array();
		foreach($csv as $row){
			$d = array_combine($header,$row);
			$output[] = $d;
		}
	} else {
		$output = $csv;
	}
	return $output;
}

// http://www.php.net/manual/en/function.str-getcsv.php#111665
function parse_gcsv ($csv_string, $delimiter = ",", $skip_empty_lines = true, $trim_fields = true)
{
    $enc = preg_replace('/(?<!")""/', '!!Q!!', $csv_string);
    $enc = preg_replace_callback(
        '/"(.*?)"/s',
        function ($field) {
            return urlencode(utf8_encode($field[1]));
        },
        $enc
    );
    $lines = preg_split($skip_empty_lines ? ($trim_fields ? '/( *\R)+/s' : '/\R+/s') : '/\R/s', $enc);
    return array_map(
        function ($line) use ($delimiter, $trim_fields) {
            $fields = $trim_fields ? array_map('trim', explode($delimiter, $line)) : explode($delimiter, $line);
            return array_map(
                function ($field) {
                    return str_replace('!!Q!!', '"', utf8_decode(urldecode($field)));
                },
                $fields
            );
        },
        $lines
    );
}



?>
