How to install yAronet
======================

Getting started
---------------

yAronet is using Git submodules for its code dependencies. Please make sure you
retreived them when cloning yAronet source code (downloading a ZIP file from
GitHub is not OK).

After cloning yAronet source code, browse to root directory and enter
`git submodule update --init` if you're using command line Git or any
other equivalent if you're using a GUI.

Install on development server
-----------------------------

You can get a working yAronet instance by either using provided
[Vagrant](https://www.vagrantup.com/) box in `/setup/vm` or installing manually
on your target server.

### Option 1: install on Vagrant virtual machine

Following software is required before you can install yAronet on a server using
provided Vagrant box:

* Vagrant 2.2.2 or above (not tested with previous versions)
  * Make sure you also satisfied Vagrant dependencies (e.g. Oracle VirtualBox)

Step by step install instructions:

* Run `vagrant up` from directory `/setup/vm`
* Wait for machine to be ready
* Go to "Configure website" section below

### Option 2: install manually on a server

Following software is required before you can manually install yAronet on a
development server:

* PHP v7.0.1 or above
  * php-curl, php-gd, php-mbstring, php-mcrypt, php-mysql & php-xml extensions
* Any compatible HTTP server like Apache or Nginx
* MariaDB/MySQL server v5.6.41 or above (not tested with previous versions)
* Node.js v10.13.0 or above (you can skip this dependency but won't be able to deploy to production)
  * imagemin, imagemin-cli, less, less-plugin-clean-css & uglify-js packages
* Optionally a Unix shell (running it through Cygwin is fine)

Step by step install instructions:

* Create a new MySQL database and import `/setup/database/schema.sql` to it
  * Make sure to have a MySQL user with read/write permissions on this database
* Create a new location in HTTP server e.g. `/yaronet/` and have it point to `/src/` directory
  * If you are using Apache you'll generate required `.htaccess` files in next steps
  * If you are using another HTTP server you will need extra configuration detailed below
* Run `/src/configure.sh` from your Unix shell
* Or, if you don't have a Unix shell available:
  * Retreive file `parser.php` from latest release of [Deval](https://github.com/r3c/deval/releases) and save it to `/setup/module/deval/src/parser.php`
  * Copy `/setup/module/amato/src` to `/src/library/amato` (or create a link to it)
  * Copy `/setup/module/deval/src` to `/src/library/deval` (or create a link to it)
  * Copy `/setup/module/glay/src` to `/src/library/glay` (or create a link to it)
  * Copy `/setup/module/losp/src` to `/src/library/losp` (or create a link to it)
  * Copy `/setup/module/queros/src` to `/src/library/queros` (or create a link to it)
  * Copy `/setup/module/redmap/src` to `/src/library/redmap` (or create a link to it)
* Go to "Configure website" section below

If you're using Apache HTTP server configuration files (`/src/.htaccess` and
`/src/static/.htaccess`) will automatically be created when running
configuration script during next steps. Do not forget to include them when
deploying website or run configuration script directly from server to generate
them.

If you are using another HTTP server you will need to configure it so that:

* Requests to `index.php` and `install.php` are passed to PHP interpreter
* Requests to `/static/*` are replied using corresponding files from `src/static/` directory
* Other requests are forwarded to `index.php`, preserving original path & query

Here is a sample Nginx configuration, assuming you're using php7.0-fpm module
for serving PHP-generated contents:

```
# Pass requests to `/index.php` and `/install.php` through php7.0-fpm socket
location = /index.php {
	fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
	fastcgi_pass unix:/var/run/php/php7.0-fpm.sock;
	include fastcgi-php.conf;
}

location = /install.php {
	fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
	fastcgi_pass unix:/var/run/php/php7.0-fpm.sock;
	include fastcgi-php.conf;
}

# Serve contents from `/static/*` directly
location ^~ /static/ {
	try_files $uri $uri/ =404;
}

# Redirect other requests to `/index.php`
location ^~ / {
	rewrite "^/(.*)$" / break;
	return 404;
}
```

Once your HTTP server configuration is done you can go to "Configure website"
section below.

### Configure website

After host environment is properly setup, open a web browser and browse to file
`/src/install.php` to configure website. If you used the Vagrant option, full
URL should appear in console output at the end of install process. If you used
the manual option URL depends on how you configured your server.

Complete and submit form to create an initial configuration for your website.
Once done you will be redirected to home page. In case of error you should have
a look at your HTTP error logs as well as files in `/src/storage/log`
directory.

Deploy to production server
---------------------------

yAronet can be deployed either by copying files from `/src/` directory to your
target environment, or using [Creep](https://github.com/r3c/creep) for this
purpose. Creep is the preferred deployment method as the manual deployment
described in this document doesn't cover all optimizations (namely JavaScript
and images minification) but only recommended ones (CSS pre-compilation).

### Option 1: deploy using Creep

* When deploying using Creep, pre-processing of all static files is done automatically
  * Make sure option "engine.text.display.use-less" is `false` in `/src/config.php`
* Create Creep configuration files for deployement (only the first time you're deploying)
  * Create `/src/.creep.env`, `/src/library/.creep.env` and `./static/.creep.env` files following Creep's documentation
  * Three files are needed to take custom deployment configuration into account, see `/src/library/.creep.def` and `/src/static/.creep.def` for details
  * File `/src/.creep.env` should delegate deployment to the other two using "cascades" directive
  * Execute `creep` from `/src/` directory to trigger deployment
  * Use the sample files below to see how your configuration should look like

Sample file `/src/.creep.env`:

```
{
	"default": {
		"connection": "ssh://user@host/website.com",
		"cascades": {
			"library": ["default"],
			"static": ["default"]
		}
	}
}
```

Sample file `/src/library/.creep.env`:

```
{
	"default": {
		"connection": "ssh://user@host/website.com/library"
	}
}
```

Sample file `/src/static/.creep.env`:

```
{
	"default": {
		"connection": "ssh://user@host/website.com/static"
	}
}
```

* Run `./setup/configure.sh -d` to start deployment (first deployment will take a few minutes due to all static files being pre-processed)
* Go to "Post-deployment actions" section below

### Option 2: deploy manually

When deploying yAronet to a server you can decide to enable pre-processing of
static files from `/src/static/` or leave them untouched for debugging purpose.
The later option has a significant negative impact on performance and should
never be used on a production environment.

* To use pre-compiled CSS file on deployed version:
  * From `/` directory, run `npm install` then `npm run build`
  * Make sure option "engine.text.display.use-less" is `false` in `/src/config.php`
* To use on-the-fly CSS compilation (debug) on deployed version:
  * Make sure option "engine.text.display.use-less" is `true` in `/src/config.php`
* Copy all files from `/src/` directory to remote server (including generated ones if you used CSS compilation)
* Go to "Post-deployment actions" section below

### Post-deployment actions

If you were not deploying website for the first time, e.g. after an upgrade,
browse to `/tasks/flush` page to reset internal cache and avoid incompatibility
issues with cache files generated by previous versions of the code.

This page is restricted to website administrators ; if you were not
authenticated then manually remove all files from directories under
`/src/storage/cache` (but not the directories) on server.

Schedule maintenance task
-------------------------

You will need to periodically execute maintenance (cleaning) task to keep
website working while taking care of removing obsolete data, otherwise it may
gradually slow down and eventually stop working.

Maintenance task can be executed by browsing to `/tasks/clean` page
(e.g. `http://yourhost/yourlocation/tasks/clean` in our previous example) while
being authenticated with an administrator account. This operation should be
executed at least once a day.

Since doing this operation manually is not convenient, you will probably want
to automate it. Script provided in `/setup/schedule/clean.sh` can be used for
this purpose: schedule it so it is executed from a crontab or equivalent,
passing base URL to your website as an argument, e.g.:

```
0 * * * * /path/to/clean.sh http://yourhost/yourlocation
```

Then execute the script manually once and specify "-t" command line option to
create authentication token:

```
/path/to/clean.sh -t http://yourhost/yourlocation
```

Script will prompt for your credentials and save authentication token to a
`.token` file so the schedule can work without having to hardcode user and
password anywhere.
