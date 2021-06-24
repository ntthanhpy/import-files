<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 6/4/2021
 * Time: 10:34 AM
 */

namespace Papmallcom\Repositories;


use Papmallcom\Models\PapmallCategory;
use Papmallcom\Models\PapmallCountry;
use Papmallcom\Models\PapmallProduct;
use Papmallcom\Models\PapmallProductFeatureVariantValue;
use Papmallcom\Models\PapmallProductVariationGroup;
use Phalcon\Di;

class Import
{
    public static function getCategoryIdByName($name)
    {
        $model = PapmallCategory::findFirst(
            array (
                'category_name = :name:',
                'bind' => array('name' => $name),
            ));

        return $model ? $model->getCategoryId() : '';
    }
    public static function getCountryByCode($code)
    {
        $model = PapmallCountry::findFirst(
            array (
                'country_code = :code:',
                'bind' => array('code' => $code),
            ));

        return $model ? $model->getCountryCode() : '';
    }

    public static function checkProductCodeByCode($code)
    {
        $model = PapmallProduct::findFirst(
            array (
                'product_code = :code:',
                'bind' => array('code' => $code),
            ));
        if($model){
            if($model->getProductCode()==$code){
                return true;
            }
            else{
                return false;
            }
        }
//        return $model ? $model ? $model->getProductCode() : '' : '';
    }

}