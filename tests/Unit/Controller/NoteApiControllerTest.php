<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Bruno Alfred <hello@brunoalfred.me>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\TwigacloudSignup\Tests\Unit\Controller;

use OCA\TwigacloudSignup\Controller\NoteApiController;

class NoteApiControllerTest extends NoteControllerTest {
	public function setUp(): void {
		parent::setUp();
		$this->controller = new NoteApiController($this->request, $this->service, $this->userId);
	}
}
