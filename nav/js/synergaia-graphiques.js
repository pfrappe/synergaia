/** SynerGaïa
 * @version 1.3.2 Tracer de courbes graphiques (histogramme, seceteurs, courbes)
 * @version 2.3 largeur adaptée au nombre de colonnes
 * @version 2.4 title pour survol
 */

function graphiqueHistogramme(idBloc,data) {
	var margin = {top: 20, right: 100, bottom: 20, left: 20},
		padding = {top: 60, right: 60, bottom: 60, left: 60},
		outerWidth = 1300,
		outerHeight = 500,
		innerWidth = outerWidth - margin.left - margin.right,
		innerHeight = outerHeight - margin.top - margin.bottom,
		width = innerWidth - padding.left - padding.right,
		height = innerHeight - padding.top - padding.bottom;
		largeurBarre = Math.min(width / data.length,40),
		widthg = data.length * largeurBarre, // 2.3
		legendeHauteur = Math.min(18*(Math.max(data.length,6)),height),
		legendeEspaceGauche = 20; // 2.3
	var color = d3.scale.category20();
	var x = d3.scale.linear()
		.domain([0, 1])
		.range([0, largeurBarre]);	
	var y = d3.scale.linear()
		.domain([0, d3.max(data, function(d) { return d[1]; })])
		.rangeRound([0, height]);
	var tooltip = d3.select("body")
		.append("div")
		.style("position", "absolute")
		.style("z-index", "10")
		.style("visibility", "hidden")
		.text("a simple tooltip");
	
	// Définition du graphique
	var chart = d3.select(idBloc).append("svg")
		.attr("width", width + margin.left + margin.right)
		.attr("height", height + margin.top + margin.bottom)
		.append("g")
		.attr("transform", "translate(" + margin.left + "," + margin.top + ")");
	// Définition d'une colonne	
	var colonne = chart.selectAll("rect")
		.data(data)
		.enter().append("g")
		.attr("transform", function(d, i) { return "translate(" + (x(i) - .5) + "," + height + ")"; })
		.on("click", function(d, i) { gh(d, i, this);});
	// 2.4 Etiquette si survol
	colonne.append("title").text( function(d){return d[0] + ' : ' + d[1]; });
	// Colonne de donnée
	colonne.append("rect")
		.attr("x", 0)
		.attr("y", function(d,i) { return -y(d[1]); })
		.attr("width", largeurBarre)
		.attr("height", function(d) { return y(d[1]); })
		.style("fill", function(d,i) { return color(i); })
		.attr("class", "colonne");
	// Etiquette de colonne
	colonne.append("text")
		.attr("x", largeurBarre*0.5)
		.attr("y", function(d) { return -y(d[1]); })
		.attr("text-anchor", "middle")
		.text(function(d) { return d[1]; })
		.attr("class", "etiquette")
		.style("stroke", "#000");

	// Axe horizontal
	chart.append("line")
		.attr("x1", 0)
		.attr("x2", widthg) // 2.3
		.attr("y1", height - .5)
		.attr("y2", height - .5)
		.style("stroke", "#000");
	// Axe vertical
	chart.append("line")
		.attr("x1", 0)
		.attr("x2", 0)
		.attr("y1", 0)
		.attr("y2", height)
		.style("stroke", "#000");
	// Place les libellés en X
	chart.selectAll("axe")
		.data(data)
		.enter().append("text")
			.attr("x", function(d, i) {return x(i);})
			.attr("y", function(d, i) { if(i%2 == 0) {return height + 10;} else {return height + 20;}})
			.attr("class", "libelle")
			.text(function(d){return d[0];}); //			.style("stroke", "#000");

	// Bloc de légende
	var legende = chart.selectAll(".legende")
		.data(data)
		.enter().append("g")
		.attr("transform", "translate(" + (widthg + legendeEspaceGauche) + ","+ ((height - legendeHauteur)/2) +")") // 2.3
		.attr("class", "legende");
	// Rectangle de la légende
	legende.append("rect")
		.attr("x", 0)
		.attr("y", function(d,i) { return (i*legendeHauteur/data.length); })
		.attr("width", margin.right)
		.attr("height", legendeHauteur/data.length)
		.style("fill", function(d,i) { return color(i); });
	// Texte de la légende
	legende.append("text")
		.attr("transform", function(d,i) { return "translate(10," + ((i+.5)*legendeHauteur/data.length) + ")"; })
		.attr("dy", ".35em")
		.style("text-anchor", "left")
		.attr("class", "libelle")
		.text(function(d) { return d[0]; });
}

function graphiqueSecteurs(idBloc,data) {
	var margin = {top: 20, right: 20, bottom: 30, left: 40},
		width = 1300 - margin.left - margin.right,
		height = 500 - margin.top - margin.bottom
		radius = Math.min(width, height) / 2,
		legendeHauteur = Math.min(18*(Math.max(data.length,6)),height);
	var tooltip = d3.select("body")
		.append("div")
		.style("position", "absolute")
		.style("z-index", "10")
		.style("visibility", "hidden")
		.text("a simple tooltip");
	var color = d3.scale.category20();
	var arc = d3.svg.arc()
		.outerRadius(radius - 10)
		.innerRadius(0);
	var pie = d3.layout.pie()
		.sort(null)
		.value(function(d) { return d[1]; });
	// Définition du graphique
	var chart = d3.select(idBloc).append("svg:svg")
		.attr("width", width)
		.attr("height", height)
		.append("g")
		.attr("transform", "translate(" + width / 3 + "," + height / 2 + ")");
	// Secteur coloré
	var secteur = chart.selectAll(".secteur")
		.data(pie(data))
		.enter().append("g")
		.attr("class", "secteur");
	secteur.append("path")
		.attr("d", arc)
		.style("fill", function(d, i) { return color(i); })
		.on("mouseover", function(d){tooltip.text(d.data[1]);
			return tooltip.style("visibility", "visible");})
		.on("mousemove", function(){return tooltip.style("top",
			(d3.event.pageY-10)+"px").style("left",(d3.event.pageX+10)+"px");})
		.on("mouseout", function(){return tooltip.style("visibility", "hidden");});
	// Etiquette sur le secteur
	var etiquette = chart.selectAll(".etiquette")
		.data(pie(data))
		.enter().append("g")
		.attr("class", "etiquette");
	etiquette.append("text")
		.attr("transform", function(d) { return "translate(" + arc.centroid(d) + ")"; })
		.attr("dy", ".35em")
		.style("text-anchor", "middle")
		.attr("class", "etiquette")
		.text(function(d) { return d.data[1]; });
	// Bloc de légende
	var legende = chart.selectAll(".legende")
		.data(pie(data))
		.enter().append("g")
		.attr("transform", "translate(" + (radius * 1.2)  + "," + (-legendeHauteur/2) + ")")
		.attr("class", "legende");
	// Rectangle de la légende
	legende.append("rect")
		.attr("x", 0)
		.attr("y", function(d,i) { return (i*legendeHauteur/data.length); })
		.attr("width", width / 5)
		.attr("height", legendeHauteur/data.length)
		.style("fill", function(d, i) { return color(i); });
	// Texte de la légende
	legende.append("text")
		.attr("transform", function(d,i) { return "translate(10," + ((i+.5)*legendeHauteur/data.length) + ")"; })
		.attr("dy", ".35em")
		.style("text-anchor", "left")
		.attr("class", "libelle")
		.text(function(d, i) { return d.data[0] ; });
}

function graphiqueCourbes(idBloc,data,echelle) {
	var margin = {top: 20, right: 20, bottom: 20, left: 20},
		padding = {top: 20, right: 20, bottom: 20, left: 20},
		outerWidth = 1300,
		outerHeight = 500,
		innerWidth = outerWidth - margin.left - margin.right,
		innerHeight = outerHeight - margin.top - margin.bottom,
		width = innerWidth - padding.left - padding.right,
		height = innerHeight - padding.top - padding.bottom;
		largeurPas = Math.min(width / data.length,40),
		legendeHauteur = Math.min(18*(Math.max(data.length,6)),height);
	var loupe = d3.select("body")
		.append("div")
		.attr("id","loupe")
		.attr("class","loupe")
		.style("visibility", "hidden")
		.text("loupe");
	var nbcolonnes = data[0].length;
	// calcul de l'amplitude globale
	var vmin, vmax;
	var vminmin, vmaxmax;
	for (nb = 1; nb < nbcolonnes; nb++)
	{
		for (cle in data)
		{
			var val = data[cle][nb];
			if (!vmaxmax || vmaxmax < val) {vmaxmax = val}
			if (!vminmin || vminmin > val) {vminmin = val}
		}
	}
	var colonne = function (nb)
	{
		vmax=null;
		vmin=null;
		var datacol = new Array();
		var ilig = 0;
		for (cle in data)
		{
			ligne = data[cle];
			var val = ligne[nb];
			datacol[ilig] = val;
			if (!vmax || vmax < val) {vmax = val}
			if (!vmin ||vmin > val) {vmin = val}
			ilig++;
		}
		if(echelle) {
			vmax = vmaxmax;
			vmin=vminmin;
		}
		var coef = height / (vmax-vmin);
		for (ilig=0; ilig<datacol.length;ilig++)
		{
			datacol[ilig] = new Array(datacol[ilig], (datacol[ilig] - vmin) * coef);
		}
		return datacol;
	}
	var color = d3.scale.category20();
	var x = d3.scale.linear()
		.domain([0, 1])
		.range([0, largeurPas]);
	var y = d3.scale.linear()
			.domain([vmin, vmax])
			.rangeRound([0, height]);

	// création du path
	var lineFunction = 	d3.svg.line()
			.x(function(d, i) {return x(i);})
			.y(function(d, i) {return height - d[1];})
			.interpolate("linear");
	
	// Définition du graphique
	var chart = d3.select(idBloc).append("svg")
		.attr("width", width + margin.left + margin.right)
		.attr("height", height + margin.top + margin.bottom)
		.append("g")
		.attr("transform", "translate(" + margin.left + "," + margin.top + ")");
	// Axe horizontal
	chart.append("line")
		.attr("x1", 0)
		.attr("x2", width)
		.attr("y1", height)
		.attr("y2", height)
		.attr("stroke-width", 1)
		.style("stroke", "grey");

	// Axe vertical
	chart.append("line")
		.attr("x1", 0)
		.attr("x2", 0)
		.attr("y1", 0)
		.attr("y2", height)
		.attr("stroke-width", 1)
		.style("stroke", "grey");

	// Place les libellés en X
	chart.selectAll("axe")
		.data(data)
		.enter().append("text")
			.attr("x", function(d, i) {return x(i);})
			.attr("y", function(d, i) { if(i%2 == 0) {return height + 10;} else {return height + 20;}})
			.attr("class", "libelle")
			.text(function(d){return d[0];});//			.style("stroke", "#000");
			
	for (nb = 1; nb < nbcolonnes; nb++) {
		var datacol = colonne(nb);
		// création de la ligne
		chart.append("path")
			.attr("fill", "none")
			.attr("stroke", color(nb))
			.attr("stroke-width", 1)
			.attr("d", lineFunction(datacol));
		// Etiquette de point
		var ix,iy,txt, ligne;
		for (ilig = 0;ilig < datacol.length;ilig++) {
			ligne = datacol[ilig];
			ix = x(ilig);
			iy = height - ligne[1] - 5;
			txt = '' + ligne[0];
			chart.append("text")
				.attr("x", ix)
				.attr("y", iy)
				.attr("text-anchor", "middle")
				.attr("class", "etiquette")
				.text(txt)
				.style("fill", color(nb))
				.on("mouseover", function(){
					var txt = this.innerHTML;
					d3.select("#loupe")
						.text(txt)
						.style("visibility", "visible");
					})
				.on("mousemove", function(){return loupe.style("top",
					(d3.event.pageY-10)+"px").style("left",(d3.event.pageX+10)+"px");})
				.on("mouseout", function(){return loupe.style("visibility", "hidden");});
		}
	}
	d3.selectAll(".etiquette")
		.attr("id",function(d, i) {return "etiq-" + i;});
}
function gh(d, i, action, id) {
	url = action + "&c=sub&w=" + id;
	$.ajax({
		type: "GET",
		url: url,
		data: data,
		processData: false,
		contentType: false,
		success: function(result) {
			SynerGaia.distribuerResult(result, []);
			SynerGaia.finResult(timeInMs);
			if (close == true) {
				SynerGaia.closePopup();
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			$('#debug').html('erreur d\'exécution : ' + errorThrown + ' ' + textStatus);
			$('#erreurs').html(jqXHR.responseText);
			SynerGaia.imageLoader("", false);
		}
	});
	SynerGaia.submit(event, id, action, msgcond, close)
	return false;
}
