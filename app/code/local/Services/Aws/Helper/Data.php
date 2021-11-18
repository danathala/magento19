<?php
/**
 * Magento
 *
 * @category    Services
 * @package     Services_AWS
 */
require 'aws/aws-autoloader.php';
use Aws\Sqs\SqsClient; 
use Aws\Exception\AwsException;

class Services_Aws_Helper_Data extends Mage_Core_Helper_Abstract
{
    /** AWS Basic Setttings */
    const AWS_ENABLED           = 'aws_services/settings/active';
    const AWS_ACCESS_KEY        = 'aws_services/settings/access_key_id';
    const AWS_SECRET_ACCESS_KEY = 'aws_services/settings/secret_access_key';
    const AWS_REGION            = 'aws_services/settings/region';
    const AES_VERSION           = 'aws_services/settings/version';

    /** AWS SQS URL Setttings */
    const AWS_SQS_URL_SMS       = 'aws_services/settings/sqs_url_sms';
    const AWS_SQS_URL_EMAIL     = 'aws_services/settings/sqs_url_email';
    const AWS_SQS_URL_CURL      = 'aws_services/settings/sqs_url_curl';

    protected $_client;

    public function triggerSqs($message, $params) {
        /** Check if AWS is Enabled */
        $isAwsEnabled = Mage::getStoreConfig(self::AWS_ENABLED);
        if (empty($isAwsEnabled)) {
            return false;
        }

        if (!$this->_client) { /** Check if object has been already exists */
            $client = new SqsClient([
                'region' => Mage::getStoreConfig(self::AWS_REGION),
                'version' => Mage::getStoreConfig(self::AES_VERSION),
                'credentials' => [
                    'key'    => Mage::getStoreConfig(self::AWS_ACCESS_KEY),
                    'secret' => Mage::getStoreConfig(self::AWS_SECRET_ACCESS_KEY),
                ],
            ]);
            $this->_client = $client;
        }
        
        /** Set the Message Params */
        $requestParams = array();
        foreach ($params as $key => $value) {
            $requestParams[$key] =  array("DataType" => "String", "StringValue" => $value);
        }
        $requestParams['region'] = array("DataType" => "String", "StringValue" => Mage::getStoreConfig(self::AWS_REGION));

        /** SET the SQS Url */
        $sqsUrl = self::AWS_SQS_URL_SMS;
        if (!empty($params['actionCode'])) {
            switch ($params['actionCode']) {
                case 'sms':
                    $sqsUrl = self::AWS_SQS_URL_SMS;
                    break;
                case 'email':
                    $sqsUrl = self::AWS_SQS_URL_EMAIL;
                    break;
                case 'curl':
                    $sqsUrl = self::AWS_SQS_URL_CURL;
                    break;
            }
        }

        $sqsParams = [
            'MessageBody' => $message,
            'QueueUrl' => Mage::getStoreConfig($sqsUrl),
            'MessageAttributes' => $requestParams,
        ];

        try {
            $result = $this->_client->sendMessageAsync($sqsParams);
        } catch (AwsException $e) { // Save the Error log if needed

        }
    }
}
