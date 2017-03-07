<?php

    /**
     * Pnut pages
     */

    namespace IdnoPlugins\Pnut\Pages {

        /**
         * Default class to serve Pnut settings in administration
         */
        class Admin extends \Idno\Common\Page
        {

            function getContent()
            {
                $this->adminGatekeeper(); // Admins only
                $t = \Idno\Core\site()->template();
                $body = $t->draw('admin/pnut');
                $t->__(['title' => 'Pnut', 'body' => $body])->drawPage();
            }

            function postContent() {
                $this->adminGatekeeper(); // Admins only
                $appId = $this->getInput('appId');
                $secret = $this->getInput('secret');
                \Idno\Core\site()->config->config['pnut'] = [
                    'appId' => $appId,
                    'secret' => $secret
                ];
                \Idno\Core\site()->config()->save();
                \Idno\Core\site()->session()->addMessage('Your Pnut application details were saved.');
                $this->forward('/admin/pnut/');
            }

        }

    }