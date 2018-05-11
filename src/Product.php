<?php

namespace LessonPrice;

use LessonPrice\Lesson;
use DBAL\Database;
use ShoppingCart\Config;

class Product extends \ShoppingCart\Product{
    
    protected $lesson;
    
    public $postcode;
    public $priceband;
    
    public function __construct(Database $db, Config $config) {
        parent::__construct($db, $config);
        $this->lesson = new Lesson($db);
    }
    
    /**
     * Checks to see if the product is a download item
     * @param int $product_id the product ID of the item you are checking if the item is a download item
     * @return boolean If the item is a download item will return true else returns false
     */
    public function isProductLesson($product_id) {
        $productInfo = $this->getProductByID($product_id);
        if($productInfo['lesson']) {return true;}
        return false;
    }
    
    /**
     * Gets the price of any product based on the given product ID
     * @param int $productID This should be the product id of the item you wish to get the price for
     * @param string $band If the product you are wanting the price for is a lesson then the band needs to be set as the one you are getting the price for
     * @return double Returns the price of the item
     */
    public function getProductPrice($productID, $band = false){
        $this->getProductByID($productID);
        if($this->isProductLesson($productID) && !$this->productInfo['price']){
            $price = $this->lesson->lessonPrice($this->productInfo['lessonrelation'], $band);
            $this->priceband = $this->lesson->band['band'];
            return $price;
        }
        else{
            if(!empty($this->productInfo['newprice'])){return $this->productInfo['newprice'];}
            else{return $this->productInfo['price'];}
        }
    }
    
    /**
     * Build all of the product information needed to display the product on its product page
     * @param string $url This should be the unique product URL
     * @param array $where Addition where fields
     * @return array|boolean If the product information has been retrieved from the URL will return an array of information else will return false
     */
    public function buildProduct($url, $where = []) {
        $productInfo = parent::buildProduct($url, $where);
        if(!empty($productInfo)) {
            if($productInfo['lesson'] && !$productInfo['price']){
                if($this->instructor->instructor['numprices'] == 1){
                    $productInfo['priceband'] = key($this->instructor->instructor['prices']);
                }
                else{
                    $productInfo['lessonBox'] = true;
                    if($_SESSION['postcode']){
                        $productInfo['priceband'] = $this->lesson->getPostcodeBand($_SESSION['postcode']);
                    }
                }
                $productInfo['price'] = $this->getProductPrice($this->productID, $productInfo['priceband']);
            }
        }
        return $productInfo;
    }
}
