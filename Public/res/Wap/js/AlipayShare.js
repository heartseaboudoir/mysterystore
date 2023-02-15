if(navigator.userAgent.indexOf("AlipayClient")===-1){
    alert('请在支付宝钱包内运行');
}else{
	var shareFun = function(b) {
		var e = {
			shareTitle: "",
			shareImg: "",
			shareLink: "",
			shareDesc: "",
		};
		if(typeof shareTitle != 'undefined'){
			e.shareTitle = shareTitle;
		}
		if(typeof shareImg != 'undefined'){
			e.shareImg = shareImg;
		}
		if(typeof shareLink != 'undefined'){
			e.shareLink = shareLink;
		}
		if(typeof shareDesc != 'undefined'){
			e.shareDesc = shareDesc;
		}
		b = b || {};
		$.extend(e, b);
		var d = e.shareTitle;
		var f = e.shareImg;
		var c = e.shareLink;
		var g = e.shareDesc;
		var a = function() {
			if((Ali.alipayVersion).slice(0,3)>=8.1){
				Ali.share({
					//渠道名称。支持以下几种：Weibo/LaiwangContacts/LaiwangTimeline/Weixin/WeixinTimeLine/SMS/CopyLink
					'channels': [{
							name: 'ALPContact',   //支付宝联系人,9.0版本
							param: {   //请注意，支付宝联系人仅支持一下参数
							  contentType: 'url',    //必选参数,目前支持支持"text","image","url"格式
							  content:g,    //必选参数,分享描述
							  iconUrl:f,   //必选参数,缩略图url，发送前预览使用,
							  imageUrl:f, //图片url
							  url:c,   //必选参数，卡片跳转连接
							  title:d,    //必选参数,分享标题
							  memo:""   //透传参数,分享成功后，在联系人界面的通知提示。
							}
					},{
						  name: 'ALPTimeLine', //支付宝生活圈
						  param: {
							contentType: 'url',    //必选参数,目前只支持"url"格式
							title: d,   //标题
							url: c,  //url
							iconUrl:f //icon
						  }
					}, {
						name: 'Weixin', //微信
						param: {
							title: d,
							content: g,
							imageUrl: f,
							captureScreen: true,
							url: c
						}
					}, {
						name: 'WeixinTimeLine', //微信朋友圈
						param: {
							title: d,
							content: g,
							imageUrl: f,
							captureScreen: true,
							url: c
						}
					}]
				}, function(result) {
					if(result.errorCode){
						//没有成功分享的情况
						//errorCode=10，分享失败或取消
					}else{
						//成功分享的情况
					}
				});
			}else{
				Ali.alert({
					title: '亲',
					message: '请升级您的钱包到最新版',
					button: '确定'
				});
			}
		};
		a();
	};
}