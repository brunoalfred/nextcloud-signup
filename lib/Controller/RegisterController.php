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
use OCA\Twigacloudsignup\Service\PhoneService;
use OCA\Twigacloudsignup\Service\RegistrationException;
use OCA\Twigacloudsignup\Service\RegistrationService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\RedirectResponse;
use OCA\Twigacloudsignup\Db\Registration;
use OCP\AppFramework\Http\StandaloneTemplateResponse;
use OCP\AppFramework\Http\RedirectToDefaultAppResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http;
use OC\HintException;
use Exception;


class RegisterController extends Controller
{
    private IInitialState $initialState;
    private IURLGenerator $urlGenerator;
    private IL10N $l10n;
    private IEventDispatcher $eventDispatcher;
    private IConfig $config;
    private LoginFlowService $loginFlowService;
    private RegistrationService $registrationService;
    private PhoneService $phoneService;

    public function __construct(
        string $AppName,
        IRequest $request,
        IInitialState $initialState,
        IURLGenerator $urlGenerator,
        IL10N $l10n,
        IEventDispatcher $eventDispatcher,
        IConfig $config,
        LoginFlowService $loginFlowService,
        RegistrationService $registrationService,
        PhoneService $phoneService
    ) {
        parent::__construct($AppName, $request);
        $this->initialState = $initialState;
        $this->urlGenerator = $urlGenerator;
        $this->l10n = $l10n;
        $this->eventDispatcher = $eventDispatcher;
        $this->config = $config;
        $this->loginFlowService = $loginFlowService;
        $this->registrationService = $registrationService;
        $this->phoneService = $phoneService;
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
                
                if (preg_match('/^0[0-9]{9}$/', $phone)) {
                    $phone = '255' . substr($phone, 1);
                } elseif (preg_match('/^\+255[0-9]{9}$/', $phone)) {
                    $phone = substr($phone, 1);
                }

            } catch (RegistrationException $e) {
                return $this->showPhoneForm($phone, $e->getMessage());
            }

            $registration = $this->registrationService->createRegistration($phone);
        }

        try {
            $this->phoneService->sendTokenByPhone($registration);
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

    /**
     * @NoCSRFRequired
     * @PublicPage
     */
    public function showVerificationForm(string $secret, string $message = ''): TemplateResponse
    {
        try {
            $this->registrationService->getRegistrationForSecret($secret);
        } catch (DoesNotExistException $e) {
            return $this->validateSecretAndTokenErrorPage();
        }

        $this->eventDispatcher->dispatchTyped(new ShowFormEvent(ShowFormEvent::STEP_VERIFICATION, $secret));
        $this->initialState->provideInitialState('message', $message);
        $this->initialState->provideInitialState('loginFormLink', $this->urlGenerator->linkToRoute('core.login.showLoginForm'));

        return new TemplateResponse('twigacloudsignup', 'form/verification', [], 'guest');
    }

    /**
     * @PublicPage
     * @AnonRateThrottle(limit=5, period=300)
     *
     * @param string $secret
     * @param string $token
     * @return Response
     */
    public function submitVerificationForm(string $secret, string $token): Response
    {
        try {
            $registration = $this->registrationService->getRegistrationForSecret($secret);

            if ($registration->getToken() !== $token) {
                return $this->showVerificationForm(
                    $secret,
                    $this->l10n->t('The entered verification code is wrong')
                );
            }
        } catch (DoesNotExistException $e) {
            return $this->validateSecretAndTokenErrorPage();
        }

        $validateFormEvent = new ValidateFormEvent(ValidateFormEvent::STEP_VERIFICATION, $secret);
        $this->eventDispatcher->dispatchTyped($validateFormEvent);

        if (!empty($validateFormEvent->getErrors())) {
            return $this->showVerificationForm($secret, implode(' ', $validateFormEvent->getErrors()));
        }

        $this->eventDispatcher->dispatchTyped(new PassedFormEvent(PassedFormEvent::STEP_VERIFICATION, $secret));

        return new RedirectResponse(
            $this->urlGenerator->linkToRoute(
                'twigacloudsignup.register.showUserForm',
                [
                    'secret' => $secret,
                    'token' => $token,
                ]
            )
        );
    }


    /**
     * @NoCSRFRequired
     * @PublicPage
     */
    public function showUserForm(string $secret, string $token, string $loginname = '', string $fullname = '', string $email = '', string $password = '', string $message = ''): TemplateResponse
    {
        try {
            $registration = $this->validateSecretAndToken($secret, $token);
        } catch (RegistrationException $e) {
            return $this->validateSecretAndTokenErrorPage();
        }

        $additional_hint = $this->config->getAppValue('twigacloudsignup', 'additional_hint');

        $this->eventDispatcher->dispatchTyped(new ShowFormEvent(ShowFormEvent::STEP_USER, $secret));

        $this->initialState->provideInitialState('phone', $registration->getPhone());
        $this->initialState->provideInitialState('loginname', $loginname);
        $this->initialState->provideInitialState('fullname', $fullname);
        $this->initialState->provideInitialState('showFullname', $this->config->getAppValue('twigacloudsignup', 'show_fullname', 'no') === 'yes');
        $this->initialState->provideInitialState('enforceFullname', $this->config->getAppValue('twigacloudsignup', 'enforce_fullname', 'no') === 'yes');
        $this->initialState->provideInitialState('message', $message);
        $this->initialState->provideInitialState('password', $password);
        $this->initialState->provideInitialState('additionalHint', $additional_hint);
        $this->initialState->provideInitialState('loginFormLink', $this->urlGenerator->linkToRoute('core.login.showLoginForm'));

        $response = new TemplateResponse('twigacloudsignup', 'form/user', [], 'guest');

        if ($this->loginFlowService->isUsingLoginFlow(1)) {
            $csp = new ContentSecurityPolicy();
            $csp->addAllowedFormActionDomain('nc://*');
            $response->setContentSecurityPolicy($csp);
        }

        return $response;
    }

    /**
     * @PublicPage
     * @UseSession
     * @AnonRateThrottle(limit=5, period=300)
     *
     * @param string $secret
     * @param string $token
     * @param string $loginname
     * @param string $fullname
     * @param string $phone
     * @param string $password
     * @return RedirectResponse|TemplateResponse
     */
    public function submitUserForm(string $secret, string $token, string $loginname, string $fullname, string $phone, string $password): Response
    {
        try {
            $registration = $this->validateSecretAndToken($secret, $token);
        } catch (RegistrationException $e) {
            return $this->validateSecretAndTokenErrorPage();
        }

        $validateFormEvent = new ValidateFormEvent(ValidateFormEvent::STEP_USER, $secret);
        $this->eventDispatcher->dispatchTyped($validateFormEvent);

        if (!empty($validateFormEvent->getErrors())) {
            return $this->showUserForm($secret, $token, $loginname, $fullname, $phone, $password, implode(' ', $validateFormEvent->getErrors()));
        }

        try {
            $user = $this->registrationService->createAccount($registration, $loginname, $fullname, $phone, $password);
        } catch (HintException $exception) {
            return $this->showUserForm($secret, $token, $loginname, $fullname, $phone, $password, $exception->getHint());
        } catch (Exception $exception) {
            return $this->showUserForm($secret, $token, $loginname, $fullname, $phone, $password, $exception->getMessage());
        }

        // Delete registration
        $this->registrationService->deleteRegistration($registration);

        $this->eventDispatcher->dispatchTyped(new PassedFormEvent(PassedFormEvent::STEP_USER, $secret, $user));

        if ($user->isEnabled()) {
            $this->registrationService->loginUser($user->getUID(), $user->getUID(), $password);

            if ($this->loginFlowService->isUsingLoginFlow(2)) {
                $response = $this->loginFlowService->tryLoginFlowV2($user);
                if ($response instanceof Response) {
                    return $response;
                }
            }

            if ($this->loginFlowService->isUsingLoginFlow(1)) {
                $response = $this->loginFlowService->tryLoginFlowV1();
                if ($response instanceof Response && $response->getStatus() === Http::STATUS_SEE_OTHER) {
                    return $response;
                }
            }

            return new RedirectToDefaultAppResponse();
        }

        // warn the user their account needs admin validation
        return new StandaloneTemplateResponse('twigacloudsignup', 'approval-required', [], 'guest');
    }



    /**
     * @param string $secret
     * @param string $token
     * @return Registration
     * @throws RegistrationException
     */
    protected function validateSecretAndToken(string $secret, string $token): Registration
    {
        try {
            $registration = $this->registrationService->getRegistrationForSecret($secret);
        } catch (DoesNotExistException $e) {
            throw new RegistrationException('Invalid secret');
        }

        if ($registration->getToken() !== $token) {
            throw new RegistrationException('Invalid token');
        }

        return $registration;
    }

    protected function validateSecretAndTokenErrorPage(): TemplateResponse
    {
        return new TemplateResponse('core', 'error', [
            'errors' => [
                ['error' => $this->l10n->t('The verification failed.')],
            ],
        ],
            'error'
        );
    }
}
