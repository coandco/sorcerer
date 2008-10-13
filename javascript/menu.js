
sfHover = function() {
	var sfEls = document.getElementById("nav").getElementsByTagName("LI");
	//var sfEls = $('nav').getElementsByTagName("LI");
	for (var i=0; i<sfEls.length; i++) {
	  Element.extend(sfEls[i]);
		sfEls[i].onmouseover=function() {
			this.addClassName("sfhover");
		}
		sfEls[i].onmouseout=function() {
			this.removeClassName("sfhover");
		}
	}
	
	sfEls = document.getElementsByClassName("menuitem");
	for (var i=0; i<sfEls.length; i++) {
	  sfEls[i].onclick=function(){
		  this.removeClassName("sfhover");
		  this.parentNode.parentNode.removeClassName("sfhover");
		  //alert("You clicked " + this.className);
		}
	}
	
	sfEls = document.getElementsByClassName("submenuitem");
	for (var i=0; i<sfEls.length; i++) {
	  sfEls[i].onclick=function(){
		  this.removeClassName("sfhover");
		  this.parentNode.parentNode.removeClassName("sfhover");
			this.parentNode.parentNode.parentNode.parentNode.removeClassName("sfhover");
		  //alert("You clicked " + this.className);
		}
	}
}
