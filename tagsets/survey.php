<?php

// some survey functions for orsee. part of orsee. see orsee.org

function survey__print_start_time($experiment_id,$hidetime=false) {
  global $lang;
  $os=orsee_db_load_array("os_properties",$experiment_id,"experiment_id");
  if (isset($os['start_year'])) {
  	$os_time=time__load_survey_start_time($os);
  	$tstr=time__format($lang['lang'],$os_time,false,$hidetime,true,false);
	return $tstr;
	}
   else {
	return "???";
	}
}

function survey__print_stop_time($experiment_id,$hidetime=false) {
  global $lang;
  $os=orsee_db_load_array("os_properties",$experiment_id,"experiment_id");
  if (isset($os['start_year'])) {
        $os_time=time__load_survey_stop_time($os);
        $tstr=time__format($lang['lang'],$os_time,false,$hidetime,true,false);
        return $tstr;
        }
   else {
        return "???";
        }
}


function survey__get_start_unixtime($experiment_id,$ospack="") {
        if (!$ospack) $ospack=array();
        if ($experiment_id) $ospack=orsee_db_load_array("os_properties",$experiment_id,"experiment_id");
        if (isset($os['start_year'])) {
                $os_time_pack=time__load_survey_start_time($os);
                $ostime=time__time_package_to_unixtime($os_time_pack);
                return $ostime;
                }
           else {
                return false;
                }
}


function survey__get_stop_unixtime($experiment_id,$ospack="") {
	if (!$ospack) $ospack=array();
	if ($experiment_id) $ospack=orsee_db_load_array("os_properties",$experiment_id,"experiment_id");
  	if (isset($os['stop_year'])) {
        	$os_time_pack=time__load_survey_stop_time($os);
		$ostime=time__time_package_to_unixtime($os_time_pack);
		return $ostime;
		}
   	   else {
        	return false;
        	}
}

/*

<defun survey::count-questions experiment_id>
<sql::with-open-database db dsn=<site::dsn> nolock=true>
          <sql::database-query
                   db true
                 "SELECT count(question_id) as anzahl FROM ".table('os_questions')."
                  WHERE experiment_id='<get-var experiment_id>'"
                  format =<get-var anzahl>>
</sql::with-open-database>
</defun>

<defun survey::count-items question_id>
<sql::with-open-database db dsn=<site::dsn> nolock=true>
          <set-var type=<sql::database-query
                   db true
                 "SELECT question_type FROM os_questions
                  WHERE question_id='<get-var question_id>'"
                  format =<get-var question_type>>>
          <sql::database-query
                   db true
                 "SELECT count(item_id) as anzahl FROM os_items_<get-var type>
                  WHERE question_id='<get-var question_id>'"
                  format =<get-var anzahl>>
</sql::with-open-database>
</defun>

<defun survey::random-order? &key experiment_id question_id question_type answer_question_id>
<sql::with-open-database db dsn=<site::dsn> nolock=true>
<when <get-var experiment_id>>
          <set-var this::answer=<sql::database-query
                   db true
                 "SELECT sum(random_order) as ordersum FROM os_questions
                  WHERE experiment_id='<get-var experiment_id>'"
                  format =<if
			<gt <get-var ordersum> 0>
			"true"
			>>>
</when>
<when <and <not <get-var experiment_id>> <get-var question_id> <get-var question_type>>>
          <set-var this::answer=<sql::database-query
                   db true
                 "SELECT sum(random_order) as ordersum 
		  FROM os_items_<get-var question_type>
                  WHERE question_id='<get-var question_id>'"
                  format =<group
			<if
			<gt <get-var ordersum> 0>
			"true"
			>>>>
</when>
<when <and <not <get-var experiment_id>> <not <get-var question_id>> <get-var answer_question_id>>>
          <set-var this::answer=<sql::database-query
                   db true
                 "SELECT sum(random_order) as ordersum
                  FROM os_pre_answers
                  WHERE question_id='<get-var answer_question_id>'"
                  format =<group
                        <if
                        <gt <get-var ordersum> 0>
                        "true"
                        >>>>
</when>
</sql::with-open-database>
<get-var this::answer>
</defun>


<defun survey::count-answer-options question_id>
<sql::with-open-database db dsn=<site::dsn> nolock=true>
          <sql::database-query
                   db true
                 "SELECT count(answer_id) as anzahl FROM os_pre_answers
                  WHERE question_id='<get-var question_id>'"
                  format =<get-var anzahl>>
</sql::with-open-database>
</defun>


<defun survey::questions-format-alist alist>
  <alist-to-package <get-var-once alist>>
        <tr>
		<td><get-var question_order>.</td>

        	<td class="small">
        		<lang::name>:
        	</td>
        	<td class="small">
                	<get-var question_name>
        	</td>
		<td class="small" colspan=2>
			<A class="small" HREF="os_question_edit.mhtml?question_id=<get-var question_id>&experiment_id=<get-var experiment_id>"><lang::edit_basic_data></A>
		</td>
        </tr>

        <tr>
		<td>&nbsp;</TD>
        	<td class="small"><lang::type>:</td>
        	<td class="small"><get-var question_type></TD>
		<td colspan=2>
			<when <match <get-var question_type> "select_text|radio|checkbox">>
			<A class="small" HREF="os_answer_option_show.mhtml?question_id=<get-var question_id>">
			<lang::edit_answer_options>
			</A>
			</when>&nbsp;
		</td>
        </tr>

	<tr>
		<td>&nbsp;</td>
		<td class="small"><lang::items>:</td>
		<td class="small"><survey::count-items <get-var question_id>></td>
		<td class="small">
			<A class="small" HREF="os_item_show.mhtml?question_id=<get-var question_id>"><lang::edit_items></A>
		</td>
		<td class="small">
			<A class="small" HREF="javascript:open_os_question(<get-var question_id>)">
				<lang::test_question>
			</A>
		</td>
	</tr>
	<when <get-var os::results>>
		<set-var count=<os::results-count question_id=<get-var question_id>>>
	<tr>
		<td>&nbsp;</td>
		<td class="small"><lang::answers>:</td>
		<td class="small"><get-var count></td>
		<td class="small" colspan=2>
			<when <gt <get-var count> 0>>
			<A class="small" HREF="os_averages_show.mhtml?question_id=<get-var question_id>&experiment_id=<get-var experiment_id>"><lang::show_average_data></A>
			</when>
		</TD>
	</tr>
	</when>
        <TR><TD colspan=5 class=small>&nbsp;</TD></TR>
</defun>

<defun survey::get-question-name question_id>
<sql::with-open-database db dsn=<site::dsn> nolock=true>
          <sql::database-query
                   db true
                 "SELECT question_name FROM os_questions
                  WHERE question_id='<get-var question_id>'"
                  format =<get-var question_name>>
</sql::with-open-database>
</defun>

;;; creates default text for long description
<defun survey::create-long-description>
<include <get-var settings::root-directory>/lang/<get-var settings::public-standard-language>/default_texts/long_description.mhtml>
</defun>

;;; creates default text for short description
<defun survey::create-short-description>
<include <get-var settings::root-directory>/lang/<get-var settings::public-standard-language>/default_texts/short_description.mhtml>
</defun>

*/

function survey__count_finished($experiment_id,$reg="") {
	if ($reg)
        	$whereclause=" AND free_reg='".$reg."'";
            else $whereclause="";

     	$query="SELECT COUNT(playerdata_id) as cnt
      		FROM ".table('os_playerdata')."
      		WHERE experiment_id='".$experiment_id."'
		AND participant_id > -1
		AND finished='y' ".$whereclause;
        $res=orsee_query($query);
        return $res['cnt'];
}

/*

<defun survey::count-free-reg experiment_id>
<sql::with-open-database db dsn=<site::dsn> nolock=true>
    <sql::database-query
     db true
     "SELECT COUNT(playerdata_id) as number
      FROM os_playerdata
      WHERE experiment_id='<get-var experiment_id>'
        AND free_reg='y'"
       format=<get-var number> >
</sql::with-open-database>
</defun>

<defun survey::count-dataform experiment_id>
<sql::with-open-database db dsn=<site::dsn> nolock=true>
    <sql::database-query
     db true
     "SELECT COUNT(os_playerdata.playerdata_id) as number
      FROM os_playerdata, participants_os
      WHERE os_playerdata.experiment_id='<get-var experiment_id>'
        AND os_playerdata.free_reg='y'
	AND os_playerdata.participant_id=participants_os.participant_id"
       format=<get-var number> >
</sql::with-open-database>
</defun>


<defun survey::count-joined-subpool experiment_id>
<sql::with-open-database db dsn=<site::dsn> nolock=true>
    <sql::database-query
     db true
     "SELECT COUNT(os_playerdata.playerdata_id) as number
      FROM os_playerdata, participants_os, participants
      WHERE os_playerdata.experiment_id='<get-var experiment_id>'
        AND os_playerdata.free_reg='y'
        AND os_playerdata.participant_id=participants_os.participant_id
	AND participants_os.participant_id=participants.participant_id"
       format=<get-var number> >
</sql::with-open-database>
</defun>

<defun survey::any-survey?>
<sql::with-open-database db dsn=<site::dsn> nolock=true>
    <set-var actual=<sql::database-query
     db true
     "SELECT experiment_id as test
      FROM os_properties 
      WHERE free_registration='y'
	AND show_in_public='y'"
       format=<get-var test>>>
<if <get-var actual> "true"> 
</sql::with-open-database>
</defun>


<defun survey::actual-survey?>
<sql::with-open-database db dsn=<site::dsn> nolock=true>
  <set-var os::now=<concat
        <helpers::pad-number <date::format-time "YYYY"> fillzeros=4>
        <helpers::pad-number <date::format-time "MM"> fillzeros=2>
        <helpers::pad-number <date::format-time "DD"> fillzeros=2>
        <helpers::pad-number <date::format-time "hh"> fillzeros=2>
        <helpers::pad-number <date::format-time "mm"> fillzeros=2>
        >>
    <set-var actual=<sql::database-query
     db true
     "SELECT stop_year*100000000+stop_month*1000000+stop_day*10000+stop_hour*100+stop_minute as test
      FROM os_properties 
      WHERE free_registration='y'
	AND show_in_public='y' 
        HAVING test > <get-var os::now>"
       format=<get-var test>>>
<if <get-var actual> "true">
</sql::with-open-database>
</defun>

<defun survey::old-survey?>
<sql::with-open-database db dsn=<site::dsn> nolock=true>
  <set-var os::now=<concat
        <helpers::pad-number <date::format-time "YYYY"> fillzeros=4>
        <helpers::pad-number <date::format-time "MM"> fillzeros=2>
        <helpers::pad-number <date::format-time "DD"> fillzeros=2>
        <helpers::pad-number <date::format-time "hh"> fillzeros=2>
        <helpers::pad-number <date::format-time "mm"> fillzeros=2>
        >>
    <set-var old=<sql::database-query
     db true
     "SELECT stop_year*100000000+stop_month*1000000+stop_day*10000+stop_hour*100+stop_minute as test
      FROM os_properties
      WHERE free_registration='y'
	AND show_in_public='y'
        HAVING test < <get-var os::now>"
       format=<get-var test>>>
<if <get-var old> "true">
</sql::with-open-database>
</defun>

*/

?>
