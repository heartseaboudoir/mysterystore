;(function($){
	$.extend($.fn,{
		lottery: function(options,callback) {
			var defaults = {
				switch: 1,
		        number: 0,
		        total: 50,
		        speed: 100
			},
			options = $.extend( {},defaults,options ),
			list = $('#jq-prize').find('li'),
			len = list.length - 1,
		    index = -1,
		    run = 0,
		    timer = null;

			if( options.switch == 1 ) {
		    	loadlist();
		    }

			function setclass() {
				index += 1;
				if( index > len ) {
					index = 0;
				}
				list.eq(index).addClass('wrap-active').siblings().removeClass('wrap-active');
			}
			function loadlist() {
				run += 1;
				setclass();
				if( run > options.total && options.number == list.eq(index).attr('data-id') ) {
					clearTimeout(timer);
					index = 0;
					run = 0;
					options.speed = 50;
					$('.wrap-active').append('<span><img src="/Public/res/Wap/smact/img/love.png"></span>');
					setTimeout(function(){
						callback();
					},400);
				}else {
					if( run < options.total ) {
						options.speed -= 10;
					}else {
						options.speed += 40;
					}
					if( options.speed < 40 ) {
						options.speed = 40;
					}
					timer = setTimeout( loadlist,options.speed );
				}
			}
		}
	});
})(Zepto);

;(function($){

	function nameshow() {
		var oMove = $('#jq-winning'),
			  oDt = oMove.find('h3'),
			  sum = oDt.find('span').width() - oMove.width(),
			    i = 0,
			onOff = true;
		if( sum > 0 ){
			oDt.find('span').addClass('wrap-span');
			var num = parseInt( oDt.find('span').css('margin-right') );
			var timer = setInterval(function(){
				i -= 1;
				if( i <= -sum ){
					if( onOff ){
						onOff = false;
						oDt.append( oDt.find('span').eq(0).clone() );
					}
				}
				if( i <= -(oMove.width() + sum) ){
					i = num ;
				}
				oDt.css({'left': i+ 'px'});
			},20)
		}
	}
	function formattime(seconds) {
		return [
			parseInt( seconds / 60 / 60 ),
			parseInt( seconds / 60 % 60 ),
			parseInt( seconds % 60 )
		].join(':').replace(/\b(\d)\b/g,'0$1');
	}

	var config = data_json.config,
    last_config = data_json.last_config;

	if( config.is_open == 1 ) {
		$('#jq-start').hide();
		$('#jq-winning').show();
	}else {
		$('#jq-start').show().html('抽奖活动还没开始哦。');
		$('#jq-winning').hide();
	}

	if( last_config ) {
		$('#jq-prize').html(template('temp-prize', { items: last_config.products_info.list }));
		remarkshow();
		setTimeout(function(){
			$.fn.lottery({
				switch: config.is_open,
			    number: last_config.act_product
			},function(){
				$('#jq-winning').html(template('temp-winning', { items: last_config }));
				setTimeout(function(){
					nameshow();
					if( JSON.stringify( last_config.products_info.list ) != JSON.stringify( config.products_info.list ) ){
						loadimg();
					}else{
						eventshow();
					}
				},500);

				function loadimg() {
					$('#jq-mask,#jq-load').show();
					$('#jq-load').html('<img src="/Public/res/Wap/smact/img/loading.gif"><p>正在更新今日待开奖商品</p>');
					setTimeout(function(){
						$('#jq-mask,#jq-load').hide();
						$('#jq-prize').html(template('temp-prize', { items: config.products_info.list }));
						eventshow();
					},5000);
				}
				function eventshow() {
					var off = true;
					$('#jq-prize').find('li').not('.wrap-active').on({
						click:function(){
							if( !off ) {
								return false;
							}
							off = false;
							var img = $(this).find('img').attr('src'),
							  title = $(this).attr('data-title'),
							   type = $(this).attr('data-type'),
							 	box = $('#jq-layer');

							box.removeClass('wrap-end').addClass('wrap-start'); 	
							box.find('.wrap-img').attr('src',img);
							box.find('h1').html(title);
							box.find('span').html(type);

							$('#jq-mask').show();
							box.show();
							clearshow();
							return false;
						}
					});
					$('#jq-layer').find('a').on({
						click:function(){
							$(this).parents().removeClass('wrap-start').addClass('wrap-end');
							$('#jq-mask').hide();
							off = true;
						}
					});
					function clearshow() {
						$(document).not('#jq-layer').on({
							click:function(){
								$('#jq-layer').find('a').click();
							}
						});
						$('#jq-layer').on({
							click:function(event){
								event.stopPropagation();
							}
						});
					}
				}
			});
		},1000);
	}else{
		$('#jq-prize').html(template('temp-prize', { items: config.products_info.list }));
		remarkshow();
	}
	function remarkshow() {
		var remark = [];
		$.each( config.remark_arr,function(i,items){
			remark.push( items + '<span></span>' );
		});
		$('#jq-remark').html( remark.join('') );
	}

	setTimeout(function(){
		timeshow();
	},0);
	setInterval(function() {
		timeshow();
	},1000);
	function timeshow() {
		config.backtime--;
		$('#jq-time').html( formattime( config.backtime ) );
		if( config.backtime == 0 ){
			window.location.reload();
		}
	}

	if ( data_json.product_lists == '' || !last_config ) {
		$('#jq-move').html('<p>抽奖活动还没开始哦。</p>');
	} else {
		$('#jq-move').html(template('temp-move', { items: data_json.product_lists }));
	}
	var distance = $('#jq-move').offset().top - $(window).height() + $('#jq-move').height() / 2,
		   onOff = true;
	$(window).scroll(function(){
        var top = $(this).scrollTop();
        if( top > distance && onOff ) {
        	onOff = false;
        	moveshow();
        }
    });
	function moveshow() {
		var box = $('#jq-move'),
		   list = box.find('li'),
		  timer = null,
		    off = true,
		      i = 0;
		if( list.length > 5 ){
			setInterval(function() {
				if( !off ) {
					return false;
				}
				i -= 1;
				if( i <= -list.height() ) {
					off = false;
					clearInterval( timer );
					timer = setInterval(function() {
						list = box.find('li');
						off = true;
					},1000);
					box.append( list.eq(0).clone() );
					list.eq(0).remove();
					i = 0;
				}
				box.css({'top': i +'px'});
			},20);
		}
	}
})(Zepto);
/*function sortid(a,b) {
	return a.id - b.id;
}
var arr = [];
for(var i in last_config.products_info.list ) {
	arr.push( last_config.products_info.list[i] );
}
arr.sort( sortid );*/