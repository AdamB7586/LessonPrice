<?php

namespace LessonPrice;

use ShoppingCart\Mailer;
use Staff\SalesTeam;
use Soundasleep\Html2Text;

class Order extends \ShoppingCart\Order
{
        
    public $lesson = 0;
    
    protected $postcode;
    protected $priceband;
    protected $transmission = 0;
    
    protected $saleStaff;
    
    /**
     * Set the postcode
     * @param string $postcode This should be the postcode for the prices
     * @return $this
     */
    public function setPostcode($postcode)
    {
        if (is_string($postcode)) {
            $this->postcode = $postcode;
        }
        return $this;
    }
    
    /**
     * Returns the currently set postcode
     * @return string
     */
    public function getPostcode()
    {
        return $this->postcode;
    }
    
    /**
     * Set the price band
     * @param string $band This should be the price band to display the lesson price
     * @return $this
     */
    public function setPriceBand($band)
    {
        if (is_string($band)) {
            $this->priceband = $band;
            $this->product->setPrice($this->priceband);
        }
        return $this;
    }
    
    /**
     * Returns the currently set priceband
     * @return string
     */
    public function getPriceBand()
    {
        return $this->priceband;
    }

    /**
     * Sets the sales staff object
     * @param SalesTeam $sales This should be the sales team object
     * @return $this
     */
    public function setSalesStaffObject(SalesTeam $sales)
    {
        $this->saleStaff = $sales;
        return $this;
    }
    
    /**
     * Sets the transmission type
     * @param string $type This should currently be set to either 'manual' or 'auto'
     * @return $this
     */
    public function setTransmissionType($type)
    {
        if (!empty($type)) {
            $this->transmission = ($type == 'auto' ? 1 : 0);
        }
        return $this;
    }
    
    /**
     * Returns an integer to define the transmission type (1 = Automatic, 0 = Manual)
     * @return int
     */
    public function getTransmissionType()
    {
        return $this->transmission;
    }
    
    /**
     * Retrieves additional information for a given order
     * @param array $orderInfo This should be the current order information
     * @return array A full array of the order information will be returned
     */
    protected function buildOrder($orderInfo)
    {
        if (($orderInfo['postcode'] !== null && empty($this->getPostcode())) || $this->getPostcode() != $orderInfo['postcode']) {
            $this->setPostcode($orderInfo['postcode']);
        }
        if (($orderInfo['band'] !== null && empty($this->getPriceBand())) || $this->getPriceBand() != $orderInfo['band']) {
            $this->setPriceBand($orderInfo['band']);
        }
        $this->product->setPrice($this->getPriceBand());
        return parent::buildOrder($orderInfo);
    }
    
    /**
     * Adds the order information into the database
     * @param array $additional Additional fields to insert
     * @return boolean If the order is successfully inserted will return true else returns false
     */
    protected function createOrder($additional = [])
    {
        $this->updateTotals();
        return $this->db->insert($this->config->table_basket, array_merge(['customer_id' => ($this->user_id === 0 ? null : $this->user_id), 'order_no' => $this->createOrderID(), 'digital' => $this->has_download, 'lesson' => $this->lesson, 'transmission' => $this->getTransmissionType(), 'postcode' => (!empty($this->getPostcode()) ? $this->getPostcode() : null), 'band' => (!empty($this->getPriceBand()) ? $this->getPriceBand() : null), 'subtotal' => $this->totals['subtotal'], 'discount' => $this->totals['discount'], 'total_tax' => $this->totals['tax'], 'delivery' => $this->totals['delivery'], 'cart_total' => $this->totals['total'], 'sessionid' => session_id(), 'ipaddress' => $this->ip_address->getUserIP()], $additional));
    }
    
    /**
     * Returns the basket information for the current users pending order or if given a selected order number
     * @param string $orderNo This should be the order number you want to get the order information for, leave blank for current users pending order
     * @param array $additional Addition where fields
     * @return array|false If the order exists an array will be returned else will return false
     */
    public function getBasket($orderNo = '', $additional = [])
    {
        $basketInfo = parent::getBasket($orderNo, $additional);
        if (is_array($basketInfo)) {
            if (($basketInfo['postcode'] !== null && empty($this->getPostcode())) || $this->getPostcode() != $basketInfo['postcode']) {
                $this->setPostcode($basketInfo['postcode']);
            }
            if ($basketInfo['band'] !== null && (empty($this->getPriceBand()) || $this->getPriceBand() != $basketInfo['band'])) {
                $this->setPriceBand($basketInfo['band']);
            }
            $this->product->setPrice($this->getPriceBand());
            if (is_array($basketInfo['products'])) {
                foreach ($basketInfo['products'] as $i => $product) {
                    $basketInfo['products'][$i]['price'] = $this->product->getProductPrice($product['product_id']);
                }
            }
        }
        return $basketInfo;
    }
    
    /**
     * Updates the basket in the database
     * @param array $additional Additional where fields
     * @return boolean If the information is updated will return true else will return false
     */
    protected function updateBasket($additional = [])
    {
        $this->updateTotals();
        if (!empty($this->products)) {
            return $this->db->update($this->config->table_basket, ['digital' => $this->has_download, 'lesson' => $this->lesson, 'transmission' => $this->getTransmissionType(),  'postcode' => (!empty($this->getPostcode()) ? $this->getPostcode() : null), 'band' => (!empty($this->getPriceBand()) ? $this->getPriceBand() : null), 'subtotal' => $this->totals['subtotal'], 'discount' => $this->totals['discount'], 'total_tax' => $this->totals['tax'], 'delivery' => $this->totals['delivery'], 'cart_total' => $this->totals['total']], array_merge(['customer_id' => ($this->user_id === 0 ? 'IS NULL' : $this->user_id), 'sessionid' => session_id(), 'status' => 1], $additional));
        }
        return $this->emptyBasket();
    }
    
    /**
     * Update the totals for all items in the basket including delivery and tax
     */
    protected function updateTotals()
    {
        if (is_array($this->products) && !empty($this->products)) {
            foreach ($this->products as $product) {
                if ($this->lesson == 0 && $this->product->isProductLesson($product['product_id'])) {
                    $this->lesson = 1;
                    $this->product->setPrice($this->getPriceBand());
                }
            }
        }
        parent::updateTotals();
    }
    
    /**
     * Change the status of a given order
     * @param int $order_id The incremental order ID that you are updating
     * @param int $new_status The new status to give to the order
     * @param boolean $setPaidDate If set to true will update the paid date/time to current date/time
     * @param boolean $sendEmail If you want to send the email set to true else set to false for no emails being sent
     * @return boolean If the status is updated will return true else will return false
     */
    public function changeOrderStatus($order_id, $new_status, $setPaidDate = false, $sendEmail = true)
    {
        $status = parent::changeOrderStatus($order_id, $new_status, $setPaidDate, $sendEmail);
        $orderInfo = $this->getOrderByID($order_id);
        if (intval($new_status) === 2 && $sendEmail === true && $orderInfo['lesson'] != 0 && is_object($this->saleStaff)) {
            $staffInfo = $this->getStaffInfo($orderInfo);
            
            foreach ($this->lessonPurchaseEmails($orderInfo, $staffInfo) as $email) {
                Mailer::sendEmail($email['email_to'], $this->config->{"email_".strtolower($email['email'])."_subject"}, Html2Text::convert(vsprintf($this->config->{"email_".strtolower($email['email'])."_body"}, $email['variables']), ['ignore_errors' => true]), Mailer::htmlWrapper($this->config, vsprintf($this->config->{"email_".strtolower($email['email'])."_body"}, $email['variables']), $this->config->{"email_".strtolower($email['email'])."_subject"}), $email['email_from'], $email['email_from_name']);
            }
        }
        return $status;
    }
    
    /**
     * Get the staff information for the staff member to assign
     * @param array $orderInfo This should be the order information
     * @return array Returns the staff information
     */
    protected function getStaffInfo($orderInfo)
    {
        if ($orderInfo['staffid'] >= 1) {
            $staffInfo = $this->saleStaff->getStaffInfoByID($orderInfo['staffid']);
        } else {
            $staffInfo = $this->saleStaff->getActiveStaff();
            $this->db->update($this->config->table_basket, ['staffid' => $staffInfo['id']], ['order_id' => $orderInfo['order_id']], 1);
        }
        return $staffInfo;
    }


    /**
     * Gets all of the emails to send as part of a lesson purchase
     * @param array $orderInfo This is the order information
     * @param array $staffInfo This is the sales staff information of the person that has been assigned
     * @return array An array containing the information required for the emails is returned
     */
    protected function lessonPurchaseEmails($orderInfo, $staffInfo)
    {
        return [
            ['email' => 'lesson_purchase', 'email_to' => $orderInfo['user']['email'], 'email_from' => $staffInfo['email'], 'email_from_name' => $staffInfo['fullname'], 'variables' => [(empty($orderInfo['user']['title']) ? $orderInfo['user']['firstname'] : $orderInfo['user']['title']), $orderInfo['user']['lastname'], $orderInfo['order_no'], $staffInfo['fullname']]],
            ['email' => 'office_lesson', 'email_to' => $staffInfo['email'], 'email_from' => $orderInfo['user']['email'], 'email_from_name' => $orderInfo['user']['title'].' '. $orderInfo['user']['firstname'].' '.$orderInfo['user']['lastname'], 'variables' => [$orderInfo['order_no'], $this->config->site_url, $orderInfo['delivery_info']['title'], $orderInfo['delivery_info']['firstname'], $orderInfo['delivery_info']['lastname'], $orderInfo['delivery_info']['add_1'], $orderInfo['delivery_info']['add_2'], $orderInfo['delivery_info']['town'], $orderInfo['delivery_info']['county'], $orderInfo['delivery_info']['postcode'], $orderInfo['user']['phone'], $orderInfo['user']['mobile'], $orderInfo['user']['email'], $orderInfo['user']['title'], $orderInfo['user']['firstname'], $orderInfo['user']['lastname'], $orderInfo['user']['add_1'], $orderInfo['user']['add_2'], $orderInfo['user']['town'], $orderInfo['user']['county'], $orderInfo['user']['postcode'], $orderInfo['postcode'], $this->emailFormatProducts($orderInfo, false), ($orderInfo['transmission'] == 1 ? 'Automatic' : 'Manual')]],
        ];
    }
    
    /**
     * Send an order email
     * @param array $orderInfo This should be the order information
     * @param string $emailType This should be the string of the email type
     * @param array $variables This should be the variables to include in the email
     * @param boolean $toUser If the email is to be sent to the end user set to true else for the office email set to false
     * @return boolean Will return true if email is sent else will return false
     */
    public function sendOfficeEmail($orderInfo, $emailType, $variables = array())
    {
        return false;
    }
    
    /**
     * Returns the email types and variables to include
     * @param array $orderInfo This should be the order information
     * @return array Returns the email types and variables to include
     */
    protected function orderEmailTypes($orderInfo)
    {
        $emails = parent::orderEmailTypes($orderInfo);
        if ($orderInfo['lesson'] != 0) {
            unset($emails['2_office']);
        }
        return $emails;
    }
}
