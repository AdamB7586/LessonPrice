<?php

namespace LessonPrice;

class Product extends \ShoppingCart\Product{
    
    public $lesson = 0;
    public $postcode;
    public $priceband;
    
    public function isLesson(){
        
    }
}
