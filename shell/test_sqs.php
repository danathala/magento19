<?php
ini_set('display_errors',1);
ini_set('max_execution_time',0);
require_once '../app/Mage.php';
umask(0);
Mage::app();

require 'aws/aws-autoloader.php';

use Aws\Sqs\SqsClient; 
use Aws\Exception\AwsException;
// snippet-end:[sqs.php.send_message.import]

/**
 * Receive SQS Queue with Long Polling
 *
 * This code expects that you have AWS credentials set up per:
 * https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_credentials.html
 */
 //'profile' => 'default',
// snippet-start:[sqs.php.send_message.main]
$client = new SqsClient([
    
    'region' => 'us-east-2',
    'version' => '2012-11-05',
    'credentials' => [
        'key'    => 'AKIA6QVER5VQ4UYPL5VI',
        'secret' => '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',
    ],
]);

$params = [
    'MessageBody' => "Hi Test message from Namas",
    'QueueUrl' => 'https://sqs.us-east-2.amazonaws.com/997851262305/Magento_Test',
    'MessageAttributes' => [
        "phoneNumber" => [
            'DataType' => "String",
            'StringValue' => "+919538692452"
        ]
    ],
];

try {
    $result = $client->sendMessage($params);
    var_dump($result);
} catch (AwsException $e) {echo $e->getMessage();
    // output error message if fails
    error_log($e->getMessage());
}
 
echo "dsf";exit;