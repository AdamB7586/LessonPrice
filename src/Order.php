<?php

namespace LessonPrice;

use DBAL\Database;
use ShoppingCart\Config;
use LessonPrice\Product;
use Staff\SalesTeam;

class Order extends \ShoppingCart\Order{
        
    public $lesson = 0;
    
    private $postcode;
    private $priceband;
    
    /**
     * Constructor
     * @param Database $db This should be an instance of the Database class
     * @param Config $config This should be an instance of the Config class
     */
    public function __construct(Database $db, Config $config, $user) {
        parent::__construct($db, $config, $user);
        $this->product = new Product($db, $config);
    }
    
    /**
     * Adds the order information into the database
     * @param array $additional Additional fields to insert
     * @return boolean If the order is successfully inserted will return true else returns false
     */
    protected function createOrder($additional = []) {
        $this->updateTotals();
        return $this->db->insert($this->config->table_basket, array_merge(array('customer_id' => ($this->user_id === 0 ? NULL : $this->user_id), 'order_no' => $this->createOrderID(), 'digital' => $this->has_download, 'lesson' => $this->lesson, 'postcode' => NULL, 'band' => NULL, 'subtotal' => $this->totals['subtotal'], 'discount' => $this->totals['discount'], 'total_vat' => $this->totals['vat'], 'delivery' => $this->totals['delivery'], 'cart_total' => $this->totals['total'], 'sessionid' => session_id(), 'ipaddress' => filter_input(INPUT_ENV, 'REMOTE_ADDR', FILTER_VALIDATE_IP)), $additional));
    }
    
    /**
     * Updates the basket in the database
     * @param array $additional Additional where fields
     * @return boolean If the information is updated will return true else will return false
     */
    protected function updateBasket($additional = []) {
        $this->updateTotals();
        if(count($this->products) >= 1){
            return $this->db->update($this->config->table_basket, array('digital' => $this->has_download, 'lesson' => $this->lesson, 'postcode' => NULL, 'band' => NULL, 'subtotal' => $this->totals['subtotal'], 'discount' => $this->totals['discount'], 'total_vat' => $this->totals['vat'], 'delivery' => $this->totals['delivery'], 'cart_total' => $this->totals['total']), array_merge(array('customer_id' => ($this->user_id === 0 ? 'IS NULL' : $this->user_id), 'sessionid' => session_id(), 'status' => 1), $additional));
        }
        else{
            return $this->emptyBasket();
        }
    }
    
    /**
     * Update the totals for all items in the basket including delivery and tax
     */
    protected function updateTotals() {
        parent::updateTotals();
        foreach($this->products as $productID => $quantity){
            if($this->lesson == 0 && $this->product->isProductLesson($productID)){
                $this->lesson = 1;
                $this->priceband = $this->product->priceband;
            }
        }
    }
}
