<?php
// part of orsee. see orsee.org

function content__get_content($content_name) {
    global $lang;
    $this_lang=lang('lang');
    $pars=array(':content_name'=>$content_name);
    $query = "SELECT * FROM ".table('lang')."
              WHERE content_type='public_content'
              AND content_name=:content_name";
    $line = orsee_query($query,$pars);
    return $line[$this_lang];
}


?>
