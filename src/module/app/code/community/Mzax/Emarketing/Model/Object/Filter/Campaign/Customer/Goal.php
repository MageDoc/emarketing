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
 * Class Mzax_Emarketing_Model_Object_Filter_Campaign_Goal
 *
 * @method $this setAction(string $value)
 * @method $this setOffsetValue(int $value)
 * @method $this setOffsetUnit(string $value)
 */
class Mzax_Emarketing_Model_Object_Filter_Campaign_Customer_Goal
    extends Mzax_Emarketing_Model_Object_Filter_Campaign_Goal
{

    /**
     * @param Mzax_Emarketing_Model_Object_Filter_Component $parent
     *
     * @return bool
     */
    public function acceptParent(Mzax_Emarketing_Model_Object_Filter_Component $parent)
    {
        return $parent->getQuery()->hasBinding('customer_id');
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return "Goal | Occurred after customer sent/viewed/click campaign";
    }

    /**
     * @param Mzax_Emarketing_Db_Select $query
     */
    protected function _prepareQuery(Mzax_Emarketing_Db_Select $query)
    {
        $action = $this->getDataSetDefault('action');
        $campaignId = $this->getDataSetDefault('campaign');

        $query->joinTable(
            array(
                'campaign_id' => (int)$campaignId,
                'is_mock'     => 0,
                'object_id'   => '{customer_id}',
            ),
            'recipient',
            'campaign_recepient'
        );
        $query->addBinding('campaign_recipient_id', 'campaign_recepient.recipient_id');
        $query->addBinding('campaign_recipient_sent_at', 'campaign_recepient.sent_at');
        $query->addBinding('campaign_recipient_viewed_at', 'campaign_recepient.viewed_at');

        switch ($action) {
            case self::ACTION_RECEIVED:
                $query->where($this->getTimeRangeExpr('{campaign_recipient_sent_at}', 'campaign_event_date', false));
                $query->addBinding('goal_time', 'campaign_recepient.sent_at');
                break;
            case self::ACTION_VIEWED:
                $query->where($this->getTimeRangeExpr('{campaign_recipient_viewed_at}', 'campaign_event_date', false));
                $query->addBinding('goal_time', 'campaign_recepient.viewed_at');
                break;
            case self::ACTION_CLICKED:
                switch ($action) {
                    case self::ACTION_CLICKED:
                        $eventType = Mzax_Emarketing_Model_Recipient::EVENT_TYPE_CLICK;
                        break;
                    case self::ACTION_VIEWED:
                    default:
                        $eventType = Mzax_Emarketing_Model_Recipient::EVENT_TYPE_VIEW;
                        break;
                }
                $query->joinTable(
                    array(
                        'recipient_id' => '{campaign_recipient_id}',
                        'event_type' => $eventType),
                    'recipient_event',
                    'campaign_event'
                );

                $eventTime = 'campaign_event.captured_at';
                $query->where($this->getTimeRangeExpr($eventTime, 'campaign_event_date', false));
                $query->addBinding('goal_time', $eventTime);
            default:
                /*$timeLimit = $this->getTimeExpr('offset', '{recipient_sent_at}');
                $query->where("{goal_time} < $timeLimit");*/
                break;
        }
        $query->group();
    }

    /**
     * html for settings in option form
     *
     * @return string
     */
    protected function prepareForm()
    {
        return $this->__(
            "If customer %s the campaign %s %s ago.",
            $this->getSelectElement('action')->toHtml(),
            $this->getSelectElement('campaign')->toHtml(),
            $this->getTimeRangeHtml('campaign_event_date')
        );
    }

    /**
     * @return string[]
     */
    public function getCampaignOptions()
    {
        /* @var $collection Mzax_Emarketing_Model_Resource_Campaign_Collection */
        $collection = Mage::getResourceModel('mzax_emarketing/campaign_collection');
        $collection->addArchiveFilter(false);
        $collection->addFieldToFilter('provider', array('in' => array('customers', 'orders')));

        $options = array();
        $options += $collection->toOptionHash();

        return $options;
    }
}
