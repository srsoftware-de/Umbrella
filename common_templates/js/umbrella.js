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
	var url=window.location.href.replace('/add','/headings')+'?page='+encodeURIComponent(elem.value);
	console.log(url);
	$.ajax({
		url: url,
		dataType: "json",
		success: function(data){
			for (var index in data)addDescriptionOption(data[index]);
		}		
	});
}

getHeadingsTimer = null;

function getHeadings_delayed(){
	if (getHeadingsTimer != null) {
		clearTimeout(getHeadingsTimer);
		console.log('cleared timer #'+getHeadingsTimer);
	}
	getHeadingsTimer = window.setTimeout(getHeadings,200,this);
}