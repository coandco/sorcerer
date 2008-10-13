var cardRMenuItems = [
{
  name: 'Tap/Untap',
  className: 'rmenuitem',
	id: 'rmenu_tap_untap',
	title_function: function(targetEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
		//alert(Readable.toReadable(card));
	  if(card.istapped)
		  return 'Untap';
		else
		  return 'Tap';
	},
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
		if(card.istapped) {
		  card.cardAttributeAJAX({card_id: card.id, properties: {istapped: false}});
			card.cardAttributeAJAX({card_id: card.id, properties: {isattacking: false}});
		} else {
		  card.cardAttributeAJAX({card_id: card.id, properties: {istapped: true}});
		}
		//alert (menuEvent.element().id + " was clicked in the context of " + Readable.toReadable(targetEvent.element().findParentOfClass('card')));
	}	
}, {
  name: 'Attack/Unattack',
  className: 'rmenuitem',
	id: 'rmenu_attack_unattack',
	title_function: function(targetEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
	  if(card.isattacking)
		  return 'Unattack';
		else
		  return 'Attack';
	},
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
		if(card.isattacking) {
		  card.cardAttributeAJAX({card_id: card.id, properties: {istapped: false}});
			card.cardAttributeAJAX({card_id: card.id, properties: {isattacking: false}});
		} else {
		  card.cardAttributeAJAX({card_id: card.id, properties: {istapped: true}});
			card.cardAttributeAJAX({card_id: card.id, properties: {isattacking: true}});
		}
	  //alert (menuEvent.element().id + " was clicked in the context of " + Readable.toReadable(targetEvent.element().findParentOfClass('card')));
	}
}, {
  name: 'Attack without tapping',
  className: 'rmenuitem',
	id: 'rmenu_attack_notap',
	disabled_function: function(targetEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
	  if(card.isattacking)
		  return true;
		else
		  return false;
	},
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
		card.cardAttributeAJAX({card_id: card.id, properties: {isattacking: true}});
	  //alert (menuEvent.element().id + " was clicked in the context of " + Readable.toReadable(targetEvent.element().findParentOfClass('card')));
	}
}, {
  name: 'Untap as normal',
  className: 'rmenuitem',
	id: 'rmenu_normal_untap',
	title_function: function(targetEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
	  if(card.doesnotuntap)
		  return 'Untaps as Normal';
		else
		  return "Doesn't Untap as Normal";
	},
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
		card.cardAttributeAJAX({card_id: card.id, properties: {doesnotuntap: !card.doesnotuntap}});
	  //alert (menuEvent.element().id + " was clicked in the context of " + Readable.toReadable(targetEvent.element().findParentOfClass('card')));
	}
}, {
  separator: true
}, {	
  name: 'Phase out',
  className: 'rmenuitem',
	id: 'rmenu_phaseout_phasein',
	title_function: function(targetEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
	  if(card.isphased)
		  return 'Phase In';
		else
		  return 'Phase Out';
	},
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
		card.cardAttributeAJAX({card_id: card.id, properties: {isphased: !card.isphased}});
	  //alert (menuEvent.element().id + " was clicked in the context of " + Readable.toReadable(targetEvent.element().findParentOfClass('card')));
	}
}, {
  name: 'Morph',
  className: 'rmenuitem',
	id: 'rmenu_morph_unmorph',
	title_function: function(targetEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
	  if(card.ismorphed)
		  return 'Unmorph';
		else
		  return 'Morph';
	},
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
		card.cardAttributeAJAX({card_id: card.id, properties: {ismorphed: !card.ismorphed}});
		current_game.prepareCard(card);
	  //alert (menuEvent.element().id + " was clicked in the context of " + Readable.toReadable(targetEvent.element().findParentOfClass('card')));
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
		card.cardAttributeAJAX({card_id: card.id, properties: {counters: temp}});
	  //alert (menuEvent.element().id + " was clicked in the context of " + Readable.toReadable(targetEvent.element().findParentOfClass('card')));
	}
}, {
  name: 'Add Counter',
  className: 'rmenuitem',
	id: 'rmenu_add_counter',
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
		card.cardAttributeAJAX({card_id: card.id, properties: {counters: (card.counters + 1)}});
	  //alert (menuEvent.element().id + " was clicked in the context of " + Readable.toReadable(targetEvent.element().findParentOfClass('card')));
	}
}, {
  name: 'Remove Counter',
  className: 'rmenuitem',
	id: 'rmenu_remove_counter',
	disabled_function: function(targetEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
	  if(card.counters < 1)
		  return true;
		else
		  return false;
	},
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
		card.cardAttributeAJAX({card_id: card.id, properties: {counters: (card.counters - 1)}});
	  //alert (menuEvent.element().id + " was clicked in the context of " + Readable.toReadable(targetEvent.element().findParentOfClass('card')));
	}
}, {
  separator: true
}, {	
  name: 'Bury',
  className: 'rmenuitem',
	id: 'rmenu_move_grave',
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
		current_game.moveCard(card, card.location, 'btn_target_grave', targetEvent.clientX, targetEvent.clientY);
	  //alert (menuEvent.element().id + " was clicked in the context of " + Readable.toReadable(targetEvent.element().findParentOfClass('card')));
	}
}, {
  name: 'Return to Hand',
  className: 'rmenuitem',
	id: 'rmenu_move_hand',
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
    current_game.moveCard(card, card.location, 'handlist', targetEvent.clientX, targetEvent.clientY);
	  //alert (menuEvent.element().id + " was clicked in the context of " + Readable.toReadable(targetEvent.element().findParentOfClass('card')));
	}
}, {
  name: 'Return to Library',
  className: 'rmenuitem',
	id: 'rmenu_move_lib',
	disabled: true,
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
    current_game.moveCard(card, card.location, 'lib', targetEvent.clientX, targetEvent.clientY);
	  //alert (menuEvent.element().id + " was clicked in the context of " + Readable.toReadable(targetEvent.element().findParentOfClass('card')));
	}
}, {
  name: 'Remove from Game',
  className: 'rmenuitem',
	id: 'rmenu_move_rfg',
	callback: function(targetEvent, menuEvent) {
	  var card = targetEvent.element().findParentOfClass('card');
    current_game.moveCard(card, card.location, 'btn_target_rfg', targetEvent.clientX, targetEvent.clientY);
	  //alert (menuEvent.element().id + " was clicked in the context of " + Readable.toReadable(targetEvent.element().findParentOfClass('card')));
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