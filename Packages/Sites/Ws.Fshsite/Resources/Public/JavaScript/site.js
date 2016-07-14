$(document).ready(function()
{
$(".firstLevel.li.normal").hover(function()
{
	
	$(this).children("ul").show();
},function()
{
	$(this).children("ul").hide();
});
});
