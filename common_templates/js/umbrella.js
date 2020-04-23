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
			$('input[name=tags]').attr('value',data.keywords.join(' '));
		}		
	});
}

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
}

getHeadingsTimer = null;

function getHeadings_delayed(){
	if (getHeadingsTimer != null) clearTimeout(getHeadingsTimer);
	getHeadingsTimer = window.setTimeout(getHeadings,200,this);
}

document.addEventListener('keydown',keyEvent);