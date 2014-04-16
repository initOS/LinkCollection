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
 * MantisBT Core API's
 */
require_once( 'core.php' );

/**
 * requires helper_api
 */
require_once( 'helper_api.php' );

$t_project_id = helper_get_current_project();

$t_collected_links = linkcollection_get_collection_project($t_project_id, FALSE);
?>
<!-- Link list -->
<br/>
<table id="linklist" class="width100" cellspacing="1">
    <tbody>
        <tr class="row-category">
            <td><?php echo plugin_lang_get('url');?></td>
            <td><?php echo plugin_lang_get('bugnotes');?></td>
        </tr>
        <tr class="spacer">
            <td colspan="2"/>
        </tr>
        <?php foreach ($t_collected_links AS $t_collected_link){?>
            <tr border="1">
                <td class="linkcollection-links"><?php echo string_display_links($t_collected_link->url);?></td>
                <td class="linkcollection-notes">
                    <?php foreach ($t_collected_link->bugnotes as $t_bug => $t_bugnotes){?>
                        <?php foreach ($t_bugnotes as $t_bugnote){?>
                            <span class='small'>
                                <?php echo string_process_bugnote_link(config_get('bugnote_link_tag').$t_bugnote);?>
                            </span>
                        <?php }?>
                    <?php }?>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>