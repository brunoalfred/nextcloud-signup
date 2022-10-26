<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Bruno Alfred <hello@brunoalfred.me>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Twigacloudsignup\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class SmsGatewayService
{
    /** @var Client */
    private  $client;
    /** @var string */
    protected $appName;
    /** @var IConfig */
    private $config;
    /** @var LoggerInterface */
    private  $logger;
    
    public function __construct(
        IConfig $config,
        LoggerInterface $logger
    ) {
        $this->client =
        new \GuzzleHttp\Client([
            'base_uri' => 'http://demo.ubunifu.mikutano.co.tz',
        ]);
        $this->appName = 'twigacloudsignup';
        $this->config = $config;
        $this->logger = $logger;
    }
    
    public function sendSms(string $phone, string $message): Response
    {

        $this->logger->info($this->config->getAppValue($this->appName, 'sms_gateway_username') . ' ' . $this->config->getAppValue($this->appName, 'sms_gateway_password'));

       $response =  $this->client->request('POST', '/send_message_api', [
            'auth' => [$this->config->getAppValue($this->appName, 'sms_gateway_username', ''), $this->config->getAppValue($this->appName, 'sms_gateway_password', '')],
            'form_params' => [
                'receipient' => $phone,
                'message' => $message,
            ]
        ]);

        $this->logger->info('SMS sent to ' . $phone . ' with message ' . $message);

        return $response;

    }

    public function log($message)
    {
        $this->logger->error($message, ['extra_context' => 'my extra context']);
    }
}


