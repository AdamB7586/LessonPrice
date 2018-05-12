<?php
namespace LessonPrice;

use DBAL\Database;
use ShoppingCart\Config;

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
        $this->band = $this->db->select($this->config->table_priceband, array('band' => $band));
        if(isset($this->band)){
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
        $this->band = $this->db->select($this->config->table_priceband, array('band' => strtoupper($band['Price'])));
        if($this->band){
            $this->band['lesson'] = $this->band['onehour'];
            $this->band['twohours'] = $this->band['twohour'];
            $this->band['theoryfee'] = $this->band['theorytest'];
            return $this->band;
        }
        return false;
    }
    
    /**
     * Returns the price of the given lesson for the postcode given
     * @param array $relation This should be all of the lesson price information in order to work out the lesson price
     * @param string $postcode This should be the postcode that you wish to retrieve prices for
     * @return double|boolean Returns either the price if the price is not 0.00 else returns false
     */
    public function lessonPrice($relation, $postcode){
        if(!isset($this->band)){$this->selectPriceband($postcode);}
        $lessoninfo = $this->db->select($this->config->table_priceband_info, array('course' => $relation));
        
        $fee = 0;
        if($lessoninfo['testincluded']){$fee = $fee + $this->band['testfee'];}
        if($lessoninfo['theoryinclude']){$fee = $fee + $this->band['theoryfee'];}
        if($lessoninfo['materials']){$fee = $fee + $this->band['materials'];}
        if($lessoninfo['dsdvdwb']){$fee = $fee + $this->band['dsdvdwb'];}
        if($lessoninfo['dtc']){$fee = $fee + $this->band['dtc'];}
        if($relation != 'lesson'){$price = ((($this->band['lesson'] - $this->band[$relation]) * $lessoninfo['lessonshours']) + $fee);}
        else{$price = $this->band['lesson'];}
        
        $total = pounds($price);
        if($total !== '0.00'){return $total;}
        return false;
    }
}
