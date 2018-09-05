<?php

namespace ObsNomad\SSO\Broker;

use Illuminate\Auth\GenericUser;

class User extends GenericUser
{
    public function getRememberTokenName()
    {
        return "token";
    }

    public function getMemberId()
    {
        return $this->getAuthIdentifier();
    }

    public function getAuthIdentifierName()
    {
        return "memberId";
    }
}
