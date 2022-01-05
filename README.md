

# Local JSON for Gravity Forms

Store Gravity Forms as JSON to easily version control, sync and share forms across environments!

A Gravity Forms Add-On based on [ACF Local JSON](https://www.advancedcustomfields.com/resources/local-json/).
Bootstrapped using the [WordPress Plugin Boilerplate Generator](https://wppb.me/).

## What does it do?
This plugin saves JSON copies of Gravity Forms forms when they are updated. 

These JSON files can then be version controlled and shared across your team, development, staging and production environments.

Any changes between environments can be imported / updated from JSON within the WP Admin dashboard.

By default, JSON files are saved to a `gravity-forms-json` folder in your current theme directory. This can be overwritten using the `local_gf/filters/save-point` filter.

## Installation and usage
### Install plugin
- Download or clone this repo into your wp-plugins directory.
- Create an empty directory called `gravity-forms-json` in your current theme directory (or overwrite this using the `local_gf/filters/save-point` filter). This folder must have permissions for the server to read and write to.
- Activate `Local JSON for Gravity Forms` in WP Admin.
- Enable Local JSON for a Gravity Form by going to **Form -> Settings -> Local JSON**
- Save!

### Sync a form

 - Go to **Forms -> Local JSON** 
 - Find your form and click on **"Sync form"**

### Import a form

 - Go to **Forms -> Local JSON** 
 - Find your form and click on **"Import form"**


## How does it work?

When you enable a form for Local JSON a unique key and last modified timestamp are generated and added to your form settings. 

Once you have version controlled your JSON file, the rest of your team / development environments will be able to import and make updates to your form using the same key.

When your form is updated, a new copy of the JSON file is taken with an updated last modified timestamp. 

When the timestamp in your JSON file no longer matches the timestamp in your database, you will be able to sync any changes to your Gravity form.

As this plugin uses a unique key to link JSON files to forms - instead of auto-incrementing IDs - you can safely keep your forms in sync without the common pitfalls of using Gravity Forms across multiple environments.


## Filters
| Name                             | Description                                                                                                                                                                       | Type          | Default                                               |
|----------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------------|-------------------------------------------------------|
| `local_gf/filters/save-point`    | Directory to store JSON files.  Defaults to `/gravity-forms-json` within the currently activated theme directory.                                                                             | string        | `get_stylesheet_directory() . "/gravity-forms-json/"` |                                         |
| `local_gf/filters/before-save`   | Sanitise / amend any data or update settings before JSON is saved.                           |                        array      | Gravity Forms form array
|`local_gf/filters/before-sync`| Sanitise / amend any data or update settings before JSON is synced.| array| Gravity Forms form array
|`local_gf/filters/before-import`| Sanitise / amend any data or update settings before JSON is imported.| array| Gravity Forms form array

## Actions
| Name                          | Description                                                                                                                                                          | Type       |
|-------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------|
| `local_gf/actions/after-save` | Allows for any clean-up / functionality after JSON version of a form has been saved.  This action passes the Gravity Forms form array, form id and the result of `file_put_contents` or a WP_Error instance as parameters | array, int, int or WP_Error |
| `local_gf/actions/after-sync` | Allows for any clean-up / functionality after JSON version of a form has been synced with Gravity Forms.  Passes Gravity Forms array and form id or WP_Error instance as parameters| array, int or WP_Error |
| `local_gf/actions/after-import` | Allows for any clean-up / functionality after JSON version of a form has been imported into Gravity Forms.  Params as above. | array, int or WP_Error |





