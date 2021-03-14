# Local JSON for Gravity Forms

Store forms from Gravity Forms as JSON to easily share forms across environments!

Based on [ACF Local JSON](https://www.advancedcustomfields.com/resources/local-json/).
Bootstrapped using the [WordPress Plugin Boilerplate Generator](https://wppb.me/).

## Usage
This plugin saves JSON copies of any Gravity Forms forms when they are updated. Any changes between environments can then be imported / updated from JSON within the WP Admin dashboard.

By default, JSON files are saved to a 'gravity-forms-json' folder in your current theme directory. This can be overwritten using the 'local_gf/filters/save-point' filter. Forms can also be excluded from being saved using the 'local_gf/filters/exclude-forms' filter, see Filters below.

## Installation
- Download or clone this repo into your wp-plugins directory.
- Create an empty directory called 'gravity-forms-json' in your current theme directory (or overwrite this using the 'local_gf/filters/save-point' filter). This folder must have permissions for the server to read and write to.
- Activate 'Local JSON for Gravity Forms' in WP Admin.

## Filters
| Name                             | Description                                                                                                                                                                       | Type          | Default                                               |
|----------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------------|-------------------------------------------------------|
| `local_gf/filters/save-point`    | Directory to store JSON files.  Defaults to `/gravity-forms-json` within the currently activated theme directory.                                                                             | string        | `get_stylesheet_directory() . "/gravity-forms-json/"` |
| `local_gf/filters/exclude-forms` | Array of Gravity Forms form ids for forms that should not be saved as JSON.                                                                                                               | array         | Empty array                                           |
| `local_gf/filters/before-save`   | Sanitize / amend any data or update settings before JSON is saved.  This filter passes the current instance of the `Local_Json_Gravity_Forms_Admin` class as a second parameter. | array, object | Gravity Forms form array                              |                              |

## Actions
| Name                          | Description                                                                                                                                                          | Type       |
|-------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------|
| `local_gf/actions/after-save` | Allows for any clean-up / functionality after JSON version of a form has been saved.  This action passes the Gravity Forms form id and the saved data as parameters. | int, array |





