var grid_width = 40;
		{
			var ruleSelText = rule.selectorText;
	if(this.id.length > 10)
	{
	}
}
	var pixels = getElementsByClassName(document, 'div', 'pixel');

	if(status == 'on')
		{
			pixels[i].style.width = '10px';
			pixels[i].style.height = '10px';
			pixels[i].style.borderLeft = 'none';	
			pixels[i].style.borderBottom = 'none';	
		}	
	}
		for(i = 0; i < pixels.length; i++)
		{
			pixels[i].style.width = '9px';
			pixels[i].style.height = '9px';
			pixels[i].style.borderLeft = '1px solid #565656';	
			pixels[i].style.borderBottom = '1px solid #565656';	
		}	
	}
	alert('Setting painting mode: ' + mode);

function promoe_init()
{
	if(document.getElementById('promoe_paintboard'))
	{
		create_promoe_paintboard();	
	}

	var color_swatches = getElementsByClassName(document, 'div', 'promoe_color');
	for(var i = 0; i < color_swatches.length; i++)
	{
		color_swatches[i].onclick = set_active_color;
	}
	
	document.getElementById('promoe_drawing_mode_pen').onclick = function()
	{
		painting_mode = 'pen';
	}
	
	document.getElementById('promoe_drawing_mode_flood_fill').onclick = function()
	{
		painting_mode = 'flood_fill';
	}

	document.getElementById('promoe_grid_control').onclick = function()
	{
		if(this.value == 'Visa rutnätet')
		{
			this.value = 'Dölj rutnätet';
			set_grid('off');
		}
		else
		{
			this.value = 'Visa rutnätet';	
			set_grid('on');
		}
	}
	
	document.getElementById('promoe_restart_button').onclick = function()
	{
		if(confirm('Vill du verkligen börja rita en ny bild? Om du inte har sparat din bild kommer den försvinna!'))
		{
			window.location = '?create';	
		}
	}
	
	document.getElementById('promoe_preview_button').onclick = function()
	{
		document.getElementById('promoe_preview').innerHTML = '<img src="/annat/promoe_png.php?imagestring=' + get_image_string() + '&rand=' + Math.random();
	}
	
	document.getElementById('promoe_save_button').onclick = function()
	{
		var img_name;
		if(img_name = prompt('Välj ett namn på din bild:'))
		{
			window.location = '?save&name=' + img_name + '&imagestring=' + get_image_string() + '&parent=' + promoe_parent;
		}
	}
	
	if(document.getElementById('promoe_hype_button'))
	{
		document.getElementById('promoe_hype_button').onclick = function()
		{
			xmlhttp_ping('/annat/promoe_hype.php?id=' + promoe_id);
			this.value = 'Tack för din hype';
			this.disabled = 'disabled';
		}	
	}

}


womAdd('promoe_init()');