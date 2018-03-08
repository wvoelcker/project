# project
PHP microframework

This is a small framework designed for REST APIs and simple websites.  It is very lightweight, and has a shallow stack-trace, meaning that applications run fast and application errors are easy to track down.

## how to add to composer.json
Because of composer's minimum stabiity requirements, it is actually necessary to add the loggedPDO package to composer.json, as well as the 'project' package.  Additionally, it is not yet in packagist, so you need to add it specifically as a vcs repository, thus:


	"repositories": [
		{
			"type": "vcs",
			"url": "https://github.com/wvoelcker/project"
		}
	],

	"require": {
		"phryneas/logged-pdo": "dev-master#8ee1264d65439200cb1b5478852f8012deef7921@dev",
		"willv/project": "^1.0.0"
	}

There doesn't seem to be any way round this at the moment; loggedPDO may soon be forked and a stable version created, to avoid this necessity.
