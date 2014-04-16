<?php

class LinkCollectionPlugin extends MantisPlugin {

    # Register the plugin
    function register() {
        $this->name = 'LinkCollection';                                                  # plugin's name
        $this->description = 'Collects links from notices and presents them as a list.';    # plugin's description
        $this->page = '';                                                                   # Default plugin page

        $this->version = '0.15';         # plugin version string
        $this->requires = array(        # Plugin dependencies, array of basename => version pairs
            'MantisCore' => '1.2.11',   # Should always depend on an appropriate version of MantisBT
                );

        $this->author = 'Katja Matthes';                # Author/team name
        $this->contact = 'katja.matthes-at-initos.com';    # Author/team e-mail address
        $this->url = 'http://www.initos.com';           # Support webpage
    }

    # Hook events
    function hooks() {
        return array(
                'EVENT_BUGNOTE_ADD' => 'new_bugnote',				# Scan for Links, if a new bugnote was created
                'EVENT_BUGNOTE_DELETED' => 'delete_bugnote',        # Delete relation from link to bugnot
                'EVENT_BUGNOTE_EDIT' => 'edit_butnote',             # Reconnect links
                'EVENT_VIEW_BUG_EXTRA' => 'display_links',			# Display Links in bug view
                'EVENT_CORE_READY' => 'initialise',					# Initialise Plugin after core is ready
                'EVENT_LAYOUT_RESOURCES' => 'resources',            # Add resources (css)
                'EVENT_MENU_MAIN' => 'menu_link',      # Add menu link
        );
    }

    # Database schema
    function schema() {
           $schema[] = Array("CreateTableSQL", Array(plugin_table('link'), "
					id I NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
					bug_id I NOTNULL UNSIGNED,
					note_id I NOTNULL UNSIGNED,
					link C(225) NOTNULL
					"));
           $schema[] = Array("DropTableSQL", Array(plugin_table('link')));
           $schema[] = Array('CreateTableSQL', Array(plugin_table('link'), "
	           id  I       UNSIGNED NOTNULL PRIMARY AUTOINCREMENT,
               url C(500)  NOTNULL DEFAULT \" '' \"
	           ", Array( 'mysql' => 'ENGINE=MyISAM DEFAULT CHARSET=utf8', 'pgsql' => 'WITHOUT OIDS' ) ) );
           $schema[] = Array('CreateTableSQL', Array(plugin_table('link_bugnote'), "
	           link_id      I   UNSIGNED NOTNULL PRIMARY DEFAULT '0',
               bugnote_id   I   UNSIGNED NOTNULL PRIMARY DEFAULT '0'
	           ", Array( 'mysql' => 'ENGINE=MyISAM DEFAULT CHARSET=utf8', 'pgsql' => 'WITHOUT OIDS' ) ) );
           $schema[] = Array( 'CreateIndexSQL', Array( 'idx_link_url', plugin_table('link'), 'url' ) );
           $schema[] = Array( 'CreateIndexSQL', Array( 'idx_link_bugnote_link_id', plugin_table('link_bugnote'), 'link_id' ) );
           return $schema;
    }

    # Initialise configuration variables
    function config(){
        return array(
                'first_use' => 'false'		# to mark plugin's first use
        );
    }

    function install(){
        # set flag
        plugin_config_set('first_use', 'true');
        return TRUE;
    }

    # Uninstall Plugin
    function uninstall(){
        # clear links table
        db_query_bound('DELETE FROM ' . plugin_table('link'),array());
        db_query_bound('DELETE FROM ' . plugin_table('link_bugnote'),array());
        plugin_config_set('first_use', 'false');
    }

    # Initialise Plugin
    function initialise($p_event) {
       require_once( 'files/linkcollection_api.php' );				# include API

        # If it is plugin's first use, extract links from all existing bug notes
        if(plugin_config_get('first_use') == 'true'){
            # Get all bug notes
            $t_bug_note_table = db_get_table ( 'mantis_bugnote_table' );
            $query = "SELECT id, bug_id FROM $t_bug_note_table";
            $result = db_query_bound( $query, array ());
            $note_count = db_num_rows( $result );
            # For each note
            for( $i=0; $i<$note_count; $i++ ){
                $row = db_fetch_array( $result );
                $t_note_id = $row['id'];								# Get note id
                $this->search_and_store_links($t_note_id);	# Search for links and add them to database table
            }
             # reset flag
    		plugin_config_set('first_use', 'false');
    	}
    }

    # When a new bugnote is added, seachr links and add them to database table.
    function new_bugnote( $p_event, $p_bug_id, $p_note_id ){
        $this->search_and_store_links($p_note_id );
    }

    function delete_bugnote($p_event, $p_bug_id, $p_note_id){
        $c_bugnote_id = db_prepare_int( $p_note_id );
        $t_relation_table = plugin_table('link_bugnote');

        # Remove the relation
        $query = 'DELETE FROM ' . $t_relation_table . ' WHERE bugnote_id=' . db_param();
        db_query_bound( $query, Array( $c_bugnote_id ) );

        return true;
    }

    function edit_butnote($p_event, $p_bug_id, $p_note_id){
        # Delete connection
        $this->delete_bugnote($p_event, $p_bug_id, $p_note_id);
        # Reconnect
        $this->new_bugnote($p_event, $p_bug_id, $p_note_id);
    }

    # Display link overview in bug view
    function display_links( $p_event, $p_bug_id ){
        include('pages/links_view_inc.php');
    }

    # Search links in a given note and add them zu database table
    private function search_and_store_links($p_note_id ){
        # Get note text
        $t_text = bugnote_get_text($p_note_id);

        # Regular expression for URLs (copied from Mantis core->string.api->string_insert_hrefs(...)
        $t_url_protocol = '(?:[[:alpha:]][-+.[:alnum:]]*):\/\/';
        $t_url_hex = '%[[:digit:]A-Fa-f]{2}';
        $t_url_valid_chars = '-_.,!~*\';\/?%^\\\\:@&={\|}+$#[:alnum:]\pL';
        $t_url_chars = "(?:${t_url_hex}|[${t_url_valid_chars}\(\)\[\]])";
        $t_url_chars2 = "(?:${t_url_hex}|[${t_url_valid_chars}])";
        $t_url_chars_in_brackets = "(?:${t_url_hex}|[${t_url_valid_chars}\(\)])";
        $t_url_chars_in_parens = "(?:${t_url_hex}|[${t_url_valid_chars}\[\]])";
        $t_url_part1 = "${t_url_chars}";
        $t_url_part2 = "(?:\(${t_url_chars_in_parens}*\)|\[${t_url_chars_in_brackets}*\]|${t_url_chars2})";
        $t_url_regex = "/(${t_url_protocol}(${t_url_part1}*?${t_url_part2}+))/su";

        # Search for matches
        preg_match_all( $t_url_regex, $t_text, $t_matches_all );

        # Process all links
        foreach( $t_matches_all[0] as $t_url ){
            $t_link_id = linkcolletion_collect($t_url);
            if(!linkcollection_bugnote_is_collected($t_link_id, $p_note_id ) ) {
                db_query_bound('INSERT INTO ' . plugin_table('link_bugnote') . '(link_id, bugnote_id) VALUES (?, ?)', array($t_link_id, $p_note_id) );
            }
        }
	}

	/**
	 * Load css resource.
	 */
	public function resources($p_event) {
	    $resources = '<link rel="stylesheet" type="text/css" href="' . plugin_file("linkcollection.css") . '" />';
	    return $resources;
	}

	/**
	 * Add link to main menu
	 * @param $p_event
	 */
	function menu_link($p_event) {
	    return array (
	            '<a href="' . plugin_page('view_all_links_page') . '">' . 'Links' .'</a>'
	    );
	}




}