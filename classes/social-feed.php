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
			Function: getCategoryByRoute
				Returns a category for the provided route.

			Returns:
				A category or false if no match exists.
		*/

		static function getCategoryByRoute($route) {
			return sqlfetch(sqlquery("SELECT * FROM btx_social_feed_categories WHERE route = '".sqlescape($route)."'"));
		}

		/*
			Function: getPage
				Returns a page of recent items from the stream.

			Parameters:
				page - The page number to include (starts at 1)
				count - The number to return per page (defaults to 10)
				include_unapproved - Whether to include unapproved items (defaults to false)

			Returns:
				An array of social objects
		*/

		static function getPage($page = 1, $count = 10, $include_unapproved = false) {
			$page = $page ? ($page - 1) : 0;

			return static::getRecent(($count * $page).", $count", $include_unapproved);
		}

		/*
			Function: getPageFromService
				Returns a page of recent items from the stream from a given service.

			Parameters:
				service - Service to return (Flickr, Google+, Instagram, Twitter, YouTube)
				page - The page number to include (starts at 1)
				count - The number to return per page (defaults to 10)
				include_unapproved - Whether to include unapproved items (defaults to false)

			Returns:
				An array of social objects
		*/

		static function getPageFromService($service, $page = 1, $count = 10, $include_unapproved = false) {
			$page = $page ? ($page - 1) : 0;

			return static::getRecentFromService(($count * $page).", $count", $service, $include_unapproved);
		}

		/*
			Function: getPageFromServiceInCategory
				Returns a page of recent items from the stream from a given service and category.

			Parameters:
				service - Service to return (Flickr, Google+, Instagram, Twitter, YouTube)
				category - A category ID
				page - The page number to include (starts at 1)
				count - The number to return per page (defaults to 10)
				include_unapproved - Whether to include unapproved items (defaults to false)

			Returns:
				An array of social objects
		*/

		static function getPageFromServiceInCategory($service, $category, $page = 1, $count = 10, $include_unapproved = false) {
			$page = $page ? ($page - 1) : 0;
			$service = sqlescape($service);
			$category = sqlescape($category);

			return self::_get("SELECT s.* FROM btx_social_feed_stream AS `s` 
								 JOIN btx_social_feed_stream_categories AS `c` 
								 ON s.id = c.item 
							   WHERE s.service = '$service'
							     AND c.category = '$category'".
							   	 (!$include_unapproved ? " AND s.approved = 'on'" : "")." 
							   ORDER BY s.date DESC LIMIT $count");
		}
		
		/*
			Function: getPageCount
				Returns the number of pages in the stream.

			Parameters:
				count - The number to return per page (defaults to 10)
				include_unapproved - Whether to include unapproved items (defaults to false)

			Returns:
				An array of social objects
		*/

		static function getPageCount($count = 10, $include_unapproved = false) {
			$f = sqlfetch(sqlquery("SELECT COUNT(*) AS `count` FROM btx_social_feed_stream ".(!$include_unapproved ? "WHERE approved = 'on'" : "")));
			$pages = ceil($f["count"] / $count);
			
			return $pages ? $pages : 1;
		}

		/*
			Function: getPageCountFromService
				Returns the number of pages in the stream from a given service.

			Parameters:
				service - Service to return (Flickr, Google+, Instagram, Twitter, YouTube)
				count - The number to return per page (defaults to 10)
				include_unapproved - Whether to include unapproved items (defaults to false)

			Returns:
				An array of social objects
		*/

		static function getPageCountFromService($service, $count = 10, $include_unapproved = false) {
			$f = sqlfetch(sqlquery("SELECT COUNT(*) AS `count` FROM btx_social_feed_stream WHERE service = '".sqlescape($service)."'".(!$include_unapproved ? " AND approved = 'on'" : "")));
			$pages = ceil($f["count"] / $count);
			
			return $pages ? $pages : 1;
		}

		/*
			Function: getPageCountFromServiceInCategory
				Returns the number of pages in the stream from a given service and category.

			Parameters:
				service - Service to return (Flickr, Google+, Instagram, Twitter, YouTube)
				category - A category ID
				count - The number to return per page (defaults to 10)
				include_unapproved - Whether to include unapproved items (defaults to false)

			Returns:
				An array of social objects
		*/

		static function getPageCountFromServiceInCategory($service, $category, $count = 10, $include_unapproved = false) {
			$results = sqlrows(sqlquery("SELECT DISTINCT(item) FROM btx_social_feed_stream_categories JOIN btx_social_feed_stream
										 ON btx_social_feed_stream_categories.item = btx_social_feed_stream.id
										 WHERE service = '".sqlescape($service)."'
										   AND category = '".sqlescape($category)."'"));
			$pages = ceil($results / $count);
			
			return $pages ? $pages : 1;
		}

		/*
			Function: getPageCountInCategory
				Returns the number of pages of items in a given category in the stream.

			Parameters:
				category - A category ID
				count - The number to return per page (defaults to 10)
				include_unapproved - Whether to include unapproved items (defaults to false)

			Returns:
				An array of social objects
		*/

		static function getPageCountInCategory($category, $count, $include_unapproved = false) {
			$results = sqlrows(sqlquery("SELECT DISTINCT(item) FROM btx_social_feed_stream_categories WHERE category = '".sqlescape($category)."'"));
			$pages = ceil($results / $count);
			
			return $pages ? $pages : 1;
		}

		/*
			Function: getPageInCategory
				Returns a page of items from the stream in a specific category.

			Parameters:
				category - Category ID
				page - The page number to include (starts at 1)
				count - Number of results to return
				include_unapproved - Whether to include unapproved items

			Returns:
				An array of social objects.
		*/

		static function getPageInCategory($category, $page, $count, $include_unapproved = false) {
			$page = $page ? ($page - 1) : 0;

			return static::getRecentInCategory(($count * $page).", $count", $category, $include_unapproved);
		}

		/*
			Function: getRecent
				Returns recent items from the stream.

			Parameters:
				count - Number of results to return
				include_unapproved - Whether to include unapproved items (defaults to false)

			Returns:
				An array of social objects.
		*/

		static function getRecent($count,$include_unapproved = false) {
			return self::_get("SELECT * FROM btx_social_feed_stream ".(!$include_unapproved ? "WHERE approved = 'on'" : "")." ORDER BY date DESC LIMIT $count");
		}

		/*
			Function: getRecentInCategory
				Returns recent items from the stream in a specific category.

			Parameters:
				count - Number of results to return
				category - Category ID
				include_unapproved - Whether to include unapproved items

			Returns:
				An array of social objects.
		*/

		static function getRecentInCategory($count,$category,$include_unapproved = false) {
			return self::_get("SELECT s.* FROM btx_social_feed_stream AS `s` JOIN btx_social_feed_stream_categories AS `c` ON s.id = c.item WHERE c.category = '$category'".(!$include_unapproved ? " AND s.approved = 'on'" : "")." ORDER BY s.date DESC LIMIT $count");
		}

		/*
			Function: getRecentInCategoryFromService
				Returns recent items from the stream in a specific category from a specific service.

			Parameters:
				count - Number of results to return
				category - Category ID
				service - Service to return (Flickr, Google+, Instagram, Twitter, YouTube)
				include_unapproved - Whether to include unapproved items

			Returns:
				An array of social objects.
		*/

		static function getRecentInCategoryFromService($count,$category,$service,$include_unapproved = false) {
			return self::_get("SELECT s.* FROM btx_social_feed_stream AS `s` JOIN btx_social_feed_stream_categories AS `c` ON s.id = c.item WHERE c.category = '$category' AND s.service = '$service'".(!$include_unapproved ? " AND s.approved = 'on'" : "")." ORDER BY s.date DESC LIMIT $count");
		}

		/*
			Function: getRecentFromService
				Returns recent items from the stream from a specified service.

			Parameters:
				count - Number of results to return
				service - Service to return (Flickr, Google+, Instagram, Twitter, YouTube)
				include_unapproved - Whether to include unapproved items

			Returns:
				An array of social objects.
		*/

		static function getRecentFromService($count,$service,$include_unapproved = false) {
			return self::_get("SELECT * FROM btx_social_feed_stream WHERE service = '$service'".(!$include_unapproved ? " AND approved = 'on'" : "")." ORDER BY date DESC LIMIT $count");
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

		// Filter functions for admin
		static function filterActive($item) {
			return $item["ignored"] ? false : true;
		}

		static function filterIgnored($item) {
			return $item["ignored"] ? true : false;
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
					$params = array("tweet_mode" => "extended");

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
						$results = $youtube->getChannelVideos($f["query"],self::$SyncCount);
					} elseif ($f["type"] == "Search") {
						$results = $youtube->searchVideos($f["query"],self::$SyncCount,"date");
					}
					
					// Add channel data
					foreach ($results->Results as &$result) {
						$result->Channel = $youtube->getChannel(false, $result->ChannelID);
					}
					
					self::syncData($f,"YouTube",$results);
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
						// Look up info on the page first to get username
						$user_response = $facebook->callUncached($f["query"]."?fields=username,link");

						// Get posts
						$response = $facebook->callUncached($user_response->id."/posts?fields=id,message,type,picture,link,actions,created_time,updated_time&limit=".self::$SyncCount);

						// We're going to emulate the response from the more mature APIs
						$data = new stdClass;
						$data->Results = array();
						foreach ($response->data as $item) {
							$result = new stdClass;
							$result->ID = $item->id;
							$result->Link = $item->link;
							$result->Message = $item->message;
							$result->PageLink = $user_response->link;
							$result->PageUsername = $user_response->username;
							$result->Picture = $item->picture;
							$result->Type = $item->type;
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
			if (BIGTREE_REVISION > 400) {
				$modules = BigTreeJSONDB::getAll("modules");

				foreach ($modules as $module) {
					if (is_array($module["views"])) {
						foreach ($module["views"] as $view) {
							if ($view["table"] == "btx_social_feed_stream" && $view["title"] != "Ignored") {
								break 2;
							}
						}
					}
				}
			} else {
				$view = sqlfetch(sqlquery("SELECT * FROM bigtree_module_views WHERE `table` = 'btx_social_feed_stream' AND `title` != 'Ignored'"));
				$view["fields"] = json_decode($view["fields"], true);
			}

			if ($view && $view["table"] == "btx_social_feed_stream") {
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
					} else {
						sqlquery("UPDATE btx_social_feed_stream SET data = '".sqlescape(json_encode($r))."' WHERE id = '".$existing["id"]."'");
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
