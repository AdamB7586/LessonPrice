<?php

namespace LessonPrice;

class Order extends \ShoppingCart\Order{
        
    public $lesson = 0;
    
    private $postcode;
    private $priceband;
    
    /**
     * Set the postcode
     * @param string $postcode This should be the postcode for the prices
     * @return $this
     */
    public function setPostcode($postcode) {
        if(is_string($postcode)) {
            $this->postcode = $postcode;
        }
        return $this;
    }
    
    /**
     * Set the price band
     * @param string $band This should be the price band to display the lesson price
     * @return $this
     */
    public function setPriceBand($band) {
        if(is_string($band)) {
            $this->priceband = $band;
            $this->product->setPrice($this->priceband);
        }
        return $this;
    }
    
    /**
     * Retrieves additional information for a given order
     * @param array $orderInfo This should be the current order information
     * @return array A full array of the order information will be returned 
     */
    protected function buildOrder($orderInfo) {
        if($orderInfo['postcode'] !== NULL && empty($this->postcode)){$this->postcode = $orderInfo['postcode'];}
        if($orderInfo['band'] !== NULL && empty($this->priceband)){$this->priceband = $orderInfo['band'];}
        $this->product->setPrice($this->priceband);
        return parent::buildOrder($orderInfo);
    }
    
    /**
     * Adds the order information into the database
     * @param array $additional Additional fields to insert
     * @return boolean If the order is successfully inserted will return true else returns false
     */
    protected function createOrder($additional = []) {
        $this->updateTotals();
        return $this->db->insert($this->config->table_basket, array_merge(array('customer_id' => ($this->user_id === 0 ? NULL : $this->user_id), 'order_no' => $this->createOrderID(), 'digital' => $this->has_download, 'lesson' => $this->lesson, 'postcode' => (!empty($this->postcode) ? $this->postcode : NULL), 'band' => (!empty($this->priceband) ? $this->priceband : NULL), 'subtotal' => $this->totals['subtotal'], 'discount' => $this->totals['discount'], 'total_vat' => $this->totals['vat'], 'delivery' => $this->totals['delivery'], 'cart_total' => $this->totals['total'], 'sessionid' => session_id(), 'ipaddress' => filter_input(INPUT_ENV, 'REMOTE_ADDR', FILTER_VALIDATE_IP)), $additional));
    }
    
    /**
     * Returns the basket information for the current users pending order or if given a selected order number
     * @param string $orderNo This should be the order number you want to get the order information for, leave blank for current users pending order
     * @param array $additional Addition where fields
     * @return array|false If the order exists an array will be returned else will return false
     */
    public function getBasket($orderNo = '', $additional = []){
        $basketInfo = parent::getBasket($orderNo, $additional);
        if($basketInfo['postcode'] !== NULL && empty($this->postcode)){$this->postcode = $basketInfo['postcode'];}
        if($basketInfo['band'] !== NULL && empty($this->priceband)){$this->priceband = $basketInfo['band'];}
        $this->product->setPrice($this->priceband);
        foreach($basketInfo['products'] as $i => $product){
            $basketInfo['products'][$i]['price'] = $this->product->getProductPrice($product['product_id']);
        }
        return $basketInfo;
    }
    
    /**
     * Updates the basket in the database
     * @param array $additional Additional where fields
     * @return boolean If the information is updated will return true else will return false
     */
    protected function updateBasket($additional = []) {
        $this->updateTotals();
        if(count($this->products) >= 1){
            return $this->db->update($this->config->table_basket, array('digital' => $this->has_download, 'lesson' => $this->lesson, 'postcode' => (!empty($this->postcode) ? $this->postcode : NULL), 'band' => (!empty($this->priceband) ? $this->priceband : NULL), 'subtotal' => $this->totals['subtotal'], 'discount' => $this->totals['discount'], 'total_vat' => $this->totals['vat'], 'delivery' => $this->totals['delivery'], 'cart_total' => $this->totals['total']), array_merge(array('customer_id' => ($this->user_id === 0 ? 'IS NULL' : $this->user_id), 'sessionid' => session_id(), 'status' => 1), $additional));
        }
        else{
            return $this->emptyBasket();
        }
    }
    
    /**
     * Update the totals for all items in the basket including delivery and tax
     */
    protected function updateTotals() {
        if(is_array($this->products)) {
            foreach($this->products as $product) {
                if($this->lesson == 0 && $this->product->isProductLesson($product['product_id'])) {
                    $this->lesson = 1;
                    $this->product->setPrice($this->priceband);
                }
            }
        }
        parent::updateTotals();
        
    }
}
