<?php

class Mzax_Emarketing_Model_Outbox_Sms extends Mzax_Emarketing_Model_Outbox_Email
{
    protected $_defaultTransporter = 'magedoc_sms';

    public function setDefaultTransporter($transporter)
    {
        $this->_defaultTransporter = $transporter;
    }

    /**
     * Retrieve medium
     *
     * @return Mzax_Emarketing_Model_Medium_Email
     */
    public function getMedium()
    {
        return Mage::getSingleton('mzax_emarketing/medium_sms');
    }

    /**
     * Render this email
     * An email can be rerendered as lon as it has not been sent
     *
     * @param bool $previewMode
     *
     * @return $this
     */
    public function render($previewMode = false)
    {
        if ($this->getStatus() == self::STATUS_NOT_SEND) {
            $composer = $this->getEmailComposer();
            $composer->setRecipient($this->getRecipient());
            $composer->compose($previewMode);

            $this->setSubject($composer->getSubject());
            $html = $composer->getBodyHtml();
            $this->setBodyHtml($composer->getBodyHtml());
            $text = Html2Text_Html2Text::convert($html);
            $text = trim(preg_replace('/\[[^\]]+\]/im', '', $text));
            $this->setBodyText($text);
            $this->setRenderTime($composer->getRenderTime());

            $this->_linkReferences = $composer->getLinkReferences();

            // don't actually save any coupons for mock emails
            if (!$this->getRecipient()->isMock()) {
                $this->_coupons = $composer->getCoupons();
            }
        }

        return $this;
    }

    /**
     * Prepare mail transporter
     *
     * @return Mzax_Emarketing_Model_Outbox_Transporter_Interface
     */
    protected function _prepareTransporter()
    {
        $store  = $this->getCampaign()->getStore();

        /** @var Mzax_Emarketing_Model_Outbox_Transporter $factory */
        $factory = Mage::getSingleton('mzax_emarketing/outbox_transporter');

        //$transporter = $this->_config->get('mzax_emarketing/email/transporter', $store);
        $transporter = $this->_defaultTransporter;
        $transporter = $factory->factory($transporter);
        $transporter->setup($this);

        $wrapper = new Varien_Object();
        $wrapper->setData('transporter', $transporter);

        Mage::dispatchEvent('mzax_emarketing_email_prepare_transport', array(
            'data'  => $wrapper,
            'email' => $this
        ));

        return $wrapper->getData('transporter');
    }
}
