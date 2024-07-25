<?php

namespace Util;

const CARIMG_FOLDER = "/var/www/apis/carimg/";
//Posible Error
const BASE_IMG_URL = "https://apis.fpfch.gob.mx/carimg/";

class Img
{
    public static function getCarImg($make, $family, $year)
    {
        $make = trim(strtolower($make));
        $family = trim(strtolower($family));
        $year = trim(strtolower($year));

        // remove non alphanumeric characters from 3 variables
        $make = preg_replace('/[^A-Za-z0-9\-]/', '', $make);
        $family = preg_replace('/[^A-Za-z0-9\-]/', '', $family);
        $year = preg_replace('/[^A-Za-z0-9\-]/', '', $year);

        $imgbase = $make . "_" . $family . "_" . $year . ".jpg";
        $img = CARIMG_FOLDER . $imgbase;
        if (!file_exists($img)) {
            $remote_img = "https://cdn.imagin.studio/getImage?customer=mxlineaexpres&make=".$make."&modelFamily=".$family."&modelYear=".$year."&width=400";
            try {
                $imgfile = @file_get_contents($remote_img);
                file_put_contents($img, $imgfile);
            } catch (\Exception $e) {
                return "";
            }


        }

        return BASE_IMG_URL . $imgbase;
    }

    public static function getTPImg()
    {
        return BASE_IMG_URL . 'tpw1.png';
    }
}
