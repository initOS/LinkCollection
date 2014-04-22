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
     * @package LinkCollection
     * @copyright Copyright (C) 2014  Katja Matthes - katja.matthes-at-initos.com
     * @link http://www.initos.com
     */
     /**
      * MantisBT Core API's
      */
    require_once( 'core.php' );

    require_once( 'compress_api.php' );
    require_once( 'last_visited_api.php' );

    auth_ensure_user_authenticated();

    //$f_page_number        = gpc_get_int( 'page_number', 1 );

    # Get Project Id and set it as current
    $t_project_id = gpc_get_int( 'project_id', helper_get_current_project() );
    if( ( ALL_PROJECTS == $t_project_id || project_exists( $t_project_id ) )
     && $t_project_id != helper_get_current_project()
    ) {
        helper_set_current_project( $t_project_id );
        # Reloading the page is required so that the project browser
        # reflects the new current project
        print_header_redirect( $_SERVER['REQUEST_URI'], true, false, true );
    }

    compress_enable();

    # don't index view issues pages
    html_robots_noindex();

    html_page_top1( 'Links' );

    html_page_top2();

    print_recently_visited();

    include( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'view_all_inc.php' );

    html_page_bottom();
