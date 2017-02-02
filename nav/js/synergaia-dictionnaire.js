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
