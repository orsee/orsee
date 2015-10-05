<?php
// part of orsee. see orsee.org
ob_start();

$menu__area="options";
$title="my_rights";
include ("header.php");
if ($proceed) {
    echo '<center>';
    $rights=$expadmindata['rights'];
    echo '<TABLE class="or_listtable"><thead>
        <TR style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">
            <TD>'.lang('authorization').'</TD>
            <TD>'.lang('description').'</TD>
        </TR></thead>
        <tbody>';

    $shade=true; $lastclass="";
    foreach ($system__admin_rights as $right) {
        $line=explode(":",$right);
        if (isset($rights[$line[0]]) && $rights[$line[0]]) {
            $tclass=str_replace(strstr($line[0],"_"),"",$line[0]);
            if ($tclass!=$lastclass) {
                echo '<TR><TD colspan=4>&nbsp;</TD></TR>';
                $lastclass=$tclass; //$shade=true;
            }
            echo '  <TR bgcolor="';
            if ($shade) echo $color['list_shade1']; else echo $color['list_shade2'];
            echo '">
                    <TD class="small" align=left>'.$line[0].'</TD>
                    <TD class="small">'.$line[1].'</TD>
              </TR>';
            if ($shade) $shade=false; else $shade=true;
        }
    }
    echo '  </tbody></TABLE>
        </center>
        <BR><BR>';

}
include ("footer.php");

?>
