<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Bruno Alfred <hello@brunoalfred.me>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Twigacloudsignup\AppInfo;

use OCA\Twigacloudsignup\Capabilities;
use OCA\Twigacloudsignup\Listener\UserEnabledListener;
// use OCA\Twigacloudsignup\RegistrationLoginOption;
use OCP\User\Events\UserChangedEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap
{
	public const APP_ID = 'twigacloudsignup';

	public function __construct()
	{
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void
	{
		// $context->registerAlternativeLogin(RegistrationLoginOption::class);
		$context->registerEventListener(UserChangedEvent::class, UserEnabledListener::class);
		$context->registerCapability(Capabilities::class);
	}

	public function boot(IBootContext $context): void
	{
	}
}
