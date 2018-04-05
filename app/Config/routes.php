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

  // ROUTES HANDLING
  // first of all, more specific routes are evaluated (e.g. /API)

  // API ROUTES
  // login route
  Router::connect('/API/login', array('controller' => 'AuthApi', 'action' => 'login'));
  // logout route
  Router::connect('/API/logout', array('controller' => 'AuthApi', 'action' => 'logout'));
  // insert new session configuration
  Router::connect('/API/sessions/config', array('controller' => 'SessionConfigApi', 'action' => 'edit', '[method]' => 'POST'));
  // retireves all sessions configurations
  Router::connect('/API/sessions/config', array('controller' => 'SessionConfigApi', 'action' => 'view', '[method]' => 'GET'));
  // edit already existent session configuration
  Router::connect('/API/sessions/:id/config', array('controller' => 'SessionConfigApi', 'action' => 'edit', '[method]' => 'POST'));
  // retrieves already existent session configuration
  Router::connect('/API/sessions/:id/config', array('controller' => 'SessionConfigApi', 'action' => 'view', '[method]' => 'GET'));
  // returns default values
  Router::connect('/API/defaults', array('controller' => 'DefaultsApi', 'action' => 'index'));
  // no action defined: api controller index returns 404 by default
  Router::connect('/API/*', array('controller' => 'Api', 'action' => 'index'));

  // PAGES ROUTES
  // displays test page
	Router::connect('/cakephp', array('controller' => 'Pages', 'action' => 'display', 'home'));
  // renders login page
  Router::connect('/login', array('controller' => 'Pages', 'action' => 'login'));
  // renders session bound to passed id
  Router::connect('/sessions/:id/config', array('controller' => 'Pages', 'action' => 'sessionConfig'));
  // renders empty session form (used for new session configuration creation)
  Router::connect('/sessions/config', array('controller' => 'Pages', 'action' => 'sessionConfig'));
  // renders sessions overview
  Router::connect('/sessions', array('controller' => 'Pages', 'action' => 'sessions'));
  // displays default page
  Router::connect('/*', array('controller' => 'Pages', 'action' => 'index'));

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
