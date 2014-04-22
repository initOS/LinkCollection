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

# get the LinkCollection data of the current bug
$t_collected_links = linkcollection_get_collection_bug( $p_bug_id, TRUE); #TODO sichtbare Links filtern
$num_links = count( $t_collected_links );
$t_normal_date_format = config_get( 'normal_date_format' );
?>
<?php # LinkCollection BEGIN ?>

<a name="linkcollection" id="linkcollection"></a><br />

<?php # when expanded
collapse_open( 'linkcollection' ); ?>
    <table class="width100">
        <tr>
            <td class="form-title">
            <?php collapse_icon( 'linkcollection' );
                  echo plugin_lang_get('Links'); ?>
            </td>
        </tr>

        <?php # no links
        if ( 0 == $num_links ) { ?>
            <tr>
                <td class="center">
                    <?php echo plugin_lang_get('no_links'); ?>
                </td>
            </tr>
        <?php } #fi

        # for each collected link of the current bug grouped by link id
        for ( $i=0; $i < $num_links; $i++) {
            $t_collected_link = $t_collected_links[$i];?>
            <tr id="lc<?php echo $t_collected_link->id ?>">
            <?php # --- left column ------------------------------------------------------------------------?>
                 <td class='linkcollection-links'>
                     <?php echo string_display_links($t_collected_link->url);?><br/>
                     <?php /* ?>
                     <span class="small"><?php echo date( $t_normal_date_format, $t_collected_link->first_submit);?> -
                         <?php echo date( $t_normal_date_format, $t_collected_link->last_submit);?>
                     </span>
                     <?php */?>
                 </td>
            <?php # --- right column ------------------------------------------------------------------------?>
                <td class='linkcollection-notes'>
                    <div>
                        <span><?php echo plugin_lang_get('current_issue');?></span>
                        <?php $t_bugnotes = $t_collected_link->bugnotes[$p_bug_id];
                        foreach ($t_bugnotes as $t_bugnote){?>
                            <span class='small'>
                                 :<a href="<?php echo string_get_bugnote_view_url($t_bug, $t_bugnote);?>"
                                 title="<?php echo lang_get( 'bugnote_link_title' );?>"><?php echo bugnote_format_id( $t_bugnote)?></a>
                            </span>
                        <?php }?>
                    </div>
                    <?php if (count($t_collected_link->bugs) > 1){ ?>
                        <div>
                            <span><?php echo plugin_lang_get('other_issues');?></span>
                            <?php foreach ($t_collected_link->bugnotes as $t_bug => $t_bugnotes){
                                if ($t_bug == $p_bug_id) {
                                    continue;
                                } ?>
                                <?php foreach ($t_bugnotes as $t_bugnote){?>
                                    <span class='small'>
                                        <?php echo string_process_bugnote_link(config_get('bugnote_link_tag').$t_bugnote);?>
                                    </span>
                                <?php } ?>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </td>
            </tr>
        <?php } # end loop ?>
    </table>


<?php #when collapsed
collapse_closed( 'linkcollection' ); ?>
    <table class="width100">
        <tr>
            <td class="form-title">
                <?php collapse_icon( 'linkcollection' );
                      echo plugin_lang_get('Links'); ?>
            </td>
        </tr>
    </table>

<?php # end collapse environment
collapse_end( 'linkcollection' );