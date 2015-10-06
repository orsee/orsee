<?php
// part of orsee. see orsee.org

if ($proceed) {
        echo '
            <br><BR><BR>
            <center>';

        if (!(preg_match("(admin_login|admin_logout|index.php)",thisdoc())))
            echo '
                '.icon('home','index.php').'<A href="index.php">'.lang('mainpage').'</a>
                <BR><BR>
                ';
        if (!(preg_match("(admin_login|admin_logout)",thisdoc())))
            echo '<A href="admin_logout.php">'.icon('logout').'<FONT COLOR=RED>'.lang('logout').'</FONT></A>';

        echo '<BR><BR></center>';

        debug_output();

    html__show_style_footer('admin');
    html__footer();
}
?>
