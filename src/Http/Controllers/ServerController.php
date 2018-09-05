<?php

namespace ObsNomad\SSO\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ObsNomad\SSO\Helper;

class ServerController extends Controller
{
    public function login(Request $request)
    {
        // ...
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Exception
     */
    public function attach(Request $request)
    {
        @list($name, $hostId, $sessionToken, $backUrl) = array_values($request->validate([
            'name' => 'required',
            'hostId' => 'required',
            'sessionToken' => 'required',
            'backUrl' => 'required',
        ]));
        try {
            if (Helper::isHostValid($hostId, $name)) {
                session()->start();
                Helper::saveSessionIdForToken($sessionToken);
                $backUrl = (new Helper())->filterUrl(
                    $backUrl,
                    [
                        'sa' => 1,
                        'ss' => session()->getId(),
                        'st' => $sessionToken,
                    ]
                );
            }
        } finally {
            session()->save();
            return redirect($backUrl);
        }
    }

    /**
     * @param Request $request
     * @return $this
     * @throws \Exception
     */
    public function user(Request $request)
    {
        @list($name, $hostId, $sessionToken) = array_values($request->validate([
            'name' => 'required',
            'hostId' => 'required',
            'sessionToken' => 'required',
        ]));
        if (Helper::isHostValid($hostId, $name)) {
            $sessionId = cookie('sso_client')->getValue() ?: cache($sessionToken);
            session()->setId($sessionId);
            session()->start();
        }
        session()->save();
        return response()
            ->json(auth()->user())
            ->header('Content-Type', 'application/json');
    }

    public function logout(Request $request)
    {
        // ...
    }
}
