<?php
// https://gist.github.com/hugowetterberg/81747
function ical_split($preamble, $value) {
  $value = trim($value);
  //$value = strip_tags($value);
  $value = preg_replace('/\n+/', ' ', $value);
  $value = preg_replace('/\s{2,}/', ' ', $value);
 
  $preamble_len = strlen($preamble);
 
  $lines = array();
  while (strlen($value)>(75-$preamble_len)) {
    $space = (75-$preamble_len);
    $mbcc = $space;
    while ($mbcc) {
      $line = mb_substr($value, 0, $mbcc);
      $oct = strlen($line);
      if ($oct > $space) {
        $mbcc -= $oct-$space;
      }
      else {
        $lines[] = $line;
        $preamble_len = 1; // Still take the tab into account
        $value = mb_substr($value, $mbcc);
        break;
      }
    }
  }
  if (!empty($value)) {
    $lines[] = $value;
  }
 
  return join($lines, "\n\t");
}

// Modified from http://wordpress.org/plugins/events-manager/
$id = $_GET['sessionid'];
$post = get_post($id);
Conferencer::add_meta($post);
//calendar header
$output = "BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//".get_bloginfo()."//EN";
echo preg_replace("/([^\r])\n/", "$1\r\n", $output);

$dateStart	= ':'.get_gmt_from_date(date('Y-m-d H:i:s', get_post_meta($post->time_slot, '_conferencer_starts', true)), 'Ymd\THis\Z');
$dateEnd = ':'.get_gmt_from_date(date('Y-m-d H:i:s', get_post_meta($post->time_slot, '_conferencer_ends', true)), 'Ymd\THis\Z');


//formats
$summary = $post->post_title;
$permalink = get_permalink($id);
$altdescription = do_shortcode("[session_meta
							post_id='$post->ID'
							show='room,track'
							room_prefix='Room: '
							room_suffix='\r\n'
							track_prefix='Track: ']")."\r\n".$post->post_content."\r\n".$permalink;
//$description =  mysql_real_escape_string(strip_tags($altdescription));

$altdescription = str_replace("\r\n","\\n",str_replace(";","\;",str_replace(",",'\,',$altdescription)));
$description = strip_tags($altdescription);
$location = get_the_title($post->room);
$dateModified = get_gmt_from_date($post->post_modified, 'Ymd\THis\Z');


//create a UID
$UID = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
	// 32 bits for "time_low"
	mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
	// 16 bits for "time_mid"
	mt_rand( 0, 0xffff ),
	// 16 bits for "time_hi_and_version",
	// four most significant bits holds version number 4
	mt_rand( 0, 0x0fff ) | 0x4000,
	// 16 bits, 8 bits for "clk_seq_hi_res",
	// 8 bits for "clk_seq_low",
	// two most significant bits holds zero and one for variant DCE1.1
	mt_rand( 0, 0x3fff ) | 0x8000,
	// 48 bits for "node"
	mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
);
		
//output ical item		
$output = "
BEGIN:VEVENT
UID:{$UID}
DTSTART{$dateStart}
DTEND{$dateEnd}
DTSTAMP:{$dateModified}\n";
$output .= "SUMMARY;LANGUAGE=en-gb:" . ical_split("SUMMARY;LANGUAGE=en-gb:",$summary) . "\n";
if( $description ){
    $output .= "DESCRIPTION:" . ical_split("DESCRIPTION:",$description);
}
$output .= "
LOCATION:{$location}
URL:{$permalink}
END:VEVENT";

//clean up new lines, rinse and repeat
echo preg_replace("/([^\r])\n/", "$1\r\n", $output);
?>
