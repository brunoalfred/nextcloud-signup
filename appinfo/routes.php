<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Bruno Alfred <hello@brunoalfred.me>
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\TwigacloudSignup\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
	'routes' => [
		['name' => 'register#showPhoneForm', 'url' => '/', 'verb' => 'GET'],
		['name' => 'register#submitPhoneForm', 'url' => '/', 'verb' => 'POST'],
		['name' => 'register#showVerificationForm', 'url' => '/verify/{secret}', 'verb' => 'GET'],
		['name' => 'register#submitVerificationForm', 'url' => '/verify/{secret}', 'verb' => 'POST'],
		['name' => 'register#showUserForm', 'url' => '/register/{secret}/{token}', 'verb' => 'GET'],
		['name' => 'register#submitUserForm', 'url' => '/register/{secret}/{token}', 'verb' => 'POST'],
	]
];
