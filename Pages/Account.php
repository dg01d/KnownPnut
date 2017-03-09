<?php

    /**
     * Pnut pages
     */

    namespace IdnoPlugins\Pnut\Pages {

        /**
         * Default class to serve Pnut-related account settings
         */
        class Account extends \Idno\Common\Page
        {

            function getContent()
            {
                $this->gatekeeper(); // Logged-in users only
                if ($pnut = \Idno\Core\site()->plugins()->get('Pnut')) {
                    if (!$pnut->hasPnut()) {
                        if ($pnutAPI = $pnut->connect()) {
                            $login_url = $pnutAPI->getAuthenticationUrl(
				\IdnoPlugins\Pnut\Main::$AUTHORIZATION_ENDPOINT,
				\IdnoPlugins\Pnut\Main::getRedirectUrl(),
				['response_type' => 'code', 'state' => \IdnoPlugins\Pnut\Main::getState(), 'scope' => 'basic,write_post'] 
                            );
			    
                        }
                    } else {
                        $login_url = '';
                    }
                }
                $t = \Idno\Core\site()->template();
                $body = $t->__(['login_url' => $login_url])->draw('account/pnut');
                $t->__(['title' => 'Pnut', 'body' => $body])->drawPage();
            }

            function postContent() {
                $this->gatekeeper(); // Logged-in users only
                if (($this->getInput('remove'))) {
                    $user = \Idno\Core\site()->session()->currentUser();
                    $user->pnut = [];
                    $user->save();
                    \Idno\Core\site()->session()->addMessage('Your Pnut settings have been removed from your account.');
                }
                $this->forward('/account/pnut/');
            }

        }

    }