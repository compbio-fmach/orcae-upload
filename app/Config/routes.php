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
 * Here, we are connecting '/' (base path) to controller called 'Pages',
 * its action called 'display', and we pass a param to select the view file
 * to use (in this case, /app/View/Pages/home.ctp)...
 */

  // ROUTES HANDLING
  // first of all, more specific routes are evaluated (e.g. /API)

  // API ROUTES
  // login route
  Router::connect('/API/login', array('controller' => 'auth', 'action' => 'login'));
  // logout route
  Router::connect('/API/logout', array('controller' => 'auth', 'action' => 'logout'));
  // no action defined: api controller index returns 404 by default
  Router::connect('/API/*', array('controller' => 'api', 'action' => 'index'));

  // PAGES ROUTES
  // display test page
	Router::connect('/cakephp', array('controller' => 'pages', 'action' => 'display', 'home'));
  // display default page
  Router::connect('/', array('controller' => 'pages', 'action' => 'index'));
  // display error page not found
  Router::connect('/*', array('controller' => 'pages', 'action' => 'error'));

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
