<?php
// part of orsee. see orsee.org

function javascript__toast_messages($duration_ms=3200,$root_selector='.orsee',$message_host_id='orsee-public-message-host',$toast_host_id='orsee-public-toast-host') {
    $duration_ms=(int)$duration_ms;
    if ($duration_ms<800) {
        $duration_ms=800;
    }
    if ($duration_ms>12000) {
        $duration_ms=12000;
    }
    $root_selector=json_encode((string)$root_selector);
    $message_host_id=json_encode((string)$message_host_id);
    $toast_host_id=json_encode((string)$toast_host_id);
    return '<script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function () {
                var root = document.querySelector('.$root_selector.');
                if (!root) return;

                var burger = root.querySelector(".orsee-burger");
                if (burger) {
                    burger.addEventListener("click", function (ev) {
                        ev.preventDefault();
                        root.classList.toggle("orsee-m-nav-open");
                    });
                }
                document.addEventListener("click", function (ev) {
                    if (!root.classList.contains("orsee-m-nav-open")) return;
                    var menu = root.querySelector(".orsee-menu");
                    var localBurger = root.querySelector(".orsee-burger");
                    if (!menu || !localBurger) return;
                    if (menu.contains(ev.target) || localBurger.contains(ev.target)) return;
                    root.classList.remove("orsee-m-nav-open");
                });

                var host = document.getElementById('.$message_host_id.');
                if (!host) return;
                root.querySelectorAll(".orsee-public-inline-message-buffer").forEach(function (buffer) {
                    var messages = buffer.querySelectorAll(".orsee-message-box");
                    messages.forEach(function (messageBox) {
                        host.appendChild(messageBox);
                    });
                    buffer.remove();
                });
                var toasts = root.querySelectorAll(".orsee-message-box.orsee-message-toast");
                if (!toasts.length) return;
                var toastHost = document.getElementById('.$toast_host_id.');
                if (!toastHost) {
                    toastHost = document.createElement("div");
                    toastHost.id = '.$toast_host_id.';
                    host.appendChild(toastHost);
                }
                toasts.forEach(function (toast, idx) {
                    if (toast.parentNode !== toastHost) toastHost.appendChild(toast);
                    toast.classList.add("orsee-public-toast");
                    var delay = '.$duration_ms.' + (idx * 250);
                    window.setTimeout(function () {
                        toast.classList.add("orsee-public-toast-hide");
                        window.setTimeout(function () {
                            if (toast && toast.parentNode) toast.parentNode.removeChild(toast);
                        }, 280);
                    }, delay);
                });
            });
        </script>';
}

function javascript__confirm_modal_script() {
    $fallback_text=htmlspecialchars(lang('mobile_confirmation'),ENT_QUOTES);
    $out='<script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function () {
            var confirmBox = document.getElementById("orsee-public-confirm");
            var confirmText = document.getElementById("orsee-public-confirm-text");
            var confirmNo = document.getElementById("orsee-public-confirm-no");
            var confirmYes = document.getElementById("orsee-public-confirm-yes");
            var pendingForm = null;
            var pendingSubmitter = null;

            if (!confirmBox || !confirmText || !confirmNo || !confirmYes) return;

            function closeConfirm() {
                confirmBox.classList.add("is-hidden");
                pendingForm = null;
                pendingSubmitter = null;
            }

            document.querySelectorAll("[data-orsee-confirm-submit]").forEach(function (btn) {
                btn.addEventListener("click", function (ev) {
                    ev.preventDefault();
                    var form = null;
                    var formId = this.getAttribute("data-orsee-confirm-form");
                    if (formId) form = document.getElementById(formId);
                    if (!form) form = this.closest("form");
                    if (!form) return;
                    pendingForm = form;
                    pendingSubmitter = this;
                    confirmText.textContent = this.getAttribute("data-confirm") || "'.$fallback_text.'";
                    confirmBox.classList.remove("is-hidden");
                });
            });

            confirmNo.addEventListener("click", closeConfirm);
            confirmYes.addEventListener("click", function () {
                if (pendingForm) {
                    if (pendingSubmitter && typeof pendingForm.requestSubmit==="function") {
                        pendingForm.requestSubmit(pendingSubmitter);
                    } else if (pendingSubmitter && pendingSubmitter.name) {
                        var hidden = document.createElement("input");
                        hidden.type = "hidden";
                        hidden.name = pendingSubmitter.name;
                        hidden.value = pendingSubmitter.value;
                        pendingForm.appendChild(hidden);
                        pendingForm.submit();
                    } else {
                        pendingForm.submit();
                    }
                }
                closeConfirm();
            });

            confirmBox.addEventListener("click", function (ev) {
                if (ev.target === confirmBox) closeConfirm();
            });
        });
    </script>';
    return $out;
}

function javascript__language_switch_script($selector='[data-orsee-language-select]',$default_param_name='language') {
    $selector=json_encode((string)$selector);
    $default_param_name=json_encode((string)$default_param_name);
    return '<script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function () {
            var selects=document.querySelectorAll('.$selector.');
            selects.forEach(function (select) {
                select.addEventListener("change", function () {
                    var selected=(this.value || "").trim();
                    if (selected==="") return;
                    var paramName=this.getAttribute("data-orsee-language-param") || '.$default_param_name.';
                    var url=new URL(window.location.href);
                    url.searchParams.set(paramName, selected);
                    window.location.assign(url.toString());
                });
            });
        });
    </script>';
}

function get_tag_picker($name,$data,$selected=array(),$options=array()) {
    static $orsee_tag_picker_bootstrap_emitted=false;

    $out='';
    if (!is_array($data)) {
        return $out;
    }
    if (!is_array($selected)) {
        $selected=array();
    }

    $op=array(
        'prompt_text'=>lang('choose').' ...',
        'tag_bg_color'=>'',
        'tag_font_color'=>''
    );
    if (is_array($options)) {
        foreach ($options as $key=>$value) {
            if (isset($op[$key])) {
                $op[$key]=$value;
            }
        }
    }

    $safe_name=preg_replace('/[^a-zA-Z0-9_\-]/','_',$name);

    $selected_map=array();
    foreach ($selected as $sid) {
        $selected_map[(string)$sid]=true;
    }

    $picker_style='';
    $picker_bg=trim((string)$op['tag_bg_color']);
    $picker_fg=trim((string)$op['tag_font_color']);
    if ($picker_bg!=='') {
        if (preg_match('/^--[A-Za-z0-9_-]+$/',$picker_bg)) {
            $picker_style.='--color-tag-picker-bg: var('.$picker_bg.');';
        } else {
            $picker_style.='--color-tag-picker-bg: '.$picker_bg.';';
        }
    }
    if ($picker_fg!=='') {
        if (preg_match('/^--[A-Za-z0-9_-]+$/',$picker_fg)) {
            $picker_style.='--color-tag-picker-fg: var('.$picker_fg.');';
        } else {
            $picker_style.='--color-tag-picker-fg: '.$picker_fg.';';
        }
    }

    $out.='<div class="orsee-tag-picker" data-orsee-tag-picker="'.$safe_name.'"';
    if ($picker_style!=='') {
        $out.=' style="'.htmlspecialchars($picker_style).'"';
    }
    $out.='>';
    $out.='<input type="hidden" class="orsee-tag-picker-hidden" name="'.htmlspecialchars($name).'" value="'.htmlspecialchars(implode(',',$selected)).'">';
    $out.='<div class="orsee-tag-picker-tags">';
    $out.='<div class="orsee-tag-adder"><div class="select select-compact"><select>';
    $out.='<option value="">'.htmlspecialchars($op['prompt_text']).'</option>';
    foreach ($data as $id=>$label) {
        $id_s=(string)$id;
        $out.='<option value="'.htmlspecialchars($id_s).'"';
        if (isset($selected_map[$id_s])) {
            $out.=' disabled';
        }
        $out.='>'.htmlspecialchars((string)$label).'</option>';
    }
    $out.='</select></div></div>';
    $out.='</div></div>';

    $force_bootstrap=(strpos($name,'#')!==false);
    if ($force_bootstrap || !$orsee_tag_picker_bootstrap_emitted) {
        $orsee_tag_picker_bootstrap_emitted=true;
        $out.='<script type="text/javascript">
            (function () {
                if (window.orseeTagPickerInit) {
                    window.orseeTagPickerInit();
                    return;
                }

                function parseSelected(hidden) {
                    if (!hidden.value) return [];
                    return hidden.value.split(",").map(function(v) { return v.trim(); }).filter(Boolean);
                }

                function writeSelected(hidden, selected) {
                    hidden.value = selected.join(",");
                }

                function syncSelectDisabled(select, selectedMap) {
                    Array.prototype.forEach.call(select.options, function(opt) {
                        if (!opt.value) return;
                        opt.disabled = !!selectedMap[opt.value];
                    });
                }

                function renderTags(root, selected, optionsMap) {
                    var container = root.querySelector(".orsee-tag-picker-tags");
                    var adder = root.querySelector(".orsee-tag-adder");
                    container.innerHTML = "";

                    selected.forEach(function(id) {
                        if (!(id in optionsMap)) return;

                        var item = document.createElement("div");
                        item.className = "orsee-tag-item tags has-addons";
                        item.setAttribute("data-value", id);

                        var label = document.createElement("span");
                        label.className = "tag orsee-tag-label";
                        label.textContent = optionsMap[id];

                        var del = document.createElement("a");
                        del.className = "tag is-delete is-small";
                        del.setAttribute("role", "button");
                        del.setAttribute("aria-label", "remove");
                        del.setAttribute("data-orsee-tag-delete", "1");

                        item.appendChild(label);
                        item.appendChild(del);
                        container.appendChild(item);
                    });

                    if (adder) container.appendChild(adder);
                }

                function initPicker(root) {
                    if (root.getAttribute("data-orsee-tag-picker-initialized") === "1") return;
                    root.setAttribute("data-orsee-tag-picker-initialized", "1");

                    var hidden = root.querySelector(".orsee-tag-picker-hidden");
                    var select = root.querySelector(".orsee-tag-adder select");
                    if (!hidden || !select) return;

                    var optionsMap = {};
                    Array.prototype.forEach.call(select.options, function(opt) {
                        if (opt.value) optionsMap[opt.value] = opt.text;
                    });

                    var selected = [];
                    var selectedMap = {};

                    function syncFromHidden() {
                        selected = parseSelected(hidden).filter(function(id) { return id in optionsMap; });
                        selectedMap = {};
                        selected.forEach(function(id) { selectedMap[id] = true; });
                        writeSelected(hidden, selected);
                        syncSelectDisabled(select, selectedMap);
                        renderTags(root, selected, optionsMap);
                    }

                    syncFromHidden();

                    select.addEventListener("change", function() {
                        var id = select.value;
                        if (!id || selectedMap[id]) return;
                        selected.push(id);
                        selectedMap[id] = true;
                        writeSelected(hidden, selected);
                        syncSelectDisabled(select, selectedMap);
                        renderTags(root, selected, optionsMap);
                        select.value = "";
                    });

                    root.querySelector(".orsee-tag-picker-tags").addEventListener("click", function(e) {
                        var target = e.target;
                        if (!target || target.getAttribute("data-orsee-tag-delete") !== "1") return;
                        var item = target.closest(".orsee-tag-item");
                        if (!item) return;
                        var id = item.getAttribute("data-value");
                        if (!selectedMap[id]) return;

                        delete selectedMap[id];
                        selected = selected.filter(function(x) { return x !== id; });
                        writeSelected(hidden, selected);
                        syncSelectDisabled(select, selectedMap);
                        renderTags(root, selected, optionsMap);
                    });

                    hidden.addEventListener("change", function() {
                        syncFromHidden();
                    });
                }

                window.orseeTagPickerInit = function() {
                    var pickers = document.querySelectorAll(".orsee-tag-picker");
                    Array.prototype.forEach.call(pickers, initPicker);
                };

                if (document.readyState === "loading") {
                    document.addEventListener("DOMContentLoaded", window.orseeTagPickerInit);
                } else {
                    window.orseeTagPickerInit();
                }
            })();
        </script>';
    } else {
        $out.='<script type="text/javascript">if (window.orseeTagPickerInit) window.orseeTagPickerInit();</script>';
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
            foreach ($data as $k=>$v) {
                $ret[]=$k;
            }
        } elseif ($json=='[]') {
        } else {
            $data=explode(",",$json);
            foreach ($data as $v) {
                if ($v || $v=='0') {
                    $ret[]=$v;
                }
            }
        }
    }
    return $ret;
}

function javascript__iframe_modal_bootstrap() {
    static $done=false;
    if ($done) {
        return '';
    }
    $done=true;
    return '<script>
            (function(){
                if (window.orseeInitIframeModal) return;

                function byId(id) { return document.getElementById(id); }
                window.orseeIframeModals = window.orseeIframeModals || {};

                function closeModal(config, clearFrame) {
                    var modal = byId(config.modalId);
                    var frame = byId(config.frameId);
                    var loader = byId(config.loaderId);
                    if (!modal) return;
                    modal.classList.remove("is-active");
                    if (!document.querySelector(".modal.is-active")) {
                        document.documentElement.classList.remove("is-clipped");
                    }
                    if (loader) loader.style.display = "none";
                    if (frame) frame.style.visibility = "hidden";
                    if (clearFrame && frame) frame.src = "about:blank";
                }

                function ensureEscBinding() {
                    if (document.documentElement.getAttribute("data-orsee-iframe-modal-esc") === "1") return;
                    document.documentElement.setAttribute("data-orsee-iframe-modal-esc", "1");
                    document.addEventListener("keydown", function(e){
                        if (e.key !== "Escape") return;
                        Object.keys(window.orseeIframeModals || {}).forEach(function(mid){
                            var entry = window.orseeIframeModals[mid];
                            var modal = byId(mid);
                            if (entry && modal && modal.classList.contains("is-active")) {
                                closeModal(entry.config, false);
                            }
                        });
                    });
                }

                window.orseeInitIframeModal = function(config){
                    if (!config || !config.modalId || !config.frameId || !config.loaderId ||
                        !config.closeSelector || !config.openFunctionName || typeof config.buildSrc !== "function") {
                        return;
                    }

                    var modal = byId(config.modalId);
                    var frame = byId(config.frameId);
                    var loader = byId(config.loaderId);
                    if (!modal || !frame) return;

                    window.orseeIframeModals[config.modalId] = {
                        config: config,
                        close: function(clearFrame){ closeModal(config, clearFrame); }
                    };

                    if (!frame.getAttribute("data-orsee-modal-load-bound")) {
                        frame.setAttribute("data-orsee-modal-load-bound", "1");
                        frame.addEventListener("load", function(){
                            frame.style.visibility = "visible";
                            if (loader) loader.style.display = "none";
                            if (typeof config.onFrameLoad === "function") {
                                config.onFrameLoad(frame);
                            }
                        });
                    }

                    document.querySelectorAll(config.closeSelector).forEach(function(btn){
                        if (btn.getAttribute("data-orsee-modal-close-bound") === "1") return;
                        btn.setAttribute("data-orsee-modal-close-bound", "1");
                        btn.addEventListener("click", function(){
                            closeModal(config, true);
                        });
                    });

                    window[config.openFunctionName] = function(id){
                        if (loader) loader.style.display = "flex";
                        frame.style.visibility = "hidden";
                        frame.src = config.buildSrc(id);
                        modal.classList.add("is-active");
                        document.documentElement.classList.add("is-clipped");
                    };

                    ensureEscBinding();
                };
            })();
        </script>';
}

function javascript__edit_popup() {
    $out=javascript__iframe_modal_bootstrap();
    $out.='<script>
            (function(){
                function syncParticipantRow(frame) {
                    if (!frame || !frame.contentDocument) return;
                    var marker = frame.contentDocument.querySelector("[data-edited-item]");
                    if (!marker) return;
                    var data = null;
                    try {
                        data = JSON.parse(marker.getAttribute("data-edited-item"));
                    } catch (e) {
                        return;
                    }
                    if (!data || !data.id || !Array.isArray(data.columns)) return;
                    var row = document.querySelector("[data-participant-id=\\"" + data.id + "\\"]");
                    if (!row) return;
                    var cells = row.querySelectorAll(".orsee-listcell, td");
                    if (!cells || !cells.length) return;
                    for (var i = 0; i < data.columns.length && i < cells.length; i++) {
                        cells[i].innerHTML = data.columns[i];
                    }
                }

                document.addEventListener("DOMContentLoaded", function(){
                    if (!window.orseeInitIframeModal) return;
                    window.orseeInitIframeModal({
                        modalId: "participantEditModal",
                        frameId: "participantPopupIframe",
                        loaderId: "participantPopupLoadAnimation",
                        closeSelector: "[data-close-participant-modal]",
                        openFunctionName: "editPopup",
                        buildSrc: function(id){
                            return "participants_edit.php?hide_header=true&participant_id=" + encodeURIComponent(id);
                        },
                        onFrameLoad: syncParticipantRow
                    });
                });
            })();
        </script>';
    return $out;
}

function javascript__email_popup_button_link($message_id) {
    $out='<A class="button orsee-btn" style="padding: 0 0.5em 0 1.0em;" '.
            'onclick="javascript:emailPopup(\''.urlencode($message_id).'\'); return false;"><i class="fa fa-envelope-square"></i> '.lang('email_view_message').'</A>';
    return $out;
}


function javascript__email_popup() {
    $out=javascript__iframe_modal_bootstrap();
    $out.='<script>
            (function(){
                document.addEventListener("DOMContentLoaded", function(){
                    if (!window.orseeInitIframeModal) return;
                    window.orseeInitIframeModal({
                        modalId: "emailViewModal",
                        frameId: "emailPopupIframe",
                        loaderId: "emailPopupLoadAnimation",
                        closeSelector: "[data-close-email-modal]",
                        openFunctionName: "emailPopup",
                        buildSrc: function(id){
                            return "emails_view.php?hide_header=true&message_id=" + id;
                        }
                    });
                });
            })();
        </script>';
    return $out;
}

function javascript__selectall_checkbox_script($target_name='sel') {
    if (!preg_match('/^[a-zA-Z0-9_]+$/',$target_name)) {
        $target_name='sel';
    }
    $out='<INPUT id="selall" type="checkbox" name="selall" value="y">
            <script language="JavaScript">
                (function() {
                    var selall=document.getElementById("selall");
                    if (!selall) return;
                    selall.addEventListener("change", function() {
                        var boxes=document.querySelectorAll("input[name*=\''.$target_name.'[\']");
                        boxes.forEach(function(box) {
                            box.checked=selall.checked;
                        });
                    });
                })();
            </script>';
    return $out;
}

function javascript__tooltip_prepare() {
    echo '<script type="text/javascript">
        (function () {
            if (window.orseeTooltipInit) {
                window.orseeTooltipInit();
                return;
            }

            function ensureStyles() {
                if (document.getElementById("orsee-tooltip-styles")) return;
                var style = document.createElement("style");
                style.id = "orsee-tooltip-styles";
                style.type = "text/css";
                style.textContent = "\
                    .orsee-tooltip-trigger {\
                        display: inline-flex;\
                        align-items: center;\
                        justify-content: center;\
                        width: 1rem;\
                        height: 1rem;\
                        margin-inline-start: 0.35rem;\
                        margin-inline-end: 0;\
                        border: 1px solid var(--color-border-strong);\
                        border-radius: 999px;\
                        background: transparent;\
                        color: inherit;\
                        font-size: 0.66rem;\
                        font-weight: 700;\
                        line-height: 1;\
                        cursor: pointer;\
                        opacity: 0.85;\
                        transition: transform 120ms ease, opacity 120ms ease;\
                        vertical-align: middle;\
                    }\
                    .orsee-tooltip-trigger:hover,\
                    .orsee-tooltip-trigger:focus-visible {\
                        opacity: 1;\
                        transform: scale(1.06);\
                    }\
                    .orsee-tooltip-popover {\
                        display: none;\
                        position: absolute;\
                        z-index: 10060;\
                        max-width: min(36rem, calc(100vw - 1rem));\
                        border: 1px solid color-mix(in srgb, var(--color-tooltip-background) 56%, var(--color-mix-anchor-dark));\
                        background-color: var(--color-tooltip-background);\
                        border-radius: 0.5rem;\
                        padding: 0.5rem 0.62rem;\
                        color: var(--color-tooltip-text);\
                        font-family: var(--font-ui, \"Inter\", Helvetica, Arial, sans-serif);\
                        font-size: var(--font-size-compact, 0.8rem);\
                        line-height: 1.3;\
                        box-shadow: 0 12px 22px var(--color-shadow-3);\
                    }\
                    .orsee-tooltip-popover::before {\
                        content: \"\";\
                        position: absolute;\
                        width: 0.58rem;\
                        height: 0.58rem;\
                        background: var(--color-tooltip-background);\
                        border-left: 1px solid color-mix(in srgb, var(--color-tooltip-background) 56%, var(--color-mix-anchor-dark));\
                        border-top: 1px solid color-mix(in srgb, var(--color-tooltip-background) 56%, var(--color-mix-anchor-dark));\
                        transform: rotate(45deg);\
                    }\
                    .orsee-tooltip-popover[data-pos=\"below\"]::before {\
                        top: -0.34rem;\
                        left: var(--tooltip-arrow-left, 1rem);\
                    }\
                    .orsee-tooltip-popover[data-pos=\"above\"]::before {\
                        bottom: -0.34rem;\
                        left: var(--tooltip-arrow-left, 1rem);\
                        transform: rotate(225deg);\
                    }";
                document.head.appendChild(style);
            }

            function ensurePopover() {
                var popover = document.querySelector(".orsee-tooltip-popover");
                if (!popover) {
                    popover = document.createElement("div");
                    popover.className = "orsee-tooltip-popover";
                    popover.setAttribute("role", "tooltip");
                    popover.setAttribute("aria-hidden", "true");
                    var host = document.querySelector(".orsee") || document.body;
                    host.appendChild(popover);
                }
                return popover;
            }

            function closePopover() {
                var popover = document.querySelector(".orsee-tooltip-popover");
                if (popover) {
                    popover.style.display = "none";
                    popover.setAttribute("aria-hidden", "true");
                    popover._trigger = null;
                }
                document.querySelectorAll(".orsee-tooltip-trigger.is-open").forEach(function (el) {
                    el.classList.remove("is-open");
                });
            }

            function positionPopover(trigger, popover) {
                if (!trigger || !popover) return;
                var el = trigger;
                var rect = el.getBoundingClientRect();
                var gap = 8;

                popover.style.top = "-10000px";
                popover.style.left = "-10000px";
                popover.style.display = "block";

                var popW = popover.offsetWidth;
                var popH = popover.offsetHeight;

                var viewportW = window.innerWidth;
                var viewportH = window.innerHeight;
                var scrollX = window.pageXOffset;
                var scrollY = window.pageYOffset;

                var spaceBelow = viewportH - rect.bottom;
                var spaceAbove = rect.top;
                var placeBelow = !(spaceBelow < popH + gap && spaceAbove > spaceBelow);

                var top = placeBelow ? (scrollY + rect.bottom + gap) : (scrollY + rect.top - popH - gap);
                var left = scrollX + rect.left + (rect.width / 2) - (popW / 2);
                var minLeft = scrollX + 8;
                var maxLeft = scrollX + viewportW - popW - 8;
                if (left < minLeft) left = minLeft;
                if (left > maxLeft) left = maxLeft;

                var arrowLeft = (scrollX + rect.left + (rect.width / 2)) - left - 5;
                if (arrowLeft < 8) arrowLeft = 8;
                if (arrowLeft > popW - 14) arrowLeft = popW - 14;

                popover.setAttribute("data-pos", placeBelow ? "below" : "above");
                popover.style.top = top + "px";
                popover.style.left = left + "px";
                popover.style.setProperty("--tooltip-arrow-left", arrowLeft + "px");
            }

            function bindTooltips() {
                document.querySelectorAll(".tooltip").forEach(function (scope) {
                    var txt = scope.getAttribute("title") || scope.dataset.tooltiptext;
                    if (!txt) return;

                    scope.dataset.tooltiptext = txt;
                    scope.removeAttribute("title");

                    var anchor = scope.querySelector("label.label") || scope.querySelector("td") || scope;
                    var hasDirect = false;
                    Array.prototype.forEach.call(anchor.children, function (child) {
                        if (child.classList && child.classList.contains("orsee-tooltip-trigger")) hasDirect = true;
                    });
                    if (hasDirect) return;

                    var btn = document.createElement("button");
                    btn.type = "button";
                    btn.className = "orsee-tooltip-trigger";
                    btn.setAttribute("aria-label", "More information");
                    btn.textContent = "i";
                    anchor.appendChild(btn);
                });
            }

            window.orseeTooltipInit = function () {
                ensureStyles();
                bindTooltips();

                var popover = ensurePopover();

                if (!document.body.dataset.orseeTooltipBound) {
                    document.body.dataset.orseeTooltipBound = "1";

                    document.addEventListener("click", function (e) {
                        var trigger = e.target.closest(".orsee-tooltip-trigger");
                        if (trigger) {
                            e.preventDefault();
                            e.stopPropagation();
                            var parent = trigger.closest(".tooltip");
                            var text = parent ? (parent.dataset.tooltiptext || "") : "";
                            if (!text) return;

                            var isOpen = popover.style.display === "block";
                            var sameTriggerOpen = isOpen && popover._trigger === trigger;
                            closePopover();
                            if (sameTriggerOpen) return;

                            trigger.classList.add("is-open");
                            popover.textContent = text;
                            popover._trigger = trigger;
                            popover.setAttribute("aria-hidden", "false");
                            positionPopover(trigger, popover);
                            return;
                        }

                        if (!e.target.closest(".orsee-tooltip-popover")) closePopover();
                    });

                    document.addEventListener("keydown", function (e) {
                        if (e.key === "Escape") closePopover();
                    });

                    window.addEventListener("resize", closePopover);
                    window.addEventListener("scroll", closePopover, true);
                } else {
                    closePopover();
                }
            };

            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", window.orseeTooltipInit);
            } else {
                window.orseeTooltipInit();
            }
        })();
    </script>';
}

?>
