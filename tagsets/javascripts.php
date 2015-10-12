<?php
// part of orsee. see orsee.org


function script__login_page() {
    echo '
    <script language="JavaScript">
        <!--
         function gotoUsername() { document.login.adminname.focus(); }
         function gotoPassword() { document.login.password.value=""; document.login.password.focus(); }
         function sendForm() { document.login.submit(); }
         // -->

    </script>
    ';
}

function javascript__close_window() {
    echo '<SCRIPT LANGUAGE="JavaScript">
        <!--
        window.close()
          //-->
        </SCRIPT>';
}

function get_multi_picker($name,$data,$selected=array(),$options=array()) {
    global $settings;
    $out='';
    $op=array(
        'prompt_text'=>'Choose ...',
        'show_prompt'=>true,
        'show_arrow'=>true,
        'show_clear'=>false,
        'focus_effect'=>true,
        'show_picker'=>true,
        'picker_icon'=>'tags',
        'picker_color'=>'#555555',
        'picker_numcols'=>0,
        'picker_maxnumcols'=>0,
        'tag_color'=>'#e2e6f0',
        'rows'=>1,
        'cols'=>50,
        'trim_display_values'=>false,
        'trim_picker_values'=>true,
        'trim_to_chars'=>30
    );
    if (is_array($options)) {
        foreach ($options as $key=>$value) {
            if (isset($op[$key])) $op[$key]=$value;
        }
    }
    $continue=true;
    if (!is_array($data)) $continue=false;
    else {
        // create json data for textex, but also look for max length entry
        $temp_data=array(); $maxlen=0;
        foreach ($data as $key=>$value) {
            $temp_data[]= '{ show: "'.trim($value).'", value: "'.$key.'", disabled: false }';
            $thislen=strlen(trim($value)); if ($thislen>$maxlen) $maxlen=$thislen;
        }
        $myitems='[ '.implode(" , ",$temp_data).' ]'; $myitems_picker="";
        // if maxlen < cols, resize, otherwise trim display values
        if ($op['cols']>=$maxlen) $op['cols']=$maxlen;
        else {
            if ($op['trim_display_values'] || $op['trim_picker_values']) {
                $temp_data=array();
                foreach ($data as $key=>$value) {
                    if (strlen($value)>$op['trim_to_chars']) $value=substr($value,0,$op['trim_to_chars']-3).'...';
                    $temp_data[]= '{ show: "'.trim($value).'", value: "'.$key.'", disabled: false }';
                }
                $myitems_picker='[ '.implode(" , ",$temp_data).' ]';
                if ($op['trim_display_values']) $myitems=$myitems_picker;
                if (!$op['trim_picker_values']) $myitems_picker="";
            }
        }
    }
    if ($continue && is_array($selected) && count($selected)>0) {
        $temp_data=array();
        foreach ($selected as $id) {
            if (isset($data[$id])) $temp_data[]= '{ show: "'.$data[$id].'", value: "'.$id.'"}';
        }
        $selitems='[ '.implode(" , ",$temp_data).' ]';
    } else $selitems="";
    if ($continue) {
        //$out.='<TABLE style="margin: 0; padding: 0; border: 0;" border="0" cellspacing="0" cellpadding="0"><TR><TD>';
        if (isset($settings['multipicker_left_or_right']) && $settings['multipicker_left_or_right']=='right') $mpright=true;
        else $mpright=false;
        if ($mpright) $out.='<textarea id="'.$name.'_textarea" name="'.$name.'"'.
                        ' rows="'.$op['rows'].'" cols="'.($op['cols']+10).'" class="'.$name.'_class"></textarea>';
        if ($op['show_picker']) {
        //  $out.='</TD><TD>';
            $out.='<i id="'.$name.'_picker" class="fa fa-'.$op['picker_icon'].' fa-fw"'.
                ' style="padding-left: 5px; vertical-align: top; margin-top: 5px;'.
                ' color: '.$op['picker_color'].'"></i>';
        }
        if (!$mpright) $out.='&nbsp;<textarea id="'.$name.'_textarea" name="'.$name.'"'.
            ' rows="'.$op['rows'].'" cols="'.($op['cols']+10).'" class="'.$name.'_class"></textarea>';
        //$out.='</TABLE>';
        $out.="\n".'<script type="text/javascript">';
        $out.="
        var ".$name."_myitems = ".$myitems.";";
        if ($myitems_picker) {
            $out.="
                var ".$name."_myitems_picker = ".$myitems_picker.";";
        }
        $out.="
            $('#".$name."_textarea').textext({
            plugins: 'autocomplete suggestions tags filter";
            if ($op['show_arrow']) $out.=" arrow";
            if ($op['show_prompt']) $out.=" prompt";
            if ($op['focus_effect']) $out.=" focus";
            $out.="',
            ";
        if ($op['show_prompt'] && $op['prompt_text']) $out.="prompt: '".$op['prompt_text']."',
            ";
        $out.="suggestions: ".$name."_myitems,
            ";
        if ($selitems) $out.="tagsItems: ".$selitems.",
            ";
        $out.="filterItems: ".$name."_myitems,
            ";
        if ($op['tag_color']!='#e2e6f0') {
            $out.="html: {
                    tag: '".'<div class="text-tag"><div class="text-button" style="background: '.$op['tag_color'].';"><span class="text-label"/><a class="text-remove"/></div></div>'."'
                },
                ";
        }
        $out.="ext: {
                    itemManager: {
                        stringToItem: function(str)
                        {
                            var thisindex = -1; var thisvalue = '';
                            for (index = 0; index < ".$name."_myitems.length; index++) {
                                if (".$name."_myitems[index].show==str) {
                                   thisindex = index;
                                   break;
                                }
                            }
                            if (thisindex>-1) { thisvalue = ".$name."_myitems[thisindex].value; }
                            return { show: str, value: thisvalue };
                        },
                        itemToString: function(item)
                        {
                            return item.show;
                        },
                        compareItems: function(item1, item2)
                        {
                            return item1.show == item2.show;
                        }
                    }
                }
            });";
        if ($op['show_picker']) {
            $out.="
                function ".$name."_updateTagField (avalue,ashow,ah) {
                    var thisindex = -1; var thisshow = '';
                    for (index = 0; index < ".$name."_myitems.length; index++) {
                        if (".$name."_myitems[index].value==avalue) {
                           thisindex = index;
                           break;
                        }
                    }
                    if (thisindex>-1) { thisshow = ".$name."_myitems[thisindex].show; }
                    $('#".$name."_textarea').textext()[0].tags().addTags([ {show: thisshow, value: avalue } ]);
                }
                $('#".$name."_picker').arraypick({
                    numcols: ".$op['picker_numcols'].",
                    maxnumcols: ".$op['picker_maxnumcols'].",
                    arraydata: ".$name."_myitems";
            if ($myitems_picker) $out.="_picker";
            $out.="
                }, ".$name."_updateTagField);
                ";
        }

        $out.="
                if(typeof multiDefaults !== 'undefined'){
                    for(p = 0; p < multiDefaults.length; p++){
                        ".$name."_updateTagField (multiDefaults[p],'',0);
                    }
                    multiDefaults = [];
                }
            ";

        $out.="
            </script>
            ";
    }
    return $out;
}

function multipicker_json_to_array($json) {
    $ret=array();
    if ($json || $json=='0') {
        if (preg_match_all('/"show":"([^"]*)","value":"([^"]*)"/',$json,$matches)) {
            $data=array();
            foreach ($matches[0] as $k=>$v) {
                $data[$matches[2][$k]]=$matches[1][$k];
            }
            $done=natcasesort($data);
            foreach($data as $k=>$v) $ret[]=$k;
        } elseif ($json=='[]') {
        } else {
            $data=explode(",",$json);
            foreach ($data as $v) if ($v || $v=='0') $ret[]=$v;
        }
    }
    return $ret;
}

function javascript__edit_popup_link($participant_id) {
    global $color;
    $out='<A style="color: '.$color['body_link'].'; text-decoration: underline; cursor: hand;" onclick="javascript:editPopup('.$participant_id.'); return false;">'.lang('edit').'</A>';
    return $out;
}

function javascript__edit_popup() {
    global $color;
    $out='<script>
            function editPopup(id){
                $("#popupLoadAnimation").fadeIn(50);
                $("#popupIframe").hide();
                $("#popupIframe").attr("src", "participants_edit.php?hide_header=true&participant_id=" + id);
                $("#popupDiv").bPopup({
                        amsl: 50,
                        positionStyle: "fixed",
                        modalColor: "'.$color['popup_modal_color'].'",
                        opacity: 0.8
                    });
            }
            $(document).ready(function(){
                $("#popupIframe").load(function(){
                    $("#popupIframe").fadeIn(100);
                    $("#popupLoadAnimation").fadeOut(300);
                    if($("#popupIframe").contents().find("[data-edited-item]").length > 0){
                        var data = $.parseJSON($("#popupIframe").contents().find("[data-edited-item]").attr("data-edited-item"));
                        for(var i = 0; i < data.columns.length; i++){
                            $("[data-participant-id=\'" + data.id + "\']").find("td").eq(i).html(data.columns[i]);
                        }
                    }
                });
                $("#popupIframe").bind("beforeunload", function(){
                    $("#popupLoadAnimation").fadeIn(20);
                });
            });
        </script>
        <div id="popupDiv" class="popupDiv" style="">
            <div align="right"><button class="b-close button fa-backward popupBack">'.lang('back_to_results').'</button></div>
            <iframe id="popupIframe" src=""></iframe>
            <i id="popupLoadAnimation" class="fa fa-spinner fa-spin" style="color: #444;"></i>
        </div>';
    return $out;
}

function javascript__email_popup_button_link($message_id) {
    $out='<A class="button fa-envelope-square  style="padding: 0 0.5em 0 1.5em;" '.
            'onclick="javascript:emailPopup(\''.urlencode($message_id).'\'); return false;">'.lang('email_view_message').'</A>';
    return $out;
}


function javascript__email_popup() {
    global $color;
    $out='<script>
            function emailPopup(id){
                $("#popupLoadAnimation").fadeIn(50);
                $("#popupIframe").hide();
                $("#popupIframe").attr("src", "emails_view.php?hide_header=true&message_id=" + id);
                $("#popupDiv").bPopup({
                        amsl: 50,
                        positionStyle: "fixed",
                        modalColor: "'.$color['popup_modal_color'].'",
                        opacity: 0.8
                    });
            }
            $(document).ready(function(){
                $("#popupIframe").load(function(){
                    $("#popupIframe").fadeIn(100);
                    $("#popupLoadAnimation").fadeOut(300);
                });
                $("#popupIframe").bind("beforeunload", function(){
                    $("#popupLoadAnimation").fadeIn(20);
                });
            });
        </script>
        <div id="popupDiv" class="popupDiv" style="">
            <div align="right"><button class="b-close button fa-backward popupBack">'.lang('email_back_to_list').'</button></div>
            <iframe id="popupIframe" src=""></iframe>
            <i id="popupLoadAnimation" class="fa fa-spinner fa-spin" style="color: #444;"></i>
        </div>';
    return $out;
}

function javascript__selectall_checkbox_script() {
    $out='<INPUT id="selall" type="checkbox" name="selall" value="y">
            <script language="JavaScript">
                $("#selall").change(function() {
                    if (this.checked) {
                        $("input[name*=\'sel[\']").each(function() {
                            this.checked = true;
                        });
                    } else {
                        $("input[name*=\'sel[\']").each(function() {
                            this.checked = false;
                        });
                    }
                });
            </script>';
    return $out;
}

function javascript__tooltip_prepare() {
    global $color;
    echo '<script type="text/javascript">
            $("<style>").prop("type", "text/css")
            .html("\
            .tooltipbox {\
                display:none;\
                position:absolute;\
                max-width: 400px;\
                border:1px solid #333;\
                background-color:'.$color['tool_tip_background_color'].';\
                border-radius:5px;\
                padding:10px;\
                color:'.$color['tool_tip_text_color'].';\
                font-size:12px;\
                font-family: Arial;\
            }")
            .appendTo("head");
        $(document).ready(function() {
        $(".tooltip").hover(function(){
                var title = $(this).attr("title");
                $(this).data("tooltiptext", title).removeAttr("title");
                $("<p class=\'tooltipbox\'></p>").text(title).appendTo("body").fadeIn("slow");
        }, function() {
                $(this).attr("title", $(this).data("tooltiptext"));
                $(".tooltipbox").remove();
        }).mousemove(function(e) {
                var mousex = e.pageX + 20;
                var mousey = e.pageY + 10;
                $(".tooltipbox")
                .css({ top: mousey, left: mousex })
        });
        });
    </script>';
}

?>
