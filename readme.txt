=== Plugin Name ===
Contributors: littlejon
Tags: serp, position, longtail, long tail, results, google, bing, yahoo, SEO, widget, sidebar, keywords, keyword
Requires at least: 2.5
Tested up to: 3.3
Donate link: http://www.thelazysysadmin.net/software/appreciation/
Stable tag: 1.91

LJ Longtail SEO is a tool that detects search engine visits and uses this information to display a list of links based on second page search results

== Description ==

LJ Longtail SEO is a tool that detects search engine visits and uses this information to display a list of links based on second page search results.

The results in the database are aged off based on customizable settings so that once your longtail keywords have been boosted they will fall off the list and give way for other searches.

Admin interface has an optional keyword report that can be turned on via an option (On a large blog it is possible that there could be a performance hit on the admin page when using this option. Please note this will not slow down the blog side, just the admin page.). The keyword report will show the Top 100 keywords ordered by popularity and also showing the number of times the keyword has occurred.

As of Version 1.5 the plugin you can now add keywords to an Ignore list. This means if you don't want a certain keyword registering as a referred search term you can simply put the phrase into the Ignored Keywords list and it will no longer register.

A widget will display a list of the popular searches that have come from search engine result pages beyond the first and provide a link on your sidebar back to the pages found.

Using this method can help to increase traffic to a blog via bring more importance to the Longtail keywords that can be used to find a site.

The original idea for this plugin was taken from [SEO Booster Lite](http://wordpress.org/extend/plugins/seo-booster-lite/ "SEO Booster Lite") and [Second Page Poaching - Advanced White Hat SEO](http://www.thegooglecache.com/advanced-white-hat-seo-techniques/second-page-poaching-adanced-white-hat-seo-techniques/ "Second Page Poaching - Advanced White Hat SEO"). I used the other plugin for a short period of time and didn't like a few of the restrictions. Therefore I decided to make my own, there will be no Pro version or paid version. The version I release here is in production on my main blog. Any updates I make there will end up here including bugfixes.

This plugin currently only registers search results from Google, Bing, Yahoo, and Ask. It is compatible with both of Googles current referrer information meaning you should always have accurate SERP positioning results.

== Installation ==

1. Unzip and upload the `lj-longtail-seo` folder into your `/wp-content/plugins/` directory
2. Activate the Plugin through the Plugins menu
3. Edit the settings in the LJ Longtail SEO Admin page
4. Enjoy

If you install the update manually, please ensure you deactivate the plugin and then reactive. If you don't do this the plugin will not function!!

= Theme Installation (Optional, there is a widget that can be used for the Sidebar) =

If you don't want to use the Sidebar widget you can place the following code anywhere in your theme. It will display an Unordered List of Search Results.

`<?php
  if (class_exists('LJLongtailSEO')) {
    LJLongtailSEO::SEOResultsList();
  }
?>`

== Frequently Asked Questions ==

= There are no results shown in the widget or admin screen =

I recommend you leave the plugin activated and the widget disabled for the first week to allow the plugin to collect some data. The admin screen has a simulation of what will be shown in the widget so please wait a while until you are happy with the results. Please note that if you deactivate the plugin, it won't be able to collect data to display later on.

= I like your plugin, it is exactly what I was looking for. How can I help? =

The best way to help is to provide a link back to the plugins homepage [LJ Longtail SEO](http://www.thelazysysadmin.net/software/wordpress-plugins/lj-longtail-seo/ "The Lazy Sys Admin Wordpress Plugins")
 
= I have some ideas for your plugin =

Please leave a comment on the plugins homepage, that way I will know when someone has posted.

= I would like to have your plugin collect the information from the search engine but I would like to access the data and format it myself =

LJ Longtail SEO now has a new static function that you can use to get an array of data. To call this function use the following:

`$array = LJLongtailSEO::SEOResultsArray();`

The format of the returned array:

`Array
(
    [0] => Array
        (
            [postid] => 1
            [postpermalink] => http://urltopost/2009/10/post-1/
            [query] => some search term
        )

    [1] => Array
        (
            [postid] => 2
            [postpermalink] => http://urltopost/2009/10/post-2/
            [query] => some other search term
        )

)`

= There is an alert about Late Init in my admin bar =

As of Version 1.9 support for WP Super Cache has been added, although it will only work in certain configurations for WP Super Cache. You must be running in PHP Caching Mode and you must enable the Late Init function.

If you cant run WP Super Cache in this configuration you will get limited results from LJ Longtail SEO.

= I would like to use your plugin but dont want to use the wigdet, I would prefer to hardcode the plugin data into my theme =

Version 1.6 allows you to use a method call to display the plugins output. Place the following code in your theme

`<?php
  if (class_exists('LJLongtailSEO')) {
    LJLongtailSEO::SEOResultsList();
  }
?>`

== Upgrade Notice ==

= 1.91 =
This update fixes an issue with database load on highload sites by adding an appropriate index

== Changelog ==

= 1.91 =

* Added database index for highload sites

= 1.9 =

* Added support for running alongside WP Super Cache

= 1.8 =

* Bugfix: Yahoo search results were not being detected

= 1.7 =

* Bugfix: Cron function had a bug which lead the the cron tasks not being performed

= 1.6 =

* Bugfix: Some Google Image searches where being registered as incorrect
* Ability to use LJ Longtail SEO without the widget
* Ability to get an array of results from LJ Longtail SEO

= 1.5 =

* Widget: Fixed some theme incompatibility issues
* Feature Request: Ability to have ignored keywords
* Ability to clean old records in the database that contain any ignored keywords

= 1.4 =

* Divide by Zero bugfix in the Keyword report. If your blog had no registered keywords or you had emptied the database the Keyword Report would have errored with a Divide By Zero warning. This error is not critical and no extra functionality has been added, if you are running version 1.3 there is no real reason to upgrade.

= 1.3 =

* Added support for international characters
* Added keyword report section in admin interface

= 1.2 =

* Added backwards compatiblity down to Wordpress 2.5

= 1.1 =

* Added age weighting to the Widget results page. This will help stabilise the results and allow for graceful aging. The figure shown on the admin page is purely relative (it is based on the average id of the database records, as such this value will continue getting higher and higher but yet still remain relevant to the results being shown)

= 1.0 =

* Initial Release

== Screenshots ==

1. Widget Config Screen
2. Admin Screen Reports
3. Admin Screen Configuration
