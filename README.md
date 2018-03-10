# project
PHP microframework

This is a small framework designed for REST APIs and simple websites.  It is very lightweight, and has a shallow stack-trace, meaning that applications run fast and application errors are easy to track down.

## how to add to a project via composer
to add this microframework to your project, you should add it to the 'require' section of composer.json, as follows:

	"require": {
		"willv/project": "^1.0.0"
	}

## how to use
Basically, copy the [example app](https://github.com/wvoelcker/project-example-app) and adapt it to your needs

	* The web root should be 'www' and all PHP requests should be routed to www/index.php (other content in the www directory should be static resources only; this microframework does not concern itself with handling them)
	* All 'project' files and directories should be at the same level as the 'www' directory (i.e. one above the web root)
	* You will need a namespace for your app, for the autoloader to work; see the example app for more details.
