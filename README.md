# project
PHP microframework

This is a small framework designed for REST APIs and simple websites.  It is very lightweight, and has a shallow stack-trace, meaning that applications run fast and application errors are easy to track down.

## how to add to a project via composer
to add this microframework to your project, you should add it to the 'require' section of composer.json, as follows:

	"require": {
		"willv/project": "^1.0.0"
	}

## how to use

### example app
The [example app](https://github.com/wvoelcker/project-example-app) can be used to get up and running quickly; just copy it and adapt it to your needs

### directory structure
The web root should be 'www' and all PHP requests should be routed to www/index.php (other content in the www directory should be static resources only; this microframework does not concern itself with handling them).
All 'project' files and directories should be at the same level as the 'www' directory (i.e. one above the web root)

### bootstrapper
The microframework contains a bootstrapper object called App that can be used to easily set-up your projects.  You should instantiate it in a file called, e.g. "global.php" which you then require in every script that needs to use the framework; typically, the main front-controller for HTTP requests, and any shell scripts that need the environment as well.

### namespace
You will need to invent a namespace for your app, for the autoloader to work; see the [example app](https://github.com/wvoelcker/project-example-app) for more details.

### constructors
The 'new' keyword is disabled for all classes in this framework; instead, there is a factory method called 'create' which should be called statically (e.g. User::create()).  This is because the static-method syntax allows, more versions of PHP, for calling methods on objects in the same line as instantiating them.

### abstract classes
Many of the classes in 'project' are abstract classes, meaning that you can't instantiate them directly.  Instead you should extend the class with a version relevant for your app, and use that instead.  For example, if you are writing a blog app you might have Domain Objects (see below) called User, Post, and Comment.  These classes should all extend the base DomainObject class.

### setUp methods
Many of the abstract classes in 'project' have setUp methods, which is where you should do your configuring (e.g. defining fields in a dataset).  See the [example app]() for more details.

### core concepts

#### domain objects
Domain objects represent entities used by your app (for example, blog posts, or users).  Each domain object is associated with a type of Dataset, meaning that that data submitted for its various properties has a set of validation rules; if you break them, an exception will be thrown.

For example, if you have a domain object called User, you might associate it with a dataset called UserDataset, which contained the fields "name" and "age"

	$alice = User::create(array("name" => "Alice", "age" => "54"))
	$alice->set("age", "55")

On both the above lines, the whole dataset will be validated.

#### datasets
Datasets are used in two ways:

	* To validate data submitted by a user
	* To validate data associated with a domain object

To make a new type of dataset, extend the class \WillV\Project\Dataset; you can then

	* Instantiate this dataset and call its isValid method to check if a set of data is valid
	* Associate the class with a domain object, and it will be used to validate data submitted when creating or changing the domain object

To associate a dataset with a domain object, configure it in the relevant domain object's setUp method, like this:
	
	class Task extends DomainObject {
		protected function setUp() {
			$this->dataSetName = "\ProjectExampleApp\Datasets\TaskDataset";
		}
	}
	

#### data mappers
Datamappers are used to save domain objects.

At present, there is only one type of datamapper; MySQLMapper; although more may be added (next on the list is a MongoMapper).

The DataMappers assume that the database schema has fields for "id", "created_utc" (date created), and "updated_utc" (date updated) so you should make sure that your database schema contains those fields, or there will be an error.

#### controllers
Controllers are simple PHP scripts, which should be placed in a directory called 'controllers'.  While being simple scripts, they are included from a context in which they have access to various pieces of information, for example URL parameters, which are available from the array '$this->urlParams'.
They should directly 'echo' their output, and are then free to 'exit'.

#### routing
Routing should be done by extending the Router class.  In the Router's setUp method, you can define routes for most common HTTP methods; parameters should be included in {braces}, and will be available in controllers in the numerically-indexed array $this->urlParams.  When you add a route, there are three parameters; the pattern, the controller-name, and the response type; the latter defaults to text/html.  See the [example app](https://github.com/wvoelcker/project-example-app) for more information.

#### 404 and 500 errors
If you create controllers called 404.php and 500.php these will be used automatically to handle the cases, respectively, of no-such-route and uncaught exceptions.

#### environments
Environment objects in 'project' store key-value pairs, and a function for determining whether or not the environment is active.

Your app should extend the \WillV\Project\Environment class, configuring your environment with a list of required fields that each environment must provide.  You can then instantiate your environments and add them to your environment list (see below).

When instantiating environments, you should provide a list of key/value pairs, and a function to determine if the new environment is active or not.  Some of the key/value pairs can be stored in JSON config files, which have the advantage that you can list them in .gitignore so that they will not end up in version control; these are good for storing, for example, access credentials for databases and third-party APIs.

##### environment lists
You should use an EnvironmentList to store a list of available environments.  The getActiveEnvironment method will find the activeEnvironment by calling the appropriate method of each environment in turn, until the active environment is found.