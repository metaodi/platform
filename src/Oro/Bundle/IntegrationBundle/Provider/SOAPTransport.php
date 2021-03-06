<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Guzzle\Http\Url;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

use Oro\Bundle\IntegrationBundle\Entity\Transport;

/**
 * @package Oro\Bundle\IntegrationBundle
 */
abstract class SOAPTransport implements TransportInterface
{
    /** @var ParameterBag */
    protected $settings;

    /** @var \SoapClient */
    protected $client;

    /**
     * {@inheritdoc}
     */
    public function init(Transport $transportEntity)
    {
        $this->settings = $transportEntity->getSettingsBag();

        $wsdlUrl = $this->settings->get('wsdl_url');
        if (!$wsdlUrl) {
            throw new InvalidConfigurationException("SOAP Transport require 'wsdl_url' option to be defined.");
        }

        $isDebug      = $this->settings->get('debug', false);
        $this->client = $this->getSoapClient($wsdlUrl, $isDebug);
    }

    /**
     * {@inheritdoc}
     */
    public function call($action, $params = [])
    {
        if (!$this->client) {
            throw new InvalidConfigurationException("SOAP Transport does not configured properly.");
        }
        $result = $this->client->__soapCall($action, $params);

        return $result;
    }

    /**
     * @return string last SOAP response
     */
    public function getLastResponse()
    {
        return $this->client->__getLastResponse();
    }

    /**
     * @return string last SOAP request
     */
    public function getLastRequest()
    {
        return $this->client->__getLastRequest();
    }

    /**
     * Clone
     */
    public function __clone()
    {
        $this->client = null;
    }

    /**
     * Does not allow to serialize
     * It may cause serialization error on SoapClient
     *
     * @return array
     */
    public function __sleep()
    {
        return [];
    }

    /**
     * @param string $wsdlUrl
     * @param bool   $isDebug
     *
     * @return \SoapClient
     */
    protected function getSoapClient($wsdlUrl, $isDebug = false)
    {
        $options = [];
        if ($isDebug) {
            $options['trace'] = true;
        }
        $urlParts = parse_url($wsdlUrl);
        if (isset($urlParts['user'], $urlParts['pass'])) {
            $options['login']    = $urlParts['user'];
            $options['password'] = $urlParts['pass'];
            unset($urlParts['user'], $urlParts['pass']);
        }
        $wsdlUrl = Url::buildUrl($urlParts);

        return new \SoapClient($wsdlUrl, $options);
    }
}
