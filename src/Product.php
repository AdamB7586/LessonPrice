<?php

namespace LessonPrice;

use LessonPrice\Lesson;
use DBAL\Database;
use Configuration\Config;

use ShoppingCart\Modifiers\Cost;

class Product extends \ShoppingCart\Product{
    
    protected $lesson;

    public $postcode;
    public $priceband;
    
    protected $numPrices = 1;
    protected $lessonsAvailable = [];
    protected $offers = [];

    public function __construct(Database $db, Config $config) {
        parent::__construct($db, $config);
        
    }
    
    /**
     * Sets the lesson object to be used
     * @param Lesson $lesson This should be an instance of the Lesson object
     */
    public function setLessonObject(Lesson $lesson){
        $this->lesson = $lesson;
        return $this;
    }
    
    /**
     * Sets the number of prices
     * @param int $prices This should be the number of prices based on the area covered
     * @return $this
     */
    public function setNumPrices($prices) {
        if(is_numeric($prices)) {
            $this->numPrices = intval($prices);
        }
        return $this;
    }
    
    /**
     * Returns the number of different prices
     * @return int
     */
    public function getNumPrices() {
        return $this->numPrices;
    }
    
    /**
     * Sets the price
     * @param string $price This should be the price band
     * @return $this
     */
    public function setPrice($price) {
        $this->priceband = $price;
        return $this;
    }
    
    /**
     * Returns the price
     * @return string
     */
    public function getPrice() {
        return $this->priceband;
    }
    
    /**
     * Sets the array of lessons available
     * @param array $lessons This should be an array of the lessons available
     * @return $this
     */
    public function setLessonsAvailable($lessons) {
        if(is_array($lessons)) {
            $this->lessonsAvailable = $lessons;
        }
        return $this;
    }
    
    /**
     * Returns an array of the lessons available
     * @return array
     */
    public function getLessonsAvaiable() {
        return $this->lessonsAvailable;
    }
    
    /**
     * Sets the offers available fro the current site
     * @param array $offers This should be the offers available
     * @return $this
     */
    public function setOffers($offers) {
        if(is_array($offers)) {
            $this->offers = $offers;
        }
        return $this;
    }
    
    /**
     * Returns the offers
     * @return array This should be the offers array
     */
    public function getOffers() {
        return $this->offers;
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
     * @param int $product_id This should be the product id of the item you wish to get the price for
     * @param string $band If the product you are wanting the price for is a lesson then the band needs to be set as the one you are getting the price for
     * @return double Returns the price of the item
     */
    public function getProductPrice($product_id) {
        $productInfo = $this->getProductByID($product_id);
        if($this->isProductLesson($product_id) && !$productInfo['price']) {
            if($this->getPrice() && !empty($this->getPrice())){
                $price = $this->lesson->lessonPrice($productInfo['lessonrelation'], $this->getPrice());
                $this->priceband = $this->lesson->band['band'];
                return Cost::priceUnits($price, $this->decimals);
            }
            return false;
        }
        else{
            if(is_numeric($productInfo['sale_price'])) {return Cost::priceUnits($productInfo['sale_price'], $this->decimals);}
            return Cost::priceUnits($productInfo['price'], $this->decimals);
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
            if($productInfo['lesson'] && !$productInfo['price'] && $this->getNumPrices() > 1) {
                $productInfo['lessonBox'] = true;
            }
            $productInfo['price'] = $this->getProductPrice($productInfo['product_id']);
        }
        return $productInfo;
    }
    
    /**
     * Get category information from the database
     * @param array $where This should be an array with the values that are being searched
     * @return array|false If the query returns any results will return the category information as an array else will return false 
     */
    protected function getCategoryInfo($where) {
        $categoryInfo = parent::getCategoryInfo($where);
        if($categoryInfo['lessons'] && $this->getNumPrices() > 1) {
            $categoryInfo['lessonBox'] = true;
        }
        if($categoryInfo['lessons'] && ($this->getNumPrices() == 1 || $_SESSION['postcode'])) {
            if($this->getNumPrices() == 1) {
                $categoryInfo['band'] = $this->getPrice();
            }
            else{
                $categoryInfo['band'] = $this->lesson->getPostcodeBand($_SESSION['postcode']);
            }
        }
        return $categoryInfo;
    }
    
    /**
     * Get all of the products in a given category based on the given parameters
     * @param int $category_id This should be the category ID that you are getting all the products within 
     * @param string $orderBy How the products should be ordered can be on fields such as `sales`, `price`, `views` 
     * @param int $limit The maximum number of results to show
     * @param int $start The start location for the database results (Used for pagination)
     * @param boolean $activeOnly If you only want to display active product this should be set to true else should be set to false
     * @return array|false Returns an array containing the products in a given category if any exist else will return false if none exist 
     */
    public function getProductsInCategory($category_id, $orderBy = 'sales', $limit = 20, $start = 0, $activeOnly = true) {
        $sqlExist = false;
        foreach($this->getLessonsAvaiable() as $i => $lesson) {
            $sql.= ($i >= 1 ? " OR " : "")."`products`.`lessonrelation` = '".$lesson."'";
            $sqlExist = true;
        }
        foreach($this->getSpecialProducts() as $i => $special){
            $sql.= ($sqlExist === true ? " OR " : "")."`products`.`product_id` = '".$special['product_id']."'";
        }
        $products = $this->db->query("SELECT `products`.* FROM `{$this->config->table_products}` as `products`, `{$this->config->table_product_categories}` as `category` WHERE ".($activeOnly === true ? "`products`.`active` = 1 AND " : "")."`products`.`product_id` = `category`.`product_id` AND `category`.`category_id` = ? AND (`products`.`lesson` != 1 OR (`products`.`lesson` = 1".($sql ? " AND ({$sql})" : "").")) ORDER BY `{$orderBy}`".($limit > 0 ? " LIMIT {$start}, {$limit}" : "").";", array($category_id));
        if(is_array($products)){
            foreach($products as $i => $product) {
                $products[$i] = $this->buildProduct($product['custom_url']);
            }
        }
        return $products;
    }
    
    /**
     * Returns the special products available
     * @return array
     */
    protected function getSpecialProducts() {
        $offerRelation = [];
        foreach($this->getOffers() as $offer => $true) {
            $offerRelation[]['product_id'] = $this->db->fetchColumn($this->config->table_offers, array('offer_id' => $offer), array('product_id'));
        }
        return $offerRelation;
    }
}
