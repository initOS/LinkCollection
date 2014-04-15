<?php
	# Task 3: Ausgabe erstellen
	#	Dazu:	Template entwerfen							- entworfen
	#			Template umsetzen							- umgesetzt
	#         	collapse-Umgebung implementieren 			- implementiert
	#			expandierte Ansicht fuellen					- implementiert
	#				TODO sichtbare Links anhand der Zugriffsrechte des aktuellen Nutzers filtern
	#				TODO Sprachdatei einbinden
	#			Wenn kein Link vorhanden, Nachricht ausgeben - implementiert

echo '<link rel="stylesheet" type="text/css" href="', plugin_file( 'linkcollection.css' ), '"/>';

# get the LinkCollection data of the current bug
$t_collected_links = linkcollection_get_all_collected_links( $p_bug_id); #TODO sichtbare Links filtern
$num_links = count( $t_collected_links );
?>
<?php # LinkCollection BEGIN ?>

<a name="linkcollection" id="linkcollection"></a><br />

<?php # when expanded
collapse_open( 'linkcollection' ); ?>
	<table class="width100">
		<tr>
			<td class="form-title">
			<?php collapse_icon( 'linkcollection' );
				  echo 'Links' #TODO Sprachdatei einbinden ?>
			</td>
		</tr>

		<?php # no links
		if ( 0 == $num_links ) { ?>
			<tr>
				<td class="center">
					<?php echo 'Zu diesem Eintrag gibt es keine Links.' #TODO Sprachdatei ?>
				</td>
			</tr>
		<?php } #fi

		# for each collected link of the current bug grouped by link id
		for ( $i=0; $i < $num_links; $i++) {
			$t_collected_link = $t_collected_links[$i];?>
			<tr id="lc<?php echo $t_collected_link->id ?>">
			<?php # --- left column ------------------------------------------------------------------------?>
			     <td class='linkcollection-links'>
			         <?php echo string_display_links($t_collected_link->url);?>
			     </td>
            <?php # --- right column ------------------------------------------------------------------------?>
                <td class='linkcollection-notes'>
                    <div>
                        <span>Current issue:</span> <?php # TODO Sprachdatei einbinden
                        $t_bug = $p_bug_id;
                        $t_bugnotes = $t_collected_link->bugs[$t_bug]; ?>
                        <?php foreach ($t_bugnotes as $t_bugnote){?>
                            <span class='small'>
                                 :<a href="<?php echo string_get_bugnote_view_url($t_bug, $t_bugnote);?>"
                                 title="<?php echo lang_get( 'bugnote_link_title' );?>"><?php echo bugnote_format_id( $t_bugnote)?></a>
                            </span>
                        <?php }?>
                    </div>
                    <?php if (count($t_collected_link->bugs) > 1){ #TODO change output style similar to ~bugnote_id?>
                        <div>
                            <span>Other issues:</span><?php # TODO Sprachdatei einbinden ?>
                            <?php foreach ($t_collected_link->bugs as $t_bug => $t_bugnotes){?>
                                <?php if ($t_bug == $p_bug_id) {
                                	continue;
                                }?>
                                <?php foreach ($t_bugnotes as $t_bugnote){?>
                                    <span class='small'>
                                        <a href="<?php echo string_get_bug_view_url($t_bug);?>"
                                         title="<?php ;?>"><?php echo bug_format_id($t_bug)?></a>:<a
                                         href="<?php echo string_get_bugnote_view_url($t_bug, $t_bugnote);?>"
                                         title="<?php echo lang_get( 'bugnote_link_title' );?>"><?php echo bugnote_format_id( $t_bugnote)?></a>
                                    </span>
                                <?php }?>
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
					  echo 'Links' #TODO Sprachdatei einbinden ?>
			</td>
		</tr>
	</table>

<?php # end collapse environment
collapse_end( 'linkcollection' );