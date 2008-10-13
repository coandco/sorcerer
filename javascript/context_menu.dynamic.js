var cardRMenuItems = [
{
  name: 'Tap/Untap',
  className: 'rmenuitem',
	id: 'rmenu_tap_untap',
	title_function: function(targetEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
		//alert(Readable.toReadable(card));
	  if(current_game.getAttribute(card.location, card.id, 'istapped'))
		  return 'Untap';
		else
		  return 'Tap';
	},
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
		if(current_game.getAttribute(card.location, card.id, 'istapped')) {
		  card.responselessAJAX("cardAttributes", new Array({card_id: card.id.replace(/[^0-9]/g, ''), attributes: {istapped: false, isattacking: false}}));
      current_game.setAttributes(card.location, card.id, {istapped: false, isattacking: false});
		} else {
		  card.responselessAJAX("cardAttributes", new Array({card_id: card.id.replace(/[^0-9]/g, ''), attributes: {istapped: true}}));
      current_game.setAttributes(card.location, card.id, {istapped: true});
		}
    current_game.prepareCard(card);
	}	
}, {
  name: 'Attack/Unattack',
  className: 'rmenuitem',
	id: 'rmenu_attack_unattack',
	title_function: function(targetEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
	  if(current_game.getAttribute(card.location, card.id, 'isattacking'))
		  return 'Unattack';
		else
		  return 'Attack';
	},
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
		if(current_game.getAttribute(card.location, card.id, 'isattacking')) {
		  card.responselessAJAX("cardAttributes", new Array({card_id: card.id.replace(/[^0-9]/g, ''), attributes: {istapped: false, isattacking: false}}));
			current_game.setAttributes(card.location, card.id, {istapped: false, isattacking: false});
		} else {
		  card.responselessAJAX("cardAttributes", new Array({card_id: card.id.replace(/[^0-9]/g, ''), attributes: {istapped: true, isattacking: true}}));
			current_game.setAttributes(card.location, card.id, {istapped: true, isattacking: true});
		}
    current_game.prepareCard(card);
	}
}, {
  name: 'Attack without tapping',
  className: 'rmenuitem',
	id: 'rmenu_attack_notap',
	disabled_function: function(targetEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
	  if(current_game.getAttribute(card.location, card.id, 'isattacking'))
		  return true;
		else
		  return false;
	},
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
		card.responselessAJAX("cardAttributes", new Array({card_id: card.id.replace(/[^0-9]/g, ''), attributes: {isattacking: true}}));
    current_game.setAttributes(card.location, card.id, {isattacking: true});
    current_game.prepareCard(card);
	}
}, {
  name: 'Untaps as normal',
  className: 'rmenuitem',
	id: 'rmenu_normal_untap',
	title_function: function(targetEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
	  if(current_game.getAttribute(card.location, card.id, 'doesntuntap'))
		  return 'Untaps as Normal';
		else
		  return "Doesn't Untap as Normal";
	},
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
		card.responselessAJAX("cardAttributes", new Array({card_id: card.id.replace(/[^0-9]/g, ''), attributes: {doesntuntap: !current_game.getAttribute(card.location, card.id, 'doesntuntap')}}));
    current_game.setAttributes(card.location, card.id, {doesntuntap: !current_game.getAttribute(card.location, card.id, 'doesntuntap')});
    current_game.prepareCard(card);
	}
}, {
  separator: true
}, {	
  name: 'Phase out',
  className: 'rmenuitem',
	id: 'rmenu_phaseout_phasein',
	title_function: function(targetEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
	  if(current_game.getAttribute(card.location, card.id, 'isphased'))
		  return 'Phase In';
		else
		  return 'Phase Out';
	},
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
		card.responselessAJAX("cardAttributes", new Array({card_id: card.id.replace(/[^0-9]/g, ''), attributes: {isphased: !current_game.getAttribute(card.location, card.id, 'isphased')}}));
    current_game.setAttributes(card.location, card.id, {isphased: !current_game.getAttribute(card.location, card.id, 'isphased')});
    current_game.prepareCard(card);
	}
}, {
  name: 'Morph',
  className: 'rmenuitem',
	id: 'rmenu_morph_unmorph',
	title_function: function(targetEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
	  if(current_game.getAttribute(card.location, card.id, 'ismorphed'))
		  return 'Unmorph';
		else
		  return 'Morph';
	},
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
		card.responselessAJAX("cardAttributes", new Array({card_id: card.id.replace(/[^0-9]/g, ''), attributes: {ismorphed: !current_game.getAttribute(card.location, card.id, 'ismorphed')}}));
    current_game.setAttributes(card.location, card.id, {ismorphed: !current_game.getAttribute(card.location, card.id, 'ismorphed')});
		current_game.prepareCard(card);
	}
}, {
  separator: true
}, {	
  name: 'Set Counters...',
  className: 'rmenuitem',
	id: 'rmenu_set_counters',
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
		do {
		  temp = parseInt(prompt("Enter the number of counters:"));
		} while (isNaN(temp));
		card.responselessAJAX("cardAttributes", new Array({card_id: card.id.replace(/[^0-9]/g, ''), attributes: {counters: temp}}));
    current_game.setAttributes(card.location, card.id, {counters: temp});
    current_game.prepareCard(card);
	}
}, {
  name: 'Add Counter',
  className: 'rmenuitem',
	id: 'rmenu_add_counter',
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
		card.responselessAJAX("cardAttributes", new Array({card_id: card.id.replace(/[^0-9]/g, ''), attributes: {counters: ((current_game.getAttribute(card.location, card.id, 'counters') || 0) + 1)}}));
    current_game.setAttributes(card.location, card.id, {counters: (current_game.getAttribute(card.location, card.id, 'counters') || 0) + 1});
	  current_game.prepareCard(card);
	}
}, {
  name: 'Remove Counter',
  className: 'rmenuitem',
	id: 'rmenu_remove_counter',
	disabled_function: function(targetEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
	  if(current_game.getAttribute(card.location, card.id, 'counters') < 1)
		  return true;
		else
		  return false;
	},
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
		card.responselessAJAX("cardAttributes", new Array({card_id: card.id.replace(/[^0-9]/g, ''), attributes: {counters: ((current_game.getAttribute(card.location, card.id, 'counters') || 0) - 1)}}));
    current_game.setAttributes(card.location, card.id, {counters: (current_game.getAttribute(card.location, card.id, 'counters') || 0) - 1});
	  current_game.prepareCard(card);
	}
}, {
  separator: true
}, {	
  name: 'Bury',
  className: 'rmenuitem',
	id: 'rmenu_move_grave',
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
    card.responselessAJAX("cardMove", new Array({card_id: card.id.replace(/[^0-9]/g, ''), to: 'grave', x: (targetEvent.clientX - Element.cumulativeOffset($('table')).left), y: (targetEvent.clientY - Element.cumulativeOffset($('table')).top)}));
		current_game.moveCard(card, $('btn_target_grave').location_name, (targetEvent.clientX - Element.cumulativeOffset($('table')).left), (targetEvent.clientY - Element.cumulativeOffset($('table')).top));
	}
}, {
  name: 'Return to Hand',
  className: 'rmenuitem',
	id: 'rmenu_move_hand',
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
    card.responselessAJAX("cardMove", new Array({card_id: card.id.replace(/[^0-9]/g, ''), to: 'hand', x: (targetEvent.clientX - Element.cumulativeOffset($('table')).left), y: (targetEvent.clientY - Element.cumulativeOffset($('table')).top)}));
    current_game.moveCard(card, $('handlist').location_name, (targetEvent.clientX - Element.cumulativeOffset($('table')).left), (targetEvent.clientY - Element.cumulativeOffset($('table')).top));
	}
}, {
  name: 'Return to Library',
  className: 'rmenuitem',
	id: 'rmenu_move_lib',
	disabled: true,
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
    card.responselessAJAX("cardMove", new Array({card_id: card.id.replace(/[^0-9]/g, ''), to: 'library', x: (targetEvent.clientX - Element.cumulativeOffset($('table')).left), y: (targetEvent.clientY - Element.cumulativeOffset($('table')).top)}));
    current_game.moveCard(card, $('btn_target_lib').location_name, (targetEvent.clientX - Element.cumulativeOffset($('table')).left), (targetEvent.clientY - Element.cumulativeOffset($('table')).top));
	}
}, {
  name: 'Remove from Game',
  className: 'rmenuitem',
	id: 'rmenu_move_rfg',
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
    card.responselessAJAX("cardMove", new Array({card_id: card.id.replace(/[^0-9]/g, ''), to: 'rfg', x: (targetEvent.clientX - Element.cumulativeOffset($('table')).left), y: (targetEvent.clientY - Element.cumulativeOffset($('table')).top)}));
    current_game.moveCard(card, $('btn_target_rfg').location_name, (targetEvent.clientX - Element.cumulativeOffset($('table')).left), (targetEvent.clientY - Element.cumulativeOffset($('table')).top));
	}
}, {
  separator: true
}, {	
  name: 'View Card',
  className: 'rmenuitem',
	id: 'rmenu_view_card',
	callback: function(targetEvent, menuEvent) {
	  alert (menuEvent.element().id + " was clicked in the context of " + Readable.toReadable(targetEvent.element().findParentOfClass('card')));
	}
}];