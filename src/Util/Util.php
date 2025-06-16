<?php

namespace App\Util;

class Util
{

    public static function secondsFromDateToNow( \DateTimeImmutable $dateFirst ): int
    {

        $diff = $dateFirst->diff( new \DateTimeImmutable('now') );
        return ( ( $diff->days * 24) * 60 ) + ( $diff->h * 60 ) + ( $diff->i * 60 ) + $diff->s;

    }

    /******  Base64 static functions  ******/
    public static function base64url_encode( string $input ){
        //return strtr(base64_encode($input, true), '+/', '-_');
        return strtr(base64_encode($input), '+/', '-_');
    }

    public static function base64url_decode( string $input ){
        return base64_decode(strtr($input, '-_', '+/'));
    }

}