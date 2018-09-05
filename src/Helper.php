<?php

namespace ObsNomad\SSO;

use Carbon\Carbon;

class Helper
{
    public $sessionUrl = false;

    public static function isHostValid($hostId, $name)
    {
        return collect(config('ssoserver.clients'))
                ->map(function ($row) use ($hostId, $name) {
                    return $name == $row['name'] && $hostId == md5($row['name'] . $row['secret'] . $row['salt']);
                })
                ->filter()
                ->count() > 0;
    }

    /**
     * @param $sessionToken
     * @throws \Exception
     */
    public static function saveSessionIdForToken($sessionToken)
    {
        $expiresAt = Carbon::now()->addMinutes(config('ssoserver.session_lifetime'));
        cache($sessionToken, session()->getId(), $expiresAt);
        session()->save();
    }

    /**
     * @param $requestToken
     * @return \Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public static function validSessionToken($requestToken)
    {
        return cache($requestToken);
    }

    public function filterUrl($url = null, $extraValues = [])
    {
        if (!$url) {
            $url = $_SERVER['REQUEST_SCHEME'] . "://" .
                $_SERVER['HTTP_HOST'] .
                $_SERVER['REQUEST_URI'];
        }
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        $queryArray = explode("&", $query);
        $queryValues = [];

        foreach ($queryArray as $param) {
            @list($key, $value) = @explode("=", $param);

            if (in_array($key, ['sa', 'st', 'ss'])) {
                $this->sessionUrl = true;
            } else {
                $queryValues[$key] = $value;
            }
        }
        $queryValues = array_merge($queryValues, $extraValues);
        return $scheme . "://" . $host . $path . (count($queryValues) > 0 ? "?" : '') . http_build_query($queryValues);
    }
}