// SynerGaïa 1.3.2 Calcul du cercle des relations entre documents
function cercleDeRelations(id, data, url) {
	// prepare les noeuds
	var nb = 0;
	var noeuds=[];
	for(var k in data){
		// initialisation du node parent
		var node = data[k];
		nb++;
		// initialisation d'un noeud par enfant (si nécessaire), et rattachement au parent
		noeuds.push(node);
		if(node.children) {
			for (var ke in node.children) {
				var enfant = node.children[ke];
				nom = enfant.name;
				if(data[nom]) {
					node.children[ke] = data[nom];
				} else {
					noeuds.push(data[nom] = node.children[ke] = {name: nom, children: [], url: (url + enfant.uid)});
					nb++;
				}
			}
		}
	};
	// calcul des rotations des textes (en degrés)
	var x = -90, y = 0;
	var pas = 360 / nb;
	var liens=[];
	for (var key in noeuds) {
		var d = noeuds[key];
		x+=pas;
		d.x = x;
		d.y = y;
		for(var k in d.children) {
			enfant = d.children[k];
			liens.push({x: x, y:y, source:d, target : enfant});
		}
	}
	var bundle = d3.layout.bundle(); // pour charger les liens
	// prépare le graphique
	
	var diameter = 9 * nb,
		radius = diameter / 2,
		innerRadius = Math.floor(radius - 290);
	
	var svg = d3.select(id).html('').append("svg")
		.attr("width", diameter)
		.attr("height", diameter)
	  .append("g")
		.attr("transform", "translate(" + radius + "," + radius + ")");
	var id = 0;
	for (var key in noeuds) {
		var d = noeuds[key];
		d.id = "noeud" + id;
		svg.append("g")
			.attr('transform','rotate(' + (d.x) + ')translate(' + innerRadius + ',0)' + (d.x > -90 && d.x < 90 ? '':'rotate(180)'))
			.append("a")
				.attr("xlink:href", d.url + d.uid)
			.append("text")
			.attr("class","node")
			.text(d.name)
			.attr("dy",".31em")
			.attr("id", d.id)
			.style("text-anchor", (d.x > -90 && d.x < 90 ? "start" :"end"))
			.on("mouseover", cercle.mouseovered)
			.on("mouseout", cercle.mouseouted)
		id++;
	};
	var line = d3.svg.line.radial()
		.interpolate("bundle")
		.tension(.85)
		.radius(function(d) { return innerRadius; })
		.angle(function(d, i) { return i / 180 * Math.PI; });
		
	var arc = function(d) {
		var arc;
		var rayon = innerRadius;// + Math.abs(d.source.x - d.target.x);
		var angle = function(n) {return (n.x) / 180 * Math.PI};
		var point = function(a) {return (Math.cos(a) * innerRadius) + ',' + (Math.sin(a) * innerRadius)};

		var pente = function (s, t) {return '1';};
		var interne = function(s, t) { return (90 + (s.x - t.x) > 0 ? '1' : '0');} 

		arc = 'm' +  point(angle(d.source));
		arc+= 'A' + rayon + ',' + rayon + ',' + interne(d.source, d.target) + ',' + '0' + ',' + '0';
		arc+= ',' + point(angle(d.target));
		
		return arc;}

	svg.selectAll(".link")
      .data(liens)
    .enter().append("path")
			.attr("class", "link")
			.attr("d", function(d, i) { return arc(d, i); })
			.attr("source", function(d) {return d.source.id;})
			.attr("target", function(d) {return d.target.id;});
	
}
// cercle de relations
var cercle = {
	mouseovered: function mouseovered() {
		var d = this;
		var liensdepuis = $('path[source="' + d.id + '"]');
		var liensvers = $('path[target="' + d.id + '"]');
		
		liensdepuis
			.attr("class", "link link--source")
			.each(function () {
				$(this.attributes).each(function( index, attr ) {
					if (attr.specified) {
						if(attr.name === 'target') {
							var nds = $('#' + attr.value);
							nds.attr("class","node node--target");
						}
					}
				})
			});
			
		liensvers
			.attr("class", "link link--target")
			.each(function() {
				$.each(this.attributes, function( index, attr) {
					if(attr.specified) {
						if(attr.name === 'source') {
							var nds = $('#' + attr.value);
							nds.attr("class","node node--source");
						}
					}
				})
			});
		},

	mouseouted: function mouseouted() {
			$('.link--target').attr("class","link");
			$('.link--source').attr("class","link");
			$('.node--target').attr("class","node");
			$('.node--source').attr("class","node");
		}
	};
// recherche dans le cercle de relations;
function searchRelations(id) {
	svg = d3.select(id);
	$('.surligne').remove();
	$('use').remove();
	var key = $('#cercle_search')[0].value.toLowerCase();
	if (key!=='') {
		var textes = d3.selectAll('text').filter(function(){
			return this.textContent.toLowerCase().indexOf(key) > -1;
		});
		var padding = 2;
		var rect = textes
			.each(function (d, i) {
				var text=textes[0][i];
				var bbox = text.getBBox();
				var parent = d3.select(this.parentNode);
				parent.append('rect')
					.attr({
						class: "surligne",
						x: function(d, i) {return '' + (bbox.x - padding);},
						y: function(d, i) {return '' + (bbox.y - padding);},
						width:function(d, i) {return '' + (bbox.width + (padding*2))},
						height: function(d, i) {return '' + (bbox.height + (padding*2))}
						})
					.style("fill", "yellow");
				var clonetext = text.cloneNode(true);
				clonetext.addEventListener("mouseover",  cercle.mouseovered);
				clonetext.addEventListener("mouseout", cercle.mouseouted);
				text.remove();
				parent[0][0].appendChild(clonetext);
			});
	}
}
