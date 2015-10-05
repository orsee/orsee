<?php
// part of orsee. see orsee.org

function faq__load_question($faq_id="") {
    $pars=array(':faq_id'=>$faq_id);
    $query="SELECT * from ".table('lang')."
            WHERE content_type='faq_question'
            AND content_name= :faq_id";
    $line=orsee_query($query,$pars);
    return $line;
}

function faq__load_answer($faq_id="") {
    $pars=array(':faq_id'=>$faq_id);
    $query="SELECT * from ".table('lang')."
            WHERE content_type='faq_answer'
            AND content_name= :faq_id";
    $line=orsee_query($query,$pars);
    return $line;
}

?>
