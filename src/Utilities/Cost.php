<?php
namespace LessonPrice\Utilities;

class Cost {
    public static function priceUnits($price){
        return number_format($price, 2, '.', ',');
    }
}
