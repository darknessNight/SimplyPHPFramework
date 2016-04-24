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
	url: "/?_app=CMS_pages&_action=list&sort=" + $("form#sortForm select[name=sort]").val()
	+ "&sortType=" + $("form#sortForm input[name=sortType]:checked").val()
		+ "&name=" + $("form#searchForm #searchName").val()
		+ "&createdDate=" + $("form#searchForm #searchDate").val()
		+ "&author=" + $("form#searchForm #searchAuthor").val()
		+ "&AJAX=true",
	timeout: 5000,
	dataType: 'text',
	success: function(data, status, xhr) {
	    var dane = JSON.parse(data);
	    if (dane) {
		$("table#listTable").html($(dane['table']).html());
		$("div.pagination").html(dane['pag']);

		$("form#searchForm #searchName").autocomplete({
		    source: dane['hints']['name']
		});
		$("form#searchForm #searchDate").autocomplete({
		    source: dane['hints']['createdDate']
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
    $("form#searchForm #searchDate").autocomplete({
	source: []
    });
    $("form#searchForm #searchAuthor").autocomplete({
	source: []
    });

    $("form#searchForm input[type=text]").keyup(SendQuery);
    $("form#searchForm").submit(SendQuery);
    $("form#sortForm").submit(SendQuery);
    $("form#sortForm input").click(SendQuery);
    $("form#sortForm select").change(SendQuery);
});