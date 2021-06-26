<?php
/**
 * Mzax Emarketing (www.mzax.de)
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this Extension in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    Mzax
 * @package     Mzax_Emarketing
 * @author      Jacob Siefer (jacob@mzax.de)
 * @copyright   Copyright (c) 2015 Jacob Siefer
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

use Mage_Core_Model_Email_Template as CoreTemplate;


/**
 * PHP Sendmail transporter
 */
class Mzax_Emarketing_Model_Outbox_Transporter_MagedocSms
    extends Mzax_Mail_Transport_Mock
    implements Mzax_Emarketing_Model_Outbox_Transporter_Interface
{
    protected $_eventType = 'SMS Campaign';
    protected $_channel = Testimonial_CustomerNotification_Helper_Data::NOTIFICATION_CHANNEL_SMS;
    protected $_bodyAttribute = 'body_text';

    /**
     * @param Mzax_Emarketing_Model_Outbox_Email $email
     *
     * @return void
     */
    public function setup(Mzax_Emarketing_Model_Outbox_Email $email)
    {

    }

    public function _sendMail()
    {
        $mail = $this->_mail;
        $outboxEmail = $mail->getOutboxEmail();
        $message = Mage::getModel('customernotification/message');
        $recipients = $mail->getRecipients();
        $campaign = $outboxEmail->getCampaign();
        $store = $campaign->getStore();
        $_recipient = $outboxEmail->getRecipient();
        $provider = $campaign->getRecipientProvider();
        $provider->prepareRecipient($_recipient);
        foreach ($recipients as $telephone) {
            $message->setData(array(
                'channel'   =>  $this->_channel,
                'event'     =>  $this->_eventType,
                'recipient' =>  $telephone,
                'text'      =>  $outboxEmail->getData($this->_bodyAttribute),
                'store_id'  =>  $store->getId(),
                'customer_id'   =>  $_recipient->getCustomer()
                                    ? $_recipient->getCustomer()->getId()
                                    : $_recipient->getCustomerId(),
                'customer_name' =>  $_recipient->getCustomer()
                                    ? $_recipient->getCustomer()->getName()
                                    : $_recipient->getName(),
                'entity_type'   =>  'mzax_emarketing/recipient',
                'entity_id'     =>  $_recipient->getId(),
                'attempt_count' =>  0,
                'success_count' =>  0,
            ));
            $message->send();
            $message->save();
            $outboxEmail->setMessageId($message->getId());
        }
    }
}
