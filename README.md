# wp-ckan-backend

WordPress plugin to manage datasets, organizations and groups in CKAN via API.

## Requirements

* [CMB2](https://wordpress.org/plugins/cmb2/) plugin must be installed

## Installation

In order to use this plugin you need to define two constants in your `wp-config.php` file:

```php 
define( 'CKAN_API_ENDPOINT', 'http://ckan.example.com/api/3/action/' );
define( 'CKAN_API_KEY', 'f359d6af-a3e8-4c35-aab2-17f6d4198b42' ); // ckan admin user api key
```

Additionally you have to define an array (`$language_priority`) with all supported languages in your `wp-config.php` file.
Please order the languages according to your needs. This order is used to find fallbacks for missing translations:

```php
$language_priority = array(
	1 => 'en',
	2 => 'de',
	3 => 'fr',
	4 => 'it'
);
```

## Development

1. Install composer if it isn't installed system wide:
    ```
   $ curl -sS https://getcomposer.org/installer | php
   ```

1. Run `php composer.phar install` to install dependencies

1. add wordpress-standard to phpcs: `./bin/phpcs --config-set installed_paths vendor/wp-coding-standards/wpcs`


To check the code style, run the build script:

```
$ ./build.sh
```

This script runs on GitLab CI as well for every pull request.

## Extract messages / Compile translation files

Run the following script to extract messages from php-files and generate a new wp-ogdch-theme.pot file:

```
$ ./extract_messages.sh
```

To compile all .po files to .mo files use the following script:

```
$ ./compile_translation_files.sh
```
