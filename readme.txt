=== DBMaker ===
Contributors: dskugahara
Donate link: 
Tags: database, csv, import, search
Requires at least: 4.6
Tested up to: 5.1
Stable tag: 1.0
Requires PHP: 5.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Simple database plugin. import csv file, delete all records, 
search and sort in backend, simple search form for frontend.

== Description ==

This plugin make simple database from csv.
Import from csv files to custom post type.

*features
  * you can make database from csv file
  * import over 10000 records from csv file
  * delete all data records
  * support custom fields and taxonomies
  * sort and search data in backend
  * support shortcode for search form and result list in frontend
  * import from csv, insert only. update record not supported.

*Usage
  1. Select DataBase Maker in admin menu. Add new post.
  1. Title is DataBase name, it is displayed admin menu.
  1. Content is frontend search form. If content is empty, default form inserted.
  1. post_type is database type
  1. format is colum names for csv. comma separated. see [column names]
  1. status is default post status.
  1. charcode is csv files encode
  1. "skip first line" : If first line of csv is labels, check it.
  1. public is data status. if it is checked, public access from frontend.
  1. save post. 
  1. Select your database name  in admin menu.
  1. select csv file, import csv.
  1. keyword search in all columns, taxonomy search supported.
  1. Select DataBase Maker, select display, search form display in frontend.

*column names
  * post_title : title of the post
  * post_author: (login or ID) The user name or user ID number
  * post_date:
  * post_excerpt:
  * post_status:
  * post_password:
  * post_name:
  * post_parent:
  * menu_order:
  * post_category:
  * post_tags:
  * tax_{taxonomy}: (string, comma separated) Any field prefixed with tax_ will be used as a custom taxonomy.
  * {custom_field_key}: Any other column labels used as custom field.
  * comment_status: (eclosedf or eopenf) Default is the option edefault_comment_statusf, or eclosedf.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Settings->Plugin Name screen to configure the plugin
1. (Make your instructions match the desired user flow for activating and installing your plugin. Include any steps that might be needed for explanatory purposes)


== Frequently Asked Questions ==


== Screenshots ==


== Changelog ==

= 1.0 =
* first release


== Arbitrary section ==


