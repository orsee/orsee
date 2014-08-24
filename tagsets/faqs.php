<?php
// part of orsee. see orsee.org

function faq__load_question($faq_id="") {
	$query="SELECT * from ".table('lang')." 
		WHERE content_type='faq_question' 
		AND content_name='".mysqli_real_escape_string($GLOBALS['mysqli'],$faq_id)."'";
	$result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
	$line=mysqli_fetch_assoc($result);
	return $line;
}

function faq__load_answer($faq_id="") {
        $query="SELECT * from ".table('lang')."
                WHERE content_type='faq_answer'
                AND content_name='".mysqli_real_escape_string($GLOBALS['mysqli'],$faq_id)."'";
        $result=mysqli_query($GLOBALS['mysqli'],$query) or die("Database error: " . mysqli_error($GLOBALS['mysqli']));
        $line=mysqli_fetch_assoc($result);
        return $line;
}

?>
