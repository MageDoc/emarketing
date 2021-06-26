<?php

class Mzax_Emarketing_Model_Medium_Viber extends Mzax_Emarketing_Model_Medium_Sms
{
    protected $_linkCompression = 2;
    protected $_defaultTransporter = 'magedoc_viber';

    /**
     * Retrieve medium id
     *
     * @return string
     */
    public function getMediumId()
    {
        return 'viber';
    }
}
