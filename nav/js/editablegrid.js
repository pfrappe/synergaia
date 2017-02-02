
EditableGrid.prototype.initializeGrid = function() {
	with (this) {
		tableRendered = function() {
			this.updatePaginator("#"+idTable+"_paginatorHaut");
			this.updatePaginator("#"+idTable+"_paginatorBas");
		};

        this.shortMonthNames=['Janv.', 'Févr.', 'Mars', 'Avril', 'Mai', 'Juin', 'Juil.', 'Août', 'Sept.', 'Oct.', 'Nov.', 'Déc.'];

		// render the grid
		renderGrid(idTable+'_table');

		// reset filter and sort
		this.filter(false);
		//this.sort(0,false);

		// filter when something is typed into filter
		_$(idTable + '_filter').onkeyup = function() {
			editableGrid.filter(_$(idTable + '_filter').value);
		};

		// bind page size selector
		$("#"+idTable+"_pagesize").val(pageSize).change(function() {
			editableGrid.setPageSize($("#"+idTable+"_pagesize").val());
		});
		
	}
};

// function to render the paginator control
EditableGrid.prototype.updatePaginator = function(idPaginator)
{
	function image(relativePath) {
		return "themes/defaut/img/icons/16x16/nav/" + relativePath;
	}
	
	idTable=this.idTable;
	
	var paginator = $(idPaginator).empty();
	var nbPages = this.getPageCount();

	// get interval
	var interval = this.getSlidingPageInterval(20);
	if (interval == null) return;
	
	// get pages in interval (with links except for the current page)
	var pages = this.getPagesInInterval(interval, function(pageIndex, isCurrent) {
		if (isCurrent) return "<b>" + (pageIndex + 1) + "</b>";
		return $("<a>").css("cursor", "pointer").html(pageIndex + 1).click(function(event) { editableGrid.setPageIndex(parseInt($(this).html()) - 1); });
	});
		
	// "first" link
	var link = $("<a>").html("<img src='" + image("gofirst.png") + "'/>&nbsp;");
	if (!this.canGoBack()) link.css({ opacity : 0.4, filter: "alpha(opacity=40)" });
	else link.css("cursor", "pointer").click(function(event) { editableGrid.firstPage(); });
	paginator.append(link);

	// "prev" link
	link = $("<a>").html("<img src='" + image("prev.png") + "'/>&nbsp;");
	if (!this.canGoBack()) link.css({ opacity : 0.4, filter: "alpha(opacity=40)" });
	else link.css("cursor", "pointer").click(function(event) { editableGrid.prevPage(); });
	paginator.append(link);

	// pages
	for (p = 0; p < pages.length; p++) {
		if (p == (pages.length-1)) paginator.append(pages[p]).append(" ");
		else paginator.append(pages[p]).append(" | ");
	}
	
	// "next" link
	link = $("<a>").html("<img src='" + image("next.png") + "'/>&nbsp;");
	if (!this.canGoForward()) link.css({ opacity : 0.4, filter: "alpha(opacity=40)" });
	else link.css("cursor", "pointer").click(function(event) { editableGrid.nextPage(); });
	paginator.append(link);

	// "last" link
	link = $("<a>").html("<img src='" + image("golast.png") + "'/>&nbsp;");
	if (!this.canGoForward()) link.css({ opacity : 0.4, filter: "alpha(opacity=40)" });
	else link.css("cursor", "pointer").click(function(event) { editableGrid.lastPage(); });
	paginator.append(link);
};
