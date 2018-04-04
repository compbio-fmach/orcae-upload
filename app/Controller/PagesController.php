<?php
/**
 * Static content controller.
 *
 * This file will render views from views/pages/
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
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AppController', 'Controller');

/**
 * Static content controller
 *
 * Override this controller by placing a copy in controllers directory of an application
 *
 * @package       app.Controller
 * @link https://book.cakephp.org/2.0/en/controllers/pages-controller.html
 */
class PagesController extends AppController {

/**
 * Displays a view
 *
 * @return CakeResponse|null
 * @throws ForbiddenException When a directory traversal attempt.
 * @throws NotFoundException When the view file could not be found
 *   or MissingViewException in debug mode.
 */
	public function display() {
		$path = func_get_args();

		$count = count($path);
		if (!$count) {
			return $this->redirect('/');
		}
		if (in_array('..', $path, true) || in_array('.', $path, true)) {
			throw new ForbiddenException();
		}
		$page = $subpage = $title_for_layout = null;

		if (!empty($path[0])) {
			$page = $path[0];
		}
		if (!empty($path[1])) {
			$subpage = $path[1];
		}
		if (!empty($path[$count - 1])) {
			$title_for_layout = Inflector::humanize($path[$count - 1]);
		}
		$this->set(compact('page', 'subpage', 'title_for_layout'));

		try {
			$this->render(implode('/', $path));
		} catch (MissingViewException $e) {
			if (Configure::read('debug')) {
				throw $e;
			}
			throw new NotFoundException();
		}
	}

	/*
	* Handles page authorization.
	* If user is not authorized, renders login page.
	* Then, id dies to stop flow execution.
	*/
	public function authRequired() {
		if(!$this->auth()) {
			// selects page to be rendered
			$this->login();
			// tells default value has been selected
			return false;
		}

		return true;
	}

	/*
	* Handles default page.
	* If user is authenticated: shows
	*/
	public function index() {
		// sets correct layout
		$this->layout = 'main';

		// case user is authenticated
		// sessions is default page in this case
		if($this->auth()) {
			$this->display('sessions');
		}
		// case user is not authenticated
		// login is default page in this case
		else {
			$this->display('login');
		}
	}

	/*
	* Displays login page
	*/
	public function login() {
		// sets correct layout
		$this->layout = 'main';

		// checks if user is authenticated
		if($this->auth()) {
			// if user is authenticated, renders login page
			$this->display('sessions');
		}
		// renders login page
		else {
			$this->display('login');
		}
	}

	public function sessions() {
		// checks if user is authenticated
		if($this->authRequired()) {
			// sets correct layout
			$this->layout = 'main';
			// renders sessions page
			$this->display('sessions');
		}
	}

	public function sessionConfig() {
		// checks if user is authenticated
		if($this->authRequired()) {
			// retrieves session id if any
			$id = isset($this->request->params['id']) ? $this->request->params['id'] : null;
			// set session id for config page
			$this->set('id', $id);
			// sets correct layout
			$this->layout = 'main';
			// renders session config page
			$this->display('session_config');
		}
	}
}
