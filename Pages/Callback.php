<?php

/**
 * Pnut pages
 */

namespace IdnoPlugins\Pnut\Pages {

    /**
     * Default class to serve the Pnut callback
     */
    class Callback extends \Idno\Common\Page {

        function getContent() {
            $this->gatekeeper(); // Logged-in users only

            try {
                if ($pnut = \Idno\Core\site()->plugins()->get('Pnut')) {
                    if ($pnutAPI = $pnut->connect()) {

                        if ($response = $pnutAPI->getAccessToken(\IdnoPlugins\Pnut\Main::$TOKEN_ENDPOINT, 'authorization_code', [
                            'code' => $this->getInput('code'), 
                            'redirect_uri' => \IdnoPlugins\Pnut\Main::getRedirectUrl(), 
                            'state' => \IdnoPlugins\Pnut\Main::getState()])) {

                            $response = json_decode($response['content']);
                            
                            $user = \Idno\Core\site()->session()->currentUser();
                            if ($response->access_token) {
                                $user->pnut = ['access_token' => $response->access_token];
                                
                                $user->save();
                                \Idno\Core\site()->session()->addMessage('Your Pnut account was connected.');
                            } else {
                                \Idno\Core\site()->session()->addErrorMessage('There was a problem connecting your Pnut account.');
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                \Idno\Core\site()->session()->addErrorMessage($e->getMessage());
            }
            
            $this->forward('/account/pnut/');
        }

    }

}