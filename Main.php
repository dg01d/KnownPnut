<?php

namespace IdnoPlugins\Pnut {

    class Main extends \Idno\Common\Plugin {

	    public static $AUTHORIZATION_ENDPOINT = 'https://pnut.io/oauth/authenticate';
	    public static $TOKEN_ENDPOINT = 'https://api.pnut.io/v0/oauth/access_token';

	    public static function getRedirectUrl() {
	        return \Idno\Core\site()->config()->url . 'pnut/callback';
	    }

	    public static function getState() {
	        return md5(\Idno\Core\site()->config()->site_secret . \Idno\Core\site()->config()->url . dirname(__FILE__));
	    }

	    function registerPages() {
	        // Register the callback URL
	        \Idno\Core\site()->addPageHandler('pnut/callback', '\IdnoPlugins\Pnut\Pages\Callback');
	        // Register admin settings
	        \Idno\Core\site()->addPageHandler('admin/pnut', '\IdnoPlugins\Pnut\Pages\Admin');
	        // Register settings page
	        \Idno\Core\site()->addPageHandler('account/pnut', '\IdnoPlugins\Pnut\Pages\Account');

	        /** Template extensions */
	        // Add menu items to account & administration screens
	        \Idno\Core\site()->template()->extendTemplate('admin/menu/items', 'admin/pnut/menu');
	        \Idno\Core\site()->template()->extendTemplate('account/menu/items', 'account/pnut/menu');
	    }

	    function registerEventHooks() {

	        // Register syndication services
	        \Idno\Core\site()->syndication()->registerService('pnut', function() {
	        	return $this->hasPnut();
	        }, ['note', 'article', 'image']);


	        // Activate syndication automatically, if replying to twitter
	        \Idno\Core\Idno::site()->addEventHook('syndication/selected/pnut', function (\Idno\Core\Event $event) {
	            $eventdata = $event->data();
	            if (!empty($eventdata['reply-to'])) {
	                $replyto = (array) $eventdata['reply-to'];
	                foreach ($replyto as $url) {
	                    if (strpos(parse_url($url)['host'], 'pnut.io')!==false)
	                        $event->setResponse(true);
	                }
	            }
	        });


	        // Push "notes" to Pnut
	        \Idno\Core\site()->addEventHook('post/note/pnut', function(\Idno\Core\Event $event) {

		        $object = $event->data()['object'];
		        if ($this->hasPnut()) {
		            if ($pnutAPI = $this->connect()) {
			            $pnutAPI->setAccessToken(\Idno\Core\site()->session()->currentUser()->pnut['access_token']);
			            $message = strip_tags($object->getDescription());

			            if (strlen($message) != 0 && substr($message, 0, 1) != '@') {

			                try {

				                $entity = new \stdClass();
				                $entity->text = $message;
				                // single post created on pnut will be a reply to the last pnut.io reply given
				                if (count($object->inreplyto)) {
				                    foreach($object->inreplyto as $url) {
				                        $parsed = parse_url($url);
				                        if (isset($parsed['host']) && (strtolower($parsed['host'])=='posts.pnut.io' || strtolower($parsed['host'])=='beta.pnut.io') && ($pieces = explode('/',$parsed['path'])) && is_numeric($pieces[count($pieces)-1])) {
				                            $entity->reply_to = $pieces[count($pieces)-1];
				                        }
				                    }
				                }

				                $attachment_list = [];
				                $cross = new \stdClass();
				                $cross->type = 'io.pnut.core.crosspost';
				                $cross->value = new \stdClass();
				                $cross->value->canonical_url = $object->getUrl();

				                $attachment_list[] = $cross;
				                $entity->raw = $attachment_list;

				                $result = \Idno\Core\Webservice::post('https://api.pnut.io/v0/posts', json_encode($entity), ['Authorization: Bearer ' . $pnutAPI->access_token,'Content-Type: application/json']);
				                $content = json_decode($result['content']);

				                if ($result['response'] == 201) {
				                    // Success
				                    $id = $content->data->id; 
				                    $user = $content->data->user->username; // Think this gets user id
				                    $object->setPosseLink('pnut', 'https://posts.pnut.io/' . $id, '@' . $user, $id, $user);
				                    $object->save();
				                } else {
				                    \Idno\Core\site()->logging->log("PnutIo Syndication: " . $content->meta->error_message, LOGLEVEL_ERROR);

				                    throw new \Exception($content->meta->error_message);
				                }
			                } catch (\Exception $e) {
			                	\Idno\Core\site()->session()->addMessage($e->getMessage());
			                }
			            }
		            }
		        }
	        });

	        // Push "articles" to Pnut.io
	        \Idno\Core\site()->addEventHook('post/article/pnut', function(\Idno\Core\Event $event) {
		        $object = $event->data()['object'];
		        if ($this->hasPnut()) {
		            if ($pnutAPI = $this->connect()) {
			            $pnutAPI->setAccessToken(\Idno\Core\site()->session()->currentUser()->pnut['access_token']);

			            try {
			                $status = $object->getTitle();
			                $desc = html_entity_decode(strip_tags($object->getDescription()));
			                $parse = parse_url($object->getURL());
			                $domain = $parse['host'];
			                $domlen = (mb_strlen($domain,'UTF-8') + 3);

			                $post = $status . ': ' . $desc;
			                $boundary = (256 - $domlen);
			                $cutoff = ($boundary - 2);

			                if (mb_strlen($post,'UTF-8') > $boundary) { // Trim status down if required
			                	$post = mb_substr($post, 0, $cutoff) . '… ';
			                }

			                $article = $post . ' [' . $domain . '](' . $object->getURL() . ')';

			                /* Attachment crosspost for pnut as used by patter */ 
			                $attachment_list = []; 
			                $cross = new \stdClass();
			                $cross->type = 'io.pnut.core.crosspost';
			                $cross->value = new \stdClass();
			                $cross->value->canonical_url = $object->getUrl();
			                $attachment_list[] = $cross;

			                $entity = new \stdClass();
			                $entity->text = $article; 
			                $entity->raw = $attachment_list;
			                
			                $result = \Idno\Core\Webservice::post('https://api.pnut.io/v0/posts', json_encode($entity /*[
			                    'attachments' => $attachment_list // Well, I'm sending this as an attachment, but it doesn't seem to do anything...
			                ]*/), ['Authorization: Bearer ' . $pnutAPI->access_token, 'Content-Type: application/json']);
			                $content = json_decode($result['content']);

			                if ($result['response'] == 201) {
				                // Success
				                $id = $content->data->id;               // This gets the post id
				                $user = $content->data->user->username; // Think this gets user id
				                $object->setPosseLink('pnut', 'https://posts.pnut.io/' . $id, '@' . $user, $id, $user);
				                $object->save();
			                } else {
				                \Idno\Core\site()->logging->log("PnutIo Syndication: " . $content->meta->error_message, LOGLEVEL_ERROR);

				                throw new \Exception($content->meta->error_message);
			                }
			            } catch (\Exception $e) {
			                \Idno\Core\site()->session()->addMessage('There was a problem posting to PnutIo: ' . $e->getMessage());
			            }
		            }
		        }
	        });

	        // Push "images" to Pnut
	        \Idno\Core\site()->addEventHook('post/image/pnut', function(\Idno\Core\Event $event) {
		        $eventdata = $event->data();
		        $object    = $eventdata['object'];

				// Let's first try getting the thumbnail
				// if (!empty($object->thumbnail_id)) {
				//     if ($thumb = (array)\Idno\Entities\File::getByID($object->thumbnail_id)) {
				//         $attachments = array($thumb['file']);
				//     }
				// }

		        // No? Then we'll use the main event
		        if (empty($attachments)) {
		            $attachments = $object->getAttachments();
		        }

		        if (!empty($attachments)) {
		            foreach ($attachments as $attachment) {
		                // Ok - trying to extract the data
		                
		                list($imgwidth, $imgheight) = getimagesize($attachment['url']);

		                $tmp = new \stdClass();
		                $tmp->type = 'io.pnut.core.oembed';
		                $tmp->value = new \stdClass();

		                $tmp->value->type = 'photo';
		                $tmp->value->version = '1.0';
		                $tmp->value->title = '1.0';
		                $tmp->value->width = $imgwidth;
		                $tmp->value->height = $imgheight;
		                $tmp->value->url = $attachment['url'];
		    
		                $attachment_list[] = $tmp; 
		            }
		                

		            /* REMOVED FOR FUTURE USE-CASE

		            if (!empty($object->thumbnail_large)) {
		                $src = $object->thumbnail_large;                
		            }
		            else if (!empty($object->thumbnail_medium)) {
		                $src = $object->thumbnail_medium;                
		            } 
		             else if (!empty($object->small)) { 
		                $src = $object->thumbnail_small;
		            } else if (!empty($object->thumbnail)) { // Backwards compatibility
		                $src = $object->thumbnail;
		            } else {
		                $src = $attachment['url'];
		            }

		            $tmp->value->thumbnail_url = $src;
		            $tmp->value->thumbnail_width = $width;
		            $tmp->value->thumbnail_height = $height; */    
		                
		            if ($this->hasPnut()) {
			            if ($pnutAPI = $this->connect()) {
			                $pnutAPI->setAccessToken(\Idno\Core\site()->session()->currentUser()->pnut['access_token']);

			                try {

				                $status = $object->getTitle();
				                $caption = $status . ' [' . $domain . '](' . $object->getURL() . ')';
				                
				                
				                $entity = new \stdClass();
				                $entity->text = $caption;

				                // $attachment_list = []; 
				                $cross = new \stdClass();
				                $cross->type = 'io.pnut.core.crosspost';
				                $cross->value = new \stdClass();
				                $cross->value->canonical_url = $object->getUrl();
				                $attachment_list[] = $cross;

				                $entity->raw = $attachment_list;
				                
				                $result = \Idno\Core\Webservice::post('https://api.pnut.io/v0/posts?include_post_raw=1', json_encode($entity), ['Authorization: Bearer '.$pnutAPI->access_token, 'Content-Type: application/json']);
				                $content = json_decode($result['content']);
				                
				                if ($result['response'] == 201) {
				                    // Success
				                    $id = $content->data->id;               // This gets user id
				                    $user = $content->data->user->username; // Think this gets user id
				                    $object->setPosseLink('pnut', 'https://posts.pnut.io/' . $id, '@' . $user, $id, $user);
				                    $object->save();
				                } else {
				                    \Idno\Core\site()->logging->log("PnutIo Syndication: " . $content->meta->error_message, LOGLEVEL_ERROR);

				                    throw new \Exception($content->meta->error_message);
				                }
			                } catch (\Exception $e) {
			                	\Idno\Core\site()->session()->addMessage('There was a problem posting to PnutIo: ' . $e->getMessage());
			                }
			            }
		            }
		        }
	        });
	    }

	    /**
	     * Connect to PnutIo
	     * @return bool|\IdnoPlugins\Pnut\Client
	     */
	    function connect() {
	        if (!empty(\Idno\Core\site()->config()->pnut)) {
		        $api = new Client(
		            \Idno\Core\site()->config()->pnut['appId'], \Idno\Core\site()->config()->pnut['secret']
		        );
		        return $api;
	        }
	        return false;
	    }

	    /**
	     * Can the current user use Pnut?
	     * @return bool
	     */
	    function hasPnut() {
	        if (\Idno\Core\site()->session()->currentUser()->pnut) {
	        	return true;
	        }
	        return false;
	    }
    }
}
