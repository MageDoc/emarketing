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

/**
 * Class Mzax_Emarketing_Model_Medium_Sms
 */
class Mzax_Emarketing_Model_Medium_Sms extends Mzax_Emarketing_Model_Medium_Email
{
    const TELEPHONE_REGEXP = '/^\+?[0-9]{10,12}$/';

    protected $_linkCompression = 4;
    protected $_defaultTransporter = 'magedoc_sms';

    /**
     * Retrieve medium id
     *
     * @return string
     */
    public function getMediumId()
    {
        return 'sms';
    }

    public function getLinkPrefix()
    {
        return 'go/';
    }

    public function getUtmMedium()
    {
        return $this->getMediumId();
    }

    /**
     * Prepare recipient
     *
     * @param Mzax_Emarketing_Model_Recipient $recipient
     */
    public function prepareRecipient(Mzax_Emarketing_Model_Recipient $recipient)
    {
        $recipient->setAddress($recipient->getTelephone());

        $recipient->addUrl('unsubscribe', 'mzax_emarketing/unsubscribe');
        $recipient->addUrl('browser_view', 'mzax_emarketing/email');
    }

    /**
     * Prepare Recipient Grid
     *
     * @param Mzax_Emarketing_Block_Campaign_Edit_Tab_Recipients_Grid $grid
     * @return void
     */
    public function prepareRecipientGrid(Mzax_Emarketing_Block_Campaign_Edit_Tab_Recipients_Grid $grid)
    {
        $campaign = $grid->getCampaign();

        $previewAction = array(
            'target' => "campaign_{$campaign->getId()}_{id}",
            'url' => array(
                'base' => '*/emarketing_campaign/preview',
                'params' => array(
                    'id' => $grid->getCampaign()->getId()
                ),
            ),
            'field'  => 'entity',
            'popup'   => true,
            'caption' => $grid->__('Preview')
        );
        $sendAction = array(
            'target' => "campaign_{$campaign->getId()}_{id}",
            'url' => array(
                'base' => '*/emarketing_campaign/sendTestMail',
                'params' => array(
                    'id' => $grid->getCampaign()->getId()
                ),
            ),
            'field'  => 'recipient',
            'popup'   => true,
            'caption' => $grid->__('Send Test Email')
        );

        if ($campaign->hasVariations()) {
            $sendAction['caption'] = $previewAction['caption'] = $grid->__('[Orignal]');

            $previewAction = array(
                'caption' => $grid->__('Preview'),
                'actions' => array($previewAction)
            );
            $sendAction = array(
                'caption' => $grid->__('Send Test Email'),
                'actions' => array($sendAction)
            );

            /* @var $variation Mzax_Emarketing_Model_Campaign_Variation */
            foreach ($campaign->getVariations() as $variation) {
                $params = array(
                    'id'        => $campaign->getId(),
                    'variation' => $variation->getId()
                );

                $previewAction['actions'][] = array(
                    'target' => "campaign_{$campaign->getId()}_{$variation->getId()}_{id}",
                    'url' => array(
                        'base'   => '*/emarketing_campaign/preview',
                        'params' => $params,
                    ),
                    'field'  => 'entity',
                    'popup'   => true,
                    'caption' => $variation->getName()
                );
                $sendAction['actions'][] = array(
                    'url' => array(
                        'base'   => '*/emarketing_campaign/sendTestMail',
                        'params' => $params,
                    ),
                    'field'  => 'recipient',
                    'popup'   => true,
                    'caption' => $variation->getName()
                );
            }
        }

        $grid->addColumn('action', array(
            'header'    => $grid->__('Action'),
            'index'     => 'id',
            'getter'    => 'getId',
            'renderer'  => 'mzax_emarketing/grid_column_renderer_action',
            'type'      => 'action',
            'sortable'  => false,
            'filter'    => false,
            'no_link'   => true,
            'is_system' => true,
            'width'     => '80px',
            'actions'   => array($previewAction, $sendAction)
        ));
    }

    /**
     * Send email to recipient
     *
     * Note: The email medium is not responsible for sending out the email directly
     * it will prepare the recipient and the email and push the email to the Outbox
     * model, which then will send out the emails
     *
     * @param Mzax_Emarketing_Model_Recipient $recipient
     * @throws Exception
     */
    public function sendRecipient(Mzax_Emarketing_Model_Recipient $recipient)
    {
        $recipient->prepare();

        if (!$recipient->getAddress()) {
            Mage::throwException("No telephone set");
        }
        if (!preg_match(self::TELEPHONE_REGEXP, $recipient->getAddress())) {
            $message = Mage::helper('mzax_emarketing')
                ->__('Wrong telephone number %s', $recipient->getAddress());
            Mage::throwException($message);
        }

        /* @var $email Mzax_Emarketing_Model_Outbox_Email */
        $email = Mage::getModel('mzax_emarketing/outbox_sms');
        $email->setDefaultTransporter($this->_defaultTransporter);
        $email->setTo($recipient->getAddress());
        $email->setRecipient($recipient);
        $email->render();
        $email->setExpireAt($recipient->getExpireAt());

        if (!$recipient->isMock()) {
            $data = $recipient->getContent()->getMediumData();
            $dayFilter  = $data->getData('day_filter');
            $timeFilter = $data->getData('time_filter');

            // apply day filter
            if (is_array($dayFilter) && count($dayFilter) && count($dayFilter) < 7) {
                $email->setDayFilter($dayFilter);
            }

            // apply time filter
            if (is_array($timeFilter) && count($timeFilter) && count($timeFilter) < 24) {
                $email->setTimeFilter($timeFilter);
            }
        }

        $email->save();

        // if mock, send out email straight away
        if ($recipient->isMock() && !$recipient->getData('skip_send')) {
            $email->send();
        }
    }
}

