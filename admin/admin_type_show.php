<?php
// part of orsee. see orsee.org
ob_start();

$title="user_types_and_privileges";
$menu__area="options";
include ("header.php");
if ($proceed) {

    $allow=check_allow('admin_type_edit','options_main.php');

    echo '<center>
        <BR>
        <form action="admin_type_edit.php">
        <input class="button" type=submit name="new" value="'.lang('create_new').'">
        </form>';

    echo '<br>

        <table class="or_listtable" style="width: 90%;"><thead>
            <tr style="background: '.$color['list_header_background'].'; color: '.$color['list_header_textcolor'].';">
                <td>'.lang('name').'</td>
                <td>'.lang('rights').'</td>
                <td></td>
            </tr></thead>
            <tbody>';

     $query="SELECT * FROM ".table('admin_types')." ORDER BY type_name";
    $result=or_query($query);

    $shade=false;
    while ($type=pdo_fetch_assoc($result)) {

                echo '<tr class="small"';
            if ($shade) echo ' bgcolor="'.$color['list_shade1'].'"';
                                else echo ' bgcolor="'.$color['list_shade2'].'"';
            echo '>

                            <td>
                                    '.$type['type_name'].'
                            </td>
                            <td class="small">
                                    '.str_replace(",",", ",$type['rights']).'
                            </td>
                            <td>
                                    <a href="admin_type_edit.php?type_id='.$type['type_id'].'">'.lang('edit').'</a>
                            </td>
                    </tr>';

                if ($shade) $shade=false; else $shade=true;
        }

    echo '</tbody></table>

                <br><br>
        </center>';

}
include ("footer.php");
?>
