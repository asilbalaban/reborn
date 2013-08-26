<?php

/**
 * UserModule Helper Functions
 *
 * @package Reborn\Module\User
 * @author Myanmar Links Professional Web Development
 **/

function module_action_permission_ui($module, $permission)
{
	$roles = \Module::getData($module, 'roles');

	$result = '';

	if (empty($roles)) {
		return $result;
	}
	$result .= '<div class="ckeck-group-block">';

	foreach ($roles as $key => $role) {
		$id = array('id' => str_replace('.', '-', $key));
		if (isset($permission[$key]) and ($permission[$key] == 1)) {
			$attr = array('checked' => 'checked') + $id;
		} else {
			$attr = $id;
		}
		$result .= '<label class="inline-label" for="'.$attr['id'].'">';
		$result .= \Form::checkbox("modules_actions[$key]", 1, $attr);
		$result .= $role.'</label>';
	}

	$result .= '</div>';

	return $result;
}

function user_has_access($role, $redirect_to = null)
{
	if (!\Sentry::check()) {

		if (!is_null($redirect_to)) {
			return \Redirect::to($redirect_to);
		}

		return false;
	}

	$user = \Sentry::getUser();

	if ($user->hasAccess($role)) {
		return true;
	}

	if (!is_null($redirect_to)) {
		return \Redirect::to($redirect_to);
	}

	return false;
}
