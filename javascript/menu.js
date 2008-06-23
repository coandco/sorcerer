
sfHover = function() {
	var sfEls = document.getElementById("nav").getElementsByTagName("LI");
	//var sfEls = $('nav').getElementsByTagName("LI");
	for (var i=0; i<sfEls.length; i++) {
		sfEls[i].onmouseover=function() {
			this.className+=" sfhover";
		}
		sfEls[i].onmouseout=function() {
			this.className=this.className.replace(new RegExp(" ?sfhover\\b"), "");
		}
	}
	
	sfEls = document.getElementsByClassName("menuitem");
	for (var i=0; i<sfEls.length; i++) {
	  sfEls[i].onclick=function(){
		  this.className=this.className.replace(new RegExp(" ?sfhover\\b"), "");
		  this.parentNode.parentNode.className=this.parentNode.parentNode.className.replace(new RegExp(" ?sfhover\\b"), "");
		  //alert("You clicked " + this.className);
		}
	}
	
	sfEls = document.getElementsByClassName("submenuitem");
	for (var i=0; i<sfEls.length; i++) {
	  sfEls[i].onclick=function(){
		  this.className=this.className.replace(new RegExp(" ?sfhover\\b"), "");
		  this.parentNode.parentNode.className=this.parentNode.parentNode.className.replace(new RegExp(" ?sfhover\\b"), "");
			this.parentNode.parentNode.parentNode.parentNode.className=this.parentNode.parentNode.parentNode.parentNode.className.replace(new RegExp(" ?sfhover\\b"), "");
		  //alert("You clicked " + this.className);
		}
	}
}
