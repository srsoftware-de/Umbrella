function toggle(selector){
	$(selector).toggle("slow");
}

function addDescriptionOption(text){
	if (text.trim() == '') return;
	var select = $('select[name=alt_comment]');
	if (select.length == 0){
		var area=$('textarea[name=comment]');
		var hint= area.attr('descr');
		$('<select/>',{name: 'alt_comment'})
			.append($('<option/>',{value: '',text: hint}))
			.insertBefore(area);		
		select = $('select[name=alt_comment]');
		select.on('change',function(){
			$('textarea[name=comment]').val(select.find('option:selected').text());
		});
	}
	select.append($('<option/>',{text: text}));
}

function getHeadings(elem){
	$('select[name=alt_comment]').remove();
	$('textarea[name=comment]').val('');
	var url=window.location.href.replace(/\/([^\/]*)$/,'/headings')+'?page='+encodeURIComponent(elem.value);
	$.ajax({
		url: url,
		dataType: "json",
		success: function(data){
			for (var index in data.headings) addDescriptionOption(data.headings[index]);
		}		
	});
}

var preview_timer = 0;

function keyEvent(e){
	if (e.ctrlKey){		
		if (e.key === 'f') { // display search form on Ctrl+F
			e.preventDefault();
			$('.search *').show();
			$('.search form>input').focus();
		}
		return;
	}
	if (e.altKey){
		switch (e.keyCode){
			case 38:
				$('a.parent').each(function(){this.click()});
				return;
			case 39:
				$('a.next').each(function(){this.click()});
				return
			default:
				console.log(e.keyCode);
		}
	}
	switch (e.keyCode){
		case 27: // ESC
			if (document.location.href.includes("/add")) window.history.back();
			if (document.location.href.includes("/edit")) window.history.back();
			return;
		case 107: // +
			var active = document.activeElement.tagName;
			switch (active){
				case 'TEXTAREA':
					// do not activate add-link when in these fields
					return;
			}
			var addLink = $('a[href^="add"]')[0];
			if (addLink) addLink.click();
			return;
	}
	if (e.target.id == 'preview-source') {
		clearTimeout(preview_timer);
		preview_timer = setTimeout(preview,750,e.target);
	}
	
	console.log(e);
}

getHeadingsTimer = null;

function getHeadings_delayed(){
	if (getHeadingsTimer != null) clearTimeout(getHeadingsTimer);
	getHeadingsTimer = window.setTimeout(getHeadings,200,this);
}

function preview(txt){
	let target = document.getElementById('preview');
	if (!target) return;
	$(target).addClass('loading');
	let url = 'https://umbrella.srsoftware.de/user/preview';
	$.ajax({
		method: 'POST',
		url: url,
		data: { source : txt.value },
		success: function(content,status,xhr){
			target.innerHTML=content;
		    $(target).removeClass('loading');
		    setTimeout(() => {
			$(txt).css('height',Math.max(target.clientHeight,200));
		    },200);
		},
		error: function(a,b,c){
			console.log("preview request failed!");
		}
	});	
}
document.addEventListener('keydown',keyEvent);
