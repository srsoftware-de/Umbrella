var SVGDocument = null;
var SVGRoot = null;

var TrueCoords = null;
var GrabPoint = null;
var BackDrop = null;
var DragTarget = null;

function Init(evt){
	SVGDocument = evt.target.ownerDocument;
	SVGRoot = evt.target;

	// these svg points hold x and y values...
	//    very handy, but they do not display on the screen (just so you know)
	TrueCoords = SVGRoot.createSVGPoint();
	GrabPoint = SVGRoot.createSVGPoint();

	// this will serve as the canvas over which items are dragged.
	//    having the drag events occur on the mousemove over a backdrop
	//    (instead of the dragged element) prevents the dragged element
	//    from being inadvertantly dropped when the mouse is moved rapidly
	BackDrop = SVGDocument.getElementById('backdrop');
}

function Wheel(evt){
	var elem = evt.target;
	if (evt.target.nodeName == 'circle'){
		evt.preventDefault();

		var cls = elem.getAttribute('class');
		if (cls == 'process'){
			var r = elem.getAttribute('r')-evt.deltaY/3;
			if (r>10) {
				elem.setAttribute('r',r);
				updateElement(elem,{r: r});
			}
		} else if(cls == 'connector'){
			var xforms = elem.getAttribute('transform');

			var parts  = /rotate\(\s*([^\s,)]+)[ ,]([^\s,)]+)[ ,]([^\s,)]+)/.exec(xforms);
			var a = +parts[1] + evt.deltaY/3;
			var x = +parts[2];
			var y = +parts[3];
			console.log({a:a,x:x,y:y});
			elem.setAttribute('transform','rotate('+a+','+x+','+y+')');
			updateElement(elem,{angle: a});
		}
	}
}

function updateElement(elem,data){
	var script = 'update_'+elem.id.replace('_','/');
	$.ajax({
		url: script,
		method: 'POST',
		data: data,
	});
}

function Grab(evt){
	// you cannot drag the background itself, so ignore any attempts to mouse down on it
	if (evt.target == BackDrop) return;

	//set the item moused down on as the element to be dragged
	DragTarget = evt.target;

	// only move groups
	while (DragTarget.nodeName != 'g'){
		DragTarget = DragTarget.parentNode;
		if (DragTarget == null) return;
	}

	// move this element to the "top" of the display, so it is (almost)
	DragTarget.parentNode.appendChild( DragTarget );

	// turn off all pointer events to the dragged element, this does 2 things:
	DragTarget.setAttributeNS(null, 'pointer-events', 'none');

	// we need to find the current position and translation of the grabbed element, so that we only apply the differential between the current location and the new location
	var transMatrix = DragTarget.getCTM();
	GrabPoint.x = TrueCoords.x - Number(transMatrix.e);
	GrabPoint.y = TrueCoords.y - Number(transMatrix.f);
};


function Drag(evt){
	// account for zooming and panning
	GetTrueCoords(evt);

	// if we don't currently have an element in tow, don't do anything
	if (DragTarget){
		// account for the offset between the element's origin and the
		//    exact place we grabbed it... this way, the drag will look more natural
		var newX = TrueCoords.x - GrabPoint.x;
		var newY = TrueCoords.y - GrabPoint.y;

		// apply a new tranform translation to the dragged element, to display
		//    it in its new location
		DragTarget.setAttributeNS(null, 'transform', 'translate(' + newX + ',' + newY + ')');
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
	if ( DragTarget )	{
		DragTarget.setAttributeNS(null, 'pointer-events', 'all'); // turn the pointer-events back on, so we can grab this item later
		var elem = getMainComponent(DragTarget);
		if (elem != null){
			var x = Math.round(+elem.getAttribute('cx') + TrueCoords.x - GrabPoint.x);
			var y = Math.round(+elem.getAttribute('cy') + TrueCoords.y - GrabPoint.y);
			updateElement(elem,{x: x, y: y});
		}
		DragTarget = null;
	}
};


function GetTrueCoords(evt){
	// find the current zoom level and pan setting, and adjust the reported mouse position accordingly
	var newScale = SVGRoot.currentScale;
	var translation = SVGRoot.currentTranslate;
	TrueCoords.x = (evt.clientX - translation.x)/newScale;
	TrueCoords.y = (evt.clientY - translation.y)/newScale;
};