var BackDrop = null;
var DragGroup = null;

var PointGrabbed = null;
var GroupOrigin = null;

function Init(evt){
	BackDrop = evt.target.ownerDocument.getElementById('backdrop');
}

function Wheel(evt){
	var elem = evt.target;
	var cls = elem.getAttribute('class');

	if (evt.target.nodeName == 'circle'){

		if (cls == 'process' && evt.shiftKey){
			evt.preventDefault();
			var r = elem.getAttribute('r')-evt.deltaY/3;
			if (r>10) {
				elem.setAttribute('r',r);
				updateElement(elem,{r: r});
			}
		} else if(cls == 'connector'){
			evt.preventDefault();
			var xforms = elem.getAttribute('transform');

			var parts  = /rotate\(\s*([^\s,)]+)[ ,]([^\s,)]+)[ ,]([^\s,)]+)/.exec(xforms);
			var a = +parts[1] + evt.deltaY/3;
			var x = +parts[2];
			var y = +parts[3];
			console.log({a:a,x:x,y:y});
			elem.setAttribute('transform','rotate('+a+','+x+','+y+')');
			updateElement(elem,{angle: a});
		}
	} else {
		if (cls == 'terminal' && evt.shiftKey){
			evt.preventDefault();
			var d = -evt.deltaY/3;
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
}

function updateElement(elem,data){
	var script = 'update_'+elem.id.replace(/_([^_]*)$/,'/$1');
	$.ajax({
		url: script,
		method: 'POST',
		data: data,
		complete: function(a,b){
			location.reload();
		}
	});
}

function getTranslation(elem){
	if (!elem.hasAttribute('transform')) return {x:0, y:0};
	var trans = elem.getAttribute('transform');
	var parts  = /translate\(\s*([^\s,)]+)[ ,]([^\s,)]+)/.exec(trans);
	return {x:+parts[1], y:+parts[2]};
}

function Grab(evt){
	if (evt.button != 0) return; // only respond to right button

	if (evt.target == BackDrop) return; // don't drag the background

	if (evt.target.getAttribute('class') == 'connector') return; // don't drag connectors

	DragGroup = evt.target;

	// only move groups
	while (DragGroup.nodeName != 'g'){
		DragGroup = DragGroup.parentNode;
		if (DragGroup == null) return;
	}

	// move this element to the "top" of the display, so it is (almost)
	DragGroup.parentNode.appendChild( DragGroup );

	// turn off all pointer events to the dragged element, this does 2 things:
	DragGroup.setAttributeNS(null, 'pointer-events', 'none');

	PointGrabbed = {x: evt.offsetX, y: evt.offsetY};
	GroupOrigin = getTranslation(DragGroup);
};


function Drag(evt){
	// if we don't currently have an element in tow, don't do anything
	if (DragGroup){
		var x = GroupOrigin.x + evt.offsetX - PointGrabbed.x;
		var y = GroupOrigin.y + evt.offsetY - PointGrabbed.y;
		DragGroup.setAttributeNS(null, 'transform', 'translate(' + x + ',' + y + ')');
	}
};

function getMainComponent(group){
	var children = group.children;
	for (var i=0; i<children.length; i++){
		var child = children[i];
		if (child.hasAttribute('id')) return child;
	}
	return null;
}
function Drop(evt){
	// if we aren't currently dragging an element, don't do anything
	if ( DragGroup )	{
		DragGroup.setAttributeNS(null, 'pointer-events', 'all'); // turn the pointer-events back on, so we can grab this item later
		var elem = getMainComponent(DragGroup);
		if (elem != null){
			var x = GroupOrigin.x + evt.offsetX - PointGrabbed.x;
			var y = GroupOrigin.y + evt.offsetY - PointGrabbed.y;
			updateElement(elem,{x: x, y: y});
		}
		DragGroup = null;
	}
};