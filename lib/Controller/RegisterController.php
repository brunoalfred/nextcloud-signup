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

namespace OCA\Twigacloudsignup\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IL10N;

class RegisterController extends Controller
{
    private IInitialState $initialState;
    private IURLGenerator $urlGenerator;
    private IL10N $l10n;

    public function __construct(string $AppName, IRequest $request, IInitialState $initialState, IURLGenerator $urlGenerator, IL10N $l10n,)
    {
        parent::__construct($AppName, $request);
        $this->initialState = $initialState;
        $this->urlGenerator = $urlGenerator;
        $this->l10n = $l10n;
    }


    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */

    public function showPhoneForm(string $phone, string $message = ''): TemplateResponse
    {
        $phoneHint = '';

        $this->initialState->provideInitialState('phone', $phone);
        $this->initialState->provideInitialState('message', $message ?: $phoneHint);
        $this->initialState->provideInitialState('phoneIsOptional', $this->config->getAppValue($this->appName, 'phone_is_optional', 'no') === 'yes');
        $this->initialState->provideInitialState('disablePhoneVerification', $this->config->getAppValue($this->appName, 'disable_phone_verification', 'no') === 'yes');
        $this->initialState->provideInitialState('isLoginFlow', $this->loginFlowService->isUsingLoginFlow());
        $this->initialState->provideInitialState('loginFormLink', $this->urlGenerator->linkToRoute('core.login.showLoginForm'));
        return new TemplateResponse('twigacloud_signup', 'form/email', [], 'guest');
    }
}
