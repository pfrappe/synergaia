//=========== CALCUL DE LA CARTE DES PROXIMITES ========================//
var noeuds, cases, svg, msg, maille, fond, taille, maxparents, liens, nbliens, legende;
var proximites  = {
	initialize: function (id, data, url) {
		noeuds = [];
		cases = [];
		svg = null;
		msg = '';
		maxparents = 0;
		$(id).html(SynerGaia.imageLoader());
		taille = 1400; // talle globale du dessin
		fond = "rect"; // dessiner des rectangles
		liens = false; // ne pas dessiner les liens
		var i, j, x, y, nblignes, key, d, cell;
		// calcul des couleurs
		var	seuil = .3,
			maxboucles=2,
			colors=[[0,0,255],[0,255,0],[255,0,0],[0,255,255],[255,255,0],[255,0,255]],
			colore=true,
			color, nc, premier, modif;
		var nb = 0;
		// prepare les noeuds
		for(var k in data){
			var node = data[k];
			node.no = nb;
			node.color=false;
			node.parents = [];
			node.url = url + node.uid;
			noeuds.push(node);
			nb++;
		}
		// initialisation d'un noeud par enfant (si nécessaire), et rattachement au parent
		for(var k in data){
			var node = data[k];
			if(node.children) {
				for (var ke in node.children) {
					var enfant = node.children[ke];
					nom = enfant.name;
					if(!data[nom]) {
						noeuds.push(data[nom] = {name: nom, children: [], url: (url + enfant.uid), no: nb, parents : [], color:false});
						nb++;
					}
					node.children[ke] = data[nom];
					node.children[ke].parents.push(node);
				}
			}
		};
		// calcul des liens et du maxparents
		var tabnbparents = [];
		for (key = 0; key < noeuds.length; key++) {
			d = noeuds[key];
			for(var k in d.children) {
				enfant = d.children[k];
			}
			if(maxparents < d.parents.length) {
				maxparents = d.parents.length;
			}
			tabnbparents[key] = d.parents.length;
		}
		if(colore) {
			// calcul des couleurs (moyenne des couleurs des parents)
			tabnbparents.sort();
			tabnbparents.reverse();
			
			nboucles=0;
			// remplissage initial
			for (nc=0; nc < colors.length; nc++) {
				premier = true;
				color = colors[nc];
				for (key = 0; key < tabnbparents.length; key++) {
					d = noeuds[tabnbparents[key]];
					if(d.color == false && premier) {
						d.color=color;
						for (i=0; i < d.children.length; i++) {
							e = d.children[i];
							if(e.color == false) {
								e.color=color;
							}
						}
						for (i=0; i < d.parents.length; i++) {
							e = d.parents[i];
							if(e.color == false) {
								e.color=color;
							}
						}
						premier = false;
					}
					proximites.couleurmoyenne(d, seuil);
				}
				nboucles++;
			}
			// étalement
			for (i = 0; i < 3; i++) {
				modif = 0;
				for (key = 0; key < noeuds.length; key++) {
					modif += proximites.couleurmoyenne(noeuds[key], 0);
				}
				if (modif == 0) {break;}
			}
		}
		// calcul des cases et de nbliens
		var nbcell = Math.floor(Math.sqrt(noeuds.length)) + 1;
		maille = taille / nbcell;
		key = 0;
		nbliens = 0;
		cases = [];
		for (i = 0; i < nbcell; i++) {
			for (j = 0; j < nbcell; j++) {
				y = i * maille;
				x = j * maille;
				cell = {x: x, y: y, k : key, i: i, j: j};
				if(key < noeuds.length) {
					d = noeuds[key];
					nbliens += d.nbliens = proximites.nbliens(d);
					cell.noeud = d;
					d.cellule = cell;
				}
				cases[key] = cell;
				key++;
			}
		}
		// melanger les cases
		cases = proximites.melanger(cases);
		
		// positionnement initial
		proximites.tracer(id);
		
		// première optimisation		
		proximites.optimize();
	},
	tracer: function(id) {
		var noeud, key, enfant, k, xn, yn, couleur;
		var demimaille = maille / 2;
		proximites.evalueroptimisation();
		// legende
		if(true) {
			var legende = '<div><ul style="list-style: none">';
			var couleurs = proximites.legende();
			for(couleur in couleurs) {
				legende+= '<li style="background-color:rgb('+ couleur.replace(/0/g,'180') + ');">' + couleurs[couleur] + '<li>'; //
			}
			legende+='</ul></div>';
		}
		svg = d3.select(id).html(legende).append("svg")
			.attr("width", taille)
			.attr("height", taille)
			.attr("class", "prox-svg")
			.on("click", proximites.optimize);
		msg = svg.append("text")
			.attr("x", '10')
			.attr("y", '10')
			.attr("dy", ".35em")
			.text('');
		svg = svg.append("g");
		// damier
		couleur='black';
		for (key = 0; key < noeuds.length; key++) {
			noeud = noeuds[key];
			xn = noeud.cellule.x;
			yn = noeud.cellule.y;
			if(noeud.parents.length + noeud.children.length > 0) {
				var opacity = (noeud.parents.length)/ (maxparents + noeud.children.length);
			}
			if(opacity < .1) {opacity = .1;}
			// element noeud
			var g = svg.append("g")
				.attr("id", "noeud" + noeud.no)
				.attr("no", noeud.no)
				.attr("transform","translate(" + xn + ',' + yn + ')')
				.attr("size", maille);
			//rectangle en dessous
			if (fond === "rect") {
				if(noeud.color !== false) {
					couleur=["rgb(",noeud.color[0],",",noeud.color[1],",",noeud.color[2],")"].join("");
				} else {
					couleur='black';
				}
				
				//couleur=["rgb(",noeud.color[0],",",noeud.color[1],",",noeud.color[2],")"].join("");
				g.append("rect")
					.attr("width", maille-2)
					.attr("height", maille-2)
					.attr('opacity', opacity)
					.style("fill", couleur);
			}
			// puis liens en dessous
			if (liens) {
				for (k = 0; k < noeud.children.length; k++) {
					enfant = noeud.children[k];
					svg.append("line")
					.attr('id', 'lien' + noeud.no + '-' + enfant.no)
					.attr("x1", xn + 20)
					.attr("y1", yn + 20)
					.attr("x2", enfant.cellule.x + 20)
					.attr("y2", enfant.cellule.y + 20)
					.attr("stroke-width", 1)
					.attr("stroke", "yellow");
				}
			}
			// cercle
			if (fond === "circle") {
				g.append("circle")
				.attr("cx", 20)
				.attr("cy", 20)
				.attr("r", 1 + noeud.children.length)
				.style("fill", 'grey');
			}
			g.append("foreignObject")
				.attr("x", 3)
				.attr("y", 3)
				.attr("width", maille-6)
				.attr("height", maille-6)
				.attr("no", noeud.no)
				.on("mouseover", proximites.mouseover)
				.on("mouseout", proximites.mouseout)
				.append("xhtml:body")
					.attr("height", maille)
					.attr("class","prox-body")
					.attr("no", noeud.no)
					.html(function() {
						var nd = noeuds[this.getAttribute("no")];
						txt = nd.name;
						if (nd.gras && nd.gras != '') {txt = '<b>' + txt + '</b>';}
						var txt = "<span class='prox-titre' >" + txt + "</span>";
						return txt;})
					.on("mouseover", proximites.mouseover)
					.on("mouseout", proximites.mouseout)
					.on("click", function () {
						window.open(noeuds[this.getAttribute("no")].url, '_blank')
						});
		}
	},
	optimize: function () {
		var mvt, i,
			noeud1, n1, noeud2, n2, cell1, cell2,
			gaincumule, gaindutour, gain, meilleurgain, meilleurs, dist1, dist2, perteinitiale;
			
		gaincumule = 1; // pour démarrer
		while(gaincumule > 0) {
			gaincumule = 0;
			
			gaindutour = 1; // pour démarrer
			while(gaindutour > 0) {
				
				// permutation aléatoire
				n1 = Math.floor(Math.random() * cases.length);
				cell1 = cases[n1];
				n2 = Math.floor(Math.random() * cases.length);
				cell2 = cases[n2];
				dist1 = 0;
				if(cell1.noeud) {
					dist1 = proximites.disttotalenoeud(cell1.noeud);
				}
				dist2 = 0;
				if(cell2.noeud) {
					dist2 = proximites.disttotalenoeud(cell2.noeud);
				}
				perteinitiale = (dist2 + dist1) - proximites.simulerpermutation(cell1, cell2);
				gaincumule+= perteinitiale;
				gaindutour = perteinitiale;
				proximites.permuter(cell1, cell2);

				// chercher des améliorations
				mvt = 0;
				for (n1 = 0; n1 < cases.length; n1++) {
					cell1 = cases[n1];
					dist1 = 0;
					if(cell1.noeud) {
						dist1 = proximites.disttotalenoeud(cell1.noeud);
					}
					meilleurgain = 0;
					meilleurs = [];
					for (n2 = n1 + 1; n2 < cases.length; n2++) {
						cell2 = cases[n2];
						dist2 = 0;
						if(cell2.noeud) {
							dist2 = proximites.disttotalenoeud(cell2.noeud);
						}
						
						gain = (dist2 + dist1) - proximites.simulerpermutation(cell1, cell2);
						if (gain > 0) {
							 if (gain === meilleurgain) {
								 meilleurs.push(cell2);
							 } else if (gain > meilleurgain) {
								 meilleurgain = gain;
								 meilleurs = [cell2];
							 }
						}
					}
					if(meilleurgain > 0) {
						if (meilleurs.length == 1) {
							i = 0;
						} else {
							i = Math.floor(Math.random() * meilleurs.length);
						}
						proximites.permuter(cell1, meilleurs[i]);
						gaindutour+= meilleurgain;
						mvt++;
					}					
				}
				gaincumule+=gaindutour;
			}
			for (n1 = 0; n1 < cases.length; n1++) {
				if (cases[n1].noeud) {
					proximites.actualiser(cases[n1].noeud);
				}
			}
		}
	},
	evalueroptimisation: function() {
		var distopt = proximites.bouletotale();
		d3.select('#prox-opt').text('(Niveau optimisation : longueur du lien moyen ' + Math.round(distopt/nbliens/maille*100)/100 + ' cases. Cliquez ici pour essayer d\'améliorer.)');
	},
	actualiser: function(noeud) {
		var d, id, lien, circle, i, color;
		proximites.evalueroptimisation();
		if (liens) {	
			for (i in noeud.children) {
				d = noeud.children[i];
				id = '#lien' + noeud.no + '-' + d.no;
				lien = svg.select(id);
				lien
					.attr("x2", d.cellule.x + 20)
					.attr("y2", d.cellule.y + 20);
			}
			for (i in noeud.parents) {
				d = noeud.parents[i];
				id = '#lien' + d.no + '-' + noeud.no;
				lien = svg.select(id);
				lien
					.attr("x1", d.cellule.x + 20)
					.attr("y1", d.cellule.y + 20);
			}
		}
		var g = svg.select('#noeud' + noeud.no);
		g.attr("transform", "translate(" + noeud.cellule.x + ',' + noeud.cellule.y + ")");
	},
	melanger: function (a) {
		var tmp, irnd;
		// partir du haut en bas
		for (var i = a.length - 1; i > 0; i--) {
			irnd = Math.floor(Math.random() * i);
			proximites.permuter(cases[i], cases[irnd]);
		}
		return a;
	},
	distance: function(cell1, cell2) {
		var dx = (cell2.x - cell1.x);
		var dy = (cell2.y - cell1.y);
		return Math.sqrt(dx * dx + dy * dy);
	},
	boule: function(noeud) {
		var dist = 0;
		var cell = noeud.cellule;
		noeud.children.forEach(function(nd) {
				dist+= proximites.distance(cell, nd.cellule);
			});
		noeud.parents.forEach(function(nd) {
				dist+= proximites.distance(cell, nd.cellule);
			});
		return dist;
	},
	bouletotale: function() {
		var dist = 0;
		noeuds.forEach(function(noeud) {dist+=proximites.boule(noeud);});
		return dist;
	},
	nbliens: function(noeud) {
		return noeud.children.length + noeud.parents.length;
	},
	permuter: function(cell1, cell2) {
		if (typeof (cell1.noeud) != 'undefined' && typeof (cell2.noeud) != 'undefined') {
			var tmpnoeud = cell1.noeud;
			cell1.noeud = cell2.noeud;
			cell2.noeud = tmpnoeud;
			cell1.noeud.cellule = cell1;
			cell2.noeud.cellule = cell2;
		} else if (typeof (cell1.noeud) != 'undefined') {
			cell2.noeud = cell1.noeud;
			delete cell1.noeud;
			cell2.noeud.cellule = cell2;
		} else if (typeof (cell2.noeud) != 'undefined') {
			cell1.noeud = cell2.noeud;
			delete cell2.noeud;
			cell1.noeud.cellule = cell1;
		}
	},
	distancemailles: function(noeud1, noeud2) {
		return Math.max(Math.abs(noeud2.cellule.i - noeud1.cellule.i), Math.abs(noeud2.cellule.j - noeud1.cellule.j));
	},
	disttotalenoeud: function(noeud) {
		var dist = 0;
		for (var ic=0; ic<noeud.children.length; ic++) {
			dist+= proximites.distancemailles(noeud, noeud.children[ic]);
		}
		for (var ic=0; ic<noeud.parents.length; ic++) {
			dist+= proximites.distancemailles(noeud, noeud.parents[ic]);
		}
		return dist;
	},
	simulerpermutation: function(cell1, cell2) {
		var ic;
		var dist = 0;
		// dist totale noeud 1
		if(typeof (cell1.noeud) != "undefined") {
			noeud1 = cell1.noeud;
			for (ic=0; ic<noeud1.children.length; ic++) {
				dist+= Math.max(Math.abs(cell2.i - noeud1.children[ic].cellule.i), Math.abs(cell2.j - noeud1.children[ic].cellule.j));
			}
			for (ic=0; ic<noeud1.parents.length; ic++) {
				dist+= Math.max(Math.abs(cell2.i - noeud1.parents[ic].cellule.i), Math.abs(cell2.j - noeud1.parents[ic].cellule.j));
			}
		}
		// dist totale noeud 2
		if(typeof (cell2.noeud) != "undefined") {
			noeud2 = cell2.noeud;
			for (ic=0; ic<noeud2.children.length; ic++) {
				dist+= Math.max(Math.abs(cell1.i - noeud2.children[ic].cellule.i), Math.abs(cell1.j - noeud2.children[ic].cellule.j));
			}
			for (ic=0; ic<noeud2.parents.length; ic++) {
				dist+= Math.max(Math.abs(cell1.i - noeud2.parents[ic].cellule.i), Math.abs(cell1.j - noeud2.parents[ic].cellule.j));
			}
		}
		return dist;
	},
	chercher: function() {
		var key = $('#proximites_search')[0].value.toLowerCase();
		$("svg body").css( "background-color", "white" );
		if(key != '') {
			$("svg body:contains('" + key + "')" ).css( "background-color", "yellow" );
		}
	},
	mouseover: function() {
		var no = $(this).attr("no");
		$("svg body[no='" + no + "']" ).css( "background-color", "yellow" );
		noeuds[no].children.forEach(function(nd) {
			$("svg body[no='" + nd.no + "']" ).css( "background-color", "yellow" );
		});
		noeuds[no].parents.forEach(function(nd) {
			$("svg body[no='" + nd.no + "']" ).css( "background-color", "yellow" );
		});
	},
	mouseout: function() {
		proximites.chercher();
	},
	couleurmoyenne: function(noeud, seuil) {
		function approchee(e) {
			if (e.color) {
				var ret = [];
				for (var i=0;i<3;i++) {
					if (e.color[i] < 50) {
						ret[i] = 0;
					} else {
						ret[i] = Math.floor(e.color[i] / 10) *10;
					}
				}
			} else {
				ret = ['false'];
			}
			return ret;
		};
		var c=true, nc, r=0, v=0, b=0, e, i, 
			modif=0, a, cle, nliens, totalpoids;
		var maxcle, maxpoids;
		nliens = noeud.children.length + noeud.parents.length;
		if (nliens > 0) {
			var couleurs={"false":[0, [0,0,0]]};
			for (i=0; i<noeud.children.length; i++) {
				e = noeud.children[i];
				a = approchee(e);
				cle = a.join(",");
				if(!couleurs.hasOwnProperty(cle)) {
					couleurs[cle] = [1, a];
				} else {
					couleurs[cle][0]++;
				}
			}
			for (i=0; i<noeud.parents.length; i++) {
				e = noeud.parents[i];
				a = approchee(e);
				cle = a.join(",");
				if(!couleurs.hasOwnProperty(cle)) {
					couleurs[cle] = [1, a];
				} else {
					couleurs[cle][0]++;
				}
			}
			maxpoids=0; totalpoids = 0;
			for(cle in couleurs) {
				if (cle != 'false') {
					poids = couleurs[cle][0]
					if(maxpoids < poids) {
						maxpoids = poids;
						maxcle = cle;
					}
					totalpoids++;
				}
			}
			if((seuil == 0 && noeud.color == false)|| (totalpoids / nbliens >= seuil && maxpoids / totalpoids >= seuil)) {
				noeud.color = couleurs[maxcle][1];
				modif = 1;
			}
		}
		return modif;
	},
	legende: function() {
		// vocabulaire associé aux couleurs
		var couleurs=[], mots, key, i, noeud, color, vocabulaire, mot;
		var motsexclus = {'avec':'', 'dans':'', 'des':'', 'les':'', 'nos':'', 'notre':'', 'nous':'', 'par':'', 'pas':'', 'plus':'', 'qui':'', 'quoi':'', 'sans':'', 
			'une':'', 'vous':''};
		for (key = 0; key < noeuds.length; key++) {
			noeud = noeuds[key];
			if(noeud.color !== false) {
				color = noeud.color.join(',');
				if(typeof couleurs[color] == 'undefined') {
					couleurs[color] = [];
				}
				vocabulaire = couleurs[color];
				mots = noeud.name.toLowerCase().replace("'"," ").split(' ');
				for (i = 0; i < mots.length; i++) {
					mot = mots[i];
					if (mot.length > 2 && !(mot in motsexclus)) {
						if(typeof vocabulaire[mot] == 'undefined') {
							vocabulaire[mot] = 1;
						} else {
							vocabulaire[mot]++;
						}
					}
				}
			}
		}
		for(color in couleurs) {
			mots = couleurs[color];
			motsmajeurs = [];
			mots.sort(function(a, b) {return (b-a);});
			for(mot in mots){
				if(mots[mot] > 1) {
					motsmajeurs.push(mot);
				}
			}
			couleurs[color] = motsmajeurs.join(', ');
		}
		return couleurs;
	},
	droite: function (x1,y1, x2,y2) { // y = p*x + d
		var p, d;
		if (x1 = x2) {
			p = null;
			d = null;
		} else if (y1 = y2) {
			p = 0;
			d = y1;
		} else {
			p = (y2 - y1) / (x2 - x1);
			d = (y1 * x2 - x1 * y2) / (x2 - x1);
		}
		return [p, d];
	},
	trigger: function() {
		if(liens===false) {
			liens = true;
		} else {
			liens = false;
		}
		proximites.optimize();	
	}
};
