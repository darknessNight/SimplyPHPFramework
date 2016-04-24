$(document).ready(function() {
    $("div.hiddenMenu div.hide").hide();
    $("div.hiddenMenu").click(function(obj) {
	$(obj.target).children("div.hide").toggle(100);
    });
});
