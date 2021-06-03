<?php

use Brick\PhoneNumber\PhoneNumber;

class PhoneAnalyzer
{
    /**
     * Moke phone number from NNNNNNNNNNNNN to +NNN.NNNNNNNNNN format
     *
     * @param string $phone
     * @param null $default_region
     *
     * @return string phone number +NNN.NNNNNNNNNN
     */
    public static function makePhoneCorrectFormat($phone, $default_region = null): string
    {
        if (is_null($phone)) {
            return '';
        }

        $phone_number = PhoneNumber::parse($phone, $default_region);

        $country_code = $phone_number->getCountryCode();
        $national_number = $phone_number->getNationalNumber();

        return '+' . $country_code . '.' . $national_number;
    }

    /**
     * Make array with partials of phone number. phone number should be +NNN.NNNNNNNNNN format
     *
     * @param $phone
     *
     * @return array [ 'area_code', 'country_code', 'subscriber_number' ]
     */
    public static function makePhoneArray($phone): array
    {
        $pos            = strpos($phone, '.');
        $area_code_length = 3;

        $country_code      = substr($phone, 0, $pos);
        $area_code         = substr($phone, $pos + 1, $area_code_length);
        $subscriber_number = substr($phone, $pos + 1 + $area_code_length);

        return [
            'country_code'      => $country_code,
            'area_code'         => $area_code,
            'subscriber_number' => $subscriber_number,
        ];
    }
}
