<?php

include("nonoutputheader.php");

html__header(true);

	if (!$_REQUEST['p']) javascript__close_window();

	if (!$participant_id) javascript__close_window();

	echo '<center>
		<BR><BR>
		<H4>'.$lang['experiment_registration'].'</H4>
		<BR><BR>
		<P align=right><A HREF="javascript:self.print()">'.$lang['print'].'</A></P>

		<TABLE width=80% border=2>
		<TR><TD colspan=2>
		'.$lang['experiments_already_registered_for'].'
		</TD>
		</TR>';
		$labs=expregister__list_registered_for($participant_id,"","print");
        $laboratories=array_unique($labs);

        if (count($laboratories)>0) {
                echo '<TR><TD colspan=2>&nbsp;<BR><BR></TD></TR>
                        <TR><TD colspan=2>';
                if (count($laboratories)==1) echo $lang['laboratory_address']; else echo $lang['laboratory_addresses'];
                echo '</TD></TR>';

                foreach ($laboratories as $laboratory_id) {
                        echo '<TR><TD valign=top>';
                        echo laboratories__get_laboratory_name($laboratory_id);
                        echo '</TD>
                                <TD>';
                        $address=laboratories__get_laboratory_address($laboratory_id);
                        echo str_replace("\n","<BR>",$address);
                        echo '</TD></TR>';
                        }
                }

	echo '</TABLE></center>
		<BR><BR>';

html__footer();

?>
