<?php

namespace Papmallcom\Backend\Controllers;
use Papmallcom\Repositories\Formatname;
use Papmallcom\Models\PapmallProduct;
use Papmallcom\Models\PapmallCountry;
use Papmallcom\Repositories\Import;

class ImportController extends ControllerBase
{
    public function initialize()
    {
        $this->title = "Import file";
        parent::initialize();
    }
    public function indexAction()
    {
        if ($this->session->has('message')) {
            $this->view->messages = $this->session->get('message');
            $this->session->remove('message');
        }
        if ($this->session->has('importFiles')) {
            $this->view->importFiles = $this->session->get('importFiles');
            $this->session->remove('importFiles');
        }
        // Check if the user has uploaded files
        if ($this->request->hasFiles() == true) {
            $msg_error = "";

//            $importFiles = array();
            $numberOfSuccess = 0;
            $numberOfFail = 0;
            $numberOfFiles = 0;
            //Upload files
            foreach ($this->request->getUploadedFiles() as $file) {
                $msg_temp = "";
                $numberOfFiles++;
                $path_parts = pathinfo($file->getName());
                $msg_error .= "<br>"."Error Filename: ".$path_parts['basename']."<br>";
                $tmp = $file->getTempName();
                $flag = 0;

                if(!empty($tmp)) {
                    $handle = fopen("$tmp", "r");
                    while(! feof($handle)){
                        while (($row = fgetcsv($handle)) !== FALSE) {
                            $flag++;
                            if ($flag!=1){
                                $message = [];
                                //CATEGORY check $row[0]
                                $cate_list = explode(";",trim($row[0]));
                                $res_cate = [];

                                foreach ($cate_list as $item){
                                    if (Import::getCategoryIdByName(trim($item)) != ''){
                                        array_push($res_cate,Import::getCategoryIdByName(trim($item)));
                                    }
                                    else{
                                        if (trim($row[0])==''){
                                            array_push($message,'++ Product Category Ids is missing field');

                                        } else{
                                            array_push($message,'++ Product Category Ids is error field: '.trim($row[0]));
                                        }
                                    }
                                }

                                $country_list = explode(";",trim($row[6]));
                                $res_country=[];
                                foreach ($country_list as $country){
                                    if (Import::getCountryByCode($country) != ''){
                                        array_push($res_country,Import::getCountryByCode($country));
                                    }
                                    else{
                                        //if (empty($data['product_sell_country_codes'])) {
                                        if (trim($row[6])==''){
                                            array_push($message,'++ Product Sell Country Codes is missing field');
                                        }
                                        else{
                                            array_push($message,'++ Product Sell Country Codes is error field: '.$country);
                                        }
                                    }
                                }

                                $data = array(
                                    'product_id' => -1,
                                    'product_parent_id' => 0,
                                    'product_type' => PapmallProduct::productType['non-physical'],
                                    'product_sell_country_codes'=> implode(",", $res_country),
                                    'product_category_ids' => implode(",",$res_cate),
                                    'product_code' => trim($row[1]),
                                    'product_name' => trim($row[2]),
                                    'product_price' => trim($row[3]),
                                    'product_sale_price' => trim($row[4]),
                                    'product_amount' => trim($row[5]),
                                    'product_keyword' => self::createSlug(trim($row[2])),
                                    'product_status' => 'A',
                                );

                                if (empty($data['product_code'])) {
                                    array_push($message,'++ Product Code is missing field');
                                }
                                else {
                                    $check_code =$data['product_code'];
                                    $check = Import::checkProductCodeByCode($check_code);
                                    if ($check) {
                                        array_push($message,'++ Product Code '.$check_code.' is exists');

                                    }
                                }

                                if (empty($data['product_name'])) {
                                    array_push($message,'++ Product Name is missing field');
                                }
                                if (empty($data['product_price'])) {
                                    array_push($message,'++ Product price is missing field');
                                }
                                elseif (!is_numeric($data['product_price'])) {
                                    array_push($message,'++ Product price '.$data['product_price'].' is not valid');
                                }
                                if (!empty($data['product_sale_price']) && !is_numeric($data['product_sale_price'])) {
                                    array_push($message,'++ Product Sale price '.$data['product_sale_price'].' is not valid');
                                }
                                if (empty($data['product_keyword'])) {
                                    array_push($message,'++ Product Keyword is missing field');
                                }

                                if (($data['product_amount'])==='') {
                                    array_push($message,'++ Product quantity is missing field');
                                }
                                elseif (!is_numeric($data['product_amount'])){
                                    array_push($message,'++ Product quantity '.$data['product_amount'].' is not valid');
                                }

                                if (count($message) === 0) {
                                    $new_product = new PapmallProduct();
                                    $new_product->setProductCategoryIds($data["product_category_ids"]);
                                    $new_product->setProductType($data["product_type"]);
                                    $new_product->setProductName($data['product_name']);
                                    $new_product->setProductCode($data["product_code"]);
                                    $new_product->setProductPrice($data['product_price']);
                                    $new_product->setProductSalePrice($data["product_sale_price"]);
                                    $new_product->setProductSellCountryCodes($data['product_sell_country_codes']);
                                    $new_product->setProductAmount($data["product_amount"]);
                                    $new_product->setProductStatus($data['product_status']);
                                    $new_product->setProductKeyword($data['product_keyword']);
                                    $new_product->save();
                                }
                                else{
                                    $msg_temp .= " + Record ".$flag.": <br>".implode(', <br>',$message)."<br>";
                                }
                            }
                        }
                        if($msg_temp==""){
                            $msg_temp .=" is uploaded successfully"."<br>";
                        }
                    }
                    $msg_error .= $msg_temp;

                    fclose($handle);
                    $numberOfSuccess++;
//                    return $this->response->redirect('/product');
                }
            }

            if($numberOfSuccess===$numberOfFiles) {
                if(count($message)===0){
                    $messageIndex = array(
                        "type" => "success",
                        "message" => "All files are uploaded successfully!<br>"
                    );
                }else{
                    $messageIndex = array(
                        "type" => "danger",
                        "message" => $msg_error,
                    );
                }
//                return $this->response->redirect('/product');
            }
            else {
                if($numberOfSuccess>=$numberOfFail) {
                    $messageIndex["type"] = "info";
                } else {
                    $messageIndex["type"] = "error";
                }
            }
            $messageIndex['status'] = 'border-danger';
            $this->view->setVars(array(
                'messages' => $messageIndex,
            ));
//            return $this->response->redirect("/import");
        }
    }
    public static function createSlug($str, $delimiter = '-'){
        $slug = strtolower(trim(preg_replace('/[\s-]+/', $delimiter, preg_replace('/[^A-Za-z0-9-]+/', $delimiter, preg_replace('/[&]/', 'and', preg_replace('/[\']/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $str))))), $delimiter));
        return $slug;
    }
}