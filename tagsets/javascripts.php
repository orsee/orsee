<?php

// javascript generation functions. part of orsee. see orsee.org.

function script__part_list_checkall() {

echo '
<SCRIPT LANGUAGE="JavaScript">

<!-- Begin
	function checkAll(chname,chcount) {
                for (var j = 1; j <= chcount; j++) {
                box = eval("document.part_list." + chname + j);
                if (box.checked == false) box.checked = true;
                   }
                }

        function uncheckAll(chname,chcount) {
                for (var j = 1; j <= chcount; j++) {
                box = eval("document.part_list." + chname + j);
                if (box.checked == true) box.checked = false;
                   }
                }

//  End -->
</script>
';

}

function script__part_reg_show() {

echo '
<SCRIPT LANGUAGE="JavaScript">
<!-- Begin
function checkAll(chname,chcount) {
for (var j = 1; j <= chcount; j++) {
box = eval("document.part_list." + chname + j);
if (box.checked == false) box.checked = true;
   }
}

function uncheckAll(chname,chcount) {
for (var j = 1; j <= chcount; j++) {
box = eval("document.part_list." + chname + j);
if (box.checked == true) box.checked = false;
   }
}

function checkpart(i) {
boxpart = eval("document.part_list.part" + i);
boxshup = eval("document.part_list.shup" + i);
if (boxpart.checked == true) {
boxshup.checked = true;
   }
}

function checkshup(i) {
boxpart = eval("document.part_list.part" + i);
boxshup = eval("document.part_list.shup" + i);
if (boxshup.checked == false) {
boxpart.checked = false;
   }
}
//  End -->
</script>
';

}

function script__open_help() {

echo '
<SCRIPT LANGUAGE="JavaScript">
function helppopup(topic) {
        helpaddress= "../admin/help.php?" + topic;
     hlpfn=open(helpaddress,"help","width=300,height=400,location=no,toolbar=no,menubar=no,status=no,directories=no,scrollbars=yes,resizable=no")
        }
</SCRIPT>

';
}

function script__login_page() {

echo '
<script language=JavaScript>

    <!--
     function gotoUsername() { document.login.adminname.focus(); }
     function gotoPassword() { document.login.password.value=""; document.login.password.focus(); }
     function sendForm() { document.login.submit(); }
     // -->

</script>
';

}


function script__open_faq() {

echo '
<SCRIPT LANGUAGE="JavaScript">

function popupfaq(address) {
faqwin=open(address, "faq", "width=470,height=400,location=no,toolbar=no,menubar=no,status=no,directories=no,scrollbars=yes,resizable=yes") }

</SCRIPT>

';

}

function javascript__close_window() {
        echo '<SCRIPT LANGUAGE="JavaScript">
<!--
window.close()
  //-->
</SCRIPT>';

}

?>
