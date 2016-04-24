/*
 * AJAX dla listy stron. Zawiera zapytania asynchroniczne dla wyszukiwania stron
 */
function getUrlVars() {
    var vars = {};
    window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m, key, value) {
	vars[key] = value;
    });
    return vars;
}

function SendQuery(obj) {
    if (obj.type == "submit")
	obj.preventDefault();
    $.ajax({
	url: "/?_app=CMS_Gallery&_action=list&sort=" + $("form#sortForm select[name=sort]").val()
	+ "&sortType=" + $("form#sortForm input[name=sortType]:checked").val()
		+ "&name=" + $("form#searchForm #searchName").val()
	+ "&author=" + $("form#searchForm #searchAuthor").val()
		+ "&AJAX=true"
	+ ($("form#searchForm input[name=onlyChecked]").size() > 0 ? '&onlyChecked=true' : ''),
	timeout: 5000,
	dataType: 'text',
	success: function(data, status, xhr) {
	    var dane = JSON.parse(data);
	    if (dane) {
		$("#listTable").html($(dane['table']).html());
		$("div.pagination").html(dane['pag']);

		$("form#searchForm #searchName").autocomplete({
		    source: dane['hints']['name']
		});
		$("form#searchForm #searchAuthor").autocomplete({
		    source: dane['hints']['author']
		});
	    }
	},
	complete: function(xhr, status) {
	}
    });
}

$(document).ready(function() {
    $("form#searchForm #searchName").autocomplete({
	source: []
    });
    $("form#searchForm #searchAuthor").autocomplete({
	source: []
    });

    $("form#searchForm input[type=text]").keyup(SendQuery);
    $("#searchForm").submit(SendQuery);
    $("#sortForm").submit(SendQuery);
    $("form#sortForm input").click(SendQuery);
    $("form#sortForm select").change(SendQuery);
});