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

use OCP\AppFramework\Http\Response;
use OCA\Twigacloudsignup\Events\PassedFormEvent;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IL10N;
use OCP\EventDispatcher\IEventDispatcher;
use OCA\Twigacloudsignup\Events\ShowFormEvent;
use OCA\Twigacloudsignup\Events\ValidateFormEvent;
use OCP\IConfig;
use OCA\Twigacloudsignup\Service\LoginFlowService;
use OCA\Twigacloudsignup\Service\RegistrationException;
use OCA\Twigacloudsignup\Service\RegistrationService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\RedirectResponse;

class RegisterController extends Controller
{
    private IInitialState $initialState;
    private IURLGenerator $urlGenerator;
    private IL10N $l10n;
    private IEventDispatcher $eventDispatcher;
    private IConfig $config;
    private LoginFlowService $loginFlowService;
    private RegistrationService $registrationService;

    public function __construct(
        string $AppName,
        IRequest $request,
        IInitialState $initialState,
        IURLGenerator $urlGenerator,
        IL10N $l10n,
        IEventDispatcher $eventDispatcher,
        IConfig $config,
        LoginFlowService $loginFlowService,
        RegistrationService $registrationService
    ) {
        parent::__construct($AppName, $request);
        $this->initialState = $initialState;
        $this->urlGenerator = $urlGenerator;
        $this->l10n = $l10n;
        $this->eventDispatcher = $eventDispatcher;
        $this->config = $config;
        $this->loginFlowService = $loginFlowService;
        $this->registrationService = $registrationService;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */

    public function showPhoneForm(string $phone = '', string $message = ''): TemplateResponse
    {
        $phoneHint = '';


        $this->eventDispatcher->dispatchTyped(new ShowFormEvent(ShowFormEvent::STEP_PHONE));

        $this->initialState->provideInitialState('phone', $phone);
        $this->initialState->provideInitialState('message', $message ?: $phoneHint);
        $this->initialState->provideInitialState('isLoginFlow', $this->loginFlowService->isUsingLoginFlow());
        $this->initialState->provideInitialState('loginFormLink', $this->urlGenerator->linkToRoute('core.login.showLoginForm'));
        return new TemplateResponse('twigacloudsignup', 'form/email', [], 'guest');
    }

    /**
     * @PublicPage
     * @AnonRateThrottle(limit=5, period=300)
     */
    public function submitPhoneForm(string $phone): Response
    {
        $validateFormEvent = new ValidateFormEvent(ValidateFormEvent::STEP_PHONE);
        $this->eventDispatcher->dispatchTyped($validateFormEvent);

        if (!empty($validateFormEvent->getErrors())) {
            return $this->showPhoneForm($phone, implode(' ', $validateFormEvent->getErrors()));
        }

        try {
            // Registration already in progress, update token and continue with verification
            $registration = $this->registrationService->getRegistrationForPhone($phone);
            $this->registrationService->generateNewToken($registration);
        } catch (DoesNotExistException $e) {
            // No registration in progress
            try {
                $phone = trim($phone);
                $this->registrationService->validatePhone($phone);
            } catch (RegistrationException $e) {
                return $this->showPhoneForm($phone, $e->getMessage());
            }

            $registration = $this->registrationService->createRegistration($phone);
        }

        try {
            $this->mailService->sendTokenByMail($registration);
        } catch (RegistrationException $e) {
            return $this->showPhoneForm($phone, $e->getMessage());
        } catch (\Exception $e) {
            return $this->showPhoneForm($phone, $this->l10n->t('A problem occurred sending sms, please contact your administrator.'));
        }

        $this->eventDispatcher->dispatchTyped(new PassedFormEvent(PassedFormEvent::STEP_PHONE, $registration->getClientSecret()));

        return new RedirectResponse(
                $this->urlGenerator->linkToRoute(
                    'twigacloudsignup.register.showVerificationForm',
                    ['secret' => $registration->getClientSecret()]
                )
            );
    }
}
