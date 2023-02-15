function GetQueryString(name)
{
     var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)");
     var r = window.location.search.substr(1).match(reg);
     if(r!=null)return  unescape(r[2]); return null;
}
var test = GetQueryString("_test");
var debug_in = false;
if(test == 1){
	debug_in = true;
}
wx.config({
    debug: debug_in, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。

    appId: wx_appId, // 必填，公众号的唯一标识

    timestamp: wx_timestamp, // 必填，生成签名的时间戳

    nonceStr: wx_nonceStr, // 必填，生成签名的随机串

    signature: wx_signature,// 必填，签名，见附录1

    jsApiList: ["checkJsApi", "onMenuShareAppMessage", "onMenuShareTimeline", "onMenuShareWeibo", "onMenuShareQQ", "onMenuShareQZone"] // 必填，需要使用的JS接口列表，所有JS接口列表见附录2

});
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
        wx.ready(function() {
            wx.onMenuShareAppMessage({
                title: d,
                desc: g,
                link: c,
                imgUrl: f,
                success: function() {
                },
                cancel: function() {
                }
            });
            wx.onMenuShareTimeline({
                title: d + g,
                link: c,
                imgUrl: f,
                success: function() {
                },
                cancel: function() {
                }
            });
            wx.onMenuShareWeibo({
                title: d,
                desc: g,
                link: c,
                imgUrl: f,
                success: function() {
                },
                cancel: function() {
                }
            });
            wx.onMenuShareQQ({
                title: d,
                desc: g,
                link: c,
                imgUrl: f,
                success: function() {
                },
                cancel: function() {
                }
            });
            wx.onMenuShareQZone({
                title: d,
                desc: g,
                link: c,
                imgUrl: f,
                success: function() {
                },
                cancel: function() {
                }
            });
        });
    };
    a();
};
shareFun();