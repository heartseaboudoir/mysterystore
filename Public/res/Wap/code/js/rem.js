function htmlSize(){
	var oHtml = document.documentElement;
	var aWidth = oHtml.getBoundingClientRect().width;
	if(aWidth < 750){
		oHtml.style.fontSize = aWidth / 15 + "px";
	}else{
		oHtml.style.fontSize = 750 / 15 + "px";
	}	
}
(function(){
	htmlSize();
})();
window.onresize = function(){
	htmlSize();
}