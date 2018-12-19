How to contribute to yAronet
============================

Code architecture
-----------------

Repository file structure is using the following layout:

* `/setup/`: development utilities (e.g. configuration & deployment)
* `/src/`: deployable code (that should be copied to your web server)
  * `/src/engine/`: core file shared (included) with most other components
  * `/src/entity/`: entity classes persisted in database (a.k.a MVC "models")
  * `/src/library/`: third-party PHP libraries
  * `/src/page/`: request handlers holding business logic (a.k.a MVC "controllers")
  * `/src/resource/`: resource files used by code but not served directly
  * `/src/static/`: static files served directly by HTTP server
  * `/src/storage/`: read-write data (e.g. logs, caches, etc.)
* `/test/`: functional tests

When looking at the codebase for the first time, a good idea is probably to
look at `/src/route.php` file that contains every route (URL path patterns)
accepted by the website and pointers to the request handler associated to it
(within `/src/page/` directory). What the `/src/index.php` entry point does is
basically instantiate and configure core files (from `/src/engine/`), then look
for a route maching URL from input HTTP request and invoke associated request
handler to process it.

Coding style
------------

PHP source code is following [PSR-2](https://www.php-fig.org/psr/psr-2/) style
guide.

Submitting patches
------------------

Unless your patch is trivial, please consider opening a GitHub issue to discuss
the change you'd like to submit before opening a pull request.
