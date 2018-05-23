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
 Router::parseExtensions();

 // Handles Genome Configurations uploads
 Router::connect('/API/genome_configs/:id/uploads/',
  array('controller' => 'ApiGenomeUploads', 'action' => 'index'),
  array('pass' => array('id'))
 );
 // Handles Genome Configurations updates
 Router::connect('/API/genome_configs/:id/updates/',
  array('controller' => 'ApiGenomeUpdates', 'action' => 'index'),
  array('pass' => array('id'))
);
 Router::connect('/API/genome_configs/*', array('controller' => 'ApiGenomeConfigs'));
 Router::connect('/API/species/*', array('controller' => 'ApiSpecies'));
 Router::connect('/API/defaults/', array('controller' => 'ApiDefaults'));
 Router::connect('/API/login', array('controller' => 'ApiAuth', 'action' => 'login'));
 Router::connect('/API/logout', array('controller' => 'ApiAuth', 'action' => 'logout'));
 Router::connect('/API/*', array('controller' => 'Api', 'action' => 'index'));

 // Render upload page
 Router::connect('/genome_configs/:id/uploads/',
  array('controller' => 'PagesGenomeConfigs', 'action' => 'uploads'),
  array('pass' => array('id'))
 );
 // Render config page
 Router::connect('/genome_configs/:id/',
  array('controller' => 'PagesGenomeConfigs', 'action' => 'config'),
  array('pass' => array('id'))
 );
 // Render config default page
 Router::connect('/genome_configs/',
  array('controller' => 'PagesGenomeConfigs', 'action' => 'index')
 );
 // Render login page
 Router::connect('/login/',
  array('controller' => 'PagesLogin', 'action' => 'index')
 );
 // Handles default routes
 Router::connect('/*', array('controller' => 'Pages'));

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
