plugin.loadLang();

if(plugin.canChangeMenu())
{
	theWebUI.getWebSeedSource = function()
	{
		var sr = this.getTable("trt").rowSel;
		var hash = '';
		for( var k in sr )
		{
			if( sr[k] && (k.length == 40) )
			{
				if( hash )
					hash += " " + k;
				else
					hash = k;
			}
		}
		$("#webseedsrchash").val(hash);
		$("#getWebSeedSource").submit();
	}

	plugin.createMenu = theWebUI.createMenu;
	theWebUI.createMenu = function( e, id )
	{
		plugin.createMenu.call(this, e, id);
		if(plugin.enabled)
		{
			var el = theContextMenu.get( theUILang.Properties );
			if( el )
				theContextMenu.add( el, [theUILang.getWebSeedSource,  "theWebUI.getWebSeedSource()"] );
		}
	}
}

plugin.onLangLoaded = function()
{
	$(document.body).append($("<iframe name='webseedsrcfrm'/>").css({visibility: "hidden"}).attr( { name: "webseedsrcfrm", id: "webseedsrcfrm" } ).width(0).height(0).load(function()
	{
	        $("#webseedsrchash").val('');
		var d = (this.contentDocument || this.contentWindow.document);
		if(d && (d.location.href != "about:blank"))
			try { eval(d.body.textContent ? d.body.textContent : d.body.innerText); } catch(e) {}
	}));
	$(document.body).append(
		$('<form action="plugins/webseedsource/action.php" id="getWebSeedSource" method="get" target="webseedsrcfrm">'+
			'<input type="hidden" name="hash" id="webseedsrchash" value="">'+
		'</form>').width(0).height(0));
}

plugin.onRemove = function()
{
	$('#webseedsrcfrm').remove();
	$('#getWebSeedSource').remove();
}
