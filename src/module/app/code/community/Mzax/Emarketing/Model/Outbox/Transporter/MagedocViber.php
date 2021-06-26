<?php

class Mzax_Emarketing_Model_Outbox_Transporter_MagedocViber
    extends Mzax_Emarketing_Model_Outbox_Transporter_MagedocSms
    implements Mzax_Emarketing_Model_Outbox_Transporter_Interface
{
    protected $_eventType = 'Viber Campaign';
    protected $_channel = Testimonial_CustomerNotification_Helper_Data::NOTIFICATION_CHANNEL_VIBER;
    protected $_bodyAttribute = 'body_html';
}
