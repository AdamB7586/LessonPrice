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
     * @param array $lessoninfo This should be all of the lesson price information in order to work out the lesson price
     * @param string $postcode This should be the postcode that you wish to retrieve prices for
     * @return double|boolean Returns either the price if the price is not 0.00 else returns false
     */
    public function lessonPrice($lessoninfo, $postcode){
        if(!isset($this->band)){$this->selectPriceband($postcode);}
        
        $fee = 0;
        if($lessoninfo['testincluded']){$fee = $fee + $this->band['testfee'];}
        if($lessoninfo['theoryinclude']){$fee = $fee + $this->band['theoryfee'];}
        if($lessoninfo['materials']){$fee = $fee + $this->band['materials'];}
        if($lessoninfo['dsdvdwb']){$fee = $fee + $this->band['dsdvdwb'];}
        if($lessoninfo['dtc']){$fee = $fee + $this->band['dtc'];}
        if($lessoninfo['lessonrelation'] != 'lesson'){$price = ((($this->band['lesson'] - $this->band[$lessoninfo['lessonrelation']]) * $lessoninfo['lessonshours']) + $fee);}
        else{$price = $this->band['lesson'];}
        
        $total = pounds($price);
        if($total !== '0.00'){return $total;}
        return false;
    }
}
