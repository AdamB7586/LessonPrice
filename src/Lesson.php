<?php
namespace LessonPrice;

use DBAL\Database;
use Configuration\Config;

class Lesson {
    protected $db;
    protected $config;
    
    public $postcode;
    public $band;
    
    /**
     * Constructor
     */
    public function __construct(Database $db, Config $config){
        $this->db = $db;
        $this->config = $config;
    }
    
    /**
     * Lists of the the postcodes in the database
     * @param string $search If you want to search for a particular postcode enter the search value here
     * @return array|boolean If postcodes exist will return array else will return false
     */
    public function listPostcodes($search = false){
        if($search){$where = array('PostCode' => array('LIKE', $search.'%'));}
        return $this->db->selectAll($this->config->table_postcodes, $where);
    }
    
    /**
     * List all for the price-bands by price ASC
     * @return array|boolean Will return a list of all of the price bands in the database
     */
    public function listBands(){
        return $this->db->selectAll($this->config->table_priceband, '', '*', array('onehour' => 'ASC'));
    }
    
    /**
     * Update the information for a given postcode
     * @param string $postcode This should be the postcode of the information you are updating
     * @param string $priceband The new price band you want to assign the postcode to 
     * @return boolean If the information is updated will return true else returns false
     */
    public function updateBand($postcode, $priceband){
        return $this->db->update($this->config->table_postcodes, array('Price' => $priceband), array('PostCode' => $postcode), 1);
    }
    
    /**
     * Checks to see if the postcode entered exists in the database and returns the price band
     * @param string $postcode This should be the postcode you wish to check the price band for
     * @return string|boolean If the postcode exists will return the price band else will return false
     */
    public function getPostcodeBand($postcode){
        $getPriceband = $this->db->select($this->config->table_postcodes, array('PostCode' => strtoupper(smallPostcode($postcode))), array('Price'));
        if($getPriceband['Price']){
            return $getPriceband['Price'];
        }
        return false;
    }
    
    /**
     * Get the price band info for a given band
     * @param string $band This should be the band you wish to get the price band for
     * @return array|boolean If the band exists it will return the price band info else returns false
     */
    public function getPriceBandInfo($band){
        $this->band = $this->db->select($this->config->table_priceband, array('band' => strtoupper($band)));
        if(isset($this->band)){
            $this->band['twohours'] = $this->band['twohour'];
            return $this->band;
        }
        return false;
    }
    
    /**
     * Get all of the price band information for a given postcode
     * @param string $postcode This should be the postcode that you wish to retrieve the prices for
     * @return array|boolean Returns and array if the postcode price band exists else returns false
     */
    public function selectPriceband($postcode){
        $this->postcode = smallPostcode($postcode);
        $band = $this->db->select($this->config->table_postcodes, array('PostCode' => $this->postcode), array('Price'));
        return $this->getPriceBandInfo($band['Price']);
    }
    
    /**
     * Returns the price of the given lesson for the postcode given
     * @param array $relation This should be all of the lesson price information in order to work out the lesson price
     * @param string $band This should be the price band that you wish to retrieve prices for
     * @param array|false If you already have the information to compile the price assign this as an array else set to false
     * @return double|boolean Returns either the price if the price is not 0.00 else returns false
     */
    public function lessonPrice($relation, $band, $lessoninfo = false){
        if(!isset($this->band)){$this->getPriceBandInfo($band);}
        if(!is_array($lessoninfo)){$lessoninfo = $this->db->select($this->config->table_priceband_info, array('course' => $relation));}
        
        $fee = 0;
        if($lessoninfo['test']){$fee = $fee + $this->band['testfee'];}
        if($lessoninfo['theory']){$fee = $fee + $this->band['theorytest'];}
        if($lessoninfo['products']){$fee = $fee + $this->band['materials'];}
        if($lessoninfo['dsdvdwb']){$fee = $fee + $this->band['dsdvdwb'];}
        if($lessoninfo['dtc']){$fee = $fee + $this->band['dtc'];}
        $price = [];
        if($relation != 'onehour'){
            $price['price'] = (($this->band['onehour'] * $lessoninfo['hours']) + $fee);
            $price['sale_price'] = ((($this->band['onehour'] - $this->band[$relation]) * $lessoninfo['hours']) + $fee);
        }
        else{
            $price['price'] = $this->band['onehour'];
        }
        
        return $price;
    }
    
    /**
     * List all of the prices in a certain area
     * @param string $area The area you want to list the prices for
     * @return array Returns an array of prices in the given area
     */
    public function listPricesByPostcodeArea($area) {
        $sql = '';
        $postcodes = [];
        for($i = 0; $i <= 9; $i++) {
            $sql.= ($i > 0 ? " OR " : "")."`PostCode` LIKE ?";
            $postcodes[] = $area.$i.'%';
        }
        return $this->db->query("SELECT DISTINCT `{$this->config->table_priceband}`.* FROM `{$this->config->table_postcodes}` INNER JOIN `{$this->config->table_priceband}` ON `{$this->config->table_postcodes}`.`Price` = `{$this->config->table_priceband}`.`band` WHERE {$sql} ORDER BY `{$this->config->table_priceband}`.`onehour` ASC;", $postcodes);
    }
    
    /**
     * Return a list of postcode in a postcode area
     * @param string $area The area you want to list the postcode of
     * @return array|false Returns an array of postcodes in the set area if any exist else will return false
     */
    public function listPostcodesInArea($area) {
        $sql = '';
        $postcodes = [];
        for($i = 0; $i <= 9; $i++) {
            $sql.= ($i > 0 ? " OR " : "")."`PostCode` LIKE ?";
            $postcodes[] = $area.$i.'%';
        }
        return $this->db->query("SELECT `PostCode` FROM `{$this->config->table_postcodes}` WHERE {$sql} ORDER BY `postcode` ASC;", $postcodes);
    }
    
    /**
     * Return a list of all of the prices for an array of given postcodes
     * @param array $postcodes Should be an array of postcode that you wish to get the prices for
     * @return array|false If any prices exist they will be returned as an array else will return false
     */
    public function listPricesbyPostcodes($postcodes){
        if(is_array($postcodes)){
            $sql = [];
            foreach(array_filter($postcodes) as $postcode){
                $sql[] = "`PostCode` LIKE ?";
            }
            return $this->db->query("SELECT DISTINCT `{$this->config->table_priceband}`.* FROM `{$this->config->table_postcodes}` INNER JOIN `{$this->config->table_priceband}` ON `{$this->config->table_postcodes}`.`Price` = `{$this->config->table_priceband}`.`band` WHERE ".implode(" OR ", $sql)." ORDER BY `{$this->config->table_priceband}`.`onehour` ASC;", array_filter($postcodes));
        }
        return false;
    }
    
    /**
     * Returns a list of all of the lessons and prices for a given band
     * @param string $band This should be the price band
     * @return array Returns an array of prices
     */
    public function listLessonPrices($band){
        $item = [];
        foreach($this->db->selectAll($this->config->table_priceband_info) as $lesson){
            $item[$lesson['course']] = $this->lessonPrice($lesson['course'], $band, $lesson);
        }
        return $item;
    }
}
