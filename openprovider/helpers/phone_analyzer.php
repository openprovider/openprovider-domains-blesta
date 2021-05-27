<?php

use Brick\PhoneNumber\PhoneNumber;

class PhoneAnalyzer
{
    /**
     * @param string $phone
     * @return string
     */
    public static function makePhoneCorrectFormat($phone, $defaultRegion = null)
    {
        if (is_null($phone)) {
            return '';
        }

        try {
            $phone_number = PhoneNumber::parse($phone, $defaultRegion);

        } catch (Exception $e) {
        }

        $country_code = $phone_number->getCountryCode();
        $national_number = $phone_number->getNationalNumber();

        return '+' . $country_code . '.' . $national_number;
    }

    /**
     * @param $phone
     * @return array [ 'area_code', 'country_code', 'subscriber_number' ]
     */
    public static function makePhoneArray($phone)
    {
        $pos            = strpos($phone, '.');
        $areaCodeLength = 3;

        $country_code      = substr($phone, 0, $pos);
        $area_code         = substr($phone, $pos + 1, $areaCodeLength);
        $subscriber_number = substr($phone, $pos + 1 + $areaCodeLength);

        return [
            'country_code'      => $country_code,
            'area_code'         => $area_code,
            'subscriber_number' => $subscriber_number,
        ];
    }
}
