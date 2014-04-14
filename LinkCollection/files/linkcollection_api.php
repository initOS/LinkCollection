<?php

# MantisBT - a php based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

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
        if (!db_num_rows( $result ))
            return 0;
        return db_fetch_array( $result )['id'];
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
	var $bugnotes = array();
}



/**
 * Build the link collection array for the given bug_id.
 * Return CollectedLink class object with raw values from the tables except the field
 * The data is not filtered by VIEW_STATE !!
 * @param int $p_bug_id bug id
 * @return array array of collected links
 * @access public
 */
# inspired by bugnote_api -> function bugnote_get_all_bugnotes( $p_bug_id )
function linkcollection_get_all_collected_links( $p_bug_id ) {
	global $g_cache_collected_links, $g_cache_collected_link;

	if( !isset( $g_cache_collected_links ) ) {
		$g_cache_collected_links = array();
	}

	if( !isset( $g_cache_collected_link ) ) {
		$g_cache_collected_link = array();
	}

	if( !isset( $g_cache_collected_links[(int)$p_bug_id] ) ) {
	    $t_links = array();

	    $t_bug_bugnotes = bugnote_get_all_bugnotes( $p_bug_id );
	    $t_bug_bugnote_ids = array_map(function($a){return $a->id;}, $t_bug_bugnotes);

	    $t_relation_table = plugin_table('link_bugnote');
	    $t_link_table = plugin_table('link');
	    $query = "SELECT links.id, links.url, relation.bugnote_id
                    FROM $t_link_table AS links
                    JOIN $t_relation_table AS relation
                    ON (links.id = relation.link_id)
                    WHERE links.id IN (SELECT link_id FROM $t_relation_table
	                   WHERE bugnote_id IN (".implode(',',$t_bug_bugnote_ids)."))
	                ORDER BY links.url";
	    $t_result = db_query_bound( $query, array());

	    $last_id = -1;
	   while( $row = db_fetch_array( $t_result ) ) {
            if (!array_key_exists($row['id'],$g_cache_collected_link)){
                $t_link = new CollectedLink();
                $t_link->id = $row['id'];
                $t_link->url = $row['url'];
                $t_links[] = $t_link;
            } else {
                $t_link = $g_cache_collected_link[$row['id']];
            }
            $t_link->bugnotes[] = $row['bugnote_id'];
			$g_cache_collected_link[$t_link->id] = $t_link;
		}
		$g_cache_collected_links[(int)$p_bug_id] = $t_links;
	}

	return $g_cache_collected_links[(int)$p_bug_id];
}

