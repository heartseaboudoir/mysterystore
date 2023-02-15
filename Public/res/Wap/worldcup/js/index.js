;(function($){
    function moneyshow() {
    	 var box = $('#jq-money'),
	    distance = box.offset().top - $(window).height() + box.height() / 2,
		   onOff = true;
		$(window).scroll(function(){
	        var top = $(this).scrollTop();
	        if( top > distance && onOff ) {
	        	onOff = false;
	        	var oMoney = box.find('span'),
			        vMoney = oMoney.text();
			    	 money = data_json.prices,
			    	second = 20,
			    	   num = 1;  
			    if( String(money).split('.')[1] > 0 ){
			    	var isFloat = true;
			    }
			    if( money > 50 ){
			    	second = 10;
			    	num = parseInt( money / 50 );
			    }
			    var timer = setInterval(function(){
			    	if( vMoney >= money ){
			    		clearInterval(timer);
			    	}
			    	vMoney = parseInt(vMoney) + num;
			    	var sum = vMoney;
			    	if( sum > money ){
			    		if( isFloat ){
			    			oMoney.html( money );
			    		}
			    		return false;
			    	}
			    	oMoney.html( sum );
			    }, second);
	        }
	    });
    }
    moneyshow();
	
	function viewshow() {
		var off = true;
		$('#jq-pdt').find('li').on({
			click: function(){
				if( !off ) {
					return false;
				}
				off = false;
				var img = $(this).attr('data-src'),
				  title = $(this).find('p').attr('data-name'),
				   type = $(this).attr('data-type'),
				 	box = $('#jq-view');
	
				box.removeClass('wrap-end').addClass('wrap-start'); 
				box.find('img').attr('src',img);
				box.find('h1').html(title);
				box.find('span').html(type);

				$('#jq-mask').show();
				box.show();
				pdtclear();
				return false;
			}
		});
		function pdtclear() {
			$(document).not('#jq-view').one({
				click: function(){
					$('#jq-view').removeClass('wrap-start').addClass('wrap-end');
					$('#jq-mask').hide();
					off = true;
				}
			});
			//ios兼容处理
			$('body').children().on({
				click:function(){}
			});
			$('#jq-view').on({
				click: function(event){
					event.stopPropagation();
				}
			});
		}
	}
	viewshow();

	function formatdate(options) {
		var hour = parseInt( options / 60 / 60 ),
		  minute = parseInt( options / 60 % 60 ),
		 seconds = parseInt( options % 60 ); 
		date = hour + ':' + minute + ':' + seconds;
		if( hour > 24 ) {
			hour = parseInt( hour % 24 );
			var day = parseInt( options / 60 / 60 / 24 );
			date = day + ':' + hour + ':' + minute + ':' + seconds;
		}
		return date.replace(/\b(\d)\b/g,'0$1');
	}

	var startshow = false;
	var rechargeshow = false;
	function timeshow(options) {
		var box = $( options.id ).find('span');
		switch( options.data ) {
			case -1:
			box.text( '已结束' );
			if( options.id == '#jq-time-recharge' ) {
				$('#jq-total').find('a').on({
		    		click:function(){
		    			tips('充值时间已结束');
		    		}
		    	});
				return false;
			}
			if( options.id == '#jq-time2' ) {
				startshow = true;
			}
			$('#jq-end').val(0);
			resultshow();
			break;
			case 0:
			box.text( '未开始' );
			$('#jq-team').find('li a').on({
				click:function(){
					tips('竞猜时间未开始');
					return false;
				}
			});
			if( options.id == '#jq-time-recharge' ) {
				if( data_json.record.uinfo > 0 ) {
					$('#jq-total').find('a').on({
						click:function(){
							tips('充值时间未开始');
							return false;
						}
					})
				}
			}
			break;
			default:
			if( options.id == '#jq-time2' ) {
				startshow = true;
			}
			if( options.id == '#jq-time-recharge' ) {
				rechargeshow = true;
			}
			setTimeout(function() {
				countdown(options,box);
			},0);
			options.timer = setInterval(function() {
				countdown(options,box);
			},1000);
			break;
		}
	}
	timeshow({
		id: '#jq-time',
		data: data_json.backtime_price
	});
	timeshow({
		id: '#jq-time2',
		data: data_json.backtime_vote
	});
	timeshow({
		id: '#jq-time-recharge',
		data: data_json.backtime_recharge
	});

	function countdown(options,box) {
		options.data--;
		box.text( formatdate(options.data) );
		if( options.data == 0 ){
			clearInterval( options.timer );
			if( options.id == '#jq-time' ) {
				tips( '奖池积累时间已结束' );
			}
			if( options.id == '#jq-time2' ) {
				tips( '竞猜时间已结束' );
				$('#jq-end').val(0);
				$('#jq-bet,#jq-mask').hide();
				resultshow();
			}
			$( options.id ).find('span').text('已结束');
		}
	}

	function resultshow() {
		if( data_json.win ) {
			var data_default = {
				win: [{
					wc_pic: '/Public/res/Wap/worldcup/img/result12.png',
					wc_img: '/Public/res/Wap/worldcup/img/result13.png',
				},{
					wc_pic: '/Public/res/Wap/worldcup/img/result14.png',
					wc_img: '/Public/res/Wap/worldcup/img/result15.png',
				}]	
			};
			$.each( data_default.win,function(i,items){
				data_default.win[i]['wc_name'] = data_json.win.wc_name;
				data_default.win[i]['win_num'] = data_json.win.win_num;
			})

			switch( parseInt( data_json.win.wc_id ) ){
				case 1: 
				$('#jq-result').html(template('temp-result', { items: data_default.win[0] }));
				break;
				case 2: 
				$('#jq-result').html(template('temp-result', { items: data_default.win[1] }));
				break;
			};
		}
	}

	if( data_json.record.uinfo > 0 ) {
		$('#jq-total').find('img').attr('src','/Public/res/Wap/worldcup/img/total02.jpg');
		$('#jq-total').find('a').attr('href','javascript:void(0);');

		if( startshow ) {
			var teamarr = ['/Public/res/Wap/worldcup/img/team05.png',
			'/Public/res/Wap/worldcup/img/team06.png'];
			$.each( data_json.teams,function(i,items){
				data_json.teams[i]['wc_img'] = teamarr[i];
			});
			$('#jq-team').html(template('temp-team', { items: data_json.teams }));
			$('#jq-total').find('span').text( data_json.record.data.can );
			
			var bet_index = 0;
			function teamshow() {
				var off = true;
				$('#jq-team').find('li a').on({
					click:function(){
						if( parseInt( $('#jq-end').val() ) == 0 ){
							tips('竞猜时间已结束');
							return false;
						}
						var oSum = $('#jq-total').find('span'),
					         sum = parseInt( oSum.html() );
						if( !off ) {
							return false;
						}
						if( sum <= 0 ) {
							tips('竞猜次数已用完');
							return false;
						}
						bet_index = $(this).parents('li').index();

						off = false;
						var box = $('#jq-bet');

						box.removeClass('wrap-end').addClass('wrap-start'); 
						box.find('input').val('');
						$('#jq-mask').show();
						box.show();
						teamclear();
						return false;
					}
				});
				function teamclear() {
					$(document).not('#jq-bet').one({
						click:function(){
							if( $('.site-tips').length > 0 ) {
								return false;
							}
							$('#jq-bet').removeClass('wrap-start').addClass('wrap-end');
							$('#jq-mask').hide();
							off = true;
						}
					});
					$('body').children().on({
						click:function(){}
					});
					$('#jq-bet').on({
						click:function(event){
							event.stopPropagation();
						}
					});
				}
			}
			teamshow();

			$('#jq-bet').find('input').keyup(function(){
				if( $(this).attr('num') == '' ){
					$(this).val( $(this).val().replace(/\D/gi, '') );
				}
				var oSum = $('#jq-total').find('span'),
					 sum = parseInt( oSum.html() );
				if( $(this).val() > sum ) {
					$(this).val( sum );
				}
			});

			$('#jq-bet').find('form').on({
				submit: function(){
					var current = $('#jq-bet').find('input').val() | 0,
						  oSpan = $('#jq-team').find('li').eq(bet_index).find('span');
					if( current <= 0 ) {
						tips( '竞猜次数须大于0' );
						return false;
					}
					$('#jq-bet,#jq-mask').hide();
					var data = {
						num: current,
						team: $('#jq-team').find('li').eq(bet_index).attr('data-id')
					}
					$.post('/Wap/WorldCup/select', data, function(res){
						if ( res.code == 0 ) {
							oSpan.html( parseInt( oSpan.html() ) + current );

							var oSum = $('#jq-total').find('span'),
							     sum = parseInt( oSum.html() );
							    last = sum - current;
							oSum.html( last );
						}
						tips( res.data.message );
						teamshow();
					}, 'json');
					return false;
				}
			});

		}
		if( rechargeshow ) {
			$('#jq-total').find('span').text( data_json.record.data.can );

	    	/*if( parseInt( $('#jq-end').val() ) == 0 ){
				$('#jq-total').find('a').on({
		    		click:function(){
		    			tips('竞猜时间已结束');
		    		}
		    	});
		    	return false;
		    }*/

	    	if( typeof test == 'object' || typeof hello == 'function' ) {
	    		$('#jq-total').find('a').on({
		    		click:function(){
		    			test.hello();
		    			return false;
		    		}
		    	});
		    	return false;
	    	}
	    	linkedme.init("163284dee1b7af618c361b8df20016ba", {type: "test"}, null);
		    var data = {};
		    data.type = "test";
		    data.params = '{"action_type": 1, "bind_id": 99998 }';
		    linkedme.link(data, function(err, response){
		        if(err){
		          tips( '打开出错！' );
		        } else {
		          $('#jq-total').find('a').addClass('linkedme').attr('href',response.url);
		        }
		    },false);
		}
	}else{
		//app执行
		if( typeof test == 'object' || typeof hello == 'function' ) {
			$('#jq-total').find('a').attr('href','javascript:void(0);').on({
	    		click:function(){
	    			test.hello();
	    			return false;
	    		}
	    	});
		}
	}

})(Zepto);