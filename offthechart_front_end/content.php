<?

/*
content.php
Fuse Playout System Web Content aka FusicBrainz
Generates various pages for artists and tracks in the system using MusicBrainz and Last.FM, along with the ability to display playlists
*/

ini_set('display_errors', 0); 

$bbcimgpath = realpath($_SERVER["DOCUMENT_ROOT"]) . "/sites/all/modules/offthechart_front_end/cache/bbcimgs/";

if (isset($_GET['artistimage']) AND isset($_GET['mbid'])) {
  $mbid = str_replace(".jpg","",$_GET['mbid']);
  if (($_GET["artistimage"] == "true") AND ($mbid != "") AND (preg_match('/^\w{8}-\w{4}-\w{4}-\w{4}-\w{12}$/',$mbid,$matches) == 1)) {
    header('Content-Type: image/jpeg');
    $imagedata = "";
    if (stristr($mbid,"/") === FALSE) {
      $filename = $mbid . ".jpg";
      // Let's use a 14 day cache
      $oldtime = time() - (86400*14);
      $fileexists = false;
      if (file_exists($bbcimgpath . $filename)) {
        $fileexists = true;
        if (filemtime($bbcimgpath . $filename) < $oldtime) {
          $fileexists = false;
        }
      }
      
      if ($fileexists) {
        $handle = fopen($bbcimgpath . $filename,"r",false);
        if ($handle) {
  	  $imagedata = stream_get_contents($handle);
          fclose($handle);
        }
      } else {
        $imagedata = getimage($_GET["mbid"]);
        if ($imagedata != "") {
          $fh = @fopen($bbcimgpath . $filename, 'w',false);
          if ($fh) {
            fwrite($fh, $imagedata);
            fclose($fh);
          }
        } else {
          $handle = fopen(realpath($_SERVER["DOCUMENT_ROOT"]) . "/sites/all/modules/offthechart_front_end/noimg.jpg","r",false);
          if ($handle) {
  	      $imagedata = stream_get_contents($handle);
              fclose($handle);
          }
        }
      }
    } else {
      $handle = fopen(realpath($_SERVER["DOCUMENT_ROOT"]) . "/sites/all/modules/offthechart_front_end/noimg.jpg","r",false);
      if ($handle) {
	    $imagedata = stream_get_contents($handle);
            fclose($handle);
      }
    }
    exit($imagedata);
  }
}

function getimageurl($mbid_in) {
  $bbcimgpath = realpath($_SERVER["DOCUMENT_ROOT"]) . "/sites/all/modules/offthechart_front_end/cache/bbcimgs/";
  if (preg_match('/^\w{8}-\w{4}-\w{4}-\w{4}-\w{12}$/',$mbid_in,$matches) != 1) {
    return "noimg.jpg";
  }
  $fileexists = false;
  if (file_exists($bbcimgpath . $mbid_in . ".jpg")) {
    $fileexists = true;
    $oldtime = time() - (86400*14);
    if (filemtime($bbcimgpath . $mbid_in . ".jpg") < $oldtime) {
      $fileexists = false;
    }
  }
  if ($fileexists) {
    return "/music/image/" . $mbid_in . ".jpg";
  } else {
    return "/music/artist/content.php?artistimage=true&amp;mbid=" . rawurlencode($mbid_in);
  }
}

// XML parsing function
function gettextbetweentags($string, $tagname) {
  $starttag = "<" . $tagname . ">";
  $endtag = "</" . $tagname . ">";
  if (stristr($string, $tagname)) {
    $string = explode($starttag,$string);
    if (sizeof($string) > 1) {
      $string = explode($endtag,$string[1]);
    } else {
      return "";
    }
    return $string[0];
  } else {
    return "";
  }
}

// Gets data from BBC Music
function getbbcdata($mbid_in,$datatype) {
  // Get artist info via BBC Music in XML
  if (preg_match('/^\w{8}-\w{4}-\w{4}-\w{4}-\w{12}$/',$mbid_in,$matches) != 1) {
    return "";
  }
  
  $bbcdatapath = realpath($_SERVER["DOCUMENT_ROOT"]) . "/sites/all/modules/offthechart_front_end/cache/bbcdata/";
  $filename = $mbid_in . "-" . $datatype . ".xml";
  $oldtime = time() - (86400*14);
  $fileexists = false;
  if (file_exists($bbcdatapath . $filename)) {
    $fileexists = true;
    if (filemtime($bbcdatapath . $filename) < $oldtime) {
      $fileexists = false;
    }
  }

  $xmldata = "";
  if ($fileexists) {
    $handle = fopen($bbcdatapath . $filename,"r",false);
    if ($handle) {
      $xmldata = stream_get_contents($handle);
      fclose($handle);
    }
  } else {
    $options = array( 'http' => array(
          'user_agent'    => 'Off The Chart Radio Data Collector',
          'max_redirects' => 2,
          'timeout'       => 10,
    ) );
    $context = stream_context_create($options);
    if (($handle = @fopen("http://www.bbc.co.uk/music/artists/" . $mbid_in . "/" . $datatype . ".xml","r",false,$context)) !== FALSE) {
      $xmldata = stream_get_contents($handle);
      fclose($handle);
      $fh = @fopen($bbcdatapath . $filename, 'w',false);
      if ($fh) {
        fwrite($fh, $xmldata);
        fclose($fh);
      }
    }
  }
  return $xmldata;
}

// Gets data from Songkick
function getsongkickdata($mbid_in) {
  // Get artist event info in JSON
  if (preg_match('/^\w{8}-\w{4}-\w{4}-\w{4}-\w{12}$/',$mbid_in,$matches) != 1) {
    return "";
  }
  
  $songkickpath = realpath($_SERVER["DOCUMENT_ROOT"]) . "/sites/all/modules/offthechart_front_end/cache/songkick/";
  $filename = $mbid_in . ".json";
  $oldtime = time() - (86400*2);
  $fileexists = false;
  if (file_exists($songkickpath . $filename)) {
    $fileexists = true;
    if (filemtime($songkickpath . $filename) < $oldtime) {
      $fileexists = false;
    }
  }

  $jsondata = "";
  if ($fileexists) {
    $handle = fopen($songkickpath . $filename,"r",false);
    if ($handle) {
      $jsondata = stream_get_contents($handle);
      fclose($handle);
    }
  } else {
    $options = array( 'http' => array(
          'user_agent'    => 'Off The Chart Radio Data Collector',
          'max_redirects' => 2,
          'timeout'       => 10,
    ) );
    $context = stream_context_create($options);
    $handle = fopen("http://api.songkick.com/api/3.0/artists/mbid:" . $mbid_in . "/calendar.json?apikey=myapikey&per_page=3","r",false,$context);
    $jsondata = "";
    if ($handle) {
      $jsondata = stream_get_contents($handle);
      fclose($handle);
      $fh = @fopen($songkickpath . $filename, 'w',false);
      if ($fh) {
        fwrite($fh, $jsondata);
        fclose($fh);
      }
    }
  }
  return $jsondata;
}

function getimage($mbid_in) {
  if (preg_match('/^\w{8}-\w{4}-\w{4}-\w{4}-\w{12}$/',$mbid_in,$matches) != 1) {
    return "";
  }
  
  $imgurl = "http://www.bbc.co.uk/music/images/artists/542x305/" . $mbid_in . ".jpg";
  $options = array( 'http' => array(
        'user_agent'    => 'Off The Chart Radio Data Collector',
        'max_redirects' => 2,
        'timeout'       => 10,
  ) );
  $context = stream_context_create($options);
  $handle = @fopen($imgurl,"r",false,$context);
  if ($handle) {
	  $imagedata = stream_get_contents($handle);
	  fclose($handle);
  } else {
	  $imagedata = "";
  }
  return $imagedata;
}

// Gets basic info about a particular artist
function getartistdescription($mbid_in) {

  if (preg_match('/^\w{8}-\w{4}-\w{4}-\w{4}-\w{12}$/',$mbid_in,$matches) != 1) {
    return array("","","");
  }

  $xmldata = getbbcdata($mbid_in,"wikipedia");
  
  // Handle the XML, creating an array of (artist name, description, more info url)
  if (!strstr($xmldata,"<artist artist_type=")) {
    $artistarray = array("","","");
  } else {
    $artistarray[] = str_replace("&amp;","&",gettextbetweentags($xmldata,"name"));
    $artistarray[] = gettextbetweentags($xmldata,"content");
    $artistarray[] = gettextbetweentags($xmldata,"url");
  }

  // Return the data
  return $artistarray;
}

// Gets links to artists' official homepages, fan pages and myspace - generate last.fm and musicbrainz links dynamically
function getartistlinks($mbid_in) {
  if (preg_match('/^\w{8}-\w{4}-\w{4}-\w{4}-\w{12}$/',$mbid_in,$matches) != 1) {
    return array("","","");
  }

  $xmldata = getbbcdata($mbid_in,"links");
  
  // Handle the XML, creating an array of (artist name, description, more info url)
  if (!strstr($xmldata,"<artist artist_type=")) {
    $artistarray = array("","","");
  } else {
    if (stristr($xmldata, "official homepage")) {
      $officialhomepage = explode("<link type=\"official homepage\">",$xmldata);
      $officialhomepage = explode("</link>",$officialhomepage[1]);
      $officialhomepage = gettextbetweentags($officialhomepage[0],"url");
    } else {
      $officialhomepage = "";
    }
    if (stristr($xmldata, "fanpage")) {
      $fanpage = explode("<link type=\"fanpage\">",$xmldata);
      if (sizeof($fanpage) > 1) {
        $fanpage = explode("</link>",$fanpage[1]);
        $fanpage = gettextbetweentags($fanpage[0],"url");
      } else {
        $fanpage = "";
      }
    } else {
      $fanpage = "";
    }
    if (stristr($xmldata, "myspace")) {
      $myspace = explode("<link type=\"myspace\">",$xmldata);
      if (sizeof($myspace) > 1) {
        $myspace = explode("</link>",$myspace[1]);
        $myspace = gettextbetweentags($myspace[0],"url");
      } else {
        $myspace = "";
      }
    } else {
      $myspace = "";
    }
    if (stristr($xmldata, "social network")) {
      $facebook = explode("<link type=\"social network\">",$xmldata);
      if (sizeof($facebook) > 1) {
        $facebook = explode("</link>",$facebook[1]);
        $facebook = gettextbetweentags($facebook[0],"url");
        if (stristr($facebook, "facebook.com/")) {
          $facebook = explode("facebook.com/",$facebook);
          if (sizeof($facebook) > 1) {
	          $facebook = trim($facebook[1]);
	        } else {
	          $facebook = "";
	        }
        } else {
	        $facebook = "";
        }
      } else {
        $facebook = "";
      }
    } else {
      $facebook = "";
    }
    if (stristr($xmldata, "microblog")) {
      $twitter = explode("<link type=\"microblog\">",$xmldata);
      if (sizeof($twitter) > 1) {
        $twitter = explode("</link>",$twitter[1]);
        $twitter = gettextbetweentags($twitter[0],"url");
        if (stristr($twitter, "twitter.com/")) {
          $twitter = explode("twitter.com/",$twitter);
          if (sizeof($twitter) > 1) {
            $twitter = trim($twitter[1]);
          } else {
            $twitter = "";
          }
        } else {
          $twitter = "";
        }
      } else {
        $twitter = "";
      }
    } else {
      $twitter = "";
    }
    $artistarray = array($officialhomepage,$fanpage,$myspace,$facebook,$twitter);
  }

  // Return the data
  return $artistarray;
}

// Generates a list of the tracks for this artist most recently played on Fuse with shows and links
function getlatesttracks($artistid) {
  if (!is_numeric($artistid)) {
    return array();
  }

  // Check the database for recent tracks  
  $query = db_query("SELECT {otc_playlist_tracks}.playlist_id,{otc_tracks}.track_name,{otc_tracks}.track_mix,{otc_playlist_tracks}.playlist_track_timestamp FROM {otc_tracks},{otc_playlist_tracks} WHERE {otc_tracks}.artist_id = '$artistid' AND {otc_tracks}.track_id = {otc_playlist_tracks}.playlist_track_id ORDER BY {otc_playlist_tracks}.playlist_track_timestamp DESC LIMIT 20");
  $dbtracklist = $query->fetchAll();
  
  $trackarray = array();
  
  for ($i=0;$i<sizeof($dbtracklist);$i++) {
    $playlistid = $dbtracklist[$i]->playlist_id;
    if ($dbtracklist[$i]->playlist_track_timestamp != 0) {
      $showquery = db_query("SELECT schedule_title,schedule_uid,schedule_show_id,schedule_start,schedule_minisite FROM {otc_schedule} WHERE schedule_playlisted = '$playlistid' OR schedule_playlist_draft = '$playlistid' LIMIT 1");
    } else {
      $showquery = db_query("SELECT schedule_title,schedule_uid,schedule_show_id,schedule_start,schedule_minisite FROM {otc_schedule} WHERE schedule_playlisted = '$playlistid' AND schedule_start < '" . date("Y-m-d H:i:s") . "' LIMIT 1");
    }
    $dbshowlist = $showquery->fetch();
    if ($dbshowlist != "") {
      $showtitle = $dbshowlist->schedule_title;
      $showuid = $dbshowlist->schedule_uid;
      $showshowid = $dbshowlist->schedule_show_id;
      $showstart = strtotime($dbshowlist->schedule_start);
      $showsite = $dbshowlist->schedule_minisite;
      if ($showuid != 0) {
        // Query the user name
        $shownamequery = db_query("SELECT name FROM {users} WHERE uid = '$showuid' AND status = '1'");
        $showminisitequery = db_query("SELECT value FROM {profile_value} INNER JOIN {profile_field} ON {profile_value}.fid = {profile_field}.fid WHERE {profile_value}.uid = '$showuid' AND {profile_field}.name = 'profile_otc_minisite'");
        $shownames = $shownamequery->fetch();
        $showsites = $showminisitequery->fetch();
        if (($showtitle == "") AND ($shownames)) {
          $showtitle = $shownames->name;
        }
        if (($showsite == "") AND ($showsites)) {
          $showsite = $showsites->value;
        }
      } else if ($showshowid != 0) {
        // Query the show name
        $shownamequery = db_query("SELECT show_title,show_minisite FROM {otc_shows} WHERE show_id = '$showshowid' AND show_visible = '1'");
        $shownames = $shownamequery->fetch();
        if ($shownames) {
          if ($showtitle == "") {
            $showtitle = $shownames->show_title;
          }
          if ($showsite == "") {
            $showsite = $shownames->show_minisite;
          }
        }
      }
      $trackarray[] = array($dbtracklist[$i]->track_name,$dbtracklist[$i]->track_mix,$showtitle,$dbtracklist[$i]->playlist_track_timestamp,$showstart,$showsite);
    }
  }
  
  while(sizeof($trackarray) > 5) {
    array_pop($trackarray);
  }
  
  // Return the data
  return $trackarray;
}

// Gets up to five shows that play a particular artist the most
function getplayedby($artistid) {
  // Check the database for tracks
  if (!is_numeric($artistid)) {
    return array();
  }
  
  $trackquery = db_query("SELECT schedule_uid,schedule_show_id,COUNT(playlist_unique_id) AS playcount,show_title,name,show_minisite FROM {otc_playlist_tracks} LEFT JOIN {otc_tracks} ON {otc_playlist_tracks}.playlist_track_id = {otc_tracks}.track_id LEFT JOIN {otc_schedule} ON {otc_schedule}.schedule_playlisted = {otc_playlist_tracks}.playlist_id OR {otc_schedule}.schedule_playlist_draft = {otc_playlist_tracks}.playlist_id LEFT JOIN {users} ON {users}.uid = {otc_schedule}.schedule_uid AND {users}.status = '1' LEFT JOIN {otc_shows} ON {otc_shows}.show_id = {otc_schedule}.schedule_show_id AND {otc_shows}.show_visible = '1' WHERE {otc_tracks}.artist_id = '$artistid' AND schedule_show_type < 2 GROUP BY schedule_uid,schedule_show_id ORDER BY COUNT(playlist_unique_id) DESC LIMIT 20");
  
  // Get the data
  $dbtracklist = $trackquery->fetchAll();
  
  $showretarray = array();
  
  for ($i=0;$i<sizeof($dbtracklist);$i++) {
    if ($dbtracklist[$i]->schedule_uid != 0) {
      $showminisitequery = db_query("SELECT value FROM {profile_value} INNER JOIN {profile_field} ON {profile_value}.fid = {profile_field}.fid WHERE {profile_value}.uid = '" . $dbtracklist[$i]->schedule_uid . "' AND {profile_field}.name = 'profile_otc_minisite'");
      $showsites = $showminisitequery->fetch();
      $showsite = "";
      if ($showsites) {
        $showsite = $showsites->value;
      }
      if ($dbtracklist[$i]->name != null) {
        $showretarray[] = array($dbtracklist[$i]->name,$dbtracklist[$i]->playcount,$showsite);
      }
    } else if ($dbtracklist[$i]->schedule_show_id != 0) {
      if ($dbtracklist[$i]->show_title != null) {
        $showretarray[] = array($dbtracklist[$i]->show_title,$dbtracklist[$i]->playcount,$dbtracklist[$i]->show_minisite);
      }
    }
  }
  
  while(sizeof($showretarray) > 5) {
    array_pop($showretarray);
  }

  // Return the data
  return $showretarray;
}

// Gets most played tracks for an artist
function getmostplayed($artistid) {

  if (!is_numeric($artistid)) {
    return array();
  }
  // Check the database for tracks
  $trackquery = db_query("SELECT track_name,track_mix,COUNT(playlist_unique_id) AS playcount FROM {otc_playlist_tracks} LEFT JOIN {otc_tracks} ON {otc_playlist_tracks}.playlist_track_id = {otc_tracks}.track_id WHERE {otc_tracks}.artist_id = '$artistid' GROUP BY {otc_playlist_tracks}.playlist_track_id ORDER BY COUNT(playlist_unique_id) DESC LIMIT 5");
  
  // Get the data
  $dbtracklist = $trackquery->fetchAll();
  
  $playretarray = array();
  
  for ($i=0;$i<sizeof($dbtracklist);$i++) {
    $playretarray[] = array($dbtracklist[$i]->track_name,$dbtracklist[$i]->track_mix,$dbtracklist[$i]->playcount);
  }

  // Return the data
  return $playretarray;
}

?>
