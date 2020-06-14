<?php
// part of orsee. see orsee.org


// select field for numbers from begin to end by steps
function helpers__select_numbers($name,$prevalue,$begin,$end,$fillzeros=2,$steps=1,$none=false) {

    $i=$begin;
    echo '<select name="'.$name.'" id="'.$name.'">';
    if ($none) echo '<option value="">-</option>';
    while ($i<=$end) {
        echo '<option value="'.$i.'"';
        if ($i == (int) $prevalue) echo ' SELECTED';
                echo '>';
        echo helpers__pad_number($i,$fillzeros);
        echo '</option>
            ';
        $i=$i+$steps;
        }
    echo '</select>';
}

function helpers__select_number($name,$prevalue,$begin,$end,$fillzeros=2,$steps=1,$none=false) {
    $out='';
    $i=$begin;
    $out.='<select name="'.$name.'" id="'.$name.'">';
    if ($none) $out.='<option value="">-</option>';
    while ($i<=$end) {
        $out.='<option value="'.$i.'"';
        if ($i == (int) $prevalue) $out.=' SELECTED';
        $out.='>';
        $out.=helpers__pad_number($i,$fillzeros);
        $out.='</option>
            ';
        $i=$i+$steps;
        }
    $out.='</select>';
    return $out;
}


// select field for text array
function helpers__select_text($tarray,$name,$prevalue,$none=false) {
    global $lang;
        $out='<select name="'.$name.'">';
        if ($none) $out.='<option value=""></option>';
        foreach ($tarray as $k=>$text) {
                $out.='<option value="'.$k.'"';
                if ($k == $prevalue) $out.=' SELECTED';
                $out.='>';
                $out.=lang($text);
                $out.='</option>
                        ';
                }
        $out.='</select>';
    return $out;
}


// select field for values for reminder time and registration end
function helpers__select_numbers_relative($name,$prevalue,$begin,$end,$fillzeros=2,$steps=1,$current_time=0) {
    global $authdata;
    $i=$begin;
    echo '<select name="'.$name.'">';
    while ($i <= $end) {
        echo '<option value="'.$i.'"';
        if ($i== (int) $prevalue) echo ' SELECTED';
                echo '>';
        echo helpers__pad_number($i,$fillzeros);
        if ($current_time > 0) {
            $utime=$current_time - ($i * 60 * 60);
            echo ' ('.ortime__format($utime,'',$authdata['language']).')';
            }
        echo '</option>
            ';
        $i=$i+$steps;
        }
    echo '</select>';
}



function experiment_ext_types__checkboxes($postvarname,$showvar,$checked=array()) {
    $exptypes=load_external_experiment_types();
    foreach($exptypes as $exptype_id=>$exptype) {
        echo '<INPUT type="checkbox" name="'.$postvarname.'['.$exptype_id.']"
                 value="'.$exptype_id.'"';
        if (isset($checked[$exptype_id]) && $checked[$exptype_id]) echo " CHECKED";
                echo '>'.$exptype[lang('lang')];
                echo '<BR>';
    }
}

// select field for sessions
function select__sessions($preval,$varname,$sessions,$hide_nosession=false,$with_exp=false) {
    global $lang, $expadmindata;
    if (!$preval) $preval=0;
    if (!$varname) $varname="session";

    $out='';
    $out.='<SELECT name="'.$varname.'">';
    if (!$hide_nosession) {
        $out.='<OPTION value="0"';
        if ($preval==0) $out.=" SELECTED";
        $out.='>'.lang('no_session').'</OPTION>';
    }

    foreach ($sessions as $line) {
        $out.='<OPTION value="'.$line['session_id'].'"';
            if ($preval==$line['session_id']) $out.=" SELECTED";
        $out.='>';
        if ($with_exp) $out.=$line['experiment_name'].' - ';
        $out.=ortime__format(ortime__sesstime_to_unixtime($line['session_start']),'hide_second:true',lang('lang'));
        if (isset($line['p_is_assigned'])) {
            if ($line['p_is_assigned']) $out.=' - '.lang('is_assigned_to_experiment_short');
            else $out.=' - '.lang('is_not_yet_assigned_to_experiment_short');
        }
        $out.='</OPTION>';
    }
    $out.='</SELECT>';
    return $out;
}


function formhelpers__pick_date($field, $selected_date=0, $years_backward=0, $years_forward=0) {
    global $settings, $lang;
    $out="";

    if (!$selected_date) $selected_date=ortime__unixtime_to_sesstime();
    if (!$years_backward) $years_backward=$settings['session_start_years_backward'];
    if (!$years_forward) $years_forward=$settings['session_start_years_forward'];
    $sda=ortime__sesstime_to_array($selected_date);

    $year_start=$sda['y']-$years_backward;
    $year_stop=$sda['y']+$years_forward;
    if (date("Y")>=$year_stop) $year_stop=date("Y")+1;

    $tformat=lang('format_datetime_date');

    $day_field=helpers__select_number($field."_d",$sda['d'],1,31,2,1,false);
    $month_field=helpers__select_number($field."_m",$sda['m'],1,12,2,1,false);
    $year_field=helpers__select_number($field."_y",$sda['y'],$year_start,$year_stop,4,1,false);

    $tformat=str_replace("%d",$day_field,$tformat);
    $tformat=str_replace("%m",$month_field,$tformat);
    $tformat=str_replace("%Y",$year_field,$tformat);

    $out=$tformat;
    $daysMin=explode(",",lang('format_datetime_weekday_abbr')); foreach ($daysMin as $k=>$v) $daysMin[$k]=trim($v);
    $months=explode(",",lang('format_datetime_month_names')); foreach ($months as $k=>$v) $months[$k]=trim($v);
    $monthsShort=explode(",",lang('format_datetime_month_abbr')); foreach ($monthsShort as $k=>$v) $monthsShort[$k]=trim($v);
    $locale='{ daysMin: ["'.implode('","',$daysMin).'"],
                months: ["'.implode('","',$months).'"],
                monthsShort: ["'.implode('","',$monthsShort).'"] }';
    $out.='
                <i class="fa fa-calendar fa-lg '.$field.'_datepicker"></i>
                <script type="text/javascript">
                $(".'.$field.'_datepicker").pickmeup({
                    position        : "right",
                    hide_on_select  : true,
                    format          : "Y-m-d",
                    date            : "'.$sda['y'].'-'.$sda['m'].'-'.$sda['d'].'",
                    locale          : '.$locale.',
                    first_day       : '.lang('format_datetime_firstdayofweek_0:Su_1:Mo').',
                    change          : function (selectedDate) {
                                        selectedDate = new Date(selectedDate);
                                        var d = selectedDate.getDate();
                                        var m = selectedDate.getMonth();
                                        var y = selectedDate.getFullYear();
                                        ($("#'.$field.'_d")[0]).value = d;
                                        ($("#'.$field.'_m")[0]).value = m+1;
                                        ($("#'.$field.'_y")[0]).value = y;
                                    }
                });
                $("#'.$field.'_d, #'.$field.'_m, #'.$field.'_y").bind(
                    "change", function() {
                    var d = new Date($("#'.$field.'_y").val(), $("#'.$field.'_m").val()-1,$("#'.$field.'_d").val());
                    $(".'.$field.'_datepicker").pickmeup("set_date", d);
                });
                </script>';
    return $out;
}

function helpers__select_hour($name,$prevalue,$begin,$end,$steps=1,$military=true) {
    $out='';
    $i=$begin;
    $out.='<select name="'.$name.'" id="'.$name.'">';
    while ($i<=$end) {
        $out.='<option value="'.$i.'"';
        if ($i == (int) $prevalue) $out.=' SELECTED';
        $out.='>';
        if ($military) {
            $out.=helpers__pad_number($i,2);
        } else {
            $ampm=($i<12)?'am':'pm';
            if ($i==0) $display=12;
            elseif ($ampm=='pm' && $i>12) $display=$i-12;
            else $display=$i;
            $out.=$display.$ampm;
        }
        $out.='</option>
            ';
        $i=$i+$steps;
        }
    $out.='</select>';
    return $out;
}

function formhelpers__pick_time($field, $selected_time=0,$minute_steps=0) {
    global $settings, $lang;

    if (!$selected_time) $selected_time=ortime__unixtime_to_sesstime();
    if (!$minute_steps) $minute_steps=$settings['session_duration_minute_steps'];

    $tformat=lang('format_datetime_time_no_sec');
    $is_mil_time=is_mil_time($tformat);
    $is_mil_time_str=($is_mil_time)?'true':'false';
    $tformat=str_replace("%a","",$tformat);

    $minutedivisions=round(60/$minute_steps);
    if (!($minutedivisions>1)) $minutedivisions=4;
    $sda=ortime__sesstime_to_array($selected_time);

    $hour_field=helpers__select_hour($field."_h",$sda['h'],0,23,1,$is_mil_time);
    $minute_field=helpers__select_number($field."_i",$sda['i'],0,59,2,$settings['session_duration_minute_steps']);

    $tformat=str_replace("%H",$hour_field,$tformat);
    $tformat=str_replace("%h",$hour_field,$tformat);
    $tformat=str_replace("%i",$minute_field,$tformat);

    $cp='<i id="'.$field.'_clockpicker" class="fa fa-clock-o fa-lg"></i>';
    $cp.='<script type="text/javascript">
    $(function() {
    function updateTimeSelects_'.$field.' (time) {
        console.log(time);
        var cpos = time.indexOf( ":", 0 );
        if(cpos<2) {
            var h = Number(time.substr(0,1));
            var i = Number(time.substr(2,2));
        } else {
            var h = Number(time.substr(0,2));
            var i = Number(time.substr(3,2));
        }
        if (time.indexOf("AM",0)>0) {
            if (h==12) { h=0; }
        }
        if (time.indexOf("PM",0)>0) {
            console.log("pm");
            if (h==12) { h=0; }
            h+=12;
        }
        console.log(h + ":" + i);
        ($("#'.$field.'_h")).val(h);
        ($("#'.$field.'_i")).val(i);
    }

    $("#'.$field.'_clockpicker").clockpick({
    starthour: 8,
    endhour: 20,
    showminutes: true,
    minutedivisions: '.$minutedivisions.',
    military: '.$is_mil_time_str.',
    event: "click", // click, mouseover, or focus
    layout: "vertical",
    hoursopacity: 1.0,
    minutesopacity: 1.0
    }, updateTimeSelects_'.$field.');
    });
    </script>
    ';

    $out=trim($tformat).$cp;
    return $out;
}

function formhelpers__orderlist($listID, $formName, $rows, $no_add_button=false, $add_button_title="", $tableHeads = ""){
    $dropdownSelector = ($no_add_button ? 'null' : "$('#".$listID."Dropdown')");
    $out='';
    $out.=" <script>
            var ".$listID."_rows = "; $out.=json_encode($rows); $out.=";
            $(document).ready(function(){
                list_".$listID." = new ListTool(".$listID."_rows, $('#list_".$listID."'), " . $dropdownSelector . ", '".$formName."');
            });
            </script>";
    $out.='<TABLE border=0>';
    if (!$no_add_button) {
        if (!$add_button_title) $add_button_title=lang('add_item');
        $out.='<TR><TD>';
        $out.='
            <ul id="'.$listID.'Dropdown" class="query_add">
                <li>
                    <A HREF="#" class="button fa-plus-circle query_add_btn">'.$add_button_title.'</A>
                    <ul class="dropdownItems">

                    </ul>
                </li>
            </ul>';
        $out.='<TD></TR>';
    }
    $out.='<TR><TD>';
    $out.='<table id="list_'.$listID.'" class="listtable">';
    if ($tableHeads) {
        $out.='<thead><tr><td></td>';
        $out.=$tableHeads;
        $out.= '<td></td></tr></thead>';
    }
    $out.= '<tbody></tbody>
            </table>';
    $out.='<TD></TR></TABLE>';
    return $out;
}




function headcell($value,$sort="",$focus="") {
    global $color;
    if (!isset($_REQUEST['focus'])) $_REQUEST['focus']="";
    if (!isset($_REQUEST['experiment_id'])) $_REQUEST['experiment_id']="";
    if (!isset($_REQUEST['session_id'])) $_REQUEST['session_id']="";
    echo '
        <TD class=small';
    if ($_REQUEST['focus']==$focus && $focus) echo ' style="background: '.$color['list_header_highlighted_background'].'; color: '.$color['list_header_highlighted_textcolor'].';"';
    echo '>';
        if ($sort) {
        echo '<A HREF="'.thisdoc().'?sort='.urlencode($sort).'&show=true';
        if ($_REQUEST['experiment_id']) echo '&experiment_id='.$_REQUEST['experiment_id'];
        if ($_REQUEST['session_id']) echo '&session_id='.$_REQUEST['session_id'];
        if ($_REQUEST['focus']) echo '&focus='.$_REQUEST['focus'];
        echo '">';
        }
    echo '<FONT class="small"';
    if ($_REQUEST['focus']==$focus && $focus) echo ' color="'.$color['list_header_highlighted_textcolor'].'"';
    echo '>';
        echo $value;
    echo '</FONT>';
        if ($sort) echo '</A>';
        echo '</TD>';
}

?>
