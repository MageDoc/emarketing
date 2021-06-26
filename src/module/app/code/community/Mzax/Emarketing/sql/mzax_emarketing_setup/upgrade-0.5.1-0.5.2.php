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



/* @var $installer Mzax_Emarketing_Model_Resource_Setup */
$installer  = $this;
$installer->startSetup();

$connection = $installer->getConnection();

$table = $installer->getTable('mzax_emarketing/recipient');

$connection->addIndex(
    $table,
    $installer->getIdxName(
        $table,
        array('campaign_id', 'is_mock', 'object_id'),
        Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX
    ),
    array('campaign_id', 'is_mock', 'object_id'),
    Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX);

$table = $installer->getTable('mzax_emarketing/outbox_email');

$connection->addIndex(
    $table,
    $installer->getIdxName(
        $table,
        array('message_id'),
        Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX
    ),
    array('message_id'),
    Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX);


$installer->endSetup();
