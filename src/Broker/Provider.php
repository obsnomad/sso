<?php

namespace ObsNomad\SSO\Broker;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;
use ObsNomad\SSO\Helper;

class Provider implements UserProvider
{
    private $credentials;
    private $server;
    private $token;
    private $helper;

    public function __construct()
    {
        $this->helper = new Helper;
        if (!cookie(config('ssoclient.cookie_name'))->getValue()) {
            session()->regenerate();
            $sessionId = session()->getId();
            setcookie(config('ssoclient.cookie_name'), $sessionId, 45000000);
        } else {
            session()->setId(cookie(config('ssoclient.cookie_name')));
        }
        session()->start();

        $this->credentials = config('ssoclient.credentials');
        $this->credentials['hostId'] = md5($this->credentials['name']
            . $this->credentials['secret']
            . $this->credentials['salt']);

        $this->token = $this->getToken();
        $this->server = config('ssoclient.server_url');

        if (empty($this->token)) {
            $this->token = $this->uniqid();

            session()->put('session_token', $this->token);
            session()->save();

            $this->attachSession();
        }
        $this->hideSessionFromUrl();
    }

    public function retrieveFromServer()
    {
        $name = $this->credentials['name'];
        $hostId = $this->credentials['hostId'];
        $token = $this->token;

        $user = $this->request('GET', 'user', [
            'name' => $name,
            'hostId' => $hostId,
            'sessionToken' => $token,
        ]);

        return $this->getUser($user);
    }

    public function retrieveById($identifier)
    {
        $user = $this->request("GET", '/user', [
            'name' => $this->credentials['name'],
            'hostId' => $this->credentials['hostId'],
            'sessionToken' => $this->token,
        ]);

        return $this->getUser($user);
    }

    public function retrieveByToken($identifier, $token)
    {
        dd('Provider::retrieveByToken - incomplete', $identifier, $token);

        $user = new User(11026, 'Sergey', 'Dudchenko', '1232123');

        return $this->getUser($user);
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        $user->setRememberToken($token);
        $timestamps = $user->timestamps;
        $user->timestamps = false;
        $user->timestamps = $timestamps;
    }

    public function retrieveByCredentials(array $credentials)
    {
        dd('Provider::retrieveByCredentials', $credentials);

        if (empty($credentials) ||
            (count($credentials) === 1 && array_key_exists('password', $credentials))) {
            return;
        }

        // Check here

        dd('Provider::retrieveByCredentials', $credentials);

        $user = new User(110261, 'Sergey1', 'Dudchenko1', '1232123111');

        return $this->getUser($user);
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $password = config('ssoclient.password_field');
        if (!empty($user->$password)) {
            return Hash::check($credentials['password'], $user->$password);
        } elseif (($password = config('ssoclient.old_password_field')) && !empty($user->$password)) {
            return md5($credentials['password']) === $user->$password;
        }
        return false;
    }

    protected function hideSessionFromUrl()
    {
        $url = $this->helper->filterUrl();
        if ($this->helper->sessionUrl) {
            session()->save();
            redirect($url);
        }
    }

    protected function attachSession()
    {
        $name = $this->credentials['name'];
        $hostId = $this->credentials['hostId'];
        $token = $this->token;

        $url = $this->server . "/attach?" . http_build_query([
                'name' => $name,
                'hostId' => $hostId,
                'sessionToken' => $token,
                'backUrl' => $this->helper->filterUrl(),
            ]);

        session()->save();
        redirect($url);
    }

    protected function getUser($user)
    {
        return $user ? new User((array)$user) : null;
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array $params
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function request($method, $endpoint, $params = null)
    {
        $cookieJar = CookieJar::fromArray([
            'sso_client' => session()->get('session_server_id'),
        ], parse_url($this->server, PHP_URL_HOST));

        $client = new Client([
            'base_uri' => $this->server,
            'timeout' => 1,
            'verify' => false,
        ]);
        $options = [
            'cookies' => $cookieJar,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];
        if ($method == 'POST') {
            $options['body'] = $params;
        } else {
            $options['query'] = $params;
        }

        $response = $client->request($method, $endpoint, $options);
        return json_decode($response->getBody()->getContents());
    }

    private function getToken()
    {
        $requestToken = request()->get('st');
        $token = session()->get('session_token', $requestToken);

        if (!empty($token)) {
            session()->put('session_token', $token);
            session()->save();
        }

        if (request()->get('sa') == 1 && request()->get('ss') !== '') {
            session()->put('session_server_id', request()->get('ss'));
            session()->save();
        }

        return $token;
    }

    private function uniqid()
    {
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }

        return sprintf(
            '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(16384, 20479),
            mt_rand(32768, 49151),
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(0, 65535)
        );
    }
}
