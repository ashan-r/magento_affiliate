<?php
namespace Mageplaza\Affiliate\Model;
use Magento\Framework\Model\AbstractModel;
class Message extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Mageplaza\Affiliate\Model\ResourceModel\Message');
    }
}