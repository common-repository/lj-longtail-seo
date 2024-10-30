<?php
/*
Plugin Name: LJ Longtail SEO
Plugin URI: http://www.thelazysysadmin.net/software/wordpress-plugins/lj-longtail-seo/
Description: LJ Longtail SEO is a tool that detects search engine visits and uses this information to display a list of links based on second page search results
Author: Jon Smith
Version: 1.91
Author URI: http://www.thelazysysadmin.net/
*/

class LJLongtailSEO {

  private $dbversion = "1.76";
  private $pluginversion = "1.91";

  private $defaults = array
    (
      'enabled' => true,
      'widget-title' => 'Popular Searches',
      'widget-numitems' => '5',
      'daysbeforearchive' => '28',
      'showdonate' => false,
      'showkeywordreport' => false,
      'ignorelist' => ''
    );

  function LJLongtailSEO() {
    add_action('admin_menu', array(&$this, 'admin_menu'));
    add_action('plugins_loaded', array(&$this, 'plugins_loaded'));
    add_action('init', array(&$this, 'checkreferrer'));
    add_action('admin_init', array(&$this, 'admin_init'));

    //add_filter('wp_head', array(&$this, 'checkreferrer'));
    add_filter('plugin_action_links', array(&$this, 'plugin_action_links'), 10, 2 );

    register_activation_hook(__FILE__, array(&$this, 'db_install'));
    register_deactivation_hook(__FILE__, array(&$this, 'plugin_deactivate'));
  }

  function admin_init() {
    global $wp_super_cache_late_init;

    if (is_plugin_active('wp-super-cache/wp-cache.php')) {
        if (isset($wp_super_cache_late_init) && $wp_super_cache_late_init == true) {

        } else {
            add_action('admin_notices', array(&$this, 'wp_super_cache_warning'));
        }
    }

  }

  function wp_super_cache_warning() {
    echo "<div id='lj-warning' class='updated fade'><p><strong>LJ Longtail SEO - WP Super Cache detected without Late Init setting. For LJ Longtail SEO to function with WP Super Cache you need to enable the Late Init mode and be operating in PHP Caching mode. This plugin will not work with mod_rewrite caching.</strong></p></div>";
  }

  function admin_menu() {
    if ( function_exists('add_options_page') )
      add_options_page(__('LJ Longtail SEO Configuration'), __('LJ Longtail SEO'), 8, __FILE__, array(&$this, 'config'));
  }

  function plugins_loaded() {
    register_sidebar_widget("LJ Longtail SEO", array(&$this, "widget"));
    register_widget_control("LJ Longtail SEO", array(&$this, "widget_control"));
  }

  function checkreferrer() {
    global $wpdb;

    // These calls no longer work as the plugin has been moved to the INIT stage for
    // WP Super Cache compatibility

    /*
    if (is_feed())
      return;
    if (is_category())
      return;
    if (is_tag())
      return;
    if (is_front_page())
      return;
    */

    $table_name = $wpdb->prefix."ljlongtailseo";

    if (isset($_SERVER["HTTP_REFERER"])) {
      $arraylist = parse_url($_SERVER['HTTP_REFERER']);

      if ($this->is_search($arraylist)) {
        $query = $this->get_query($arraylist);
        if ($query != false) {
          $query = urldecode($query);
          $query = $this->stripunwantedcharacters($query);
          $query = strtolower($query);

          if ($this->should_ignore($query)) return;

          $serpposition = $this->get_serpposition($arraylist);

          // As the call for this plugin has moved to INIT we loose the ability to use
          // $wp_query
          $postid = url_to_postid("http://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);
          if ($postid > 0) {
            $wpdb->query($wpdb->prepare("INSERT INTO $table_name (postid, serppage, query, referrer) VALUES (%d, %d, %s, %s);", $postid, $serpposition, $query, $_SERVER["HTTP_REFERER"]));
          }
        }
      }
    }
  }

  function stripunwantedcharacters($string) {
    $return = str_replace("'", '', $string);
    $return = str_replace('"', '', $return);
    $return = str_replace('+', '', $return);
    $return = ereg_replace("[ \t\n\r]+", " ", $return);
    $return = htmlspecialchars($return);

    return $return;
  }

  function is_search($urlparts) {
    $urllist[] = "bing.com";
    $urllist[] = ".google.";
    $urllist[] = ".yahoo.";
    $urllist[] = ".ask.";

    foreach ($urllist as $searchurl) {
      if (stripos($urlparts["host"], $searchurl) !== false) {
        return true;
      }
    }

    return false;
  }

  function get_query($urlparts) {
    if ((stripos($urlparts["query"], "q=") !== false) || (stripos($urlparts["query"], "p=") !== false)) {

      $querylist = explode("&", $urlparts["query"]);

      foreach ($querylist as $query) {
        if (stripos($query, "q=") !== false) {
          $t = explode("=", $query);

          // This is for the Google Image problem where a Querystring is incorrectly
          // registered.
          if (strtolower($t[0]) == "q") {
            if (stripos($t[1], "site:") !== false) return false;
            if (stripos($t[1], "related:") !== false) return false;
            if (stripos($t[1], "cache:") !== false) return false;
            if (stripos($t[1], "link:") !== false) return false;

            return $t[1];
          } else {
            return false;
          }
        }

        if (stripos($query, "p=") !== false) {
          $t = explode("=", $query);

          // This is for the Google Image problem where a Querystring is incorrectly
          // registered.
          if (strtolower($t[0]) == "p") {
            if (stripos($t[1], "site:") !== false) return false;
            if (stripos($t[1], "related:") !== false) return false;
            if (stripos($t[1], "cache:") !== false) return false;
            if (stripos($t[1], "link:") !== false) return false;

            return $t[1];
          } else {
            return false;
          }
        }
      }
    }

    return false;
  }

  function should_ignore($query) {
    $options = get_option('LJLongtailSEO');

    if ($options === false) {
      $options = array();
    }

    foreach ($this->defaults as $key => $value) {
      if (!isset ($options[$key]))
        $options[$key] = $value;
    }

    $ignorelist = explode(',', $options['ignorelist']);

    foreach ($ignorelist as $item) {
      $test = trim($item);

      if (stripos($query, $test) !== false) {
        return true;
      }
    }

    return false;
  }

  function get_serpposition($urlparts) {
    $querypart[] = "first";
    $querypart[] = "page";
    $querypart[] = "b";
    $querypart[] = "start";
    $querypart[] = "cd";

    $querylist = explode("&", $urlparts["query"]);

    foreach ($querypart as $part) {
      if (stripos($urlparts["query"], $part) !== false) {
        switch ($part) {
          case "first":
          case "b":
          case "start":
          case "cd":
            if (preg_match("/[&\?](first|b|start|cd)=([0-9]*)/i", $urlparts["query"], $temp)){
              return (floor($temp[2] / 10) + 1);
            }
            break;
          case "page":
            if (preg_match("/[&\?](page)=([0-9]*)/i", $urlparts["query"], $temp)){
              return $temp[2];
            }
            break;
        }
      }
    }

    return -1;
  }

  function widget($args) {
    global $wpdb, $wp_locale;

    extract($args);

    $options = get_option('LJLongtailSEO');
    $table_name = $wpdb->prefix."ljlongtailseo";

    if ($options === false) {
      $options = array();
    }

    foreach ($this->defaults as $key => $value) {
      if (!isset ($options[$key]))
        $options[$key] = $value;
    }

    echo "\n<!-- LJLongtailSEO Version ".$this->pluginversion." Start -->\n";
    echo $before_widget;
    echo $before_title.$options['widget-title'].$after_title."\n";

    $dbresults = $wpdb->get_results($wpdb->prepare("SELECT avg(id) AS relage, postid, query, serppage, count(query) AS num, archive FROM $table_name GROUP BY postid, query, serppage HAVING serppage != -1 AND serppage != 1 AND postid != 0 AND archive = 0 ORDER BY serppage, num DESC, relage LIMIT 0, %d;", $options['widget-numitems']), ARRAY_A);

    if (count($dbresults) > 0) {
      echo "  <ul>\n";

      foreach ($dbresults as $result) {
        echo "    <li><a href=\"".get_permalink($result["postid"])."\" title=\"".$result["query"]."\">".$result["query"]."</a></li>\n";
      }

      echo "  </ul>\n";
    } else {
      echo "There are currently no popular searches registered. Please be patient, in the meantime it is probably best to disable the widget. Check on the widget results in the admin screen.";
    }

    echo $after_widget;
    echo "\n<!-- LJLongtailSEO End -->\n";
  }

  static function SEOResultsArray($varcount = 5) {
    global $wpdb;

    $options = get_option('LJLongtailSEO');
    $table_name = $wpdb->prefix."ljlongtailseo";

    $dbresults = $wpdb->get_results($wpdb->prepare("SELECT avg(id) AS relage, postid, query, serppage, count(query) AS num, archive FROM $table_name GROUP BY postid, query, serppage HAVING serppage != -1 AND serppage != 1 AND postid != 0 AND archive = 0 ORDER BY serppage, num DESC, relage LIMIT 0, %d;", $varcount), ARRAY_A);

    $array = array();

    $i = 0;
    if (count($dbresults) > 0) {
      foreach ($dbresults as $result) {
        $array[$i]["postid"] = $result["postid"];
        $array[$i]["postpermalink"] = get_permalink($result["postid"]);
        $array[$i]["query"] = $result["query"];
        $i++;
      }
    }

    return $array;
  }

  static function SEOResultsList($varcount = 5) {
    global $wpdb;

    $options = get_option('LJLongtailSEO');
    $table_name = $wpdb->prefix."ljlongtailseo";

    $dbresults = $wpdb->get_results($wpdb->prepare("SELECT avg(id) AS relage, postid, query, serppage, count(query) AS num, archive FROM $table_name GROUP BY postid, query, serppage HAVING serppage != -1 AND serppage != 1 AND postid != 0 AND archive = 0 ORDER BY serppage, num DESC, relage LIMIT 0, %d;", $options['widget-numitems']), ARRAY_A);

    if (count($dbresults) > 0) {
      echo "  <ul>\n";

      foreach ($dbresults as $result) {
        echo "    <li><a href=\"".get_permalink($result["postid"])."\" title=\"".$result["query"]."\">".$result["query"]."</a></li>\n";
      }

      echo "  </ul>\n";
    }
  }

  function widget_control() {

    $options = get_option('LJLongtailSEO');

    if ($options === false) {
      $options = array();
    }

    foreach ($this->defaults as $key => $value) {
      if (!isset ($options[$key]))
        $options[$key] = $value;
    }

    if ($_POST['widget-LJLongtailSEO-submit']) {
      $options['widget-title'] = $_POST['widget-LJLongtailSEO-title'];
      $options['widget-numitems'] = sprintf("%d", $_POST['widget-LJLongtailSEO-numitems']);

      update_option('LJLongtailSEO', $options);
    }

    echo '<p><label for="widget-LJLongtailSEO-title">Title: <br />';
    echo '<input type="text" class="widefat" id="widget-LJLongtailSEO-title" name="widget-LJLongtailSEO-title" value="'.$options['widget-title'].'" /></label></p>';

    echo '<p><label for="widget-LJLongtailSEO-numitems">Num of Items: <br />';
    echo '<input type="text" class="widefat" id="widget-LJLongtailSEO-numitems" name="widget-LJLongtailSEO-numitems" value="'.$options['widget-numitems'].'" /></label></p>';

    echo '<input type="hidden" id="widget-LJLongtailSEO-submit" name="widget-LJLongtailSEO-submit" value="1" />';
  }

  function config() {
    global $wpdb;

    $options = get_option('LJLongtailSEO');
    $table_name = $wpdb->prefix."ljlongtailseo";

    if ($options === false) {
      $options = array();
    }

    foreach ($this->defaults as $key => $value) {
      if (!isset ($options[$key]))
        $options[$key] = $value;
    }

    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

    switch ($action) {
      case "editsettings":
        check_admin_referer('LJongtailSEO-editsettings');

        $links_archivedays = isset($_REQUEST['linksarchivedays']) ? $_REQUEST['linksarchivedays'] : 28;
        $links_keywordreport = isset($_REQUEST['linkskeywordreport']) ? $_REQUEST['linkskeywordreport'] : 0;
        $links_ignorelist = isset($_REQUEST['linksignorelist']) ? $_REQUEST['linksignorelist'] : '';
        $links_donate = isset($_REQUEST['linksdonate']) ? $_REQUEST['linksdonate'] : 0;

        $options['daysbeforearchive'] = sprintf("%d", $links_archivedays);
        $options['showkeywordreport'] = ($links_keywordreport == 1) ? true : false;
        $options['ignorelist'] = $links_ignorelist;
        $options['showdonate'] = ($links_donate == 1) ? true : false;

        if ($options['daysbeforearchive'] == 0) {
          $options['daysbeforearchive'] = 28;
        }

        break;
      case "maintenance":
        check_admin_referer('LJongtailSEO-maintenance');

        if (isset($_REQUEST["emptydb"])) {
          $wpdb->query("DELETE FROM $table_name;");
        }

        if (isset($_REQUEST["cleandb"])) {
          $wpdb->query("DELETE FROM $table_name WHERE archive = 1;");
        }

        if (isset($_REQUEST["archiveignored"])) {
          $results = $wpdb->get_results("SELECT id, query FROM $table_name;", ARRAY_A);

          $toarchive = array();
          foreach ($results as $result) {
            if ($this->should_ignore($result['query'])) {
              $toarchive[] = $result['id'];
            }
          }

          $sql = "UPDATE $table_name SET archive = 1 WHERE id IN (";
          foreach ($toarchive as $id) {
            $sql .= $id.",";
          }
          $sql = substr($sql, 0, strlen($sql) - 1);
          $sql .= ");";

          $wpdb->query($sql);
        }

        break;
    }
?>
<div class='wrap'>
<h2>LJ Longtail SEO</h2>
<h3>Top 10 Search Queries (includes Page 1 results, excludes archived records)</h3>
<div class="clear"></div>
<table class="widefat tag fixed" cellspacing="0">
  <thead>
  <tr>
  <th scope="col" id="position" class="manage-column column-position" style="">Position</th>
  <th scope="col" id="query" class="manage-column column-query" style="">Query</th>
  <th scope="col" id="postfound" class="manage-column column-postfound" style="">Post Found</th>
  <th scope="col" id="hits" class="manage-column column-hits" style="">Hits</th>
  </tr>
  </thead>

  <tfoot>
  <tr>
  <th scope="col" id="position" class="manage-column column-position" style="">Position</th>
  <th scope="col" id="query" class="manage-column column-query" style="">Query</th>
  <th scope="col" id="postfound" class="manage-column column-postfound" style="">Post Found</th>
  <th scope="col" id="hits" class="manage-column column-hits" style="">Hits</th>
  </tr>
  </tfoot>

  <tbody id="the-list" class="list:tag">
<?php

  $results = $wpdb->get_results("SELECT postid, query, count(query) AS num, archive FROM $table_name GROUP BY postid, query HAVING archive = 0 ORDER BY num DESC LIMIT 0, 10;", ARRAY_A);

  if (count($results) > 0) {
    $i = 1;
    foreach ($results as $result) {
?>
    <tr id="tag-<?php echo $i; ?>" class="iedit">
      <td class="text column-position"><?php echo $i; ?></td>
      <td class="text column-query"><?php echo $result['query']; ?></td>
      <td class="text column-postfound"><?php echo get_the_title($result['postid']); ?></td>
      <td class="text column-hits"><?php echo $result['num']; ?></td>
    </tr>
<?php
      $i++;
    }
  }
?>
</tbody>

</table>
<h3>Links that will be shown in widget</h3>
<div class="clear"></div>
<table class="widefat tag fixed" cellspacing="0">
  <thead>
  <tr>
  <th scope="col" id="query" class="manage-column column-query" style="">Query</th>
  <th scope="col" id="postfound" class="manage-column column-postfound" style="">Post Found</th>
  <th scope="col" id="hits" class="manage-column column-hits" style="">Hits</th>
  <th scope="col" id="ageweight" class="manage-column column-ageweight" style="">Age Weighting</th>
  </tr>
  </thead>

  <tfoot>
  <tr>
  <th scope="col" id="query" class="manage-column column-query" style="">Query</th>
  <th scope="col" id="postfound" class="manage-column column-postfound" style="">Post Found</th>
  <th scope="col" id="hits" class="manage-column column-hits" style="">Hits</th>
  <th scope="col" id="ageweight" class="manage-column column-ageweight" style="">Age Weighting</th>
  </tr>
  </tfoot>

  <tbody id="the-list" class="list:tag">
<?php

  $results = $wpdb->get_results($wpdb->prepare("SELECT avg(id) AS relage, postid, query, serppage, count(query) AS num, archive FROM $table_name GROUP BY postid, query, serppage HAVING serppage != -1 AND serppage != 1 AND postid != 0 AND archive = 0 ORDER BY serppage, num DESC, relage LIMIT 0, %d;", $options['widget-numitems']), ARRAY_A);

  if (count($results) > 0) {
    foreach ($results as $result) {
?>
    <tr id="tag-<?php echo $i; ?>" class="iedit">
      <td class="text column-query"><?php echo $result['query']; ?></td>
      <td class="text column-postfound"><?php echo get_the_title($result['postid']); ?></td>
      <td class="text column-hits"><?php echo $result['num']; ?></td>
      <td class="text column-hits"><?php echo sprintf("%d", $result['relage']); ?></td>
    </tr>
<?php
    }
  }
?>
</tbody>

</table>

<?php
  if ($options['showkeywordreport']) {
?>
<h3>Keyword Report</h3>
<div class="clear"></div>
<style type="text/css">
<!--
.ljlongtailseo-box {
  padding: 0px 0px 0px 25px;
}
.ljlongtailseo-section {
  float: left;
}
.ljlongtailseo-section ul {
  list-style-type: disc;
  padding: 0px 15px 0px 0px;
}
.ljlongtailseo-section li {
  margin-bottom: 0px;
}
.ljlongtailseo-section-next {
  padding-left: 10px;
}
.ljlongtailseo-clear {
  clear: both;
}
-->
</style>
<?php

    $array = $this->getkeywordarray();

    $count = count($array);

    if ($count > 0) {
      echo "<div class='ljlongtailseo-box'>\n";

      if ($count > 100) {
        $count = 100;
      }

      $mod = ceil($count / 4);

      $i = 0;
      foreach ($array as $item) {
        if (($i % $mod) == 0) {
          echo "  <div class='ljlongtailseo-section";
          if ($i > 0) {
            echo " ljlongtailseo-section-next";
          }
          echo "'>\n";
          echo "    <ul>\n";
        }

        echo "      <li>".$item['term']." (".$item['count'].")</li>\n";

        if ((($i+1) % $mod) == 0) {
          echo "    </ul>\n";
          echo "  </div>\n";
        }

        $i++;

        if ($i >= $count) break;
      }

      if ((($i) % $mod) != 0) {
        echo "    </ul>\n";
        echo "  </div>\n";
      }

      echo "</div>\n";

      echo "<div class='ljlongtailseo-clear'></div>\n";
    } else {
?>
Your blog hasn't registered any keyword based searches yet. Please be patient.
<?php
    }

  }
?>
<h3>Maintenance</h3>
<div class="clear"></div>
<form action="" method="post" id="LJLongtailSEOEditForm" accept-charset="utf-8">
  <input type="hidden" name="action" value="maintenance" />
  <?php wp_nonce_field('LJongtailSEO-maintenance'); ?>
  Running Archive Ignored Keywords will retrospectively run through your database and mark any search result that contains an ignored word as archived. This will stop the results from showing in the widget, keyword report, and top 10 listing.
  <p class="submit"><input onclick="return confirm('Are you sure you want to Archive Previous Results with ignored keywords from the database?');" type="submit" class="button-primary" name="archiveignored" value="Archive Ignored Keywords" /></p>
  Running Clean Database will delete all records in the LJ Longtail SEO database marked as Archived. This operation cannot be reversed.
  <p class="submit"><input onclick="return confirm('Are you sure you want to Clean the database?');" type="submit" class="button-primary" name="cleandb" value="Clean Database" /></p>
  Running Empty Database will delete ALL RECORDS IN THE DATABASE. This operation cannot be reversed. Unless of course you have backups, you do have backups dont you.
  <p class="submit"><input onclick="return confirm('Are you sure you want to Empty the database?');" type="submit" class="button-primary" name="emptydb" value="Empty Database" /></p>
</form>

<h3>Settings</h3>
<div class="clear"></div>
<form action="" method="post" id="LJLongtailSEOEditForm" accept-charset="utf-8">
  <input type="hidden" name="action" value="editsettings" />
  <?php wp_nonce_field('LJongtailSEO-editsettings'); ?>
  <table class="form-table">
  <tr class="form-field form-required">
    <th scope="row" valign="top"><label for="linkurl">Days Befores Archiving:</label></th>

    <td><input name="linksarchivedays" id="linksarchivedays" type="text" value="<?php echo $options['daysbeforearchive']; ?>" size="40" aria-required="true" /><br />
          <span class="description">Number of days before database records get marked as archived. Records marked as archived are not used for the widget display.</span></td>
  </tr>
  <tr valign="top">
  <th scope="row">Show Keyword Report</th>
  <td><fieldset><legend class="screen-reader-text"><span>Show Keyword Report</span></legend>
  <label for="linkskeywordreport">
  <input name="linkskeywordreport" type="checkbox" id="linkskeywordreport" value="1" <?php if ($options['showkeywordreport']) echo "checked='checked'"; ?> />
  To display a keyword report in the admin screen check this option.</label><br />
  </fieldset></td>
  </tr>
  <tr class="form-field form-required">
    <th scope="row" valign="top"><label for="linkurl">Ignore Keyword List:</label></th>

    <td><input name="linksignorelist" id="linksignorelist" type="text" value="<?php echo $options['ignorelist']; ?>" size="40" aria-required="true" /><br />
          <span class="description">List of keywords separated by comma to be used as an ignore list. Any word used here will not register a search engine referral.</span></td>
  </tr>
  <tr valign="top">
  <th scope="row">Show me the donate button</th>
  <td><fieldset><legend class="screen-reader-text"><span>Show the donate button</span></legend>
  <label for="linksdonate">
  <input name="linksdonate" type="checkbox" id="linksdonate" value="1" <?php if ($options['showdonate']) echo "checked='checked'"; ?> />
  If you like this plugin and would like to support it please turn this option on to show the donate button.</label><br />
  </fieldset></td>
  </tr>
</table>
  <p class="submit"><input type="submit" class="button-primary" name="submit" value="Update Settings" /></p>
</form>
<?php
    if ($options['showdonate']) {
?>
<div align="right">
If you would like to make a donation,<br />
please feel free to submit feature requests<br />
for this plugin via <a href="http://www.thelazysysadmin.net/software/wordpress-plugins/lj-longtail-seo/" target="_blank">LJ Longtail SEO</a><br />
plugin page. Submit a feature request even if<br />
you dont make a donation. Requests with donations will<br />
be given a higher priority :-).
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="8087129">
<input type="image" src="https://www.paypal.com/en_AU/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online.">
<img alt="" border="0" src="https://www.paypal.com/en_AU/i/scr/pixel.gif" width="1" height="1">
</form>
</div>
<?php
    }
?>
</div>
<div align="right"><a href="http://www.thelazysysadmin.net/software/wordpress-plugins/lj-longtail-seo/">LJ Longtail SEO Verion <?php echo $this->pluginversion; ?></a> - <a href="http://www.thelazysysadmin.net/">The Lazy Sys Admin</a></div>
<?php
    update_option('LJLongtailSEO', $options);

  }

  function getkeywordarray() {
    global $wpdb;

    $table_name = $wpdb->prefix."ljlongtailseo";

    $sql = "SELECT query FROM $table_name WHERE archive = 0;";
    $results = $wpdb->get_results($sql, ARRAY_A);

    $array = array();

    if ($results != "") {
      foreach ($results as $result) {
        $terms = explode(' ', $result['query']);
        foreach ($terms as $term) {
          if (isset($array[$term])) {
            $array[$term]["count"]++;
          } else {
            $array[$term]["count"] = 1;
            $array[$term]["term"] = $term;
          }
        }
      }
    }

    //arsort($array);
    uasort($array, array(&$this, 'sort_terms'));

    return $array;
  }

  function sort_terms($a, $b) {
    return ($b['count'] - $a['count']);
  }

  function db_install() {
    global $wpdb;

    $currentdbversion = get_option('ljlongtailseo-dbversion');

    $table_name = $wpdb->prefix."ljlongtailseo";

    if ( ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) || ($currentdbversion != $this->dbversion)) {
      $sql = "CREATE TABLE $table_name (
        id INT NOT NULL AUTO_INCREMENT  PRIMARY KEY ,
        postid INT NOT NULL ,
        query VARCHAR(255) NOT NULL ,
        serppage INT NOT NULL ,
        referrer TEXT NOT NULL ,
        timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
        archive TINYINT NOT NULL DEFAULT '0',
        KEY `Select_Index` (`postid`,`serppage`,`query`,`archive`)
      );";

      require_once(ABSPATH.'wp-admin/includes/upgrade.php');
      dbDelta($sql);

      update_option("ljlongtailseo-dbversion", $this->dbversion);
    }

    $options = get_option('LJLongtailSEO');

    if ($options === false) {
      $options = array();
    }

    foreach ($this->defaults as $key => $value) {
      if (!isset ($options[$key]))
        $options[$key] = $value;
    }

    wp_schedule_event(time(), 'daily', 'LJLongtailSEO_cron_hook');

    update_option('LJLongtailSEO', $options);
  }

  function plugin_deactivate() {
    wp_clear_scheduled_hook('LJLongtailSEO_cron_hook');
  }

  function plugin_action_links( $links, $file ) {
    static $this_plugin;

    if( empty($this_plugin) )
      $this_plugin = plugin_basename(__FILE__);

    if ( $file == $this_plugin )
      $links[] = '<a href="' . admin_url( 'options-general.php?page=lj-longtail-seo/lj-longtail-seo.php' ) . '">Settings</a>';

    return $links;
  }
}

function LJLongtailSEO_Cron() {
  global $wpdb;

  $options = get_option('LJLongtailSEO');
  $table_name = $wpdb->prefix."ljlongtailseo";

  $sql = "UPDATE $table_name SET archive = 1 ";
  $sql .= "WHERE (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(timestamp)) > %d;";

  $sql = $wpdb->prepare($sql, ($options['daysbeforearchive'] * 86400));
  $wpdb->query($sql);
}

add_action('LJLongtailSEO_cron_hook', 'LJLongtailSEO_Cron');

$LJLongtailSEO = new LJLongtailSEO();

?>