<?php

namespace ObsNomad\SSO\Broker;

use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Session\Session;
use Symfony\Component\HttpFoundation\Request;

class Guard extends SessionGuard
{
    /**
     * Create a new authentication guard.
     *
     * @param string $name
     * @param Provider $provider
     * @param \Illuminate\Contracts\Session\Session $session
     * @param \Symfony\Component\HttpFoundation\Request|null $request
     * @return void
     */
    public function __construct($name,
                                Provider $provider,
                                Session $session,
                                Request $request = null)
    {
        parent::__construct($name, $provider, $session, $request);
        $this->provider = $provider;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if ($this->loggedOut) {
            return null;
        }

        if (!is_null($this->user)) {
            return $this->user;
        }

        $id = session()->get($this->getName());

        if (!is_null($id) && $this->user = $this->provider->retrieveById($id)) {
            $this->fireAuthenticatedEvent($this->user);
        }

        if (is_null($this->user)) {
            $this->user = $this->provider->retrieveFromServer();

            if ($this->user) {
                $this->updateSession($this->user->getAuthIdentifier());
                $this->fireLoginEvent($this->user, true);
            }
        }

        return $this->user;
    }
}
