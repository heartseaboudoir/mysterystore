;(function($){

	function countdown(num){
		var oSend = $('#jq-send');
		var timer = setInterval(function(){
			if( num == 0 ){
				oSend.html('发送验证码').removeAttr('verify');
				clearInterval(timer);
				return false;
			}
			num--;
			oSend.html( '剩余 ' + num + ' 秒' );
		},1000);
	}
	function verifyPhone(){
		var oPhone = $('#jq-phone'),
			vPhone = oPhone.val(),
			rPhone = /^0?(13|14|15|17|18|19)[0-9]{9}$/;
		if( vPhone == '' || ( vPhone != '' && !rPhone.test(vPhone) ) ){
			if( !$('.site-tips').length > 0 ){
				tips('请输入正确手机号码');
			}
			oPhone.focus().val('');
			return false;
		}
		return true;
	}

	$('#jq-send').on({
		click:function(){
			if( typeof $(this).attr('verify') != 'undefined' ){
				return false;
			}
			var self = $(this);
			if( verifyPhone() ){
				var data = {
					mobile: $('#jq-phone').val()
				}
				$.post('/Wap/WorldCup/build_code', data, function(res){
					if( res.code == 0 ) {
						self.attr('verify',true);
						countdown(60);
					} 
					tips( res.data.message );
				}, 'json');
			}
			return false;
		}
	});
	$('#jq-form').find('input').keyup(function(){
		if( $(this).attr('num') == '' ){
			$(this).val( $(this).val().replace(/\D/gi, '') );
		}
	});
	$('#jq-phone').blur(function(){
		verifyPhone();
	});

	var submitFlag = true;
    $('#jq-form').on({
    	submit: function() {
    		if( !submitFlag ) {
    			return false;
    		}
    		submitFlag = false;
    		var oPhone = $('#jq-phone'),
    		     oCode = $('#jq-code'),
    		    vPhone = oPhone.val(),
    		     vCode = oCode.val();
    		if( vPhone == '' ){
    			tips('请输入手机号码');
    			oPhone.focus();
    			submitFlag = true;
    			return false;
    		}
    		if( vCode == '' ){
    			tips('请输入验证码');
    			oCode.focus();
    			submitFlag = true;
    			return false;
    		}
    		var data = {
				mobile: vPhone,
				  code: vCode,
				  type: data_type,
				   ids: data_ids
			}
			$.post('/Wap/WorldCup/build', data, function(res){
				if( res.code == 0 ) {
					tips( '登陆成功' );
					window.location.href = '/Wap/WorldCup/wc.html';
				} else {
					tips( res.data.message );
					submitFlag = true;
				}
			}, 'json');
    		return false;
    	}
    });

})(Zepto);