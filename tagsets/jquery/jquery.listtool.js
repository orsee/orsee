function ListTool(rows, table, addButton, formName){
	this.formName = formName;
	this.rows = rows;
	this.dragEnabled = false;
	this.dragStartY = -1;
	this.dragSensitivity = 10;
	var me = this;
	this.elementSelector = table;
	this.buildDefaultList();
	if(typeof addButton !== 'undefined'){
		if(addButton !== null){
			this.addButtonSelector = addButton;
		}
	}
	this.buildDropdown();
	$("html").mousemove(function(event){
		me.handleMouseMove(event.clientY);
	});
	$("html").on('mouseup', function(){
		me.handleMouseUp();
	});
	$("html").on('touchend', function(){
		me.handleMouseUp();
	});
}
ListTool.prototype.getSortedRow = function(name){
	for(var i = 0; i < this.rowsSorted.length; i++){
		if(this.rowsSorted[i].name == name){
			return {row: this.rows[name], sortedID: i};
		}
	}
}
ListTool.prototype.sortRows = function(){
	//FIRST sort based on the given position. i.e move negative positions to the very end, etc
	var rowsSorted = [];
	$.each(this.rows, function(k, v){
		rowsSorted.push({name: k, fixed_position: v.fixed_position, drag: v.allow_drag, delete: v.allow_remove});
	});
	rowsSorted.sort(function(a, b){
		if(a.fixed_position < 0 && b.fixed_position < 0){
			if(a.fixed_position > b.fixed_position){
				return 1;
			}
		}
		else if(a.fixed_position > 0 && b.fixed_position > 0){
			if(a.fixed_position > b.fixed_position){
				return 1;
			}
		}else{
			if(a.fixed_position < b.fixed_position){
				return 1;
			}
		}
		return -1;
	});
	//THEN sort based on the type of row i.e. move all solid rows to the edge of the table etc
	//we need to define the order we require first
	var order = [
/*
+ NO MOVE     -----  Always snaps to defined fixed_position when adding     Solid type (can't move, always at top or bottom)
--------------   
+ MOVE        =====---  Only uses defined fixed_position if defaulted == true
- MOVE        /  
--------------
- NO MOVE     -----  Same as solid type but at the bottom for fixed_position < 0
*/
		{drag: false, positivePos: true},
		{drag: true, positivePos: true},
		{drag: true, positivePos: false},
		{drag: false, positivePos: false},
	];
	//checkGroup returns which group (defined above) a row belongs to
	function checkGroup(rowS){
		var found = -1;
		for(var i = 0; i < order.length; i++){
			if(rowS.fixed_position < 0 && order[i].positivePos == true){
				continue;
			}
			if(rowS.fixed_position >= 0 && order[i].positivePos == false){
				continue;
			}
			if(rowS.drag == false && order[i].drag == true){
				continue;
			}
			if(rowS.drag == true && order[i].drag == false){
				continue;
			}
			found = i;
			break;
		}
		return found;
	}
	//loop through every group type and pick up relevant rows - in essence, moving the rows so that they are in the same order as the group array
	var rowsSorted2 = [];
	for(var step = 0; step < order.length; step++){
		for(var i = 0; i < rowsSorted.length; i++){
			if(checkGroup(rowsSorted[i]) == step){
				rowsSorted2.push(rowsSorted[i]);
			}
		}
	}
	return rowsSorted2;
}
ListTool.prototype.buildDefaultList = function(){
	var me = this;
	this.rowsSorted = this.sortRows();
	for(var i = 0; i < this.rowsSorted.length; i++){
		if(this.rows[this.rowsSorted[i].name].on_list){
			this.addRow(this.rowsSorted[i].name, true);
		}
	}
}
ListTool.prototype.buildDropdown = function(){
	if(typeof this.addButtonSelector !== 'undefined'){
		var me = this;
		this.addButtonSelector.find('.dropdownItems').children().remove();
		var count = 0;
		$.each(this.rows, function(rowName, row){
			if(1 > me.elementSelector.find("[data-instance='" + rowName + "']").length){
				var item = $.parseHTML('<li><a>' + row.display_text + '</a></li>');
				$(item).on('click', function(){
					me.handleAddClick(rowName);
				});
				me.addButtonSelector.find('.dropdownItems').append(item);
				count++;
			}
		});
		if(count < 1){
			this.addButtonSelector.find('.button').attr('disabled', 'disabled');
		}else{
			this.addButtonSelector.find('.button').removeAttr('disabled');
		}
		this.addButtonSelector.dropit({
			action: 'mouseenter',
			beforeShow: function(){
				//me.addButtonSelector.find('.dropit-submenu').css('width', '600px');
				//me.addButtonSelector.find('.dropit-submenu').css('left', $(me.addButtonSelector).position().left + "px");
				//console.log($(me.addButtonSelector).position().left);
			}
		});
	}
}
ListTool.prototype.handleAddClick = function(rowName){
	this.addRow(rowName);
	this.buildDropdown();
}
ListTool.prototype.handleDeleteClick = function(rowSelector){
	rowSelector.remove();
	this.buildDropdown();
}
ListTool.prototype.handleMoveMouseDown = function(rowSelector){
	this.dragRow = rowSelector;
	this.dragEnabled = true;
	$('body, html').css( 'cursor', 'none' );
	$('button').css( 'cursor', 'none' );
	$(this.dragRow).addClass('highlight_row');
}
ListTool.prototype.handleMouseMove = function(evY){
	if(this.dragEnabled){
		if(this.dragStartY == -1){
			this.dragStartY = evY;
		}
		if(this.dragStartY - evY > this.dragSensitivity){
			this.dragStartY = evY - this.dragSensitivity;
			this.moveRow('up', this.dragRow);
		}else if(evY - this.dragStartY  > this.dragSensitivity){
			this.dragStartY = evY + this.dragSensitivity;
			this.moveRow('down', this.dragRow);
		}
	}
}
ListTool.prototype.handleMouseUp = function(){
	this.dragEnabled = false;
	this.dragStartY = -1;
	$('body, html').css( 'cursor', 'auto' );
	$('button').css( 'cursor', 'auto' );
	if(typeof this.dragRow !== 'undefined'){
		$(this.dragRow).removeClass('highlight_row');
	}
}
ListTool.prototype.moveRow = function(direction, row){
	if(direction == 'up'){
		if(row.prev().length == 1){
			if(this.rows[row.prev().attr('data-instance')].allow_drag){
				row.insertBefore(row.prev());
			}
		}
	}else{
		if(row.next().length == 1){
			if(this.rows[row.next().attr('data-instance')].allow_drag){
				row.insertAfter(row.next());
			}
		}
	}
}
ListTool.prototype.addRow = function(rowName, nocheck){
	if(typeof nocheck == 'undefined'){
		nocheck = false;
	}
	var me = this;
	var row = this.rows[rowName];
	var debugtext = "";
	//debugtext = row.fixed_position;
	var rowString = '<tr data-instance="' + rowName + '"><td>'+debugtext+'<input type="hidden" name="' + this.formName + '[]" value="' + rowName + '" /></td>' + row.cols;
	rowString = rowString + '<td></td></tr>';
	var addRow = $.parseHTML(rowString);
	if(row.allow_drag){
		var moveCol = $.parseHTML('<button class="fa-bars dragHandle" style="font-family: FontAwesome; height: 2em; width: 2em; font-size: 1em; cursor: auto;" onclick="return false;"></button>');
		$(moveCol).on('mousedown', function(){
			me.handleMoveMouseDown($(this).parent().parent());
		});
		$(moveCol).on('touchstart', function(){
			me.handleMoveMouseDown($(this).parent().parent());
		});
		$(moveCol).on('touchmove', function(event){
			if(event.originalEvent.targetTouches.length == 1){
				me.handleMouseMove(event.originalEvent.targetTouches[0].clientY);
			}
			event.preventDefault();
		});
		$(addRow).children().first().append(moveCol);
	}
	if(row.allow_remove){
		var deleteCol = $.parseHTML('<i class="fa fa-times-circle" style="color: red;"></i>');
		$(deleteCol).on('click', function(){
			me.handleDeleteClick($(this).parent().parent());
		});
		$(addRow).children().last().append(deleteCol);
	}
	//nocheck is used when we don't need to follow the defined order of rows
	if(nocheck || $(this.elementSelector).find("[data-instance]").length < 1){
		this.elementSelector.find('tbody').append(addRow);
	}else if($(this.elementSelector).find("[data-instance]").length == 1){
		//if there is only one row - decide weather to add the row before or after the existing row
		var onlyRow = $(this.elementSelector).find("tbody").children().first();
		if(me.getSortedRow(onlyRow.attr('data-instance')).sortedID > me.getSortedRow(rowName).sortedID){
			$(addRow).insertBefore(onlyRow);
		}else{
			$(addRow).insertAfter(onlyRow);
		}
	}else{
		//if there are multiple rows, find the closest neighbour
		var lowestDistance = me.rowsSorted.length - 1;
		var closestID = me.rowsSorted.length - 1;
		$.each($(this.elementSelector.find('tbody tr')), function(k, v){
			var currentRow = $(v).attr('data-instance');
			if(Math.abs(me.getSortedRow(currentRow).sortedID - me.getSortedRow(rowName).sortedID) <= Math.abs(lowestDistance)){
				closestID = me.getSortedRow(currentRow).sortedID;
				lowestDistance = me.getSortedRow(currentRow).sortedID - me.getSortedRow(rowName).sortedID;
			}
		});
		var subject = $(this.elementSelector).find("[data-instance='" + me.rowsSorted[closestID].name + "']");
		if(lowestDistance > 0){
			$(addRow).insertBefore(subject);
		}else{
			$(addRow).insertAfter(subject);
		}
	}
}
