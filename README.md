Simple blog application
=================

This is a simple blog application based on Symfony framework.

Requirements
------------

PHP 7.1 or higher.


Installation and setup
------------

1. in your htdocs (or wwwroot) directory run ```$ git clone https://github.com/cizekm/blog-symfony.git ./blog```. Your application will be located in wwwroot/blog. 
2. make directories `var/cache`, `var/logs` and `var/sessions` writable for web server
3. create empty database and user who has access to it
4. create local config file `app/config/parameters.yml` from `app/config/parameters.yml.dist` and set all required properties:
    - db settings (username, password and database name)
    - secret
5. run ```$ composer install``` in your application root directory to install all dependencies
6. run ```$ php bin/console doctrine:schema:create``` in your application root directory. This should create complete database structure in your empty database.
7. run ```$ php bin/console server:run``` and visit the website on http://localhost:<port> where the server is listening

Public part
------------

Public part is located in the www root directory, eg. `http://localhost/blog/www` or `http://localhost:8000` if you are running local web server on port 8000.

Private part
------------

Private part is located in `BASE_URL/admin`, eg. `http://localhost/blog/www/admin` or `http://localhost:8000/admin` if you are running local web server on port 8000.
There is one predefined admin account with username `admin` and password `blog17TestPwd`. To change password and/or add more admin users, please modify providers in security.yml

REST API
------------

REST API has two endpoints:

1. `/api/articles` (eg. `http://localhost:8000/api/articles`) which provides list of all visible and published articles
2. `/api/article/<id>` (eg. `http://localhost:8000/api/article/1`) (where id is id of the article provided by articles list) which provides single article detail information
