<?php
	/*
		Class: BTXSocialFeed
			Provides a blended feed of social info.
			
			Future updates planned:
			- After adding a social query, sync that query immediately.
			- If a social query changes, delete everything from that query and re-sync.
	*/

	class BTXSocialFeed {

		static $DefaultApprovedState = ""; 	// Switch to "on" to have things be auto approved
		static $DefaultLocationRadius = 1;	// If the user specifies an invalid radius or omits it (numeric value, in miles)
		static $IgnoreRetweets = false;		// Don't include Twitter retweets
		static $IgnoreReplies = true;		// Don't include Twitter tweets that begin with @username
		static $ItemsToCache = array();		// Used internally
		static $SyncCount = 50; 			// This is how many we'd like to have — some calls ignore what we request anyway
		
		static protected function _get($query) {
			$items = array();
			$q = sqlquery($query);
			while ($f = sqlfetch($q)) {
				$f["data"] = json_decode($f["data"]);
				$items[] = $f;
			}
			return $items;
		}

		/*
			Function: deleteQuery
				Deletes a query and related items from the stream that this query generated.
				If two queries generated the same stream content, the content will only be deleted once all query references have been deleted.

			Parameters:
				id - Query ID
		*/

		static function deleteQuery($id) {
			$id = sqlescape($id);

			// Find all the stream content related to this query
			$q = sqlquery("SELECT item FROM btx_social_feed_stream_queries WHERE `query` = '$id'");
			while ($f = sqlfetch($q)) {
				// See if the item is related to more than one query
				$r = sqlrows(sqlquery("SELECT `query` FROM btx_social_feed_stream_queries WHERE `item` = '".$f["item"]."'"));
				// If this is the only query related to the content, delete it.
				if ($r == 1) {
					BigTreeAutoModule::deleteItem("btx_social_feed_stream",$f["item"]);
				}
			}

			// Delete the query itself -- foreign key constraints will delete the reference table
			BigTreeAutoModule::deleteItem("btx_social_feed_queries",$id);
		}

		/*
			Function: getCategories
				Returns available categories.

			Returns:
				An array of categoriess.
		*/

		static function getCategories() {
			$items = array();
			$q = sqlquery("SELECT * FROM btx_social_feed_categories");
			while ($f = sqlfetch($q)) {
				$items[] = $f;
			}
			return $items;
		}

		/*
			Function: getRecent
				Returns recent items from the stream.

			Parameters:
				count - Number of results to return
				include_uanapproved - Whether to include unapproved items

			Returns:
				An array of social objects.
		*/

		static function getRecent($count,$include_uanapproved = false) {
			return self::_get("SELECT * FROM btx_social_feed_stream ".(!$include_uanapproved ? "WHERE approved = 'on'" : "")." ORDER BY date DESC LIMIT $count");
		}

		/*
			Function: getRecentInCategory
				Returns recent items from the stream in a specific category.

			Parameters:
				count - Number of results to return
				category - Category ID
				include_uanapproved - Whether to include unapproved items

			Returns:
				An array of social objects.
		*/

		static function getRecentInCategory($count,$category,$include_uanapproved = false) {
			return self::_get("SELECT s.* FROM btx_social_feed_stream AS `s` JOIN btx_social_feed_stream_categories AS `c` ON s.id = c.item WHERE c.category = '$category'".(!$include_uanapproved ? " AND s.approved = 'on'" : "")." ORDER BY s.date DESC LIMIT $count");
		}

		/*
			Function: getRecentInCategoryFromService
				Returns recent items from the stream in a specific category from a specific service.

			Parameters:
				count - Number of results to return
				category - Category ID
				service - Service to return (Flickr, Google+, Instagram, Twitter, YouTube)
				include_uanapproved - Whether to include unapproved items

			Returns:
				An array of social objects.
		*/

		static function getRecentInCategoryFromService($count,$category,$service,$include_uanapproved = false) {
			return self::_get("SELECT s.* FROM btx_social_feed_stream AS `s` JOIN btx_social_feed_stream_categories AS `c` ON s.id = c.item WHERE c.category = '$category' AND s.service = '$service'".(!$include_uanapproved ? " AND s.approved = 'on'" : "")." ORDER BY s.date DESC LIMIT $count");
		}

		/*
			Function: getRecentFromService
				Returns recent items from the stream from a specified service.

			Parameters:
				count - Number of results to return
				service - Service to return (Flickr, Google+, Instagram, Twitter, YouTube)
				include_uanapproved - Whether to include unapproved items

			Returns:
				An array of social objects.
		*/

		static function getRecentFromService($count,$service,$include_uanapproved = false) {
			return self::_get("SELECT * FROM btx_social_feed_stream WHERE service = '$service'".(!$include_uanapproved ? " AND approved = 'on'" : "")." ORDER BY date DESC LIMIT $count");
		}

		/*
			Function: previewLink
				Sets up the preview link for the module view of the stream.
		*/

		static function previewLink($item) {
			$item = sqlfetch(sqlquery("SELECT * FROM btx_social_feed_stream WHERE id = '".$item["id"]."'"));
			$data = json_decode($item["data"]);
			if ($item["service"] == "Twitter") {
				return '" onclick="window.open(\'http://m.twitter.com/'.$data->User->Username.'/status/'.$data->ID.'/\',\'_blank\',\'fullscreen=no,width=600,height=500\'); return false;"';
			} elseif ($item["service"] == "Instagram") {
				return '" onclick="window.open(\''.$data->Image.'\',\'_blank\',\'fullscreen=no,width=612,height=612\'); return false;"';	
			} elseif ($item["service"] == "Flickr") {
				return '" onclick="window.open(\''.$data->Image640.'\',\'_blank\',\'fullscreen=no,width=640,height=640\'); return false;"';
			} elseif ($item["service"] == "YouTube") {
				return '" onclick="window.open(\'http://youtube.com/embed/'.$data->ID.'\',\'_blank\',\'fullscreen=no,width=640,height=400\'); return false;"';	
			} elseif ($item["service"] == "Google+") {
				return '" onclick="window.open(\''.$data->URL.'\',\'_blank\',\'fullscreen=no,width=960,height=650\'); return false;"';
			} elseif ($item["service"] == "Facebook") {
				return '" onclick="window.open(\''.$data->URL.'\',\'_blank\',\'fullscreen=no,width=960,height=650\'); return false;"';
			}
			return "";
		}

		/*
			Function: sync
				Syncs queries into the stream table

			Parameters:
				id - If ID is passed, only this specific query will be synced, otherwise all will be synced
		*/

		static function sync($id = false) {
			if ($id) {
				$q = sqlquery("SELECT * FROM btx_social_feed_queries WHERE id = '".sqlescape($id)."' ORDER BY service");
			} else {
				$q = sqlquery("SELECT * FROM btx_social_feed_queries ORDER BY service");
			}
			while ($f = sqlfetch($q)) {
				// Twitter
				if ($f["service"] == "Twitter") {
					$twitter = isset($twitter) ? $twitter : new BigTreeTwitterAPI;
					$params = array();
					if (self::$IgnoreRetweets) {
						$params["include_rts"] = false;
					}
					if (self::$IgnoreReplies) {
						$params["exclude_replies"] = true;
					}
					if ($f["type"] == "Person") {
						self::syncData($f,"Twitter",$twitter->getUserTimeline($f["query"],self::$SyncCount,$params));
					} elseif ($f["type"] == "Hashtag") {
						self::syncData($f,"Twitter",$twitter->searchTweets("#".ltrim(trim($f["query"]),"#"),self::$SyncCount,"recent",false,false,false,$params));
					} elseif ($f["type"] == "Search") {
						self::syncData($f,"Twitter",$twitter->searchTweets(trim($f["query"]),self::$SyncCount,"recent",false,false,false,$params));	
					}
				// Instagram
				} elseif ($f["service"] == "Instagram") {
					$instagram = isset($instagram) ? $instagram : new BigTreeInstagramAPI;

					if ($f["type"] == "Person") {
						self::syncData($f,"Instagram",$instagram->getUserMedia($f["query"],self::$SyncCount));
					} elseif ($f["type"] == "Hashtag") {
						self::syncData($f,"Instagram",$instagram->getTaggedMedia(trim($f["query"])));
					} elseif ($f["type"] == "Location") {
						$query = json_decode($f["query"],true);
						// Instagram uses meters, so we convert the default miles to meters
						$radius = is_numeric($query["radius"]) ? $query["radius"] : floor(self::$DefaultLocationRadius * 1609.34);
						self::syncData($f,"Instagram",$instagram->searchMedia($query["latitude"],$query["longitude"],$radius));
					}
				// Google+
				} elseif ($f["service"] == "Google+") {
					$gplus = isset($gplus) ? $gplus : new BigTreeGooglePlusAPI;

					if ($f["type"] == "Person") {
						self::syncData($f,"Google+",$gplus->getActivities($f["query"],self::$SyncCount));
					} elseif ($f["type"] == "Search") {
						self::syncData($f,"Google+",$gplus->searchActivities($f["query"],self::$SyncCount,"recent"));
					}
				// YouTube
				} elseif ($f["service"] == "YouTube") {
					$youtube = isset($youtube) ? $youtube : new BigTreeYouTubeAPI;

					if ($f["type"] == "Person") {
						self::syncData($f,"YouTube",$youtube->getChannelVideos($f["query"],self::$SyncCount));
					} elseif ($f["type"] == "Search") {
						self::syncData($f,"YouTube",$youtube->searchVideos($f["query"],self::$SyncCount,"date"));
					}
				// Flickr
				} elseif ($f["service"] == "Flickr") {
					$flickr = isset($flickr) ? $flickr : new BigTreeFlickrAPI;

					if ($f["type"] == "Person") {
						self::syncData($f,"Flickr",$flickr->getPhotosForPerson($f["query"],self::$SyncCount));
					} elseif ($f["type"] == "Search") {
						self::syncData($f,"Flickr",$flickr->searchPhotos($f["query"],self::$SyncCount));
					} elseif ($f["type"] == "Location") {
						$query = json_decode($f["query"],true);
						$radius = is_numeric($query["radius"]) ? $query["radius"] : self::$DefaultLocationRadius;
						self::syncData($f,"Flickr",$flickr->getPhotosByLocation($query["latitude"],$query["longitude"],$radius,"mi",self::$SyncCount));
					}
				} elseif ($f["service"] == "Facebook") {
					$facebook = isset($facebook) ? $facebook : new BigTreeFacebookAPI;

					if ($f["type"] == "Page") {
						$response = $facebook->callUncached($f["query"]."/posts?fields=id,message,type,picture,link,actions,created_time,updated_time&limit=".self::$SyncCount);
						// We're going to emulate the response from the more mature APIs
						$data = new stdClass;
						$data->Results = array();
						foreach ($response->data as $item) {
							$result = new stdClass;
							$result->ID = $item->id;
							$result->Message = $item->message;
							$result->Type = $item->type;
							$result->Picture = $item->picture;
							$result->Link = $item->link;
							$result->URL = $item->actions[0]->link;
							$result->CreatedAt = date("Y-m-d H:i:s",strtotime($item->created_time));
							$result->UpdatedAt = date("Y-m-d H:i:s",strtotime($item->updated_time));
							$data->Results[] = $result;
						}
						self::syncData($f,"Facebook",$data);
					}
				}
			}

			// Get view information for manually caching the records
			$view = BigTreeAutoModule::getViewForTable("btx_social_feed_stream");
			$parsers = array();
			foreach ($view["fields"] as $key => $field) {
				if ($field["parser"]) {
					$parsers[$key] = $field["parser"];
				}
			}

			foreach (self::$ItemsToCache as $i) {
				BigTreeAutoModule::cacheRecord($i,$view,$parsers,array(),$i);
			}
		}

		/*
			Function: syncData
				Processes data retrieved from APIs into the stream table.
		*/

		static protected function syncData($query,$service,$data) {
			if (is_array($data->Results)) {
				// If we have results, let's find out what categories they need to be tagged to.
				$categories = array();
				$cq = sqlquery("SELECT * FROM btx_social_feed_query_categories WHERE `query` = '".$query["id"]."'");
				while ($cf = sqlfetch($cq)) {
					$categories[] = $cf["category"];
				}

				foreach ($data->Results as $r) {
					$id = sqlescape($r->ID);
					// Check for existing
					$existing = sqlfetch(sqlquery("SELECT id FROM btx_social_feed_stream WHERE service = '$service' AND service_id = '$id'"));
					if (!$existing) {
						$data = sqlescape(json_encode($r));
						if ($r->Timestamp) {
							$date = sqlescape($r->Timestamp);
						} elseif ($r->CreatedAt) {
							$date = sqlescape($r->CreatedAt);
						} elseif ($r->Dates->Posted) {
							$date = sqlescape($r->Dates->Posted);
						} else {
							$date = date("Y-m-d H:i:s");
						}
						sqlquery("INSERT INTO btx_social_feed_stream (`date`,`service`,`service_id`,`data`,`approved`) VALUES ('$date','$service','$id','$data','".self::$DefaultApprovedState."')");
						$existing["id"] = sqlid();
						self::$ItemsToCache[] = array("id" => sqlid(),"date" => $date,"service" => $service,"service_id" => $id,"data" => json_encode($r),"approved" => self::$DefaultApprovedState);
					}

					// Tag to categories
					foreach ($categories as $c) {
						sqlquery("DELETE FROM btx_social_feed_stream_categories WHERE item = '".$existing["id"]."' AND category = '$c'");
						sqlquery("INSERT INTO btx_social_feed_stream_categories (`item`,`category`) VALUES ('".$existing["id"]."','$c')");
					}

					// Tag to the query
					sqlquery("DELETE FROM btx_social_feed_stream_queries WHERE `item` = '".$existing["id"]."' AND `query` = '".$query["id"]."'");
					sqlquery("INSERT INTO btx_social_feed_stream_queries (`item`,`query`) VALUES ('".$existing["id"]."','".$query["id"]."')");
				}
			}
		}
	}