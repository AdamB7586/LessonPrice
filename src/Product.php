<?php

namespace LessonPrice;

use LessonPrice\Lesson;
use DBAL\Modifiers\Modifier;
use ShoppingCart\Modifiers\Cost;

class Product extends \ShoppingCart\Product
{
    
    protected $lesson;

    public $postcode;
    public $priceband;
    
    protected $numPrices = 1;
    protected $lessonsAvailable = [];
    protected $offers = [];
    protected $offerRelation = false;
    
    /**
     * Sets the lesson object to be used
     * @param Lesson $lesson This should be an instance of the Lesson object
     */
    public function setLessonObject(Lesson $lesson)
    {
        $this->lesson = $lesson;
        return $this;
    }
    
    /**
     * Sets the number of prices
     * @param int $prices This should be the number of prices based on the area covered
     * @return $this
     */
    public function setNumPrices($prices)
    {
        if (is_numeric($prices)) {
            $this->numPrices = intval($prices);
        }
        return $this;
    }
    
    /**
     * Returns the number of different prices
     * @return int
     */
    public function getNumPrices()
    {
        return $this->numPrices;
    }
    
    /**
     * Sets the price
     * @param string $price This should be the price band
     * @return $this
     */
    public function setPrice($price)
    {
        $this->priceband = $price;
        return $this;
    }
    
    /**
     * Returns the price
     * @return string
     */
    public function getPrice()
    {
        if (!empty($this->priceband)) {
            return $this->priceband;
        }
        return false;
    }
    
    /**
     * Sets the array of lessons available
     * @param array $lessons This should be an array of the lessons available
     * @return $this
     */
    public function setLessonsAvailable($lessons)
    {
        if (is_array($lessons)) {
            $this->lessonsAvailable = $lessons;
        }
        return $this;
    }
    
    /**
     * Returns an array of the lessons available
     * @return array
     */
    public function getLessonsAvaiable()
    {
        return $this->lessonsAvailable;
    }
    
    /**
     * Sets the offers available fro the current site
     * @param array $offers This should be the offers available
     * @return $this
     */
    public function setOffers($offers)
    {
        if (is_array($offers)) {
            $this->offers = $offers;
        }
        return $this;
    }
    
    /**
     * Returns the offers
     * @return array This should be the offers array
     */
    public function getOffers()
    {
        return $this->offers;
    }
    
    /**
     * Get the product information by the lesson relation
     * @param string $relation the relation string
     * @param boolean $active If you only want to get active item set to true else set as false
     * @param array $where Any additional information to search on should be added as an array
     * @return array|false If the information exists returns an array else returns false
     */
    public function getProductByLessonRelation($relation, $active = true, $where = [])
    {
        return $this->getProduct(array_merge(['lessonrelation' => $relation], $where), $active);
    }
    
    /**
     * Checks to see if the product is a download item
     * @param int $product_id the product ID of the item you are checking if the item is a download item
     * @return boolean If the item is a download item will return true else returns false
     */
    public function isProductLesson($product_id)
    {
        $productInfo = $this->getProductByID($product_id);
        if (isset($productInfo['lesson']) && intval($productInfo['lesson']) === 1) {
            return true;
        }
        return false;
    }
    
    /**
     * Gets all of the products that are lessons
     * @return array|false If any lessons exist these will be returned else false will be returned
     */
    public function getLessonProducts()
    {
        $products = $this->listProducts(true, 0, 100, ['lesson' => 1, 'lessonrelation' => ['!=' => '']]);
        foreach ($products as $i => $product) {
            $products[$i]['price'] = $this->getProductPrice($product['product_id']);
        }
        return $products;
    }
    
    /**
     * Returns an array of active products
     * @param boolean $active If you only want to retrieve active products set this to true else for all products should be true
     * @param int $start The start location for the records in the database used for pagination
     * @param int $limit The maximum number of results to return in the array
     * @param array $where Addition where fields
     * @return array|false If any products exists they will be returned as an array else will return false
     */
    public function listProducts($active = true, $start = 0, $limit = 50, $where = [])
    {
        $products = parent::listProducts($active, $start, $limit, $where);
        foreach ($products as $i => $product) {
            if ($product['lesson'] == 1 && !$product['price']) {
                $products[$i]['price'] = $this->getProductPrice($product['product_id']);
            }
        }
        return $products;
    }
    
    /**
     * Gets the price of any product based on the given product ID
     * @param int $product_id This should be the product id of the item you wish to get the price for
     * @param string $band If the product you are wanting the price for is a lesson then the band needs to be set as the one you are getting the price for
     * @return double Returns the price of the item
     */
    public function getProductPrice($product_id)
    {
        $productInfo = $this->getProductByID($product_id);
        if ($this->isProductLesson($product_id) && !$productInfo['price']) {
            if ($this->getPrice()) {
                $price = $this->lesson->lessonPrice($productInfo['lessonrelation'], $this->getPrice());
                $this->priceband = $this->lesson->band['band'];
                return Cost::priceUnits((isset($price['sale_price']) ? $price['sale_price'] : $price['price']), $this->decimals);
            }
            return false;
        }
        return Cost::priceUnits((isset($productInfo['sale_price']) && is_numeric($productInfo['sale_price']) ? $productInfo['sale_price'] : $productInfo['price']), $this->decimals);
    }
    
    /**
     * Edit a product in the database
     * @param type $product_id This should be the unique product ID you are updating
     * @param array|false $image This should be the image to be associated with the product
     * @param array $additionalInfo Any additional information you are updating should be set as an array here
     * @return boolean If the information has successfully been updated will return true else returns false
     */
    public function editProduct($product_id, $image = false, $additionalInfo = [])
    {
        $additionalInfo['lesson'] = Modifier::setZeroOnEmpty($additionalInfo['lesson']);
        return parent::editProduct($product_id, $image, $additionalInfo);
    }
    
    /**
     * Build all of the product information needed to display the product on its product page
     * @param string $url This should be the unique product URL
     * @param array $where Addition where fields
     * @return array|boolean If the product information has been retrieved from the URL will return an array of information else will return false
     */
    public function buildProduct($url, $where = [])
    {
        $productInfo = parent::buildProduct($url, $where);
        if (!empty($productInfo)) {
            if ($productInfo['lesson'] && !$productInfo['price'] && $this->getNumPrices() > 1) {
                $productInfo['lessonBox'] = true;
            }
            if ($productInfo['lesson'] && !$productInfo['price'] && $this->getPrice()) {
                $productInfo = array_merge($productInfo, $this->lesson->lessonPrice($productInfo['lessonrelation'], $this->getPrice()));
                $productInfo['priceband'] = $this->getPrice();
            }
        }
        return $productInfo;
    }
    
    /**
     * Get category information from the database
     * @param array $where This should be an array with the values that are being searched
     * @return array|false If the query returns any results will return the category information as an array else will return false
     */
    protected function getCategoryInfo($where)
    {
        $categoryInfo = parent::getCategoryInfo($where);
        if (isset($categoryInfo['lessons']) && intval($categoryInfo['lessons']) === 1 && $this->getNumPrices() > 1) {
            $categoryInfo['lessonBox'] = true;
        }
        if (isset($categoryInfo['lessons']) && intval($categoryInfo['lessons']) === 1 && $this->getPrice()) {
            $categoryInfo['band'] = $this->getPrice();
        }
        return $categoryInfo;
    }
    
    /**
     * Get all of the products in a given category based on the given parameters
     * @param int $category_id This should be the category ID that you are getting all the products within
     * @param string $orderBy How the products should be ordered can be on fields such as `sales`, `price`, `views`
     * @param string $orderDir The direction it should be ordered ASC OR DESC
     * @param int $limit The maximum number of results to show
     * @param int $start The start location for the database results (Used for pagination)
     * @param boolean $activeOnly If you only want to display active product this should be set to true else should be set to false
     * @return array|false Returns an array containing the products in a given category if any exist else will return false if none exist
     */
    public function getProductsInCategory($category_id, $orderBy = 'sales', $orderDir = 'DESC', $limit = 20, $start = 0, $activeOnly = true)
    {
        $sqlExist = false;
        $sql = '';
        foreach ($this->getLessonsAvaiable() as $i => $lesson) {
            $sql.= ($i >= 1 ? " OR " : "")."`products`.`lessonrelation` = '".$lesson."'";
            $sqlExist = true;
        }
        $specialProducts = $this->getSpecialProducts();
        if (!empty($specialProducts) && is_array($specialProducts)) {
            foreach ($specialProducts as $i => $special) {
                $sql.= ($sqlExist === true ? " OR " : "")."`products`.`product_id` = '".$special['product_id']."'";
            }
        }
        $products = $this->db->query("SELECT `products`.* FROM `{$this->config->table_products}` as `products`, `{$this->config->table_product_categories}` as `category` WHERE ".($activeOnly === true ? "`products`.`active` = 1 AND " : "")."`products`.`product_id` = `category`.`product_id` AND `category`.`category_id` = ? AND (`products`.`lesson` != 1 OR (`products`.`lesson` = 1".(!empty($sql) ? " AND ({$sql})" : "").")) ORDER BY `{$orderBy}` {$orderDir}".($limit > 0 ? " LIMIT {$start}, {$limit}" : "").";", [$category_id]);
        if (is_array($products)) {
            foreach ($products as $i => $product) {
                $products[$i] = $this->buildProduct($product['custom_url']);
            }
        }
        return $products;
    }
    
    /**
     * Returns the special products available
     * @return array
     */
    protected function getSpecialProducts()
    {
        if (is_array($this->offerRelation)) {
            return $this->offerRelation;
        }
        $this->offerRelation = [];
        foreach ($this->getOffers() as $offer => $true) {
            if (is_numeric($offer)) {
                $this->offerRelation[]['product_id'] = $this->db->fetchColumn($this->config->table_offers, ['offer_id' => $offer], ['product_id']);
            }
        }
        return $this->offerRelation;
    }
    
    /**
     * Returns the products that should be featured on the homepage
     * @param string $orderBy How the products should be ordered can be on fields such as `sales`, `price`, `views`
     * @param string $orderDir The direction it should be ordered ASC OR DESC
     * @param int $limit The maximum number of results to show
     * @param int $start The start location for the database results (Used for pagination)
     * @param array $additionalInfo Any additional fields to add to the query
     * @return array|false Returns an array containing the products in a given category if any exist else will return false if none exist
     */
    public function getHomepageProducts($orderBy = 'sales', $orderDir = 'DESC', $limit = 20, $start = 0, array $additionalInfo = [])
    {
        $homepage = [];
        $homepage['products'] = parent::getHomepageProducts($orderBy, $orderDir, $limit, $start, $additionalInfo);
        if ($this->getPrice() && !empty($this->getPrice())) {
            $homepage['band'] = $this->getPrice();
        }
        return $homepage;
    }
}
