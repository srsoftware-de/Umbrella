var BackDrop = null;
var DragGroup = null;

var PointGrabbed = null;
var GroupOrigin = null;
var SVGRoot = null;
var pt = null;
var reload_timer_handle = null;

function addFlow(origin,target){    
    
	DragGroup.removeAttribute('transform'); // restore original position of element
    DragGroup = null;
    
    var from;
    var name = 'new flow';
    switch (origin.getAttribute('class')){
        case 'connector': 
        	from = {process_connector_id:origin.id};
        	name = proposeFlowName(origin,name);
        	break;
        case 'terminal':  from = {terminal_id:origin.id}; break;
        default: return;
    }
    var to;
    switch (target.getAttribute('class')){
        case 'connector': 
        	to = {process_connector_id:target.id};
        	name = proposeFlowName(target,name);
        	break;
        case 'terminal': to = {terminal_id:target.id}; break;
        default: return;
    }
    
	if (origin.hasAttribute('place_id')) from['place_id'] = origin.getAttribute('place_id');
	if (target.hasAttribute('place_id')) to['place_id']   = target.getAttribute('place_id');
	var name = window.prompt(flow_prompt,name);
	if (name == null || name.trim() == ''){
		alert(no_name_set);
		return;
	}
	$.ajax({
		url: model_base+'add_flow',
		method: 'POST',
		data: { from: from, to: to, name: name },
		success: function(data,status,jqXHR){
			window.open(model_base+'edit_flow/'+data,'_blank');
		},
		complete: function(a,b){
			schedule_reload();
		}
	});
}

function click(evt){
	var href = location.href.replace(/\/\d*$/,'').replace(/[^\/]*$/,''); // first: strip trailing number, if present. then: strip page
	if (evt.target.id == 'backdrop') return;
	location.href = href + evt.target.id.replace(/_([^_]*)$/,'/$1');
}

function clickPos(evt){
	pt.x = evt.clientX;
	pt.y = evt.clientY;
	var cursorpt =  pt.matrixTransform(SVGRoot.getScreenCTM().inverse());
	return {x:cursorpt.x,y:cursorpt.y};
}

function crossHair(x,y,text){
	var vl = document.createElementNS('http://www.w3.org/2000/svg','line');
	vl.setAttribute('x1',x);
	vl.setAttribute('y1',y-10);
	vl.setAttribute('x2',x);
	vl.setAttribute('y2',y+10);
	vl.setAttribute('class','arrow');
	SVGRoot.appendChild(vl);
	var hl = document.createElementNS('http://www.w3.org/2000/svg','line');
	hl.setAttribute('x1',x-10);
	hl.setAttribute('y1',y);
	hl.setAttribute('x2',x+10);
	hl.setAttribute('y2',y);
	hl.setAttribute('class','arrow');
	SVGRoot.appendChild(hl);
	var txt = document.createElementNS('http://www.w3.org/2000/svg','text');
	txt.setAttribute('x',x);
	txt.setAttribute('y',y-10);
	txt.setAttribute('class','left');
	txt.innerHTML = text;
	SVGRoot.appendChild(txt);
}

function drag(evt){
	// if we don't currently have an element in tow, don't do anything
	
	if (DragGroup){
		var cp = clickPos(evt);
		var x = GroupOrigin.x + cp.x - PointGrabbed.x;
		var y = GroupOrigin.y + cp.y - PointGrabbed.y;
		DragGroup.setAttributeNS(null, 'transform', 'translate(' + x + ',' + y + ')');
	}
}

function drop(evt){
	// if we aren't currently dragging an element, don't do anything
	if ( DragGroup )	{
		DragGroup.setAttributeNS(null, 'pointer-events', 'all'); // turn the pointer-events back on, so we can grab this item later
		
		var elem = getMainComponent(DragGroup);
		
		if (elem == null){
			DragGroup = null;
			return;
		}

		var cls = elem.hasAttribute('class') ? elem.getAttribute('class') : null;
		var target = evt.target;
		
		if (cls == 'connector' && target.hasAttribute('class')){
            var target_class = target.getAttribute('class');
            if (target_class=='connector'||target_class=='terminal'){
                console.log('we dropped a connector onto a connector or terminal!');
                return addFlow(elem,target);
            }
		}
		if (cls == 'terminal' && target.hasAttribute('class') && target.getAttribute('class')=='connector'){
            console.log('we dropped a terminal onto a connector!');
            return addFlow(elem,target);
        }
		
		var cp = clickPos(evt);
		var moveX = cp.x - PointGrabbed.x;
		var moveY = cp.y - PointGrabbed.y;
		
		if (Math.abs(moveX) < 5 && Math.abs(moveY)<5) {
		// if not dragged: handle as click
			var link = model_base + cls + '/' + elem.id
			location.href = link + (elem.hasAttribute('place_id') ? '?place_id='+elem.getAttribute('place_id') : '');
			DragGroup = null;
			return;
		}
		
		// dragged:
		if (cls == 'connector'){ // connector has been dragged
			moveConnector(DragGroup,elem);
			DragGroup = null;
			return;
		}
		
		// process or terminal has been dragged
		var x = GroupOrigin.x + moveX;
		var y = GroupOrigin.y + moveY;
		updateElement(elem,{x: x, y: y});
		DragGroup = null;
	}
}

function getMainComponent(elem){
	if (elem.hasAttribute('id')) return elem;
	var children = elem.children;
	for (var i=0; i<children.length; i++){
		var main_component = getMainComponent(children[i]);
		if (main_component != null) return main_component;
	}
	return null;
}

function getTranslation(elem){
	if (!elem.hasAttribute('transform')) return {x:0, y:0};
	var trans = elem.getAttribute('transform');
	var parts  = /translate\(\s*([^\s,)]+)\s*,\s*([^\s,)]+)\s*\)/.exec(trans);
	return {x:+parts[1], y:+parts[2]};
}

function grab(evt){
	hideContextMenu();	
	if (evt.button != 0) return; // only respond to right button
	if (evt.target == BackDrop) return; // don't drag the background

	DragGroup = evt.target;
	
	// only move groups
	while (DragGroup.nodeName != 'g'){
		DragGroup = DragGroup.parentNode;
		if (DragGroup == null) return;
	}
	if (DragGroup.getAttribute('class') == 'arrow') return; // don't drag connectors
	
	if (reload_timer_handle != null) clearTimeout(reload_timer_handle);
	
	// move this element to the "top" of the display, so it is (almost)
	DragGroup.parentNode.appendChild( DragGroup );

	// turn off all pointer events to the dragged element, this does 2 things:
	DragGroup.setAttributeNS(null, 'pointer-events', 'none');
	var cp = clickPos(evt);
	PointGrabbed = {x: cp.x, y: cp.y};
	GroupOrigin = getTranslation(DragGroup);
}

function hideContextMenu(){
	$('#contextmenu').hide();
}

function initSVG(evt){
	SVGRoot = evt.target;
	pt = SVGRoot.createSVGPoint();
	BackDrop = evt.target.ownerDocument.getElementById('backdrop');
}

function menu(evt){
	var elem = evt.target;
	if (!elem.hasAttribute('class')) return false;
	evt.preventDefault();
	var cls = elem.getAttribute('class');
	if (cls=='process'||cls=='terminal'){
		if (!elem.hasAttribute('place_id')) return false;
		$('#contextmenu').show();
		$('#contextmenu').css({left:evt.clientX+'px',top:evt.clientY+'px'});
		$('#contextmenu button.delete').off('click');
		$('#contextmenu button.delete').on('click',function(){
			hideContextMenu();
			removeInstance(cls,elem.getAttribute('place_id'));
		});
	}
	return false;
}


function moveConnector(group,connector){
	var trans_g = getTranslation(group);
	var trans_c = getTranslation(connector);
	
	var x = trans_g.x + trans_c.x;
	var y = trans_g.y + trans_c.y;
	
	var angle = 180*Math.atan(x/-y)/Math.PI;
	if (y>0) {
		angle = 180 + angle;
	} else if (x<0)angle = 360 + angle;
	
	var process = parentGroup(group);
	var circle = getMainComponent(process);
	var rad = circle.getAttribute('r');
	
	x =  rad * Math.sin(angle*Math.PI/180);
	y = -rad * Math.cos(angle*Math.PI/180);
	
	group.removeAttribute('transform');
	connector.setAttribute('transform','translate('+x+','+y+')');
	updateElement(connector,{angle:angle});
}

function parentGroup(elem){
	do {
		var elem = elem.parentNode;
		if (elem == null) return null;
		if (elem.nodeName == 'g') return elem;
	} while (true);
}

function presetConnectorName(elem){
	var id = $("input[name=process_id]").attr('value');
	var out=elem.value;
	var input = $("input[name=name]");
	var start=id.length+1;
	input.attr('value',id+(out == 1?':out':':in'));
	input.each(function(){
		this.focus();
		this.selectionStart = start;
		this.selectionEnd = 1000;
	});
}

function proposeFlowName(connector,default_name){
	var connectorGroup = parentGroup(connector);
	var processGroup = (parent == null) ? null : parentGroup(connectorGroup);
	if (processGroup != null) {
		var texts = processGroup.getElementsByTagName('text');
		for (var index in texts){
			var text = texts[index].lastChild;
			if (text!=null) return text.nodeValue+':';
		}
	}
	return default_name;
}

function removeInstance(type,place_id){
	if (confirm('Remove '+type+' instance?')){
		$.ajax({
			url: model_base+'remove_place',
			method: 'POST',
			data: {type:type,place_id:place_id},
			complete: function(a,b){
				schedule_reload();
			}
		});
	}
}


function schedule_reload(){
	if (reload_timer_handle != null) clearTimeout(reload_timer_handle);
	reload_timer_handle = setTimeout(function(){location.reload()},1000);
}

function updateElement(elem,data){
	if (elem.hasAttribute('place_id')) data['place_id'] = elem.getAttribute('place_id');
	$.ajax({
		url: model_base+'update_'+elem.getAttribute('class')+'/'+elem.id,
		method: 'POST',
		data: data,
		complete: function(a,b){
			schedule_reload();
		}
	});
}

function wheel(evt){
	var elem = evt.target;
	var cls = elem.getAttribute('class'); 

	if (cls == 'process' && evt.shiftKey){
		evt.preventDefault();
		var r = elem.getAttribute('r')-10*Math.sign(evt.deltaY);
		if (r>10) {
			elem.setAttribute('r',r);
			updateElement(elem,{r: r});
		}
	}
	if (cls == 'terminal' && evt.shiftKey){
		evt.preventDefault();
		var d = -10*Math.sign(evt.deltaY);
		var w = +elem.getAttribute('width')+d;
		if (w>10) {
			elem.setAttribute('width',w);

			var texts = elem.parentNode.getElementsByTagName('text');
			if (texts.length > 0){
				var text = texts[0];
				text.setAttribute('x',+text.getAttribute('x')+d/2);
			}
			var ellipses = elem.parentNode.getElementsByTagName('ellipse');
			for (var i=0; i<ellipses.length;i++){
				var ellipse = ellipses[i];
				ellipse.setAttribute('cx',+ellipse.getAttribute('cx')+d/2)
				ellipse.setAttribute('rx',+ellipse.getAttribute('rx')+d/2)
				elem.setAttribute('stroke-dasharray','0,'+w+',40,'+w+',40');
			}
			updateElement(elem,{w: w});
		}
	}
}
