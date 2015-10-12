<?php
// part of orsee. see orsee.org

echo '<BR><BR><BR>';
if ($settings['support_mail']) {
    echo '<center>
        <P class="small" align=center>';
    echo lang('for_questions_contact_xxx');
    echo ' ';
    helpers__scramblemail($settings['support_mail']);
    echo $settings['support_mail'];
    echo '</A>';
    echo '.<BR><BR><BR></CENTER>';
}

    debug_output();

    html__show_style_footer('public');
    html__footer();
?>