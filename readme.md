# DBMaker
* Contributors: dskugahara
* Donate link: 
* Tags: database, csv, import, search
* Requires at least: 4.6
* Tested up to: 5.1
* Stable tag: 1.0
* Requires PHP: 5.2.4
* License: GPLv2 or later
* License URI: https://www.gnu.org/licenses/gpl-2.0.html

Simple database plugin. import csv file, delete all records, 
search and sort in backend, simple search form for frontend.

## Description

This plugin make simple database from csv.
Import from csv files to custom post type.

### features
* you can make database from csv file
* import over 10000 records from csv file
* delete all data records
* support custom fields and taxonomies
* sort and search data in backend
* support shortcode for search form and result list in frontend
* import from csv, insert only. update record not supported.

### Usage

#### Create Database
1. Select DataBase Maker in admin menu. Add new post.
1. Title is database name, it is displayed admin menu.
1. Content is frontend search form. If content is empty, default form inserted.
#### Edit csv settings
1. post_type is database type
1. format is colum names for csv. comma separated. see **column names**
1. status is default post status.
1. charcode is csv files encode
1. "skip lines" : skip n lines
1. "public" is data status. if it is checked, public access from frontend.
1. save post. 

#### Import data from csv file
1. Select your database name  in admin menu.
1. Select csv file and push "read csv", start importing.
1. Display progress bar, please wait for a while.

#### Data operation in admin menu
* keyword search target all columns
* taxonomy filter supported
* title, date and  custom fields support sorting.
* Delete all data supported

#### Create search form for frontend
1. Select DataBase Maker, edit database
1. edit contents, see **Short codes**
1. save contents then view posts

### Column names
* post_title: title of the post
* post_author: (login or ID) The user name or user ID number
* post_date:
* post_excerpt:
* post_status:
* post_password:
* post_name:
* post_parent:
* menu_order:
* tax_{taxonomy}: (string, comma separated) Any field prefixed with tax_ will be used as a custom taxonomy.
* {custom_field_key}: Any other column labels used as custom field.

### Short codes

#### dbm_search
Create form tag for search. This code is enclosing.

##### option
* post_type : **required** Search database post_type.
* posts_per_page : default: 5
* pager : This option set not empty string, pager support. default: ''

#### dbm_tax_checkbox
Create checkboxes for taxonomy

##### option
* name : **requried** taxonomy name.(No "tax_" prefix)

#### dbm_tax_select
Create select tag for taxonomy.

##### option
* name : **requried** taxonomy name.(No "tax_" prefix)
* multiple : This option set not empty string, multiple select box. default: ''
* size : Select tag size.

#### dbm_textbox
Create text input box

##### option
* name : Set search target column name. 's' is target all columns. default : 's'
* required : true or false.  default : 'false'

#### dbm_result_table
Display search result table. This code need outside of dbm_search.

##### option
* label : th tag names, comma separated.
* data : column names, comma separated.

#### dbm_result_pager
Output pager

##### option
* label : pager button label. default : 'first, prev, next, last'

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Settings->Plugin Name screen to configure the plugin
1. (Make your instructions match the desired user flow for activating and installing your plugin. Include any steps that might be needed for explanatory purposes)


== Frequently Asked Questions ==
* Can i export data to csv file?
Export csv file not supported yet. ToDo.


== Screenshots ==
1. csv settings
2. data list in admin menu
3. search form editor
4. search form in frontend

== Changelog ==

= 1.1 =
* add view link in database admin menu.
* no import empty records.
* add order and orderby parameter in frontend search.
* Shortcode dbm_search: add parameter preload. Set it, data list load on init.(orderby post_id, ASC)
* Back to seach result page in frontend, recently data list display.

= 1.0 =
* first release

== Upgrade Notice == 

= 1.0 =
* first release

== Arbitrary section ==


