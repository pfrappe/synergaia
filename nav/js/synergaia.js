/*==== SYNERGAIA 2.2 ===========*/
// pour enlever facilement des éléments ou des listes
Element.prototype.remove = function() {
    this.parentElement.removeChild(this);
}
NodeList.prototype.remove = HTMLCollection.prototype.remove = function() {
    for(var i = 0, len = this.length; i < len; i++) {
        if(this[i] && this[i].parentElement) {
            this[i].parentElement.removeChild(this[i]);
        }
    }
}
// au chargement (dépend de jquery)

function dropzone_init() {
	// animpation css des zones de drag and drop
	$(document).on('dragenter','.dropzone',function() {
		$(this).css('border', '1px dashed red');
		return false;
	});
	$(document).on('dragover','.dropzone',function(e) {
		e.preventDefault();
		e.stopPropagation();
		$(this).css('border', '1px dashed red');
		return false;
	});
	$(document).on('dragleave','.dropzone',function(e) {
		e.preventDefault();
		e.stopPropagation();
		$(this).css('border', '1px dashed #bbbbbb');
		return false;
	});
	/* traitement du lâcher
	$(document).on('drop','.dropzone',function(e) {
		e.preventDefault();
		e.stopPropagation();
		$(this).css('border', '3px solid green');
		// traitement proprement dit selon le type d'objet transporté et le type de sone d'arrivée.
		var data = e.dataTransfer.getData('Text');
		var files = e.dataTransfer.files
		for (var i=0; i<files.length;i++) {
			
		}
		
		return false;
	});
	*/
}
// prise en compte du choix à partir d'une fenêtre de sélection
function suggest_click(id, idchamp) {
	//extraction du mot choisi
	var choix = $('#' + id);
	choix = choix.attr("value");
	var mots = choix.split('.');
	choix = mots[mots.length - 1];
	//repérage dans le champ de saisie
	var champ = $('[name="' + idchamp + '"]');
	var ltexte = champ.val().toLowerCase();
	//début du mot
	var deb = champ.getCursorPosition();
	var debut=0;
	for (var i=deb; i>0;i=i-1) {
		var c = ltexte.substr(i-1,1);
		if((c >= 'a' && c <= 'z') || c=='@' || (c >= '0' && c<='9')) {
		} else {
			debut=i;
			break;
		}
	}
	//fin du mot
	var fin=ltexte.length;
	for (var i=deb+1; i<ltexte.length;i++) {
		c = ltexte.substr(i-1,1);
		if((c >= 'a' && c <= 'z') || c=='@' || (c >= '0' && c<='9')) {
		} else {
			fin=i-2;
			break;
		}
	}
	//remplacement
	var texte=champ.val();
	var newtexte=texte.substring(0,debut) + choix + texte.substring(fin + 1);
	champ.val(newtexte);
	champ.getCursorPosition(fin);
	// fermeture de la fenêtre
	$("#autosuggestions").hide();
}

(function($) {
	$.fn.getCursorPosition = function() {
		var input = this.get(0);
		if (!input) return; // No (input) element found
		if ('selectionStart' in input) {
			// Standard-compliant browsers
			return input.selectionStart;
		} else if (document.selection) {
			// IE
			input.focus();
			var sel = document.selection.createRange();
			var selLen = document.selection.createRange().text.length;
			sel.moveStart('character', -input.value.length);
			return sel.text.length - selLen;
		}
	}
})(jQuery);

// pour la gestion du texte de formule
function keyup_formule (idFormule) {	
	var theTable=$('#' + idFormule);
	$("#" + idTableau + "-filtre").keyup(function() {$.uiTableFilter(theTable, this.value);})
	var e=document.getElementById;
	var texteactuel =e.innerHTML;
}

(function ($) {
	var sgdatepicker = function (nom) {
		var x = {},
			y = '';
		return {
			init: function (nom) {
				var o = $(nom);
				o.DatePicker({
					flat: true,
					date: o.val(),
					current: o.val(),
					calendars: 1,
					starts: 1
				});
				return true;
			}
		};
	}();
	$.fn.extend({
		sgdatepicker: sgdatepicker.init
	});
})(jQuery);

function beforeSubmit() {
	var o = $(".obligatoire");
	erreur = false;
	for (var i=0; i<o.length ; i++) {
		if (o[i].innerHTML == "") {
			erreur = true;
			o[i].style('.obligatoire:after{content:"obligatoire";}');
		}		
	}
	return erreur;
}

function resettoggle(id) {
	var e = document.getElementById(id);
	e.style.display = 'none';
}

function toggle_visibility(id, idimg) {
	var e = document.getElementById(id);
	if(e.style.display == 'none') {
		e.style.display = 'block';
		if (idimg) {
			$('#' + idimg).style('rotate(90)');
		}
	} else {
		e.style.display = 'none';
		if (idimg) {
			$('#' + idimg).style('rotate(-90)');
		}
	}
}

function filtrerTableau(idTableau) {
	var theTable=$('#' + idTableau);
	$("#" + idTableau + "-filtre").keyup(function() {$.uiTableFilter(theTable, this.value);})
}
function sg_getModal(formule, idBloc) {
	if (idBloc) {
		$.ajax({
			url : 'index.php',
			type : 'GET',
			data : formule,
			dataType : 'html',
			idbloc : idBloc,
			success : function(result) {
				$(this.idbloc).html(result);
				popup_open();
				}
		});
	}
}
function popup_open() {
	var winheight = $(window).height();   // hauteur du browser
	var winwidth = $(window).width();   // largeur du browser
	var docheight = $(document).height(); // hauteur du document html
	var docwidth = $(document).width(); // largeur du document HTML
	var scrheight = screen.height; // hauteur de l'écran physique
	var scrwidth = screen.width; // largeur de l'écran physique
	var imgwidth = $('#popup_window, img').width(); // largeur de l'image
	var imgheight = $('#popup_window, img').height(); // hauteur de l'image
	
	var coef = Math.min(1, winheight / imgheight, winwidth / imgwidth) * 0.9;
	
	var popup = $('#popup_window');
	$('#popup_window, img').css({
		'max-height': Number(winheight - 130 ),
		'max_width': Number(winwidth - 120)
	})
	//Faire apparaitre la pop-up et ajouter le bouton de fermeture
	popup.fadeIn().css({
		'width': Number( imgwidth * coef),
		'height': Number( imgheight * coef),
		'display': 'block'
	})
	.prepend('<img src="nav/themes/defaut/img/icons/16x16/silkicons/cancel.png" class="btn_close" title="Fermer" alt="Fermer" onclick="popup_close()"/>');

	//Récupération du margin, qui permettra de centrer la fenêtre - on ajuste de 80px en conformité avec le CSS
	var popMargTop = (winheight - imgheight * coef) / 2;
	var popMargLeft = (winwidth - imgwidth * coef) / 2;

	//On affecte le margin
	popup.css({
		'margin-top' : popMargTop,
		'margin-left' : popMargLeft
	});

	//Effet fade-in du fond opaque
	$('body').append('<div id="popup_fond" onclick="popup_close()"></div>'); //Ajout du fond opaque noir
	$('#popup_fond').css({'filter' : 'alpha(opacity=80)'}).fadeIn(); //pour corriger les bogues de IE

	return false;
}

//Fermeture de la pop-up et du fond
function popup_close() { //Au clic sur le bouton ou sur le calque...
	var popup = $('#popup_window');
	var fond = $('#popup_fond');
	fond.css('display:none');
	popup.css({
		'display':'none',
		'max-height': '',
		'max_width': '',
		'margin-top' : '',
		'margin-left' : '',
		'width': '',
		'height': ''
	})
	popup.html('');
	fond.remove();
	return false;
}

// arbre généalogique horizontal à partir de JSON
function afficherArbre(idBloc,json) {
	var content = $(".operationContenu");
	var formheight = 1000;
	var formwidth = content.width();
	var margin = {top: 0, right: 300, bottom: 0, left: 0},
		width = formwidth - margin.left - margin.right,
		height = formheight - margin.top - margin.bottom;

	var tree = d3.layout.tree()
		.separation(function(a, b) { return a.parent === b.parent ? 1 : .5; })
		.children(function(d) { return d.parents; })
		.size([height, width]);

	var svg = d3.select(idBloc).append("svg")
		.attr("width", width + margin.left + margin.right)
		.attr("height", height + margin.top + margin.bottom)
		.append("g")
			.attr("transform", "translate(" + margin.left + "," + margin.top + ")");

	var nodes = tree.nodes(json);
	
	// crée un lien entre deux noeuds
	var link = svg.selectAll(".arbre_lien")
		.data(tree.links(nodes))
		.enter().append("path")
		.attr("class", "arbre_lien")
		.attr("d", elbow);
		
	// attache un nouveau noeud et le place
	var node = svg.selectAll(".arbre_noeud")
		.data(nodes)
		.enter().append("g")
		.attr("class", "arbre_noeud")
		.attr("transform", function(d) { return "translate(" + d.y + "," + d.x + ")"; });
		
	// remplit les textes noeud	: modele
	node.append("text")
		.attr("class", "arbre_modele")
		.attr("x", 8)
		.attr("y", -6)
		.text(function(d) { return d.modele; })
		.on("click", function(d) {window.open(d.lien);});
	//titre
	node.append("text")
		.attr("class", "arbre_titre")
		.attr("x", 60)
		.attr("y", -6)
		.html(function(d) { return d.titre; })
		.on("click", function(d) {window.open(d.lien);});
		
	// récupère les résumés pour les rendre multilignes et les placer
	var resumes = node.append("text")
		.attr("class", "arbre_resume")
		.attr("x", 8)
		.attr("y", 10)
		.attr("id", function(d,i) { return "resume_" + i;})
		.text(function(d) { return d.resume instanceof Array ? d.resume.join("<br>") : d.resume;});
	// éclate le texte multiligne
	resumes.call(wrap);

	// récupère les vignettes pour les placer comme html
	var vignettes = node.append("foreignObject")
		.attr("x", 8)
		.attr("y", 10)
		.attr("width","400")
		.attr("height","150")
        .attr("requiredExtensions","http://www.w3.org/1999/xhtml")
		.attr("id", function(d,i) { return "vignette_" + i;})
		.append("xhtml:body")
			.style("background-color", "#005500")
			.style("font", "9px 'Helvetica Neue'")
			.html(function(d) { return d.vignette;});

	function wrap() {
		var textes = this[0];
		for (var i=0;i<textes.length;i++) {
			var lignes = textes[i].textContent.split("<br>");
			var resume = d3.select("#resume_" + i);
			resume.text("");
			for (var n=0;n<lignes.length;n++) {
				resume.append("tspan").text(lignes[n]).attr("x","8").attr("dy","10");
			}
		}
	}
	// trace un arc
	function elbow(d, i) {
			return "M" + d.source.y + "," + d.source.x
			+ "H" + d.target.y + "V" + d.target.x
			+ (d.target.children ? "" : "h" + margin.right);
	}
}
// ajuste les textarea des formules
function textAreaAdjust(o) {
    o.style.height = "1px";
    o.style.height = (25+o.scrollHeight)+"px";
}
// SynerGaïa 1.3.2 Consulter le dictionnaire
function consulter_dictionnaire(idBloc,urlJSON) {
	var m = [20, 120, 20, 120],
		w = 2000 - m[1] - m[3],
		h = 2000 - m[0] - m[2],
		i = 0,
		root;
	
	var tree = d3.layout.tree()
		.size([h, w]);
	
	var diagonal = d3.svg.diagonal()
		.projection(function(d) { return [d.y, d.x]; });
	
	var vis = d3.select("div#dictionnaire"+idBloc).append("svg:svg")
		.attr("width", w + m[1] + m[3])
		.attr("height", h + m[0] + m[2])
		.append("svg:g")
		.attr("transform", "translate(" + m[3] + "," + m[0] + ")");
	
	d3.json(urlJSON, function(json) {
		root = json;
		root.x0 = h / 2;
		root.y0 = 0;

		function toggleAll(d) {
			if (d.children) {
				d.children.forEach(toggleAll);
				toggle(d);
			}
		}
	
	  // Initialize the display to show a few nodes.
		if(root.children) {
			root.children.forEach(toggleAll);
			toggle(root.children[0]);
		//	toggle(root.children[0].children[0]);
		//	toggle(root.children[0].children[1]);
		}
		update(root);
	});
	
	function update(source) {
		var duration = d3.event && d3.event.altKey ? 5000 : 500;

		// Compute the new tree layout.
		var nodes = tree.nodes(root).reverse();

		// Normalize for fixed-depth.
		nodes.forEach(function(d) { d.y = d.depth * 180; });

		// Update the nodes...
		var node = vis.selectAll("g.node")
			.data(nodes, function(d) { return d.id || (d.id = ++i); });

		// Enter any new nodes at the parent\'s previous position.
		var nodeEnter = node.enter().append("svg:g")
			.attr("class", function(d) {if (d.code) {return "node node-" + d.code.replace("@","X");} else {return "node node-??";}; })
			.attr("transform", function(d) { return "translate(" + source.y0 + "," + source.x0 + ")"; })
			.on("click", function(d) { toggle(d); update(d); });

		// Ajoute un cercle
		nodeEnter.append("svg:circle")
			.attr("r", 1e-6)
			.style("fill", function(d) { return d._children ? "lightsteelblue" : "#fff"; });

		// Ajoute le code de l'objet
		nodeEnter.append("svg:text")
			.attr("x", -7)
			.attr("dy", "-.2em")
			.attr("text-anchor", "end")
			.text(function(d) { return d.code; })
			.attr("class", "code")
			.style("fill", function(d) { if(d.code) {return (d.code.charAt(0)==="@") ? "#800" : "#008";} else {return"080"} })
			.style("fill-opacity", 1e-6);

		// Ajoute le libellé de l'objet
		nodeEnter.append("svg:text")
			.attr("x", -7)
			.attr("dy", ".9em")
			.attr("text-anchor", "end")
			.attr("class", "libelle")
			.text(function(d) { return d.libelle; })
			.style("fill-opacity", 1e-6);

		// Ajoute les propriétés de l'objet
		nodeEnter.append("svg:text")
		  .text(
			  function(d) {
				  if (d.proprietes) {
					  var noeud = vis.select(".node-"+ (d.code ? d.code.replace("@","X"):"")).append("svg:g").attr("class","proprietes");
					  for (var i in d.proprietes) {
						  noeud.append("svg:text")
							  .attr("x", 10)
							  .attr("dy", i*20)
							  .attr("class","code")
							  .attr("text-anchor", "begin")
							  .text(d.proprietes[i].code+" ("+d.proprietes[i].modele+")");
						  noeud.append("svg:text")
							  .attr("x", 10)
							  .attr("dy", i*20+10)
							  .attr("class","libelle")
							  .attr("text-anchor", "begin")
							  .text(d.proprietes[i].libelle);
					  }
				  }
			  }
		  );
	
	
	  // Transition nodes to their new position.
	  var nodeUpdate = node.transition()
		  .duration(duration)
		  .attr("transform", function(d) { return "translate(" + d.y + "," + d.x + ")"; });
	
	  nodeUpdate.select("circle")
		  .attr("r", 4.5)
		  .style("fill", function(d) { return d._children ? "lightsteelblue" : "#fff"; });
	
	  nodeUpdate.selectAll("text")
		  .style("fill-opacity", 1);
	
	  // Transition exiting nodes to the parent\'s new position.
	  var nodeExit = node.exit().transition()
		  .duration(duration)
		  .attr("transform", function(d) { return "translate(" + source.y + "," + source.x + ")"; })
		  .remove();
	
	  nodeExit.select("circle")
		  .attr("r", 1e-6);
	
	  nodeExit.selectAll("text")
		  .style("fill-opacity", 1e-6);
	
	  // Update the links...
	  var link = vis.selectAll("path.link")
		  .data(tree.links(nodes), function(d) { return d.target.id; });
	
	  // Enter any new links at the parent's previous position.
	  link.enter().insert("svg:path", "g")
		  .attr("class", "link")
		  .attr("d", function(d) {
			var o = {x: source.x0, y: source.y0};
			return diagonal({source: o, target: o});
		  })
		.transition()
		  .duration(duration)
		  .attr("d", diagonal);
	
	  // Transition links to their new position.
	  link.transition()
		  .duration(duration)
		  .attr("d", diagonal);
	
	  // Transition exiting nodes to the parent's new position.
	  link.exit().transition()
		  .duration(duration)
		  .attr("d", function(d) {
			var o = {x: source.x, y: source.y};
			return diagonal({source: o, target: o});
		  })
		  .remove();
	
	  // Stash the old positions for transition.
	  nodes.forEach(function(d) {
		d.x0 = d.x;
		d.y0 = d.y;
	  });
	}
	
	// Toggle children.
	function toggle(d) {
	  if (d.children) {
		d._children = d.children;
		d.children = null;
	  } else {
		d.children = d._children;
		d._children = null;
	  }
	}
}
/** 1.3.3 ajout ; 1.3.4 ajouterfichier ; 2.0
* SynerGaia
* 	.filtrertable(table, phrase) : filtre les lignes d'un tableau html 'table' selon les mots de 'phrase'
* 	.queue : gestionnaire de queue de traitement 
* 		.add(fonction, contexte, duree) : ajoute un traitemet en fin de pile 'fonction' sur 'contexte' après un délai de 'duree' millisecondes
* 		.clear() : vide la pile
* 	.contextMenu : 
* 		.show(control, e) :
* 		.hide(control) : 
* 	.print() :
* 	.elargir(pct) :
* 	.retrecir() :
*	.deplacerVers(e, id) : déplacer le contenu du cadre 'operation' vers le cadre dont l'id est passé en paramètre
*	.ajouterfichier(e)
* 	.effacerfichier(e,id)
*	.montrercacher(id, idimg) : toggle d'affichage sur un élément
*	.initChampDate(id) : initailisation d'un champ Date
*	.initChampDateHeure(id) : initialisation des champs de type date + heure
*	.initChampDates(id, dates) : initialisation des champs de type dates multiples
*	.launchOperation(event, operation, donnees, eff) : lancement d'une opération ou d'une URL SynerGaia. Les résultats sont dispersés dans les id correspondants
* 		si eff=true, effacer
* 	.getMenu(event, thm) : cherche le menu du thème
* 	.getURL(idBloc, donnees) : exécuter une url SynerGaia (&x) et l'afficher dans le bloc
* 	.adroite(idBloc, donnees) : afficher le résultat à droite
* 	.modifier(idBloc, donnees) : calculer sur le serveur et retourner le résultat
* 	.villes(recherche, idBloc) : demander au serveur la liste des villes commençant par les caractères de recherche et mettre le résultat dans le bloc
* 	.distribuerResult(result, consignes, imageloader) : distribuer le résultat dans les différents blocs du navigateur
* 	.ouvrirLien(event, url, idBloc) : ouvrir un lien externe dans l'un des blocs (ne fonctionne pas à cause des sécurités cross domaines)
*	.imageLoader(id) : affichage de l'image d'attente
* 	.submit() : soumission d'un bouton
* 	.effacer() : effacer la gauche, la droite, le debug et cache le menu de contexte
*	.vuechoisir(cle,champ,valeurs) : permet de choisir à l'intérieur d'une vue en appel ajax
* 	.stopPropagation(event) : arrête la propagation de l'événement
* 	.changeSelected(event, id, type) : met à jour la liste des valeurs sélectées affichées
* 	.fullScreen(event, id) : agrandit le cadre photo en plein écran
* 	.devantderriere(event) : passe l'élément devant (zIndex = 50), derrière zIndex = 0;
* 	.zoomphoto
* 	.favori
* 	.onmouseover
* 	.toggle (id, idimg) : affiche ou cache un élément et éventuellement fait tourner un triangle ou l'image dont l'id est passé
* 	.tritable (id) : fonction de tri sur une colonne de table (tablesorter)
* 	.initOnLoad () : exécutée au chargement ou au réaffichage en sortie d'Ajax
* 	.initCategorie () : initialise un champ @Categorie en saisie
* 	.inputFileOnChange () : calcule la taille des fichiers à envoyer
* 	.themes() : menu des themes (pour mobile)
* 	.initOnMobile() : initialisation pour mobile
* 	.getElement(e) : trouve l'élément e dans la page
* 	.mouseX(e) : position en x de la souris
* 	.mouseY(e) : position en y de la souris
* 	
**/
SynerGaia = {
	phraseprec: '',
	depart: '',
	mousemoveTemp: null,
	offsetX: 0,
	offsetY: 0,
	dragtarget: null,
	filtrertable: function (idtable, phrase) {
		var table = $("#"  + idtable);
		var img = document.getElementById(idtable + '-loader');
		img.style.display = "inline";
		SynerGaia.queue.clear();		
		var style = document.getElementById('hidden-' + idtable);
		// pour maj nombres
		var nbspan = document.getElementById(idtable + '-nb');
		var lignes = table[0].tBodies[0].rows;
		var nbtot = lignes.length;
		var nbsel = 0;
		if (style) {
			if(phrase.length === 0) {
				//style.styleSheet.cssText
				style.textContent = '.hidden-' + idtable + '{display:table-row;}';
				for(var i = 0 ; i < lignes.length; i++) {
					var ligne = lignes[i];
					if (ligne.style.display == 'table-row') {
						ligne.style.display = '';
					}
				}
				nbspan.innerText = '';
				img.style.display = "none";
			} else {
				style.textContent = '.hidden-' + idtable + '{display:none;}';
				var mots = phrase.toLowerCase().split(" ");
				var tranche = 600;
				for(var debut = 0 ; debut < lignes.length; debut+=tranche) {
					var filtrer = function() {
						var termine = false;
						var lignes = this[0];
						var debut = this[1];
						var nb = this[2];
						var mots = this[3];
						var fin = debut + nb;
						if (fin > lignes.length) {
							fin = lignes.length;
							termine = true;
						}
						for(var i=debut;i < fin; i++) {
							var ligne = lignes[i];
							var txt = ligne.textContent.toLowerCase();
							var trouve = true;
							for (var m=0; m < mots.length; m++) {
								if (txt.indexOf(mots[m]) === -1) {
									trouve = false;
									break;
								}
							}
							var dejavisible = (ligne.style.display == 'table-row');
							if (trouve) {
								nbsel++;
								if (!dejavisible) { // à afficher
									ligne.style.display = 'table-row';
								}
							} else {
								if (dejavisible) { // masquer
									ligne.style.display = '';
								} else {// à décompter
									//nbsel--;
								}
							}
						}
						if (termine) {
							if (this.length >= 4) {
								this[4].style.display = "none"; // masquer image loading
							}
						}
						nbspan.innerText = ' ' + nbsel + ' affichées';
					}
					SynerGaia.queue.add(filtrer, [lignes, debut, tranche, mots, img]);
				}
			}
		}
		return table;
	},
	queue: {
		_timer: null,
		_queue: [],
		add: function(fn, context, time, loader) {
			var setTimer = function(time) {
				SynerGaia.queue._timer = setTimeout(function() {
					time = SynerGaia.queue.add();
					if (SynerGaia.queue._queue.length) {
						setTimer(time);
					}
				}, time || 2);
			}
			if (fn) {
				SynerGaia.queue._queue.push([fn, context, time]);
				if (SynerGaia.queue._queue.length == 1) {
					setTimer(time);
				}
				return;
			}
			var next = SynerGaia.queue._queue.shift();
			if (!next) {
				return 0;
			}
			next[0].call(next[1] || window);
			return next[2];
		},
		clear: function() {
			clearTimeout(SynerGaia.queue._timer);
			SynerGaia.queue._queue = [];
		}
	},
	contextMenu: {
		show: function (control, e) {
			if (e === undefined) e = window.event; 
			e.preventDefault();
			var posx = e.clientX +window.pageXOffset +'px';
			var posy = e.clientY + window.pageYOffset + 'px';
			elt = document.getElementById(control).style;
			elt.left = posx;
			elt.top = posy;
			elt.position = 'absolute';
			elt.display = 'inline';
			if (e.stopPropagation) {
				e.stopPropagation();
			}
			e.cancelBubble = true;    
		},
		hide: function (control) {
			document.getElementById(control).style.display = 'none'; 
		}
	},
	print: function (){		
		var elt = document.getElementById('corps').style;
		var bmt = elt.marginTop;
		var bpt = elt.paddingTop;
		elt.marginTop = '0';
		elt.paddingTop = '5px';
		
		var elt = document.getElementById('centre').style;
		var cml = elt.marginLeft;
		var cmr = elt.marginRight;
		var cmt = elt.marginTop;
		var cpt = elt.paddingTop;
		var cw = elt.width;
		elt.marginLeft = '0px';
		elt.marginRight = '0px';
		elt.marginTop = '0px';
		elt.paddingTop = '0px';
		elt.width = '100%';
		
		var elt = document.getElementById('operation').style;
		var opt = elt.paddingTop;
		elt.paddingTop = '0';

		window.print();
		
		var elt = document.getElementById('operation').style;
		elt.paddingTop = opt;
		
		var elt = document.getElementById('centre').style;
		elt.marginLeft = cml;
		elt.marginRight = cmr;
		elt.marginTop = cmt;
		elt.paddingTop = cpt;
		elt.width = cw;
		
		var elt = document.getElementById('corps').style;
		elt.marginTop = bmt;
		elt.paddingTop = bpt;
		
	},
	elargir: function (pct){
		var elt = document.getElementById('centre').style;
		var w = parseInt(elt.width);
		var ml = 0;
		if(pct) {
			if(pct == w) {
				w = 60;
			} else {
				w = pct;
			}
			ml = 50 - parseInt(w / 2);
		} else if (w < 90) {
			w = w + 10
			ml = 50 - parseInt(w / 2);
		} else {
			w = 100;
		}
		elt.marginLeft = ml + '%';
		elt.marginRight = (w + ml) + '%';
		elt.width = w + '%';
		
/*		elt = document.getElementById('gauche').style;
		elt.width = ml + '%';
		
		elt = document.getElementById('droite').style;
		elt.marginLeft = (w + ml) + '%';
		elt.width = ml + '%';*/
	},
	retrecir: function (){
		var elt = document.getElementById('centre').style;
		var w = parseInt(elt.width);
		var ml = 0;
		if(pct) {
			w = pct;
			ml = 50 - parseInt(w / 2);
		} else if (w > 10) {
			w = w - 10
			ml = 50 - parseInt(w / 2);
		} else {
			w = 0;
		}
		elt.marginLeft = ml + '%';
		elt.marginRight = (w + ml) + '%';
		elt.width = w + '%';
		
/*		elt = document.getElementById('gauche').style;
		elt.width = ml + '%';
		
		elt = document.getElementById('droite').style;
		elt.marginLeft = (w + ml) + '%';
		elt.width = ml + '%';*/
	},
	deplacerVers: function(e, id) {
		var o = $('#operation');
		elt = document.getElementById(id);
		elt.innerHTML = o.html();
		o.html('');
	},
	ajouterfichier: function(e, mult, donnees) {
		var m = 'nfi';
		if (mult) {
			m = 'nfs';
		}
		// obtenir par ajax un nouvel id
		$.ajax({
			url : 'index.php?c=' + m,
			type : 'GET',
			data : donnees,
			dataType : 'html',
			success : function(result) {
				// cloner un input en plus
				$("#attachments").append('<li>' + result + '</li>');
			},
			error : function(xhr, ajaxOptions, thrownError) {
				alert('non disponible ' + xhr.responseText + ' : ' + thrownError);
				$('#erreurs').html(xhr.responseText);
			}
		});
		
		
	},
	effacerfichier: function(e, id, champ) {
		if(champ) {
			$('#' + id + '_nom').css('display','none');
			$('#' + id + '_sup').attr('value', 'x');
		} else {
			document.getElementById(id + "_fic").innerHTML = document.getElementById(id + "_fic").innerHTML;
		}
	},
	montrercacher: function(id, idimg) {
		var e = document.getElementById(id);
		if(e.style.display == 'none') {
			e.style.display = 'block';
			if (idimg) {
				$('#' + idimg).css('transform','rotate(90deg)');
			}
		} else {
			e.style.display = 'none';
			if (idimg) {
				$('#' + idimg).css('transform', '');
			}
		}
	},
	initChampDate: function(id) {
		$(id).datepicker();
	},
	initChampDateHeure: function (id) {
		var dt = $(id);
		var valprec = dt.val();
		dt.appendDtpicker({
			"dateFormat": "DD/MM/YYYY hh:mm",
			"minuteInterval": 15,
			"firstDayOfWeek": 1,
			"closeOnSelected": true,
			"locale": "fr"});
		// corrections pour le datepicker
		if(valprec == '') {
			dt.val('');
		}
	},
	initChampDates: function (id, dates) {
		var objdt = $(id);
		objdt.multiDatesPicker({dateFormat: "dd/mm/yy"});
	},
	launchOperation: function (event, operation, donnees, eff, cible) {
		var timeInMs = Date.now();
		if (event !== null) {
			if (event.stopPropagation) {
				event.stopPropagation();
			}
			event.cancelBubble = true;
		}
		if (operation) {
			if(operation.substring(0,2) !== 'm=' && operation.substring(0,2) !== 'o=') {
				operation = 'm=' + operation;
			}
			if (event && (event.which === 2 || event.ctrlKey)) {
				window.open('index.php?' + operation, '_blank');
			} else {
				if(eff == true) {SynerGaia.effacer();}
				SynerGaia.imageLoader("#loader", true);
				$.ajax({
					url : 'index.php?c=mop&' + operation,
					type : 'POST',
					data : donnees,
					dataType : 'html',
					success : function(result) {
						$("#autosuggestions").hide(); // 2.1 masquer éventuellement la fenêtre de suggestion de formule
						SynerGaia.distribuerResult(result, [], "loader");
						timeInMs = (Date.now() - timeInMs) / 1000;
						if (SynerGaia.typemedia() === 'mobile') {
							SynerGaia.initOnMobile();
						}
						$('#pied').html('<ul><li>Page affichée en ' + timeInMs + ' secondes</li></ul>');
					},
					error : function(xhr, ajaxOptions, thrownError) {
						$('#operation').html(xhr.responseText);
						SynerGaia.imageLoader("#loader", false);
					}
				});
			}
		}
	},
	getMenu: function(event, thm) {
		var e,m;
		if (event.stopPropagation) {
			event.stopPropagation();
		}
		event.cancelBubble = true;
		$('#op-entete').html('');
		$('#erreurs').html('');
		$('#aide').html('');
		$('#debug').html('');
		if (SynerGaia.typemedia() === 'mobile') {
			m = SynerGaia.getURL('operation', thm + '&c=men');
		} else {
			m = SynerGaia.getURL('operation', thm);
		}
		//SynerGaia.distribuerResult(m, []);
	},
	getURL: function(idBloc, donnees) {
		if (idBloc) {
			$.ajax({
				url : 'index.php',
				type : 'GET',
				data : donnees + '&x',
				dataType : 'html',
				success : function(result) {
					$('#' + idBloc).html(result);
					}
			});
		}
	},
	adroite: function(idBloc, donnees) {
		if(idBloc) {
			var id = '#' + idBloc;
			if ($(id).attr('display') == 'none') {
				$(id).attr('display', 'block').html('<img class="loader" src="nav/js/loader.gif">');
				SynerGaia.getURL(idBloc, donnees);
			} else {
				$(id).html('').attr('display', 'none');
			}
		}
	},
	modifier: function(idBloc, donnees) {
		if(idBloc) {
			var id = '#' + idBloc;
			if ($(id).attr('display') == 'none') {
				$(id).attr('display', 'block').html('<img class="loader" src="nav/js/loader.gif">');
				SynerGaia.getURL(idBloc, donnees);
			} else {
				$(id).html('').attr('display', 'none');
			}
		}
	},
	villes: function(recherche, idBloc) {
		if (recherche.length > 2) {
			var actuel = $('#' + idBloc).val();
			SynerGaia.getURL(idBloc, 'c=vil&p1=' + recherche + '&p2=' + actuel);
		}
	},
	distribuerResult: function(result, consignes, loader) {
		var parsedResult = eval("(" + result + ")");
		if (Array.isArray(parsedResult) || typeof parsedResult === 'object') {
			for (var key in parsedResult) {
				if(key in consignes) {
					$('#' + consignes[key]).html(parsedResult[key]);
				} else {
					var h = $('#' + key);
					var t = parsedResult[key];
					h.html(t);
					var g = 0;
				}
			}
		} else {
			$('#debug').html(parsedResult);
		}
		SynerGaia.imageLoader(loader, false);
	},
	ouvrirLien: function(event, url, idBloc) {
		if (idBloc) {
			if(event.stopPropagation) {
				event.stopPropagation();
			}
			event.cancelBubble = true;
			$.ajax({
				url : url,
				type : 'GET',
				data : '',
				dataType : 'html',
				success : function(result) {
					$('#' + idBloc).html(result);
					},
				error: function(jqXHR, textStatus, errorThrown) {
					var statut=textStatus;
					alert("Une erreur s'est produite lors de la requete : " + errorThrown);
					}
			});
		}
	},
	imageLoader: function(id, show) {
		if (show) {
			$("#loader").show();
		} else {
			$("#loader").hide();
		}
	},
	submit: function(event, id) {
		var timeInMs = Date.now();
		if (event !== null) {
			if (event.stopPropagation) {
				event.stopPropagation();
			}
			event.cancelBubble = true;
		}
		// sauvegarder les champs textes riches (tinyMCE -> textarea)
		if(tinymce) {
			var bodies = document.getElementsByClassName("champ_TexteRiche");
			for (var i = 0; i < bodies.length; i++) {
				var bodyid = bodies[i].getAttribute("id");
				var body = tinymce.get(bodyid);
				if (body != null) {
					$('#' + bodyid).html( tinymce.get(bodyid).getContent());
				}
			}
		}
		// charger les contenus de champs
		var form = document.getElementById(id);
		var data = new FormData( form ); //id.serialize();
		var action = $('#' + id).attr('action');
		SynerGaia.imageLoader("#operation");
		$.ajax({
			type: "POST",
			url: action + "&c=sub&",
			data: data,
			processData: false,
			contentType: false,
			success: function(result) {
				timeInMs = (Date.now() - timeInMs) / 1000;
				SynerGaia.distribuerResult(result, []);
				if (SynerGaia.typemedia() === 'mobile') {
					SynerGaia.initOnMobile();
				}
				$('#pied').html('<ul><li>Page affichée en ' + timeInMs + ' secondes</li></ul>');
			},
			error: function(jqXHR, textStatus, errorThrown) {
				$('#debug').html('erreur d\'exécution : ' + errorThrown + ' ' + textStatus);
				$('#operation').html(jqXHR.responseText)
			}
		});
	},
	effacer: function() {
		$("#gauche").html('');
		$("#droite").html('');
		$("#op-entete").html('');
		$("#aide").html('');
		$("#erreurs").html('');
		$("#operation").html('');
		$('#debug').html('');
		$('#loupe').html('').css('visibility(none)');
		SynerGaia.contextMenu.hide('contextMenuCorps');
	},
	vuechoisir: function(cle,champ,valeurs) {
		for (var i = 0; i < valeurs.length; i++) {
			var ligne = valeurs[i];
		}
	},
	stopPropagation: function(event) {
		if (event !== null) {
			if (event.stopPropagation) {
				event.stopPropagation();
			}
			event.cancelBubble = true;
		}
	},
	changeSelected: function(event, id, type) {
		var liste = [];
		if(type == 's') { // select
			var champs = $("#" + id + ' checkbox');
			champs.each(function () {
				var ti = $(this);
				if(ti.prop('checked')) {
					liste.push($(this).val());
				}
			});
			$("#" + id + "_val").html(liste.join(', '));
		} else if(type == 'f') { // fieldset
			var champs = $("#" + id + '_ul input');
			champs.each(function () {
				var ti = $(this);
				if(ti.prop('checked')) {
					liste.push($(this).next('label').text());
				}
			});
			$("#" + id + "_val").html(liste.join(', '));
		} else {
			$("#" + id + "_val").html("type de liste introuvable !!");
		}
		return true;
	},
	fullScreen: function(event, id) {
		var div = $("#" + id);
		var img = div.find("img");
		var sg = div.attr("data-sg");
		if (sg == '0') {
			// aggrandir
			var imageWidth = img[0].width, // à cause d'un bug jquery sur chrome
				imageHeight = img[0].height, // à cause d'un bug jquery sur chrome
				maxWidth = $(window).width(),
				maxHeight = $(window).height(),
				ratiow = maxWidth / imageWidth,
				ratioh = maxHeight / imageHeight,
				ratio=1;
			// ratio
			if (ratioh < ratiow) {
				ratio = ratioh * 0.95;
			} else {
				ratio = ratiow * 0.95;
			}
			// la div couvre tout
			div.attr("class","photo-max");
			// aggrandir l'image selon le ratio
			div.attr('width', imageWidth * ratio)
				.attr('height', imageHeight * ratio);
			img.attr('width', imageWidth * ratio)
				.attr('height', imageHeight * ratio);
			// marqué aggrandi
			div.attr("data-sg","1");
		} else {
			// réduire la photo
			div.attr("class","photo-div");
			div.attr('width', '')
				.attr('height', '');
			img.attr('width', '95%')
				.attr('height', '95%');
			// marqué normal
			div.attr("data-sg","0");
		}
		return true;
	},
	popupClick: function(event, id) {
		var popup = $('#' + id);
		popup.hide();
		popup.css("zindex",0);
		popup.html('');
	},
	devantderriere: function(event,devant) {
		var elt = $(event.currentTarget);
		var z = elt.zIndex();
		if (z == 0 || devant == true) {
			elt.zIndex(50);
			elt.css("margin-top", "25px");
		} else if (z > 0 || devant == false) {
			elt.zIndex(0);
			elt.css("padding-top", "20px");
		}
	},
	zoomphoto: function(event, url, id) {
		var photo = $("#" + id);
		var popup = $('#popup_window');
		popup.html(photo.html());
		popup.css("zindex",10000);
		var wh = window.height-100;
		var ww = window.width-100;
		popup.css("height",wh);
		popup.css("width", ww);
		popup.show();
	},
	favori: function(event, url, titre) {
		if (window.sidebar && window.sidebar.addPanel) { // Mozilla Firefox Bookmark
			window.sidebar.addPanel(titre,url,""); 
		} else if (window.external && window.external.AddFavorite) {// Microsoft Internet Explorer
			window.external.AddFavorite(url,titre);
		} 
	},
	toggle: function(id, idimg) {
		var e = document.getElementById(id);
		if(e.style.display == 'none') {
			e.style.display = 'block';
			if (idimg) {
				$('#' + idimg).style('rotate(90)');
			}
		} else {
			e.style.display = 'none';
			if (idimg) {
				$('#' + idimg).style('rotate(-90)');
			}
		}
	},
	typemedia: function() {
		var m = document.getElementById('media');
		return m.innerHTML;
	},
	initCalendar:function (id, url, data) {
		$('#calendrier_' +id).fullCalendar({
			events: data,
			aspectRatio: 2,
			header: {
				left: "today",
				center: "prevYear,prev,title,next,nextYear",
				right: "month,agendaWeek,agendaDay"
			},
			editable: false,
			titleFormat: {
				month: "MMMM yyyy",
				week: "d MMM yyyy {\'&#8212;\' d MMM yyyy}",
				day: "dddd d MMMM yyyy"
			},
			columnFormat: {
				month: "dddd",
				week: "ddd d/M",
				day: "dddd d/M"
			},
			timeFormat: {
				agenda:"H:mm",
				"":"H:mm",
			},
			allDayText: "journée",
			axisFormat: "H:mm",
			firstDay: 1,
			monthNames: ["Janvier","Février","Mars","Avril","Mai","Juin","Juillet","Août","Septembre","Octobre","Novembre","Décembre"],
			monthNamesShort: ["Jan","Fév","Mar","Avr","Mai","Juin","Juil","Août","Sep","Oct","Nov","Déc"],
			dayNames: ["Dimanche","Lundi","Mardi","Mercredi","Jeudi","Vendredi","Samedi"],
			dayNamesShort: ["Di","Lu","Ma","Me","Je","Ve","Sa"],
			dayClick: function(date, allDay, jsEvent, view) {
				if (allDay) {
					if(url != '') {
						var pdate=date.toLocaleDateString();
						//window.alert(url + pdate);
						window.open(url + pdate,"_blank");
					}
				}
			},
			buttonText: {
				prev: "&nbsp;&#9668;&nbsp;",
				next: "&nbsp;&#9658;&nbsp;",
				prevYear: "&nbsp;&lt;&lt;&nbsp;",
				nextYear: "&nbsp;&gt;&gt;&nbsp;",
				today: "aujourd\'hui",
				month: "mois",
				week: "semaine",
				day: "jour"
			},
		});
	},
	tritable: function(id) {
		$("#" + id).tablesorter({
			theme: "defaut",
			widthFixed: false,
			showProcessing: false,
			headerTemplate: "{content}",
			onRenderTemplate: null,
			onRenderHeader: function (index) {
				$(this).find("div.tablesorter-header-inner").addClass("roundedCorners");},
			cancelSelection: true,
			dateFormat: "yyyymmdd",
			sortMultiSortKey: "shiftKey",
			sortResetKey: "ctrlKey",
			usNumberFormat: false,
			// set "sorter : false" (no quotes) to disable the column
			headers:{0: {sorter: "text"},1: {sorter: "digit"}, 2: {sorter: "text"},3: {sorter: "url"}},
			ignoreCase: true,
			sortForce: null,
			//sortList: [[0, 0], [1, 0], [2, 0]],
			sortAppend: null,
			sortInitialOrder: "asc",
			sortLocaleCompare: false,
			sortReset: false,
			sortRestart: false,
			emptyTo: "bottom",
			stringTo: "max",
			textExtraction: {0: function (node) {return $(node).text();}, 1: function (node) {return $(node).text();}},
			textSorter: null,
			initWidgets: true,
			widgets: ["zebra", "columns"],
			zebra: ["ui-widget-content even", "ui-state-default odd"],
			uitheme: "jui",
			columns: ["primary","secondary","tertiary"],
			columns_tfoot: true,
			columns_thead: true,
			widgetOptions: {
				filter_childRows: false,
				filter_columnFilters: true,
				filter_cssFilter: "tablesorter-filter",
				filter_functions: null,
				filter_hideFilters: false,
				filter_ignoreCase: true,
				filter_reset: null,
				filter_searchDelay: 300,
				filter_serversideFiltering: false,
				filter_startsWith: false,
				filter_useParsedData: false},
			resizable: true,
			saveSort: true,
			stickyHeaders: "tablesorter-stickyHeader",
			initialized: function (table) {},
			tableClass: "tablesorter",
			cssAsc: "tablesorter-headerSortUp",
			cssDesc: "tablesorter-headerSortDown",
			cssHeader: "tablesorter-header",
			cssHeaderRow: "tablesorter-headerRow",
			cssIcon: "tablesorter-icon",
			cssChildRow: "tablesorter-childRow",
			cssInfoBlock: "tablesorter-infoOnly",
			cssProcessing: "tablesorter-processing",
			selectorHeaders: "> thead th, > thead td",
			selectorSort: "th, td",
			selectorRemove: "tr.remove-me",
			debug: false}
		)
	}, 
	initOnLoad: function () {
		$('.champ_TexteFormule').keyup(function(e) {
			if (e.keyCode == 27) {
				$("#autosuggestions").hide();
				return;
			}
			var champ=$(this);
			var texte=champ.val();
			var ltexte=texte.toLowerCase();
			var ret=new Array();
			var deb = $(this).getCursorPosition();
			//rechercher du point précédent
			var c='';
			var ipoint=-1;
			var mot='';
			for (var i=deb; i>0;i=i-1) {
				c = ltexte.substr(i-1,1);
				if((c >= 'a' && c <= 'z') || c=='@' || (c >= '0' && c<='9')) {
					mot = c + mot;
				} else if (c == '.'){
					ipoint=i; //trouvé mot en cours
					break;
				} else { // autre caractère
					break;
				}
			}
			//si trouvé, mot précédent
			var motprec = '';
			if (ipoint !=-1) {
				for (var i=ipoint-1; i>0;i=i-1) {
					c = texte.substr(i-1,1);
					if((c >= 'a' && c <= 'z') || (c >= 'A' && c <= 'Z') || c=='@' || (c >= '0' && c<='9')) {
						motprec = c + motprec;
					} else {
						break;
					}
				}
			}
			$.ajax({
				url : 'index.php',
				type : 'GET',
				data : 'c=dic&p1=' + mot + '&p2=' + motprec,
				dataType : 'html',
				success : function(result) {
					var newtexte = champ.val().toLowerCase();
					if(ltexte==newtexte) { // si pas changé entre temps
						var div = '';
						var idchamp=champ.attr("name");
						result=JSON.parse(result);
						for (var i=0; i<result.length; i++) {
							var id = 'suggestion_' + i;
							div +='<div id="'+id+'" style="display:block;" class="autosuggestion" value="'+result[i]+'" ';
							div += 'onclick="suggest_click(\''+id+'\',\''+idchamp+'\',\''+ mot + '\')">'+result[i]+'</div>'
						}
						// remplissage de l'html
						$("#autosuggestions-liste").html(div);
						var suggest = $("#autosuggestions");
						suggest.css('top',champ.position().top + champ.height()).css('left',champ.position().left + 20);
						suggest.show();
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					var statut=textStatus;
					//alert("Une erreur s'est produite lors de la requete");
				}
			});
			// ajuster la taille du champ
			champ.css('height',"1px");
			champ.css('height',(25+champ.prop('scrollHeight'))+"px");
		});
		if (SynerGaia.typemedia() === 'mobile') {
			SynerGaia.initOnMobile();
		}
		// attacher un événement aux éléments dragables
		//$('[draggable="true"]').each(function( i, el) {SynerGaia.dragable(el, el)});
		
	},
	initCategorie: function (id,tags) {
		$('#' + id )
		.bind("keydown", function( event ) {// ne pas sortir du champ sur tab
			if ( event.keyCode === $.ui.keyCode.TAB &&
				$( this ).autocomplete( "instance" ).menu.active ) {
				event.preventDefault();
			}
		})
		.autocomplete({
			minLength: 0,
			source: function( request, response ) {// retourne les information d'autocomplete
				response( $.ui.autocomplete.filter(
				tags, request.term.split( /,\s*/ ).pop() ) );
			},
			focus: function() {return false;},// ne pas insérer de valeur au focus
			select: function( event, ui ) {
				var terms = this.value.split( /,\s*/ );
				terms.pop();// enlever les valeurs courantes
				terms.push( ui.item.value );// ajouter le terme choisi
				terms.push( "" );// ajouter le place holder et une virgule à la fin
				this.value = terms.join( ", " );
				return false;
			}
		});
	},
	inputFileOnChange: function (files) {
		var taille = 0;
		var fichiers = [];
		var data;
		if(files) {
			var reader = new FileReader();
			reader.onloadend = function () {
					data = reader.result;
				};
			for (var i = 0; i < files.length ; i++) {
				var fic = files.item(i);
				taille += fic.size;
				var blob = fic.slice();
				var x;
				reader.readAsDataURL(blob);
				fichiers[i] = {name: fic.name, size: fic.size, data: data};
			}
		}
		return taille;
	},
	initUpload: function(step,id) {
		if(step == '1') {
			$.ajax({
				type: "POST",
				url: 'index.php?c=upl&p1=1',
				data: data,
				processData: false,
				contentType: false,
				success: function(result) {
					window.alert(result);
				},
				error: function(jqXHR, textStatus, errorThrown) {
					$('#debug').html('erreur d\'exécution : ' + errorThrown + ' ' + textStatus);
					$('#operation').html(jqXHR.responseText)
				}
			});
		} else if (step == '2') {
			var champ = id + '_fic';
			formdata = false;
			if (window.FormData) {
				var input = document.getElementById(id);
				var files = input.files;
				var len = input.files.length;
				var reponse = '';
				for (var i = 0 ; i < len; i+=3 ) {
					formdata = new FormData();
					formdata.append(champ + '[]', input.files[i]);
					if (i + 1 < len) {
						formdata.append(champ + '[]', input.files[i+1]);
						if (i + 2 < len) {
							formdata.append(champ + '[]', input.files[i+2]);
						}
					}
					$.ajax({
						url: 'index.php?c=upl&p1=2&p2=' + champ,
						type: "POST",
						data: formdata,
						processData: false,
						contentType: false,
						success: function (res) {
							reponse += res;
							document.getElementById(id + '_rep').innerHTML = reponse;
						},
						error: function(xhr, ajaxOptions, thrownError) {
							$('#' + id + '_rep').html(xhr.responseText);
						}
					});
				} 
			}
		}
	},
	themes: function() {
		$.ajax({
			url: 'index.php?c=thm',
			type: "POST",
			success: function (res) {
				document.getElementById('operation').innerHTML = res;
			},
			error: function(xhr, ajaxOptions, thrownError) {
				$('#erreurs').html(xhr.responseText);
			}
		});
	},
	initOnMobile: function() {
		e = document.getElementById('centre');
		if (e) {
			e.style.display='block';
		}
		e = document.getElementById('themes');
		if (e) {
			e.style.display='none';
		}
		e = document.getElementById('menuetcorps');
		if (e) {
			e.style.display='block'; //
			e.style.padding='3px'; // 
		}
		$.mobile.defaultPageTransition = "flip";
		$('#operation').swiperight(function () {
			if ($('#gauche').html() != '') {
				$('#operation').hide();
				$('#gauche').show();
			}
		});
		$('#gauche').swipeleft(function () {
			$('#gauche').hide();
			$('#operation').show();
		});
		$('#operation').swipeleft(function () {
			if ($('#droite').html() != '') {
				$('#operation').hide();
				$('#droite').show();
			}
		});
		$('#droite').swiperight(function () {
			$('#droite').hide();
			$('#operation').show();
		});
	},
	getElement: function (el) {
		if (typeof el == 'string') return document.getElementById(el);
		return el;
	},
	mouseX: function (e) {
		var ret = null;
		if (e.pageX) {
			ret = e.pageX;
		} else if (e.clientX) {
			ret = e.clientX + (
				document.documentElement.scrollLeft ? document.documentElement.scrollLeft : document.body.scrollLeft
			);
		}
		return ret;
	},
	mouseY: function (e) {
		var ret = null;
		if (e.pageY) {
			ret = e.pageY;
		} else if (e.clientY) {
			ret = e.clientY + (
				document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop
			);
		}
		return ret;
	},
	move: function (t,x,y) {
		if(t.style.left === '') {
			var tx = 0;
		} else {
			tx = parseInt(t.style.left);
		}
		if(t.style.top === '') {
			var ty = 0;
		} else {
			ty = parseInt(t.style.top);
		}
		t.style.left = (tx+x) + "px";
		t.style.top  = (ty +y) + "px";
	},
	mouseMoveHandler: function (e) {
		if (SynerGaia.dragtarget) {
			e = e || window.event;

			var x = SynerGaia.mouseX(e);
			var y = SynerGaia.mouseY(e);
			if (x != SynerGaia.offsetX || y != SynerGaia.offsetY) {
				SynerGaia.move(SynerGaia.dragtarget, x-SynerGaia.offsetX,y-SynerGaia.offsetY);
				SynerGaia.offsetX = x;
				SynerGaia.offsetY = y;
			}
		}
		return false;
	},
	start_drag: function (e) {
		e = e || window.event;

		SynerGaia.offsetX = SynerGaia.mouseX(e);
		SynerGaia.offsetY = SynerGaia.mouseY(e);
		SynerGaia.dragtarget = e.currentTarget;

		// sauvegarder un onmousemove précédent
		if (document.body.onmousemove) {
			SynerGaia.mousemoveTemp = document.body.onmousemove;
		}
		document.body.onmousemove = SynerGaia.mouseMoveHandler;
		return false;
	},
	stop_drag: function (e) {
		// restaurer le onmousemove précédent
		SynerGaia.dragtarget = null;
		if (SynerGaia.mousemoveTemp) {
			document.body.onmousemove = SynerGaia.mousemoveTemp;
			SynerGaia.mousemoveTemp = null;
		}
		return false;
	},
	dragable: function (el) {
		var p = SynerGaia.getElement(el);
		SynerGaia.offsetX = 0;
		SynerGaia.offsetY = 0;
		SynerGaia.mousemoveTemp = null;

		if (p) {
			p.onmousedown = SynerGaia.start_drag;
			p.onmouseup = SynerGaia.stop_drag;
		}
	},
	photoPrec: function(e, id) {
	},
	photoSuiv: function(e, id) {
	}
}
