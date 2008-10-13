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
    this.uid = 0;
    this.cardcache = new Object();
		this.targets = new Object();
    this.gameid = gameid;
    this.valid = false;
		
    Element.responsefulAJAX($('table'), "syncGame", new Array(new Array()), function(transport) {
      var response = transport.responseText.evalJSON();
      current_game.syncGame(response[0]['gamestate']);
      Dialog.closeInfo();
    }, this);
	},
	
  isValidLocation: function (loc_string) {
    generic = loc_string.replace(/_[0-9]*/, '');
    switch(generic) {
      case 'table':
      case 'hand':
      case 'library':
      case 'grave':
      case 'rfg':
        return true;
      default:
        return false;
    }
  },
  
  idToTarget: function (idstring) {
    switch (idstring) {
      case 'table':
        return 'table' + this.uid;
      case 'handlist':
      case 'hand':
        return 'hand' + this.uid;
      case 'btn_target_lib':
      case 'library':
        return 'library' + this.uid;
      case 'btn_target_grave':
      case 'grave':
        return 'grave' + this.uid;
      case 'btn_target_rfg':
      case 'rfg':
        return 'rfg' + this.uid;
      default:
        return false;
    }
  },
  
	//moveCard: 
  moveCard: function(element, to, x, y, morphed) {
    if (Object.isUndefined(morphed))
      morphed = false;
    
    var card_id = element.id;
    
    from = element.location;
    
    this.targets[to][card_id] = new Object();
    this.targets[to][card_id].refhash = this.targets[from][card_id].refhash;
    
    //All statuses drop off when a card changes zones
    this.targets[to][card_id].istapped = false;
    this.targets[to][card_id].isattacking = false;
    this.targets[to][card_id].ismorphed = false;
    this.targets[to][card_id].isphased = false;
    this.targets[to][card_id].doesntuntap = false;;
    this.targets[to][card_id].counters = 0;
    this.targets[to][card_id].x = 0;
    this.targets[to][card_id].y = 0;
    
    if (to == 'table') { //However, we do set the x, y, and morphed attributes as a convenience
      this.targets[to][card_id].x = x;
      this.targets[to][card_id].y = y;
      this.targets[to][card_id].morphed = morphed;
    }
    
    this.syncDOM(new Array(from, to), new Array(card_id));
  },
  
  //syncDOM: Updates the HTML DOM to reflect the internal gamestate
  //The arguments are optional -- calling the function with fewer arguments will simply result in a broader sync
  //There are two algorithms.  If cardlist is specified, it iterates through the cards, checking each one
  //Otherwise, it iterates through the available zones, making sure the DOM matches
  //zonelist should be an array of zone names (even if there's only one)
  //cardlist should be an array of card IDs (even if there's only one)
  syncDOM: function (zonelist, cardlist) {
    if (Object.isUndefined(zonelist))
      zonelist = Object.keys(this.targets);
      
    if (!Object.isUndefined(cardlist)) { //Cardlist is defined, so check each card individually
      cardelements = new Array();
      for (i = 0; i < cardlist.length; i++) //Should end up with an array of elements the same length as cardlist
        cardelements[i] = $(cardlist[i]);
      
      for (i = 0; i < cardlist.length; i++) {
        for (j = 0; j < zonelist.length; j++) {
          if (!Object.isUndefined(this.targets[zonelist[j]][cardlist[i]])) { //If the card exists in this zone
            if (Object.isElement(cardelements[i])) { //If the card exists in the DOM
              if (cardelements[i].location != zonelist[j]) //If the card needs to be moved
                this.removeDOMCard(cardelements[i]);
              else //If the card doesn't need to be moved, keep going
                continue;
            }
            this.spawnCard(zonelist[j], cardlist[i]);
          }
        }
      }
    } else { //Cardlist isn't defined, so we'll iterate through the zones instead
      for (i = 0; i < zonelist.length; i++) {
        zonecards = Object.clone(this.targets[zonelist[i]]);
        domcards = $$('cardloc_' + zonelist[i]);
        
        for (j = 0; j < domcards.length; j++) { 
          if (Object.isUndefined(zonecards[domcards[j].id])) { //This card shouldn't exist.  Delete it.
            this.removeDOMCard(domcards[j]);
          } else { //This card exists as it should, so check it off on our internal list
            delete zonecards[domcards[j].id];
          }
        }
        
        for (zonecard in zonecards) { //After the previous loop, we should end up with a list of cards that don't exist in the DOM
          this.spawnCard(zonelist[i], zonecard);
        }
      }
    }
  },
  
  addCard: function(dbrow) {
    if (Object.isUndefined(dbrow['refhash'])) 
      return false;
    refhash = dbrow['refhash'];
    
    if (Object.isUndefined(dbrow['card_id']))
      return false;
    card_id = dbrow['card_id'];
    
    if (Object.isUndefined(dbrow['location']))
      dbrow['location'] = "hand_" + this.uid;
    cardlocation = dbrow['location'];
    
    if (!this.isValidLocation(cardlocation)) //Check the incoming data to make sure it's headed for one of the 5 valid card pile types
      return false;
      
    this.targets[cardlocation]['card_' + card_id] = new Object();
    this.targets[cardlocation]['card_' + card_id].refhash = refhash;
    
    if (cardlocation == 'table') {
      this.targets[cardlocation]['card_' + card_id].istapped = (dbrow['istapped'] == 1) ? true : false;
      this.targets[cardlocation]['card_' + card_id].isattacking = (dbrow['isattacking'] == 1) ? true : false;
      this.targets[cardlocation]['card_' + card_id].ismorphed = (dbrow['ismorphed'] == 1) ? true : false;
      this.targets[cardlocation]['card_' + card_id].isphased = (dbrow['isphased'] == 1) ? true : false;
      this.targets[cardlocation]['card_' + card_id].doesntuntap = (dbrow['doesntuntap'] == 1) ? true : false;;
      this.targets[cardlocation]['card_' + card_id].counters = dbrow['counters'];
      this.targets[cardlocation]['card_' + card_id].x = dbrow['x'];
      this.targets[cardlocation]['card_' + card_id].y = dbrow['y'];
    } else {
      this.targets[cardlocation]['card_' + card_id].stackorder = dbrow['stackorder'];
    }
    
    return true;
  },
  
  getAttribute: function(location, card_id, attribute) {
    return this.targets[location][card_id][attribute];
  },
  
  //Expects 'attributes' to be an associative array of attributes to set
  setAttributes: function(location, card_id, attributes) {
    for (attribute in attributes) {
      this.targets[location][card_id][attribute] = attributes[attribute];
    }
  },
  
  //removeDOMCard: Removes a card from the DOM
  //The argument can be either a card_id string or an element
  removeDOMCard: function(card) {
    if (!Object.isElement(card))
      card = $(card);
    
    card.id = '';
    card.parentNode.removeChild(card);
  },
  
  removeAllCards: function (removePiles) {
    var cardlist = $$('.card');
    
    if (Object.isUndefined(removePiles))
      removePiles = false;
    
    for (var i = 0; i < cardlist.length; i++) {
      this.removeDOMCard(cardlist[i]);
    }
    
    for (pile in this.targets) {
      for (card in this.targets[pile]) {
        delete this.targets[pile][card];
      }
      if (removePiles == true)
        delete this.targets[pile];
    }
  },
  
  syncGame: function(syncmessage) {
    this.removeAllCards(true); //Remove all cards, and remove the card piles at the same time
    
    this.uid = syncmessage['uid'];
    $('table').location_name = 'table';
    $('handlist').location_name = 'hand_' + this.uid;
    $('btn_target_lib').location_name = 'library_' + this.uid;
    $('btn_target_grave').location_name = 'grave_' + this.uid;
    $('btn_target_rfg').location_name = 'rfg_' + this.uid;
    
    
    
    this.targets['table'] = new Object();
    for (var i = 0; i < syncmessage['playerlist'].length; i++) {
      this.targets['library_' + syncmessage['playerlist'][i]['uid']] = new Object();
      this.targets['hand_' + syncmessage['playerlist'][i]['uid']] = new Object();
      this.targets['grave_' + syncmessage['playerlist'][i]['uid']] = new Object();
      this.targets['rfg_' + syncmessage['playerlist'][i]['uid']] = new Object();
    }
    
    for (i = 0; i < syncmessage['cardinfo'].length; i++) {
      this.cacheAdd(syncmessage['cardinfo'][i]);
    }
    
    for (i = 0; i < syncmessage['cardlist'].length; i++) {
      this.addCard(syncmessage['cardlist'][i]);
    }
    
    this.syncDOM();
    
    this.valid = true;
    
    //Eventually I'll need to deal with lifetotals and phases and such, but for now I just want cards
  },
	
	prepareCard: function(element) {
    if (element.location != 'table')
      return;
    
    cardinfo = Object.clone(this.targets['table'][element.id]);
  
	  element.style.left = (cardinfo.x - (cardinfo.x % 25)) + "px";
		element.style.top = (cardinfo.y - (cardinfo.y % 25)) + "px";
		element.style.zIndex = element.offsetTop / 25;
		
		Element.extend(element);
		
		element.innerHTML = '';
		if(/color_[A-Za-z]+/.exec(element.className) != null)
		  element.removeClassName(/color_[A-Za-z]+/.exec(element.className)[0]);
	  
		wrapper = new Element('div', {'class': 'card_overlay'});
		element.appendChild(wrapper);
		
		if (cardinfo.ismorphed) {
  	  wrapper.innerHTML = '<span class="card_name">Morphed Creature</span>';
		  wrapper.innerHTML += '<span class="card_atk_def">2/2</span>';
		} else {
			wrapper.innerHTML = '<span class="card_name">' + this.cardcache[cardinfo.refhash].name + '</span>';
  		if (this.cardcache[cardinfo.refhash].power != 0) 
  		  wrapper.innerHTML += '<span class="card_atk_def">' + this.cardcache[cardinfo.refhash].power + "/" + this.cardcache[cardinfo.refhash].toughness + '</span>';
		}
		
		if (cardinfo.istapped) {
		  var tap = new Element('div', {'class': 'card_tapped'}); 
		  tap.innerHTML = "T";
			if (cardinfo.doesntuntap)
			  tap.style.background = "yellow";
		  element.appendChild(tap);
		}
		
		if (cardinfo.isattacking) {
		  var atk = new Element('div', {'class': 'card_attack'}); 
		  atk.innerHTML = "A";
		  element.appendChild(atk);
		}
		
		if(cardinfo.isphased) {
		  var otherstatus = new Element('div', {'class': 'card_otherstatus'});
			otherstatus.innerHTML = "Phasing<br />";
			element.appendChild(otherstatus);
		}
		
		if ((cardinfo.counters != 0) && (!Object.isUndefined(cardinfo.counters))) {
		  if (element.getElementsByClassName('card_otherstatus').length == 0)
			  var otherstatus = new Element('div', {'class': 'card_otherstatus'});
			otherstatus.innerHTML += cardinfo.counters + ((cardinfo.counters == 1) ? ' counter' : ' counters');
			if (element.getElementsByClassName('card_otherstatus').length == 0)
		    element.appendChild(otherstatus);
		}
		
		
		var card_color = '';
		if (/land/i.test(this.cardcache[cardinfo.refhash].type)) {
		  card_color = 'L';
		  if (/basic/i.test(this.cardcache[cardinfo.refhash].type)) { 
			  if (/forest/i.test(this.cardcache[cardinfo.refhash].type)) 
				  card_color += 'G';
        else if (/plains/i.test(this.cardcache[cardinfo.refhash].type))
				  card_color += 'W';
				else if (/mountain/i.test(this.cardcache[cardinfo.refhash].type))
				  card_color += 'R';
				else if (/island/i.test(this.cardcache[cardinfo.refhash].type))
				  card_color += 'U';
				else if (/swamp/i.test(this.cardcache[cardinfo.refhash].type))
				  card_color += 'B';
			  else //Should never happen
				  card_color += 'X';
			} else {
			  card_color += 'M';
		  }
    
			if (this.cardcache[cardinfo.refhash].type.search(/snow/i) != -1)
			  card_color += 'S';
				
		} else {
		  
      if (this.cardcache[cardinfo.refhash].color == undefined) { 
    		card_color = /G/i.test(this.cardcache[cardinfo.refhash].cost) ? 'G' : '';
    		card_color += /W/i.test(this.cardcache[cardinfo.refhash].cost) ? 'W' : '';
    		card_color += /R/i.test(this.cardcache[cardinfo.refhash].cost) ? 'R' : '';
    		card_color += /U/i.test(this.cardcache[cardinfo.refhash].cost) ? 'U' : '';
    		card_color += /B/i.test(this.cardcache[cardinfo.refhash].cost) ? 'B' : '';
      } else {
        card_color = this.cardcache[cardinfo.refhash].color;
      }
  		
  		if (card_color.length < 1)
  		  card_color = 'A'; //Artifact
  		if (card_color.length > 1)
  		  card_color = 'M'; //Multicolor
		}
		
		if (cardinfo.ismorphed)
		  card_color = 'morphed';

	  element.addClassName('color_' + card_color); 
		
	},
	
  spawnCard: function(cardlocation, card_id) {
    if (!this.isValidLocation(cardlocation)) //Die if we're not fed a valid location
      return false;
    
    cardinfo = Object.clone(this.targets[cardlocation][card_id]);
    
    if (cardlocation == "table") {
      var el = new Element('div', {'class': 'tablecard card cardloc_table', 'id': card_id});
      el.location = cardlocation;
      $('table').appendChild(el);
      this.prepareCard(el);
      EventSelectors.assign(Rules); 
      document.body.card_rmenuhandler.addListener(el);
    } else if (cardlocation == ("hand_" + this.uid)) {
      var el = new Element('li', {'class': 'handcard card cardloc_' + cardlocation, 'id': card_id});
      el.location = cardlocation;
      el.innerHTML = this.cardcache[cardinfo.refhash].cost + " - " + this.cardcache[cardinfo.refhash].name;
      $('handlist').insertBefore(el, $('handlist').childNodes[0]);
      Sortable.create('handlist', {dragOnEmpty: 'true', constraint: '', onUpdate: function(target) { debug ("onUpdate: " + target.id + " " + target.lastdrop);}});
    } else {
      //Generic handler for adding stuff to Javascript "windows"
    }
	},
  
	spawnFakeCard: function(refhash) {
	  var el = new Element('div', {'class': 'tablecard card', 'id': 'card_' + this.num_table++});
		el.refhash = refhash;
		el.location = 'table';
		el.istapped = el.isattacking = el.ismorphed = el.isphased = el.doesntuntap = false;
		el.counters = 0;
		this.targets['table'][el.id] = new Object();
		this.targets['table'][el.id].refhash = refhash;
		this.prepareCard(el);
		$('table').appendChild(el);
		EventSelectors.assign(Rules); 
		document.body.card_rmenuhandler.addListener(el);
	},
  
  cacheAdd: function(card_info) {
    if (this.cardcache[card_info.refhash] == undefined)
      this.cardcache[card_info.refhash] = Object.clone(card_info);
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