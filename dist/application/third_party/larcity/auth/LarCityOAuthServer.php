<?php
class LarCityOAuthServer extends OAuth2\Server {

    public function addGrantType(/* \OAuth2\GrantType\GrantTypeInterface */ $grantType, $identifier = null) {
        //parent::addGrantType($grantType, $identifier);
        if (!is_string($identifier)) {
            $identifier = $grantType->getQuerystringIdentifier();
        }
        $this->grantTypes[$identifier] = $grantType;
        // persist added grant type down to TokenController
        if (!is_null($this->tokenController)) {
            $this->getTokenController()->addGrantType($grantType, $identifier);
        }
    }

}
