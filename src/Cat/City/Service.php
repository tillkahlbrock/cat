<?php

namespace Cat\City;


class Service 
{
    private static $cities = array(
        'de' => array(
            'berlin',
            'hamburg',
            'munich',
            'frankfurt',
            'bochum',
            'cologne',
            'kiel',
            'lübeck',
            'bielefeld',
            'dortmund',
            'leverkusen',
            'ulm',
            'tübingen'
        ),
        'fr' => array(
            'paris',
            'marseille',
            'strasbourg'
        ),
        'ch' => array(
            'bern',
            'zurich',
            'geneva'
        ),
        'uk' => array(
            'london',
            'manchester',
            'liverpool'
        )

    );

    public static function getCities($country)
    {
        if (!array_key_exists($country, self::$cities)) {
            return array();
        }

        return self::$cities[$country];
    }
}
