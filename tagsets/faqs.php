<?php

// faq functions. part of orsee. see orsee.org

function faq__load_question($faq_id="") {
	$query="SELECT * from ".table('lang')." 
		WHERE content_type='faq_question' 
		AND content_name='".$faq_id."'";
	$result=mysql_query($query);
	$line=mysql_fetch_assoc($result);
	return $line;
}

function faq__load_answer($faq_id="") {
        $query="SELECT * from ".table('lang')."
                WHERE content_type='faq_answer'
                AND content_name='".$faq_id."'";
        $result=mysql_query($query);
        $line=mysql_fetch_assoc($result);
        return $line;
}

?>
