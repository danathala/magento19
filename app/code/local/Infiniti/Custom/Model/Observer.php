<?php
/**
 * Magento
 *
 * @category    Infiniti
 * @package     Infiniti_Custom
 */
class Infiniti_Custom_Model_Observer extends Varien_Object
{
    /** Order Confirmation Communication Setttings */
    const ORDER_CONFIRMATION_CUSTOMER_SMS_ENABLED   = 'infiniti_custom/sales_communication/send_sms_customer_active';
    const ORDER_CONFIRMATION_CUSTOMER_SMS_MESSAGE   = 'infiniti_custom/sales_communication/send_sms_customer_message';
    const ORDER_CONFIRMATION_OPERATIONS_SMS_ENABLED = 'infiniti_custom/sales_communication/send_sms_operations_active';
    const ORDER_CONFIRMATION_OPERATIONS_SMS_PHONENO = 'infiniti_custom/sales_communication/send_sms_operations_phoneno';
    const ORDER_CONFIRMATION_OPERATIONS_SMS_MESSAGE = 'infiniti_custom/sales_communication/send_sms_operations_message';

    const ORDER_CONFIRMATION_OPERATIONS_EMAIL_ENABLED       = 'infiniti_custom/sales_communication/send_email_operations_active';
    const ORDER_CONFIRMATION_OPERATIONS_EMAIL_RECEPIENTS    = 'infiniti_custom/sales_communication/send_email_operations_emails';
    const ORDER_CONFIRMATION_OPERATIONS_EMAIL_SENDER        = 'infiniti_custom/sales_communication/send_email_operations_emails_sender';
    const ORDER_CONFIRMATION_OPERATIONS_EMAIL_SUBJECT       = 'infiniti_custom/sales_communication/send_email_operations_subject';
    const ORDER_CONFIRMATION_OPERATIONS_EMAIL_MESSAGE       = 'infiniti_custom/sales_communication/send_email_operations_message';
    const ORDER_CONFIRMATION_ERP_INTEGRATION_ENABLED        = 'infiniti_custom/sales_communication/send_orderinfo_erp_active';

    public function sendOrderCommunicationMessage($observer)
    {
        $order = $observer->getEvent()->getOrder();
        if (!$order) {
            return $this;
        }

        $shippingAddress = $order->getShippingAddress();

        /** Get Visible Items */
        $items = $order->getAllVisibleItems();
        $skus = array();
        foreach ($items as $item) {
            $skus[] = $item->getSku();
        }

        $operations = array('customer_sms', 'operations_sms', 'operations_email' , 'erp_order_items');
        foreach ($operations as $opt) {
            switch ($opt) {
                case 'customer_sms': /** Send SMS to Customer */
                        $isCustomerSmsEnabled = Mage::getStoreConfig(self::ORDER_CONFIRMATION_CUSTOMER_SMS_ENABLED);
                        if (!empty($isCustomerSmsEnabled)) {
                            $message = str_replace('{ORDER_ID}', $order->getIncrementId(), Mage::getStoreConfig(self::ORDER_CONFIRMATION_CUSTOMER_SMS_MESSAGE));
                            $messageParams = array('phoneNumber' => $shippingAddress->getTelephone(), 'actionCode' => 'sms');
                            Mage::helper('aws_services')->triggerSqs($message, $messageParams);
                        }
                        break;
                case 'operations_sms': /** Send SMS to Operations Team */
                        $isOperationsSmsEnabled = Mage::getStoreConfig(self::ORDER_CONFIRMATION_OPERATIONS_SMS_ENABLED);
                        if (!empty($isOperationsSmsEnabled)) {
                            $message = str_replace('{ORDER_ID}', $order->getIncrementId(), Mage::getStoreConfig(self::ORDER_CONFIRMATION_OPERATIONS_SMS_MESSAGE));

                            $recipientIds = array_map('trim', explode(',', Mage::getStoreConfig(self::ORDER_CONFIRMATION_OPERATIONS_SMS_PHONENO)));
                            if (!empty($recipientIds)) {
                                foreach ($recipientIds as $phoneno) {
                                    $messageParams = array('phoneNumber' => $phoneno, 'actionCode' => 'sms');
                                    Mage::helper('aws_services')->triggerSqs($message, $messageParams);
                                }
                            }
                        }
                        break;
                case 'operations_email': /** Send Email to Operations Team */
                        $isOperationsEmailEnabled = Mage::getStoreConfig(self::ORDER_CONFIRMATION_OPERATIONS_EMAIL_ENABLED);
                        if (!empty($isOperationsEmailEnabled)) {
                            $message = str_replace('{ORDER_ID}', $order->getIncrementId(), Mage::getStoreConfig(self::ORDER_CONFIRMATION_OPERATIONS_EMAIL_MESSAGE));

                            $recipientIds = array_map('trim', explode(',', Mage::getStoreConfig(self::ORDER_CONFIRMATION_OPERATIONS_EMAIL_RECEPIENTS)));
                            if (!empty($recipientIds)) {
                                foreach ($recipientIds as $emailId) {
                                    $messageParams = array('emailId' => $emailId, 'actionCode' => 'email',
                                                                'subject' => Mage::getStoreConfig(self::ORDER_CONFIRMATION_OPERATIONS_EMAIL_SUBJECT),
                                                                    'senderEmail' =>Mage::getStoreConfig(self::ORDER_CONFIRMATION_OPERATIONS_EMAIL_SENDER));
                                    Mage::helper('aws_services')->triggerSqs($message, $messageParams);
                                }
                            }
                        }
                        break;
                case 'erp_order_items': /** ERP to Sync Order Items */
                        $isErpSyncEnabled = Mage::getStoreConfig(self::ORDER_CONFIRMATION_ERP_INTEGRATION_ENABLED);
                        if (!empty($isErpSyncEnabled)) {
                            $messageParams = array('orderinfo' => array("order_id" => $order->getIncrementId(), "items" => $skus));
                            /*** code for webhook */
                        }
                        break;
            }
        }
    }
}
