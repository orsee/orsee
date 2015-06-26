// queryform javascript functions
// part of orsee. see orsee.org.

var Ptypes = {};
var queryCount = 0;
//if the limit prototype has been added to the footer yet
var limitUsed = false;
//counts the number of opened brackets
var multiDefaults = [];
var openCount = 0;
var dragEnabled = false;
var dragStartY = -1;
var dragStartRow = 0;
var dragSensitivity = 15;
var logicalOpPrototype = 
		'<td>&nbsp;<select>' +
		'<option value="and" selected>AND</option>' +
		'<option value="or">OR</option>' +
		'</select></td>';

var evaldCode = {};
function Ptype (content, jsEval, type, displayName) {
	this.content = content;
	this.jsEval = jsEval;
	this.type = type;
	this.displayName = displayName;
}
function clearQuery(){
	openCount = 0;
	$('#queryTable tbody').children().remove();
	$('#queryTable tfoot').children().first().next().remove();
	limitUsed=false;
	$('#protoDropdown li').remove();
	buildDropdown();
}		
function loadFromObj(fullObj){
	var obj = fullObj['query'];
	clearQuery();
	var unclosedBrackets = [];
	for(var i = 0; i < obj.length; i++){
		for(var key in obj[i]){
			if(typeof Ptypes[key] !== 'undefined'){
				////load default values for multiselect
				var dataType = key.split('_');
				dataType = dataType[1];
				if(typeof dataType !== 'undefined'){
					if(dataType == 'multiselect'){
						//each field
						$.each(obj[i][key], function(k, v){
							//check prefix for ms_
							var fieldDataType = k.split('_');
							fieldDataType = fieldDataType[0];
							if(typeof fieldDataType !== 'undefined'){
								if(fieldDataType == 'ms'){
									multiDefaults.unshift.apply(multiDefaults, v.split(','));
								}
							}
						});
					}
				}
				////
				var tr = moveToQuery(key);
				if(typeof tr !== 'undefined'){
					for(var elemKey in obj[i][key]){
						if(elemKey == 'logical_op'){
							$(tr).children().eq(logop_index).find('select').val(obj[i][key][elemKey]);
						}else{
							$(tr).find("[name='" + elemKey + "'], [data-elem-name='" + elemKey + "']").val(obj[i][key][elemKey]);
							$(tr).find("[name='" + elemKey + "'], [data-elem-name='" + elemKey + "']").trigger("change");
						}
					}
				}
			}else if(key == 'bracket_open'){
				openCount += 1;
				unclosedBrackets[unclosedBrackets.length] = openCount;
				var tr = addGroupingOpen(openCount);
				if(typeof obj[i][key]['logical_op'] !== 'undefined'){
					$(tr).children().eq(logop_index).find('select').val(obj[i][key]['logical_op']);
				}
			}else if(key == 'bracket_close'){
				addGroupingClose(unclosedBrackets[unclosedBrackets.length-1]);
				unclosedBrackets.pop();
			}else{
				console.log('ERROR: Prototype "'  + key + '" does not exist');
			}
		}
	}
	addDragEvents();
	checkAllForLogOp();
}
function addDragEvents(){
	$( ".dragHandle" ).bind('mousedown', function() {
		dragEnabled = true;
		dragStartRow = $(this).parent().parent();
		$('body').css( 'cursor', 'none' );
		$('button').css( 'cursor', 'none' );
		getGroupPairs(dragStartRow).addClass('queryform_highlight_row');
	});
	$( ".dragHandle" ).bind('mouseup', function() {
		dragEnabled = false;
		$('body').css( 'cursor', 'auto' );
		$('button').css( 'cursor', 'auto' );
		
	});
}
$("html").mousemove(function(event){
	if(dragEnabled){
		if(dragStartY == -1){
			dragStartY = event.clientY;
		}
		if(dragStartY - event.clientY > dragSensitivity){
			dragStartY = event.clientY - dragSensitivity;
			moveUp(dragStartRow);
			
		}else if(event.clientY - dragStartY  > dragSensitivity){
			dragStartY = event.clientY + dragSensitivity;
			moveDown(dragStartRow);
		}
	}
});
$("html").mouseup(function(){
	dragEnabled = false;
	$('body').css( 'cursor', 'auto' );
	$('button').css( 'cursor', 'auto' );
	if(dragStartRow != 0){
		getGroupPairs(dragStartRow).removeClass('queryform_highlight_row');
	}
	if(dragEnabled){
		dragStartY = -1;
		dragStartRow = 0;
	}
});
function getGroupPairs(td){
	if(typeof td.attr('data-group-id') === 'undefined'){
		return td;
	}
	return $("[data-group-id='"+ td.attr('data-group-id') +"']");
}
$(document).ready(function(){
	'use strict';
	buildDropdown();
	$("#queryForm").submit(function(e) {
		//pause submission
		e.preventDefault();
		//apply correct name attributes to form inputs
		buildNames();
		//continue form submission
		this.submit();
		return false;
	});
	$('#savedDropdown').dropit(
		{
			action: 'mouseenter',
			beforeShow: function(){
				$('#savedDropdown .dropit-submenu').css('left', '-371px');
				$('#savedDropdown .dropit-submenu').css('width', '600px');
			}
		} 
	)
	if(typeof jsonData !== 'undefined') loadFromObj(jsonData);
});
function buildNames(){
	$("#queryTable").find(':input').each(function(){
		var input = $(this);
		if(typeof input.attr('name') !== 'undefined' && typeof input.attr('data-elem-name') === 'undefined'){
			input.attr('data-elem-name', input.attr('name'));
		}
		input.removeAttr('name');
	});
	var i = 0;
	$("#queryTable tr").each(function(b){
		var tr = this;
		if(typeof $(tr).attr('data-row-type') !== 'undefined'){
			var type = $(tr).attr('data-row-type');
			if($(tr).children().eq(logop_index).find('select').is(":visible")){
				$(tr).children().eq(logop_index).find('select').attr('name', 'form[query][' + i + '][' + type + '][logical_op]');
			}
			var inputUsed = false;
			$(tr).children().eq(field_index).find(':input').each(function(){
				if(typeof $(this).attr('data-elem-name') !== 'undefined'){
					var elemName = $(this).attr('data-elem-name');
					var type = $(this).closest('[data-row-type]').attr('data-row-type');
					if(typeof Ptypes[type] !== 'undefined'){
						var placeholder = Ptypes[type]['placeholder'];
						var dataType = type.split('_');
						dataType = dataType[1];
						if(typeof dataType !== 'undefined'){
							if(dataType == 'multiselect'){
								//replace JSON value with comma delimited values
								try {
									var obj = JSON.parse($(this).attr('value'));
									var values = [];
									for (var item in obj) {
										values.push(obj[item].value);
									}
									if(values.length == 0) throw "no values"; 
									$(this).attr('value', values.join(','));
								} catch (e) {
									if($(this).attr('value') == "[]"){
										$(this).attr('value', "");
									}
								}
				/*
								var obj = JSON.parse($(this).attr('value'));
								var values = [];
								for (var item in obj) {
									values.push(obj[item].value);
								}
								if(values.length > 0) { 
									$(this).attr('value', values.join(','));
								} else {
									if($(this).attr('value') == "[]"){
										$(this).attr('value', "");
									}
								}
				*/
							}
						}
					}
					
					if(typeof placeholder !== 'undefined' && placeholder != ''){
						elemName = elemName.replace(placeholder + '_', '');
					}
					inputUsed = true;
					$(this).attr('name', 'form[query][' + i + '][' + type + '][' + elemName + ']');
				}
			});
			if(inputUsed){
				i++;
			}
		}
	});
}
function handleAddClick(elementType){
	moveToQuery(elementType);
}
function removeFromQuery(tr){
	if(tr.attr('data-row-type') == 'bracket_open'){
		$('#queryTable tbody').find("[data-group-id='"+ tr.attr('data-group-id') +"']").remove();
		openCount -=1;
	} else if (tr.attr('data-row-type') == 'randsubset_limitnumber'){
		tr.remove();
		limitUsed=false;
		buildDropdown();
	}else{
		tr.remove();
	}
	checkAllForLogOp();
}
function moveUp(tr){
	if(tr.attr('data-row-type') == 'bracket_close'){
		if(tr.index() < ($("[data-group-id='"+ tr.attr('data-group-id') +"'][data-row-type='bracket_open']").index()+2)){

		}else{
			if(typeof tr.prev().attr('data-group-id') === 'undefined'){
				tr.insertBefore(tr.prev());
			}else{
				tr.insertBefore($("[data-group-id='"+ tr.prev().attr('data-group-id') +"'][data-row-type='bracket_open']"));
			}
		}
	}else if(tr.attr('data-row-type') == 'bracket_open'){
		tr.prev().insertAfter($("[data-group-id='"+ tr.attr('data-group-id') +"'][data-row-type='bracket_close']"));
	}else{
		tr.insertBefore(tr.prev());
	}
	checkAllForLogOp();
}
function checkAllForLogOp(){
	$("#queryTable tbody tr").each(function(){
		var me = $(this);
		if(checkForLogOp(me)){
			me.children().eq(logop_index).find('select').show();
		}else{
			me.children().eq(logop_index).find('select').hide();
		}
	});
}
function moveDown(tr){
	if(tr.attr('data-row-type') == 'bracket_open'){
		$("[data-group-id='"+ tr.attr('data-group-id') +"'][data-row-type='bracket_close']").next().insertBefore(tr);
	}else if(tr.attr('data-row-type') == 'bracket_close'){
		if(tr.next().attr('data-row-type') != 'bracket_close' &&  tr.next().attr('data-row-type') != 'bracket_open'){
			tr.insertAfter(tr.next());
		}else if(tr.next().attr('data-row-type') == 'bracket_close'){
			//do nothing
		}else{
			tr.insertAfter($("[data-group-id='"+ tr.next().attr('data-group-id') +"'][data-row-type='bracket_close']"));
		}
	}else{
		tr.insertAfter(tr.next());
	}
	checkAllForLogOp();
}
//returns added row
function moveToQuery(elementType){
	if (elementType == 'randsubset_limitnumber'){
		var tr = $.parseHTML('<tr data-row-type="' + elementType + '"><td>&nbsp;</td><td>&nbsp;</td><td>' + Ptypes[elementType]['html'] + '</td></tr>');
		$(tr).append(deletionPrototype);
		$('#queryTable > tfoot').append(tr);
		limitUsed=true;
		buildDropdown();
		return tr;
	} else if (elementType == 'brackets'){
		addGrouping();
	} else {
		var tr = $.parseHTML('<tr data-row-type="' + elementType + '"><td>' + Ptypes[elementType]['html'] + '</td></tr>');
		$(tr).find('*').each(function(){
			if(typeof $(this).attr('id') !== 'undefined'){
				$(this).attr('id', $(this).attr('id').replace(Ptypes[elementType]['placeholder'], 'query_item_' + queryCount));
			}
			if(typeof $(this).attr('class') !== 'undefined'){
				$(this).attr('class', $(this).attr('class').replace(Ptypes[elementType]['placeholder'], 'query_item_' + queryCount));
			}
			//remove placeholder from name
			if(typeof $(this).attr('name') !== 'undefined'){
				$(this).attr('name', $(this).attr('name').replace(Ptypes[elementType]['placeholder'] + "_", ""));
			}
		});
		$(tr).append(deletionPrototype);
		//add the modified prototype row to the query table
		$('#queryTable > tbody').append(tr);
		//add the OR/AND dropdown box to the row
		var logicalOpPrototypeCopy = $.parseHTML(logicalOpPrototype);
		if(checkForLogOp(tr)){
			//tr.prepend(logicalOpPrototype);
			$(logicalOpPrototypeCopy).prependTo(tr);
		}else{
			$(logicalOpPrototypeCopy).prependTo(tr).children().hide();
		}
		$(tr).prepend(positionPrototype);
		//initiate the javascript in the prototype
		//replace placeholder with an ID
		var re = new RegExp(Ptypes[elementType]['placeholder'],"gi");
		var tmpJS = Ptypes[elementType]['jsEval'].replace(re, 'query_item_' + queryCount);
		'use strict';
		var tmp = eval(tmpJS);
		evaldCode['query_item_' + queryCount] = tmp;
		queryCount++;
		addDragEvents();
		return tr;
	}
}
function buildDropdown(){
	$('#protoDropdown li').remove()
	for (var type in Ptypes){
		var item = $.parseHTML('<li><a>' + Ptypes[type]['displayName'] + '</a></li>');
		$(item).attr("onclick", "javascript:handleAddClick('" + Ptypes[type]['type'] + "');");
		if((!limitUsed) || type != "randsubset_limitnumber"){
			$('#protoDropdown').append(item);
		}
	}
	$('#addDropdown').dropit(
		{	
			action: 'mouseenter' ,
			beforeShow: function(){
				//$('#addDropdown .dropit-submenu').css('width', '400px'); 
			}		
		} 
	)
}
//checks to see if the logical and/or oparators are required
function checkForLogOp(element){
	var attribute = $(element).prev().attr('data-row-type');
	if(attribute == "bracket_open" || typeof attribute === 'undefined'){
		return false;
	} else if($(element).attr('data-row-type') == "bracket_close"){
		return false;
	} else {
		return true;
	}
}
//returns group open row
function addGroupingOpen(target){
	if(typeof target === 'undefined'){
		openCount +=1;
		target = openCount;
	}
	var trOpen = $.parseHTML('<tr data-group-id="' + target + '" data-row-type="bracket_open"><td>(<input type="hidden" data-elem-name="type" value="open"></td></tr>');
	$('#queryTable tbody').append(trOpen);
	if(checkForLogOp(trOpen)){
		$(logicalOpPrototype).prependTo(trOpen);
	}else{
		$(logicalOpPrototype).prependTo(trOpen).children().hide();
	}
	$(trOpen).prepend(positionPrototypeOpenBracket);
	$(trOpen).append(deletionPrototype);
	return trOpen;
}
function addGroupingClose(target){
	if(typeof target === 'undefined'){
		target = openCount;
	}
	var trClose = $.parseHTML('<tr data-group-id="' + target + '" data-row-type="bracket_close"><td>)<input type="hidden" data-elem-name="type" value="close"></td></tr>');
	$('#queryTable tbody').append(trClose);
	$(trClose).prepend('<td>&nbsp;</td>');
	$(trClose).prepend(positionPrototypeCloseBracket);
	$(trClose).append('<td>&nbsp;</td>');
}
function addGrouping(){
	addGroupingOpen();
	addGroupingClose();
	addDragEvents();
	checkAllForLogOp();
}

