<?php

declare(strict_types=1);

/**
 * ownCloud - twigacloud_signup
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Bruno Alfred <hello@brunoalfred.me>
 * @copyright Bruno Alfred 2022
 */

namespace OCA\TwigacloudSignup\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;

class RegisterController extends Controller
{

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */

    public function showPhoneForm(): TemplateResponse
    {
        return new TemplateResponse('twigacloud_signup', 'form/phone', [], 'guest');
    }
}
