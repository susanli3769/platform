<?php
/**
 * Email For Download element
 *
 * @package socialfeeds.org.cashmusic
 * @author CASH Music
 * @link http://cashmusic.org/
 *
 * Copyright (c) 2013, CASH Music
 * Licensed under the Affero General Public License version 3.
 * See http://www.gnu.org/licenses/agpl-3.0.html
 *
 *
 * This file is generously sponsored by Ben Adida
 * // free the artists! http://ben.adida.net
 *
 **/

namespace CASHMusic\Elements\SocialFeeds;

use CASHMusic\Core\CASHSystem;
use CASHMusic\Core\ElementBase;
use ArrayIterator;
use CASHMusic\Seeds\TumblrSeed;
use CASHMusic\Seeds\TwitterSeed;

class SocialFeeds extends ElementBase {
	public $type = 'socialfeeds';
	public $name = 'Social Feeds';
	protected $twitter_seed = false;
	protected $tumblr_seed = false;

	public function getData() {

		$this->twitter_seed = new TwitterSeed($this->element_data['user_id']);
		$this->tumblr_seed = new TumblrSeed();
		$raw_feeds = array();
		$twitter_feeds = array();
		$tumblr_feeds = array();

		$feedcount = 0;

		if (isset($this->options['twitter'])) {
			if (is_array($this->options['twitter'])) {
				foreach($this->options['twitter'] as $feedname => $feed) {

					if (!isset($feed['twitterhidereplies'])) $feed['twitterhidereplies'] = false;
					$twitter_request = $this->twitter_seed->getUserFeed($feed['twitterusername'],$feed['twitterhidereplies'],$this->options['post_limit'],$feed['twitterfiltertype'],$feed['twitterfiltervalue']);
					if ($twitter_request) {
						$twitter_feeds[] = $twitter_request;
						$feedcount++;
					}
				}
			}
		}

		CASHSystem::errorLog("1 twitter");

		if (isset($this->options['tumblr'])) {
			if (is_array($this->options['tumblr'])) {
				foreach($this->options['tumblr'] as $feedname => $feed) {
					$tumblr_request = $this->tumblr_seed->getTumblrFeed($feed['tumblrurl'],0,$feed['tumblrtag'],(array) $feed['post_types']);
					if ($tumblr_request) {
						$tumblr_feeds[] = $tumblr_request;
						$feedcount++;
					}
				}
			}
		}

		CASHSystem::errorLog("2 tumblr");

		$raw_feeds['twitter'] = $twitter_feeds;
		$raw_feeds['tumblr'] = $tumblr_feeds;

		CASHSystem::errorLog($raw_feeds);
		try {
            if ($feedcount) {
                $formatted_feed = array();

                foreach ($raw_feeds['twitter'] as $feed) {
                    foreach ($feed as $tweet) {

                        $formatted_feed[strtotime($tweet->created_at)] = array(
                            'type' => 'twitter',
                            'markup' => $this->mustache->render("tweet", $tweet)
                        );
                    }
                }

                foreach ($raw_feeds['tumblr'] as $feed) {
                    foreach ($feed as $post) {
                        $formatted_feed[$post['unix-timestamp']] = array(
                            'type' => 'tumblr',
                            'markup' => $this->mustache->render('tumblrpost_' . $post['type'], $post)
                        );
                    }

                }

                krsort($formatted_feed);
                $formatted_feed = array_slice($formatted_feed, 0, $this->options['post_limit'], true);

                $this->element_data['raw_feeds'] = $raw_feeds;
                $this->element_data['formatted_feed'] = new ArrayIterator($formatted_feed);
            } else {
                // no dates matched
                $this->element_data['error_message'] = 'There are no posts to display right now.';
            }
        } catch (\Exception $e) {
			CASHSystem::errorLog($e->getMessage());
		}

        CASHSystem::errorLog("3 formatted");

		return $this->element_data;
	}
} // END class
?>
