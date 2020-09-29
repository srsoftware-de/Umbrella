CanvasRenderingContext2D.prototype.roundRect = function (center,dim,style) {
		var x = center.x-dim.w/2;
		var y = center.y-dim.h/2;
		this.strokeStyle = style;
		this.beginPath();
		this.moveTo(x+dim.r, y);
		this.arcTo(x+dim.w, y,   x+dim.w, y+dim.h, dim.r);
		this.arcTo(x+dim.w, y+dim.h, x,   y+dim.h, dim.r);
		this.arcTo(x,   y+dim.h, x,   y,   dim.r);
		this.arcTo(x,   y,   x+dim.w, y,   dim.r);
		this.closePath();
		return this;
}

CanvasRenderingContext2D.prototype.partition = function (style) {
	var w = canvas.width;
	var h = canvas.height;
	this.strokeStyle = style;
	this.moveTo(10,0);   this.lineTo(10,w);
	this.moveTo(w-10,0); this.lineTo(w-10,h);
	this.moveTo(0,10);   this.lineTo(w,10);
	this.moveTo(0,h-10); this.lineTo(w,h-10);
	this.moveTo(0,h/2);  this.lineTo(w,h/2);
	this.moveTo(w/2,0);  this.lineTo(w/2,h);
	return this;
}

CanvasRenderingContext2D.prototype.clear = function () {
	canvas.width = canvas.clientWidth;
	canvas.height = canvas.clientHeight;	
	this.clearRect(0, 0, canvas.width, canvas.height); // clear
	this.partition('#cccccc').stroke();	
}

// init
var canvas = document.getElementById("canvas");
var g = canvas.getContext("2d");

function move(mm){	
	mm.pos.x = (4*mm.pos.x + mm.target.x) / 5;
	mm.pos.y = (4*mm.pos.y + mm.target.y) / 5;
}


function renderMindmap(){
	console.log("render");
	g.clear();
	
	if (mindmap.pos == undefined) mindmap.pos = {x:canvas.width,y:canvas.height/2};
	mindmap.target = {x:canvas.width/2,y:canvas.height/2};
	move(mindmap);
	
	g.roundRect(mindmap.pos,{w:100,h:50,r:15},'#000000').stroke();
	setTimeout(renderMindmap,50);
	
}
