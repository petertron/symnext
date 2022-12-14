<?php

/**
 * @package Email
 */

namespace Symnext\Email;

/**
 * The Email class is a factory class to make it possible to send emails using different gateways.
 */
abstract class Email
{
    private $gateway;

    /**
     * Returns the EmailGateway to send emails with.
     * Calling this function multiple times will return unique objects.
     *
     * @param string $gateway
     *    The name of the gateway to use. Please only supply if specific
     *  gateway functions are being used.
     *  If the gateway is not found, it will throw an EmailException
     * @throws Exception
     * @return EmailGateway
     */
    public static function create(string $gateway = null): EmailGateway
    {
        $email_gateway_manager = new EmailGatewayManager;

        if ($gateway) {
            return $email_gateway_manager->create($gateway);
        } else {
            return $email_gateway_manager->create($email_gateway_manager->getDefaultGateway());
        }
    }
}
