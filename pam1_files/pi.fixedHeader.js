jQuery(function($){

	//region Fixed header
	var $w = $(window),
		$all = $('#pi-all'),
		$hrW = $('.pi-header-row-sticky-w'),
		$hr = $('.pi-header-row-sticky'),
		headerHeight = 0 ,
		headerTop = 0 ,
		fixedHeaderTreshold = -0,
		fixedHeaderActiveTreshold = 0,
		сlassFixed = 'pi-header-row-fixed',
		сlassStickyActive = 'pi-header-row-sticky-active',
		isScrollEventListenerAttached = 0;

	if($hrW.length){
		headerHeight += $hrW.height();
		headerTop += $hrW.offset().top;
	}

	if($hr.length){
		init();
		fixActiveRow();
	}

	function init(){
		if(!isScrollEventListenerAttached) {
			$w.scroll(function(){
				fixActiveRow();
			});
			isScrollEventListenerAttached = 1;
		}
	}

	function fixActiveRow(){
		var scrollTop = $w.scrollTop();
		if(scrollTop >= headerTop + fixedHeaderTreshold){
			$all.addClass(сlassFixed);
		} else {
			$all.removeClass(сlassFixed);
		}
		if(scrollTop >= headerTop + fixedHeaderActiveTreshold){
			$hr.addClass(сlassStickyActive);
		} else {
			$hr.removeClass(сlassStickyActive);
		}
	}

	//endregion

});