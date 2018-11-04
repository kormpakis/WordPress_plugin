	<?php
		
	/*
	Plugin Name: My first Plugin
	Plugin URI: http://localhost
	*/
	
		//Apokrypsh ths admin bar gia toys subscribers
		add_action('after_setup_theme', 'remove_admin_bar');
 
		function remove_admin_bar() {
			if (!current_user_can('administrator') && !is_admin()) {
			show_admin_bar(false);
			}
		}

		
		////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		function get_min_id($username){
			$mysqli70 = new mysqli( "localhost", "root", "", "wp" );
			$min_query = "SELECT MIN(post_id) FROM article_selection WHERE username = '$username'";
			$min_post_id = $mysqli70->query($min_query);

			$min_id = mysqli_fetch_row($min_post_id)[0];
			return $min_id;
		}		
		
		function handle_priority_records($username){
			$mysqli60 = new mysqli( "localhost", "root", "", "wp" );
			$query = "SELECT COUNT(*) FROM article_selection WHERE username = '$username'";
			$count_query = $mysqli60->query($query);
			$count = mysqli_fetch_row($count_query)[0];
			if ($count > 5){
				$min_post_id = get_min_id($username);
				$delete_query = "DELETE FROM article_id WHERE post_id = '$min_post_id'";
				$mysqli60->query($delete_query);
			}
		}
		////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
		// Oi dyo aytes synarthseis trexoyn mono otan ena post dhmosieyetai gia prwth fora
		add_action('transition_post_status', 'run_when_post_published', 10, 3);
		//add_action('transition_post_status', 'GetLastPostId', 10, 3);

		function run_when_post_published($new_status, $old_status, $post){
			if ( $new_status == 'publish' && $old_status != 'publish' ) { //Elegxei prohgoymeno kai twrino status (einai gia to add_action)
				$mysqli = new mysqli( "localhost", "root", "", "wp" );
				$sql_get_users = "SELECT ID FROM wp_users";
				$ids = $mysqli->query($sql_get_users);
				
				$sql_delete = "DELETE FROM interest_array WHERE count_tags = 263"; //testing query
				$mysqli->query($sql_delete); //testing query
				
				//Antlhsh ID toy teleytaioy dhmosieymenoy post
				$mysqli2 = new mysqli( "localhost", "root", "", "wp" );
				$recent_posts = wp_get_recent_posts( array( 'numberposts' => '1' ) );
				$thePostID = $recent_posts[0]['ID'];
				
				//Antlhsh tags toy teleytaioy dhmosieymenoy post apo to ID poy vrethike parapanw
				$mysqli3 = new mysqli( "localhost", "root", "", "wp" );
				$tags_of_new_post = get_the_tags($thePostID);
				$tags_ids = array_map(create_function('$o', 'return $o->term_id;'), $tags_of_new_post);	

				foreach ($tags_ids as &$value1) {
					$the_tags_of_new_post = intval($value1);
				}
				
				//Kathgoriopoihsh endiaferontos vasei tags, ana xrhsth
				while($row = $ids->fetch_assoc()) {
					$r_id = $row["ID"];
					$sql_order_top = "SELECT tags_id FROM interest_array WHERE user_id='$r_id' ORDER BY count_tags DESC LIMIT 3";
					$sql_order_middle = "SELECT tags_id FROM interest_array WHERE user_id='$r_id' ORDER BY count_tags DESC LIMIT 4,6";
					
					$ordered_tags_per_user_top = $mysqli->query($sql_order_top);
					$top_priorities_arrays = mysqli_fetch_all($ordered_tags_per_user_top,MYSQLI_ASSOC);	
					$top_priorities = array_column($top_priorities_arrays,'tags_id');
					
					$ordered_tags_per_user_middle = $mysqli->query($sql_order_middle);
					$middle_priorities_arrays = mysqli_fetch_all($ordered_tags_per_user_middle,MYSQLI_ASSOC);
					$middle_priorities = array_column($middle_priorities_arrays,'tags_id');
					
					$result = array_intersect($top_priorities, $tags_ids);
					if(empty($result)) {
						$result2 = array_intersect($middle_priorities, $tags_of_new_post);
						if(empty($result2)) {
							return 3; //TIPOTA
						}
						else { //if(!empty($result2)) 
							$mysqli20 = new mysqli( "localhost", "root", "", "wp" );
							$user = get_user_by('id', $r_id);
							$username = $user->user_login;
							handle_priority_records($username);
							$query20 = $mysqli20->query("INSERT INTO article_selection (user_id, username, post_id, priority) VALUES ('$r_id', '$username', '$thePostID', 2)");
							$mysqli20->close(); //EINAI STA MIDDLE
						}
					}
					else {//if(!empty($result1)){
						// 1; //EINAI STA TOP
						$mysqli30 = new mysqli( "localhost", "root", "", "wp" );
						$user = get_user_by('id', $r_id);
						$username = $user->user_login;
						handle_priority_records($username);
						$query30 = $mysqli30->query("INSERT INTO article_selection (user_id, username, post_id, priority) VALUES ('$r_id', '$username', '$thePostID', 1)");
						$mysqli30->close(); //EINAI STA TOP
					}
					}
				$mysqli->close();	
			}
		}
		
		add_action('rest_api_init', 'get_ontology');
		function get_ontology(){
			register_rest_route('ontology/picks/', '/user/(?P<username>[a-zA-Z0-9-]+)',array(
				'methods' => GET,
				'callback' => 'get_ontology_picks',
				'args' => ['username']));
		}
		
		function get_ontology_picks($data){
			$username = $data['username'];
			$user = get_user_by('login', $username);
			$mysqli_user = new mysqli( "localhost", "root", "", "wp" );
			$query_ontology = "SELECT artist_name FROM ontology_picks WHERE user = '$username'";
			$query_user = $mysqli_user->query($query_ontology);
			$user_pick = mysqli_fetch_row($query_user)[0];

			$tag = get_term_by('name', $user_pick, 'post_tag', $filter = 'utf8_encode');
			$tag_id = $tag->term_id;

			//Get the last 10 posts of the user's pick
			
			$request1 = new WP_REST_Request( 'GET', '/wp/v2/posts' );
			$request1->set_query_params(array(
					'tags' => $tag_id,
					'per_page' => 2));
			$response1 = rest_do_request( $request1 );
			$response_array1 = json_decode(json_encode($response1, true), true);
			$response_array_data1 = $response_array1["data"];
			echo json_encode($response_array_data1, true);
		}		
		///////////////////////////--------------------------------------------------------------------------------///////////////////////////	
		
		add_action('rest_api_init', 'get_user_id');
		function get_user_id(){
			register_rest_route('userid/','user/(?P<username>[a-zA-Z0-9-]+)', array(
				'methods' => GET,
				'callback' => 'get_this_id',
				'args' => [ 'username' ]));
		}
		
		function get_this_id($data){
			$username = $data['username'];			
			$user = get_user_by('login', $username);
			return $user;
		}
		
		add_action('rest_api_init', 'get_all_data');
		
		/*function get_all_data_test(){
			register_rest_route('suggestions/', 'user/(?P<id>\d+)', array(
			'methods' => GET,
			'callback' => 'get_all',
			'args' =>  [ 'id' ]  ));
		}*/
		
		function get_all_data(){
			register_rest_route('suggestions/', '/user/(?P<username>[a-zA-Z0-9-]+)', array(
			'methods' => GET,
			'callback' => 'get_id',
			'args' =>  [ 'username' ]  ));
		}
		
		function get_id($data){
			$username = $data['username'];
			$user = get_user_by('login', $username);
			get_all($user->ID);
		}

		function get_all($data){
			//Get the last 10 posts of the website
			$request = new WP_REST_Request( 'GET', '/wp/v2/posts' );
			$request->set_param( 'per_page', 10 );	
			$response = rest_do_request( $request );
			$response_array = json_decode(json_encode($response, true), true);
			$response_array_data = $response_array["data"];
			
			//Get the suggested articles for the user 
			//First you need to get the ids of these posts
			$mysqli_suggested = new mysqli( "localhost", "root", "", "wp" );
			$user_id = $data/*['id']*/;
			$query_suggested_posts_id_init = "SELECT post_id FROM article_selection WHERE user_id=%u";
			$query_suggested_posts_id = sprintf($query_suggested_posts_id_init,$user_id);
			$suggested = $mysqli_suggested->query($query_suggested_posts_id);
			$suggested_posts_ids_array = [];
			
			while ($row = mysqli_fetch_assoc($suggested)){
				array_push($suggested_posts_ids_array,$row);
			}
			$suggested_posts_ids = array_column($suggested_posts_ids_array,'post_id');
			//echo json_encode($suggested_posts_ids);
			//All the suggested posts's ids are in $suggested_posts_ids
			
			foreach ($suggested_posts_ids as $value){
				$article_id = strval($value);
				$link1 = "/wp/v2/posts/%u";
				$link = sprintf($link1,$article_id);
				$request1 = new WP_REST_Request('GET', $link);
				$request1->set_param( 'per_page', 10 );
				$response1 = rest_do_request( $request1 );
				$response_array1 = json_decode(json_encode($response1, true), true);
				$response_array1_data = $response_array1["data"];
				$count = sizeof($response_array_data);
				$response_array_data[$count] = $response_array1_data;
			}
			echo json_encode($response_array_data, true);			
		}


		
		add_action('wp_head', 'your_function_name');
		function your_function_name(){
		?>
		<?php
		//Changes - Phase 1: Travame ta tags twn arthrwn poy diavazei o kathe xrhsths kai ta apothikeyoyme sto database	
		$mysqli = new mysqli("localhost", "root", "", "wp");
			
		/*function debug_to_console( $data ) {
			$output = $data;
			if ( is_array( $output ) )
				$output = implode( ',', $output);

			echo "<script>console.log( 'Debug Objects: " . $output . "' );</script>";
		}*/

		$user_id = intval(get_current_user_id());
		//debug_to_console("The user is ".$user_id);
		$url_test = get_permalink();
		//debug_to_console("The url is ".$url_test);

		//Tsekaroyme ton pinaka gi ayton ton xrhsth kai ayto to tag

		if ( $url_test == 'http://localhost/wordpress/' OR $user_id == 0 ){
			//do nothing
			//debug_to_console("eimai to url ".$url_test);
			}

		else {		
				$tag_objects = get_the_tags($post->ID);
				$tags_id = array_map(create_function('$o', 'return $o->term_id;'), $tag_objects);	
				//print_r( $tags_id );

				foreach ($tags_id as &$value) {
					$tag = intval($value);
					$check_interest = $mysqli->query("SELECT * FROM interest_array WHERE user_id = '$user_id' AND tags_id = '$tag'");
				
					if (is_null($check_interest->fetch_assoc())) {
						$sql = "INSERT INTO interest_array (user_id, tags_id, count_tags) VALUES ('$user_id', '$tag', 1)";
						$mysqli->query($sql);
					} 
					else {
						$sql_update = "UPDATE interest_array SET count_tags=count_tags+1 WHERE user_id = '$user_id' AND tags_id = '$tag'";
						$mysqli->query($sql_update);
					}
				}

				$mysqli->close();
			}	
		?>		<?php
				};		
?>