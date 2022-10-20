<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Bruno Alfred <hello@brunoalfred.me>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\TwigacloudSignup\AppInfo;

use OCP\AppFramework\App;

class Application extends App {
	public const APP_ID = 'twigacloudsignup';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}
}