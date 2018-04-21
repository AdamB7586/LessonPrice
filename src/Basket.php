<?php

namespace LessonPrice;

class Basket extends \ShoppingCart\Basket{
        
    public $lesson = 0;
    private $postcode;
    private $priceband;
    
    protected function createOrder() {
        parent::createOrder();
    }
    
    protected function updateBasket() {
        parent::updateBasket();
    }
    
    protected function updateTotals() {
        parent::updateTotals();
        foreach($this->products as $productID => $quantity){
            if($this->lesson == 0 && $this->product->isProductLesson($productID)){
                $this->lesson = 1;
                $this->priceband = $this->product->priceband;
            }
        }
    }
    
    protected function getOrderProducts(){
        
    }
}
