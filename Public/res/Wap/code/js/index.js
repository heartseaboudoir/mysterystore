$(function(){
	
	function moveshow() {
		var oMove = $('#jq-move'),
			  oDt = oMove.find('dt'),
			  sum = oDt.width() - oMove.width(),
			    i = 0,
			onOff = true;
		if( sum > 0 ){
			setInterval(function(){
				i -= 1;
				if( i <= -sum ){
					if( onOff ){
						onOff = false;
						oDt.append( oDt.find('span').eq(0).clone() );
					}
				}
				if( i <= -(oMove.width() + sum * 2) ){
					i = -sum;
				}
				oDt.css({'left': i+ 'px'});
			},20)
		}
	}

    var oAudio = document.getElementById('jq-audio'),
        second = 1000,
        isLoad = true;

    setduration();
    function setduration() {
    	setInterval(function(){
	    	var currentTime = oAudio.currentTime,
	    		   duration = oAudio.duration;
	    	if( !isNaN( oAudio.duration ) && isLoad ){
	    		$('#jq-end').html( getduration( oAudio.duration ) );
	    	}
	     	if( parseInt(currentTime) > 0 ){
	     		isLoad = false;
	     	}
	    	$('#jq-start').html( getduration( currentTime ) );
	    	$('#jq-progress').find('p').css({ 'width': 100 - (currentTime / duration).toFixed(4) * 100 + '%' });
	    },second);
    }
    function getduration(options) {
    	var times = parseInt( options / 3600 ),
    	  minutes = parseInt( options / 60 ),
    	  seconds = parseInt( options % 60 ),
    	    total = compared(times) + ':' + compared(minutes) + ':' + compared(seconds);
    	return total;
    }
	function compared(options) {
		return options > 9 ? options : '0' + options;
	}

	$('#jq-play').on({
		click:function(){
			var self = $(this);
			if( oAudio.paused ) {
				self.addClass('wrap-stop').removeClass('wrap-start');
				oAudio.play();
				/*var loadresult = setInterval(function(){
					if( getduration( oAudio.currentTime ) == getduration( oAudio.duration )  ){
						clearInterval(loadresult);
						self.addClass('wrap-start').removeClass('wrap-stop');
					}
				},second);*/
			}else{
				self.addClass('wrap-start').removeClass('wrap-stop');
				oAudio.pause();
			}
		}
	});

	setTimeout(function(){
		$('#jq-container').show();
		$('#jq-loading').hide();
		moveshow();
	},2000);

	var loadresult = setInterval(function(){
    	if( !isLoad ){
    		clearInterval(loadresult);
    		$('#jq-play').addClass('wrap-stop').find('img').remove();
    	}
    },second);
});

/*
$(oAudio).on({
	'loadedmetadata':function(){
		oAudio.pause();
		$(document).on('touchend','#jq-progress',function(e){
			var x = e.originalEvent.changedTouches[0].clientX - this.offsetLeft,
				w = $(this).width();
			x = x < 0 ? 0 : x;
			var place = x > w ? w : x,
				ratio = (place / w).toFixed(2);
			oAudio.currentTime = ratio * oAudio.duration;
			$(this).find('p').css({ 'width': 100 - ratio * 100 + '%' });
		});
	}
})
function moveshow() {
	var oMove = $('#jq-move'),
		  oDt = oMove.find('dt');
		    i = 0;
	setInterval(function(){
		i += 1;
		if( i >= oMove.width() ){
			oDt.css({'left': -oDt.width()+'px'});
			i = -oDt.width();
			oDt.html( oDt.find('span').clone() );
		}
		oDt.css({'left': i+ 'px'});
	},20)
}
function moveshow() {
	var oMove = $('#jq-move'),
		  oDt = oMove.find('dt'),
		  num = 30,
		  sum = oDt.width() - oMove.width(),
		    i = 0,
		onOff = true;
	if( sum > 0 ){
		setInterval(function(){
			onOff ? i -= 1 : i += 1;
			if( i <= -(sum + num) ){
				onOff = false;
			}
			if( i >= num ){
				onOff = true;
			}
			oDt.css({'left': i+ 'px'});
		},20)
	}
}
*/