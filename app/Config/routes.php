<?php
/**
 * Routes configuration
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       app.Config
 * @since         CakePHP(tm) v 0.2.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Routes
 * Here there are the main routes of orcae-upload app
 * There are two main types of routes: Pages and APIs
 * The former are controllers which renders the views of orcae-upload
 * The latter are controllers which implements APIs of orcae upload
 */
 // API routes for authentication (login and logout APIs)
 Router::connect('/API/login', array('controller' => 'ApiAuth', 'action' => 'login'));
 Router::connect('/API/logout', array('controller' => 'ApiAuth', 'action' => 'logout'));
 // API routes for genome configuration sessions handling
 Router::connect('/API/genomecs', array('controller' => 'ApiGenomeCS'));
 Router::connect('/API/genomecs/:id', array('controller' => 'ApiGenomeCS'));
 // API route for upload handling
 Router::connect(
   '/API/genomecs/:id/uploads',
   array('controller' => 'ApiGenomeUploads'),
   array('pass' => array('id'))
 );
 // API route which returns default files value to authenticated users
 Router::connect('/API/defaults/:file', array('controller' => 'ApiDefaults'));
 // API route which returns species stored into orcae_bogas.taxid table
 Router::connect('/API/species', array('controller' => 'ApiSpecies', '[method]' => 'GET', 'action' => 'read'));
 // API default route returns 404 API not found
 Router::connect('/API/*', array('controller' => 'Api', 'action' => 'index'));

 // If route is /sessions, uses SessionsController in order to render the correct page
 Router::connect('/genomecs', array('controller' => 'GenomeCS', 'action' => 'index'));
 Router::connect('/genomecs/:id', array('controller' => 'GenomeCS', 'action' => 'config'));
 Router::connect('/genomecs/:id/uploads',
  array('controller' => 'GenomeCS', 'action' => 'uploads'),
  array('pass' => array('id'))
);
 // If route is /login, uses LoginController to render the login page
 Router::connect('/login', array('controller' => 'Login'));
 // Redirects to default page, which is /sessions, if any route has been matched
 Router::redirect('/*', array('controller' => 'GenomeCS', 'action' => 'index'));

/**
 * Load all plugin routes. See the CakePlugin documentation on
 * how to customize the loading of plugin routes.
 */
	CakePlugin::routes();

/**
 * Load the CakePHP default routes. Only remove this if you do not want to use
 * the built-in default routes.
 */
	require CAKE . 'Config' . DS . 'routes.php';
