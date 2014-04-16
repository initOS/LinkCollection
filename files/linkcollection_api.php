<?php

/*
 This file is part of LinkCollection.

BoxesAsTabs is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

BoxesAsTabs is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with BoxesAsTabs.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * LinkCollection API
 *
 * @package LinkCollection
 * @author Katja Matthes (katja.matthes@initos.com)
 * @copyright Copyright (C) 2014  initOS GmbH & Co. KG
 * @link http://www.initos.com
 */

    /**
     * Collect the link in.
     * @param char link's url
     * @return int - ID of link
     */
    function linkcolletion_collect($t_url){
        # Get and return id if link is already stored
        $stored = linkcollection_get_link_id($t_url);
        if ($stored){
            return $stored;
        }
        # Else: store link and return id
        db_query_bound('INSERT INTO ' . plugin_table('link') . '(url) VALUES (?)', array($t_url) );
        return db_insert_id(plugin_table('link'));
    }

    /**
     * Returns link's ID
     * @param char link's url
     * @return int - ID of link
     */
    function linkcollection_get_link_id($t_url){
        $t_link_table = plugin_table('link');
        $query = "SELECT t.id FROM $t_link_table t WHERE t.url LIKE" . db_param();
        $result = db_query_bound( $query, array($t_url));
        if (!db_num_rows( $result )){
            return 0;
        }
        $row = db_fetch_array( $result );
        return $row['id'];
    }

    /**
     * Determine if a link is allready in bugnote's linkcollection.
     * @param integer Tag ID
     * @param integer Bug ID
     * @return boolean True if the tag is attached
     */
    function linkcollection_bugnote_is_collected( $p_link_id, $p_bugnote_id ) {
        $t_link_bugnote_table = plugin_table('link_bugnote');

        $query = "SELECT * FROM $t_link_bugnote_table
        WHERE link_id=" . db_param() . " AND bugnote_id=" . db_param();
        $result = db_query_bound( $query, Array( $p_link_id, $p_bugnote_id ) );
	    return( db_num_rows( $result ) > 0 );
	}


/**
 * Collected Link Structure Definition
 */
class CollectedLink {
    var $id;
	var $url;
	var $projects = array();           //array with project ids
	var $bugs = array();               //array of array with bug ids, key is project_id
	var $bugnotes = array();           //array of array with bugnote ids, key is bug_id
}



/**
 * Build the link collection array for the given bug_id.
 * Return CollectedLink class object with raw values from the tables.
 * The data is not filtered by VIEW_STATE !!
 * @param int $p_bug_id bug id
 * @return array array of collected links
 * @access public
 */
# inspired by bugnote_api -> function bugnote_get_all_bugnotes( $p_bug_id )
function linkcollection_get_collection_bug( $p_bug_id, $p_thoroughly_mode = FALSE ) {
	global $g_cache_collected_bug_links;

	if( !isset( $g_cache_collected_bug_links ) ) {
		$g_cache_collected_bug_links = array();
	}

	if( !isset( $g_cache_collected_bug_links[(int)$p_bug_id] ) ) {
        #get affected bugnote ids as array
	    $t_bug_bugnotes = bugnote_get_all_bugnotes( $p_bug_id );
	    $t_bug_bugnote_ids = array_map(function($a){return $a->id;}, $t_bug_bugnotes);


	    $g_cache_collected_bug_links[(int)$p_bug_id] = _linkcollection_get_collection($t_bug_bugnote_ids, $p_thoroughly_mode);
	}

	return $g_cache_collected_bug_links[(int)$p_bug_id];
}

/**
 * Build the link collection array for the given project_id.
 * Return CollectedLink class object with raw values from the table.
 * The data is not filtered by VIEW_STATE !!
 * @param int $p_project_id project id
 * @return array array of collected links
 * @access public
 */
function linkcollection_get_collection_project( $p_project_id, $p_thoroughly_mode = FALSE ) {
    global $g_cache_collected_project_links;

    if( !isset( $g_cache_collected_project_links ) ) {
        $g_cache_collected_project_links = array();
    }

    if( !isset( $g_cache_collected_project_links[(int)$p_project_id] ) ) {
        #get affected bugnote ids as array
        $c_project_id = db_prepare_int($p_project_id);
        $t_bugnote_table = db_get_table('mantis_bugnote_table');
        $t_bug_table = db_get_table('mantis_bug_table');

        if( ALL_PROJECTS != $c_project_id ) {
    		$t_project_where = "WHERE bug.project_id = $c_project_id";
    	} else {
    		$t_project_where = '';
        }
        $query = "SELECT bugnote.id AS bugnote_id, bug.id AS bug_id
        FROM $t_bugnote_table AS bugnote
        LEFT JOIN $t_bug_table AS bug
        ON (bugnote.bug_id = bug.id)
        $t_project_where";

        $t_result = db_query_bound( $query, array());
        $t_bug_bugnote_ids = array();

        while( $row = db_fetch_array( $t_result ) ) {
            $t_bug_bugnote_ids[] = $row['bugnote_id'];
        }

        $g_cache_collected_project_links[(int)$p_project_id] = _linkcollection_get_collection($t_bug_bugnote_ids, $p_thoroughly_mode);
    }

    return $g_cache_collected_project_links[(int)$p_project_id];




}
/**
 * Build the link collection array for the given bugnotes.
 * Return CollectedLink class object with raw values from the table.
 * The data is not filtered by VIEW_STATE !!
 * @param int $p_bug_bugnote_ids ids of bugnotes
 * @param $p_thoroughly_mode true - also collect bugnotes from other bugs or projects
 * @return array array of collected links
*/
function _linkcollection_get_collection($p_bug_bugnote_ids, $p_thoroughly_mode = FALSE){
    $t_links = array();
    if(!$p_bug_bugnote_ids){
        return $t_links;
    }

    global $g_cache_collected_links;

    if( !isset( $g_cache_collected_links ) ) {
        $g_cache_collected_links = array();
    }

    $t_relation_table = plugin_table('link_bugnote');
    $t_link_table = plugin_table('link');
    $t_bug_bugnote_ids = db_prepare_string(implode(',',$p_bug_bugnote_ids));

    $t_where_thoroughly = '';
    if ($p_thoroughly_mode){
        $t_where_thoroughly = "WHERE links.id IN (SELECT link_id FROM $t_relation_table
                                WHERE bugnote_id IN (".$t_bug_bugnote_ids."))";
    } else {
        $t_where_thoroughly = "WHERE bugnote_id IN (".$t_bug_bugnote_ids.")";
    }


    $query = "SELECT links.id, links.url, relation.bugnote_id
    FROM $t_link_table AS links
    JOIN $t_relation_table AS relation
    ON (links.id = relation.link_id)
    $t_where_thoroughly
    ORDER BY links.url";

    $t_result = db_query_bound( $query, array());

    while( $row = db_fetch_array( $t_result ) ) {
	    if (!array_key_exists($row['id'],$g_cache_collected_links)){ #create new CollectedLink
	    $t_link = new CollectedLink();
	    $t_link->id = $row['id'];
	    $t_link->url = $row['url'];
	    $t_links[] = $t_link;
	    $g_cache_collected_links[$t_link->id] = $t_link;
        } else {
            $t_link = $g_cache_collected_links[$row['id']]; #load CollectedLin
        }
        # collect ids of bugnote, bug and project
        $t_bug = bugnote_get_field($row['bugnote_id'], 'bug_id');
        $t_project = bug_get_field($t_bug, 'project_id');
        $t_link->projects[] = $t_project;
        $t_link->bugs[$t_project][] = $t_bug;
        $t_link->bugnotes[$t_bug][] = $row['bugnote_id'];
    }
    return $t_links;
}
