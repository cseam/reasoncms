function addAltTextToSearchButton(){
	$('.gsc-search-button-v2')[0].alt = "Search this site";
}

window.__gcse = {
	callback: addAltTextToSearchButton
};

(function() {
	var cx = '005935510434836484605:yecpxhsqj6s';
	var gcse = document.createElement('script');
	gcse.type = 'text/javascript';
	gcse.async = true;
	gcse.src = (document.location.protocol == 'https:' ? 'https:' : 'http:') +
		'//www.google.com/cse/cse.js?cx=' + cx;
	var s = document.getElementsByTagName('script')[0];
	s.parentNode.insertBefore(gcse, s);
})();