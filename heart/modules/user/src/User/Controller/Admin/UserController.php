<?php

namespace User\Controller\Admin;

use Auth;

class UserController extends \AdminController
{
	/**
	 * Before function for UserController
	 *
	 * @return void
	 **/
	public function before() 
	{
		$this->menu->activeParent('user');
		$this->template->style('user.css', 'user');
		$this->template->header = t('user::user.title.usermod');

		if(!Auth::check()) return \Redirect::to(adminUrl('login'));
	}

	/**
	 * Display all users with pagination
	 *
	 * @return void	 
	 **/
	public function index()
	{
		$options = array(
		    'total_items'       => \User::count(),
		    'items_per_page'    => 25,
		    'param_name' => 'usrpagi'
		);

		$pagination = \Pagination::create($options);

		$users = \User::findAllWithLimit(\Pagination::limit(), \Pagination::offset());						

		$currentUser = Auth::getUser();

		$this->template->title(t('user::user.title.usermod'))
					->breadcrumb(t('user::user.title.profile'))
					->set('users', $users)
					->set('pagination', $pagination)
					->set('currentUser', $currentUser)
					->setPartial('admin/index');
	}

	/**
	 * Create a new user without activation
	 *
	 * @return void	 
	 **/
	public function create()
	{
		if (!user_has_access('user.create')) return $this->notFound();

		if (\Input::isPost()) {
			
			$rule = array(
		        'email' => 'required|email',
		        'password' => 'required|minLength:6',
		        'first_name' =>'required|minLength:2|maxLength:40',
		        'last_name' => 'required|minLength:2|maxLength:40',
		    );

			$v = new \Reborn\Form\Validation(\Input::get('*'), $rule);
			$e = new \Reborn\Form\ValidationError();

			if ($v->fail()) {
				$e = $v->getErrors();
				$this->template->set('errors', $e);
			} else {
				$email = \Input::get('email');
				$first_name = \Input::get('first_name');
				$last_name = \Input::get('last_name');
				$password = \Input::get('password');
				$confpass = \Input::get('confpass');
				$groups = (int)\Input::get('group');
				$adminPanelAccess = (int)\Input::get('admin_access', 0);

		    	if( $password !== $confpass ) {
					\Flash::error(t('user::user.password.fail'));
				} else {

					try {
						$user = Auth::getUserProvider()->create(array(
					        'email'    => $email,
					        'password' => $password,
					        'first_name' => $first_name,
					        'last_name' => $last_name,
					        'permissions' => array(),
					        'activated' => 1,
					        'permissions' => array(
					            'admin' => $adminPanelAccess,
					        )
					    ));

					    $usermeta = $this->saveMeta($user);

					    $groups = Auth::getGroupProvider()->findById($groups);
					    $user->addGroup($groups);

					    \Flash::success(t('user::user.create.success'));
					    return \Redirect::toAdmin('user');
					} catch (\Cartalyst\Sentry\Users\UserExistsException $e) {
					    \Flash::error(sprintf(t('user::user.auth.userexist'), $email));
					}
				}
			}
		}

		$groups = \UserGroup::all();

		$this->template->title(t('user::user.title.create'))
			->breadcrumb(t('user::user.title.create'))
			->set('groups', $groups)
			->setPartial('admin/create');
	}

	/**
	 * Edit a registered user
	 *
	 * @param int $uri
	 * @return void	 
	 **/
	public function edit($uri = null)
	{
		if (!user_has_access('user.edit') or $uri == null) return $this->notFound();

		$user = Auth::getUserProvider()->findById($uri);
		$usergroup = $user->getGroups();
		foreach ($usergroup as $group) {
			$group = $group->id;
		}

		if (\Input::isPost()) {
			
			$rule = array(
		        'email' => 'required|email',
		        'first_name' =>'required|minLength:2|maxLength:40',
		        'last_name' => 'required|minLength:2|maxLength:40',
		    );

			$v = new \Reborn\Form\Validation(\Input::get('*'), $rule);
			$e = new \Reborn\Form\ValidationError();

			if ($v->fail()) {
				$e = $v->getErrors();
				$this->template->set('errors', $e);
			} else {
				$email = \Input::get('email');
				$first_name = \Input::get('first_name');
				$last_name = \Input::get('last_name');
				$groups = (int)\Input::get('group');
				$adminPanelAccess = (int)\Input::get('admin_access', 0);

				try {
					$user->email = $email;
			    	$user->first_name = $first_name;
			    	$user->last_name = $last_name;
			    	$user->permissions = array(
					    'admin' => $adminPanelAccess,
					);

					$this->setPassword($user);

				    if ($user->save()) {
				    	$usermeta = $this->saveMeta($user);

					    if ((int)$group !== $groups) {
					    	$group = Auth::getGroupProvider()->findById($group);
					    	$user->removeGroup($group);
					    	$groups = Auth::getGroupProvider()->findById($groups);
					    	$user->addGroup($groups);
					    }

					    \Event::call('user_edited',array($user));
					    \Flash::success(t('user::user.edit.success'));
					    return \Redirect::toAdmin('user');

				    } else {
				    	\Flash::error(t('user::user.edit.fail'));
				    }
				} catch (\Cartalyst\Sentry\Users\UserExistsException $e) {
				   \Flash::error(sprintf(t('user::user.auth.userexist'), $email));
				}
			}			
		}

		$groups = \UserGroup::all();

		$adminAccess['dashboard'] = array_key_exists('admin', $user->getPermissions()) ?  true : false;
		$adminAccess['superUser'] = array_key_exists('superuser', $user->getPermissions()) ?  true : false;

		$this->template->title(t('user::user.title.edit'))
			->breadcrumb(t('user::user.title.edit'))
			->set('user', $user)
			->set('group', $group)
			->set('groups', $groups)
			->set('adminAccess', $adminAccess)
			->setPartial('admin/edit');
	}

	/**
	* Activate user account by using email address and activation code
	*
	* @param $email string
	* @param $activationCode string
	*/
	public function activate($id)
	{
		try {
			$user = Auth::getUserProvider()->findById(1);

			if ($user->isActivated()) {
				\Flash::error(sprintf(t('user::user.activate.admin'), $email));			
			} else {
				$activationCode = $user->getActivationCode();

			    if ($user->attemptActivation($activationCode)) {
			       	\Flash::success(t('user::user.activate.success'));
			    } else {
			       \Flash::error(t('user::user.activate.admin'));
			    }
			}
		} catch (\Cartalyst\Sentry\Users\UserNotFoundException $e) {
    		\Flash::error(t('user::user.auth.dunexist'));
		} catch (\Cartalyst\Sentry\Users\UserAlreadyActivatedException $e) {
			\Flash::error(t('user::user.auth.admin'));
		}
		return \Redirect::toAdmin('user');
	}

	/**
	 * Delete a user
	 *
	 * @return void
	 */
	public function delete($uri)
	{
		if (!user_has_access('user.delete')) return $this->notFound();

	    $user = \User::findBy('id', $uri);

	    \Event::call('user_deleted',array($user));
	    
	    $user->delete();

	    \Flash::success(t('user::user.delete.success'));
		return \Redirect::toAdmin('user');
	}

	/**
	 * Save Form Values of Create and Edit Blog
	 *
	 * @return boolean
	 **/
	protected function saveMeta($user) 
	{				
		$metadata = is_null($user->metadata) ? new \Reborn\Auth\Sentry\Eloquent\UserMetadata : $user->metadata;

		$metadata->biography = \Input::get('biography');
		$metadata->country = \Input::get('country');
		$metadata->website = \Input::get('website');
		$metadata->facebook = \Input::get('facebook');
		$metadata->twitter = \Input::get('twitter');		
		
		return $metadata->save();
	}

	/**
	 * Change Password if the user edit password
	 *
	 * @param $user
	 *
	 **/
	protected function setPassword($user)
	{
		$password = \Input::get('password');
		$confpass = \Input::get('confpass');

		if ($password) {
			$passwordRule = array(
				'password' => 'required|minLength:6',
			);
			$validatePassword = new \Reborn\Form\Validation(\Input::get('*'), $passwordRule);

			if ($validatePassword->fail()) {
				$errors = $validatePassword->getErrors();
				\Flash::error($errors);
				return \Redirect::toAdmin('user/edit/'.$user->id);
			} else {
				if ($password) {
					if($password == $confpass) {
						$user->password = $password;
					} else {
						\Flash::error(t('user::user.password.fail'));
						return \Redirect::toAdmin('user/edit/'.$user->id);
					}
				}
			}
		}
	}
	
}