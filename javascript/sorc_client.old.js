// Sorcerer client code
//
// Includes persistent game-state variables and such


var Sorcerer_game = Class.create({
  initialize: function(gameid) {
	  var defaults = { };
		
		var options = Object.extend(defaults, arguments[1] || { });
		
		var num_cards = 1;
		
		var cards;
		
		this.options = options;
		this.num_table = num_cards;
		this.num_hand = 0;
		this.num_grave = 0;
		this.num_rfg = 0;
		this.targets = new Object();
		this.targets['table'] = new Object();
		this.targets['hand'] = new Object();
		this.targets['grave'] = new Object();
		this.targets['rfg'] = new Object();
    this.gameid = gameid;
		
	},
	
  idToTarget: function (idstring) {
    switch (idstring) {
      case 'table':
        return 'table';
      case 'handlist':
      case 'hand':
        return 'hand';
      case 'btn_target_lib':
      case 'library':
        return 'library';
      case 'btn_target_grave':
      case 'grave':
        return 'grave';
      case 'btn_target_rfg':
      case 'rfg':
        return 'rfg';
      default:
        return false;
    }
  },
  
	//Expects the element to move and two strings as arguments
	//The strings need to be the IDs of the element's source and destination
	moveCard: function(element, from, to, x, y, morphed) {
	  
		var card_id = element.id;
		
    from = this.idToTarget(from);
    to = this.idToTarget(to);
    
		//Yes, I'm aware that I didn't include breaks -- I want it to fall through.
		switch(from) {
		  case 'hand':
			  card_id = /card_\d+/.exec(element.id)[0];
			case 'table':
			  element.id = ''; //Just in case we re-add this element later, I don't want a duplicate id to be an issue
			  element.parentNode.removeChild(element);
			default:
			  this.targets[to][card_id] = new Object();
				this.targets[to][card_id].card_info = Object.clone(this.targets[from][card_id].card_info);
		    delete this.targets[from][card_id];
			  break;
		}
		
		this.targets[to][card_id].location = to;
		
		switch(to) {
		  case 'hand':
			  element = document.createElement('li');
				element.className = 'handcard';
				element.id = card_id;
				element.card_info = Object.clone(this.targets[to][card_id].card_info);
				element.location = to;
				element.innerHTML = this.targets[to][card_id].card_info.cost + " - " + this.targets[to][card_id].card_info.name;
			  $('handlist').appendChild(element);
				Sortable.create('handlist', {dragOnEmpty: 'true', constraint: ''});
			  break;
			case 'table':
			  element = document.createElement('div');
				element.className = 'card';
				element.id = card_id;
		    element.card_info = Object.clone(this.targets[to][card_id].card_info);
				element.location = to;
				element.style.left = (x - Element.cumulativeOffset($('table')).left) + "px";
				element.style.top = (y - Element.cumulativeOffset($('table')).top) + "px";
				if(morphed)
				  element.ismorphed = true;
				$('table').appendChild(element);
				this.prepareCard(element);
				EventSelectors.assign(Rules); 
				document.body.card_rmenuhandler.addListener(element);
			  break;
			default:
			  break;
		}
		
		
	},
	
	prepareCard: function(element) {
    
    if (element.location != 'table')
      return;
  
	  element.style.left = (element.offsetLeft - (element.offsetLeft % 25)) + "px";
		element.style.top = (element.offsetTop - (element.offsetTop % 25)) + "px";
		element.style.zIndex = element.offsetTop / 25;
		
		Element.extend(element);
		
		element.innerHTML = '';
		if(/color_[A-Za-z]+/.exec(element.className) != null)
		  element.removeClassName(/color_[A-Za-z]+/.exec(element.className)[0]);
	  
		wrapper = new Element('div', {'class': 'card_overlay'});
		element.appendChild(wrapper);
		
		if (element.ismorphed) {
  	  wrapper.innerHTML = '<span class="card_name">Morphed Creature</span>';
		  wrapper.innerHTML += '<span class="card_atk_def">2/2</span>';
		} else {
			wrapper.innerHTML = '<span class="card_name">' + element.card_info.name + '</span>';
  		if (element.card_info.power) 
  		  wrapper.innerHTML += '<span class="card_atk_def">' + element.card_info.power + "/" + element.card_info.toughness + '</span>';
		}
		
		if (element.istapped) {
		  var tap = new Element('div', {'class': 'card_tapped'}); 
		  tap.innerHTML = "T";
			if (element.doesnotuntap)
			  tap.style.background = "yellow";
		  element.appendChild(tap);
		}
		
		if (element.isattacking) {
		  var atk = new Element('div', {'class': 'card_attack'}); 
		  atk.innerHTML = "A";
		  element.appendChild(atk);
		}
		
		if(element.isphased) {
		  var otherstatus = new Element('div', {'class': 'card_otherstatus'});
			otherstatus.innerHTML = "Phasing<br />";
			element.appendChild(otherstatus);
		}
		
		if ((element.counters != 0) && (element.counters != undefined)) {
		  if (element.getElementsByClassName('card_otherstatus').length == 0)
			  var otherstatus = new Element('div', {'class': 'card_otherstatus'});
			otherstatus.innerHTML += element.counters + ((element.counters == 1) ? ' counter' : ' counters');
			if (element.getElementsByClassName('card_otherstatus').length == 0)
		    element.appendChild(otherstatus);
		}
		
		
		var card_color = '';
		if (/land/i.test(element.card_info.type)) {
		  card_color = 'L';
		  if (/basic/i.test(element.card_info.type)) { 
			  if (/forest/i.test(element.card_info.type)) 
				  card_color += 'G';
        else if (/plains/i.test(element.card_info.type))
				  card_color += 'W';
				else if (/mountain/i.test(element.card_info.type))
				  card_color += 'R';
				else if (/island/i.test(element.card_info.type))
				  card_color += 'U';
				else if (/swamp/i.test(element.card_info.type))
				  card_color += 'B';
			  else //Should never happen
				  card_color += 'X';
			} else {
			  card_color += 'M';
		  }
    
			if (element.card_info.type.search(/snow/i) != -1)
			  card_color += 'S';
				
		} else {
		  
  		card_color = /G/i.test(element.card_info.cost) ? 'G' : '';
  		card_color += /W/i.test(element.card_info.cost) ? 'W' : '';
  		card_color += /R/i.test(element.card_info.cost) ? 'R' : '';
  		card_color += /U/i.test(element.card_info.cost) ? 'U' : '';
  		card_color += /B/i.test(element.card_info.cost) ? 'B' : '';
  		
  		if (card_color.length < 1)
  		  card_color = 'A'; //Artifact
  		if (card_color.length > 1)
  		  card_color = 'M'; //Multicolor
		}
		
		if (element.ismorphed)
		  card_color = 'morphed';

	  element.addClassName('color_' + card_color); 
		
	},
	
  spawnCard: function(db_card_info, card_id, location) {
    
    if (typeof location == "undefined")
      location = "hand";
      
    location = this.idToTarget(location);
    debug("Spawning card at '" + location + "'");
    
    switch (location) {
      case 'table':
        var el = new Element('div', {'class': 'card', 'id': 'card_' + card_id});
    		el.card_info = Object.clone(db_card_info);
    		el.location = 'table';
    		el.istapped = el.isattacking = el.ismorphed = el.isphased = el.doesnotuntap = false;
    		el.counters = 0;
    		this.targets['table'][el.id] = new Object();
    		this.targets['table'][el.id].card_info = Object.clone(db_card_info);
    		this.prepareCard(el);
    		$('table').appendChild(el);
    		EventSelectors.assign(Rules); 
    		document.body.card_rmenuhandler.addListener(el);
        break;
      case 'hand':
        debug("Switched to hand");
        var el = new Element('li', {'class': 'handcard', 'id': 'card_' + card_id});
        debug("class: " + el.className);
				el.card_info = Object.clone(db_card_info);
				el.location = 'hand';
        this.targets['hand'][el.id] = new Object();
    		this.targets['hand'][el.id].card_info = Object.clone(db_card_info);
				el.innerHTML = db_card_info.cost + " - " + db_card_info.name;
			  $('handlist').appendChild(el);
				Sortable.create('handlist', {dragOnEmpty: 'true', constraint: ''});
        break;
      case 'grave':
        el = document.createElement('li');
				el.className = 'gravecard';
				el.id = card_id;
				el.card_info = Object.clone(db_card_info);
				el.location = 'grave';
        this.targets['grave'][el.id] = new Object();
    		this.targets['grave'][el.id].card_info = Object.clone(db_card_info);
				el.innerHTML = db_card_info.name;
			  //$('handlist').appendChild(el);
				//Sortable.create('handlist', {dragOnEmpty: 'true', constraint: ''});
        break;
      case 'rfg':
        el = document.createElement('li');
				el.className = 'rfgcard';
				el.id = card_id;
				el.card_info = Object.clone(db_card_info);
				el.location = 'rfg';
        this.targets['rfg'][el.id] = new Object();
    		this.targets['rfg'][el.id].card_info = Object.clone(db_card_info);
				el.innerHTML = db_card_info.name;
			  //$('handlist').appendChild(el);
				//Sortable.create('handlist', {dragOnEmpty: 'true', constraint: ''});
        break;
      case 'library':
        break;
      default:
        break;
    }
	},
  
	spawnFakeCard: function(card_info) {
	  var el = new Element('div', {'class': 'card', 'id': 'card_' + this.num_table++});
		el.card_info = Object.clone(card_info);
		el.location = 'table';
		el.istapped = el.isattacking = el.ismorphed = el.isphased = el.doesnotuntap = false;
		el.counters = 0;
		this.targets['table'][el.id] = new Object();
		this.targets['table'][el.id].card_info = Object.clone(card_info);
		this.prepareCard(el);
		$('table').appendChild(el);
		EventSelectors.assign(Rules); 
		document.body.card_rmenuhandler.addListener(el);
	}
  
 /* getURLParameter: function(name) {
    name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
    var regexS = 
    var regex = new RegExp( regexS );
    var results = regex.exec( window.location.href );
    if( results == null )
      return "";
    else
      return results[1];
  }*/
	
});