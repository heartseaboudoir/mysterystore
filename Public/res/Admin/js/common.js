//dom加载完成后执行的js
;$(function(){

	//全选的实现
	$(".check-all").click(function(){
		$(".ids").prop("checked", this.checked);
	});
	$(".ids").click(function(){
		var option = $(".ids");
		option.each(function(i){
			if(!this.checked){
				$(".check-all").prop("checked", false);
				return false;
			}else{
				$(".check-all").prop("checked", true);
			}
		});
	});
	
	$('.data-table tbody tr').click(function(e){
		var e = e || window.event; //浏览器兼容性
		var elem = e.target || e.srcElement;
		var c = true;
		while (elem) {
		  if(elem.tagName == "A" || $(elem).prop('type') == "checkbox") {
				c = false;
				return;
		  }
		  elem = elem.parentNode;
		}
		if(c == true){
			$(this).find('input[type="checkbox"]').click();
		}
	});
	$('button').attr('disabled', false);
	$('button.disabled').attr('disabled', true);
    //ajax get请求
    $('.ajax-get').click(function(){
        var target;
        var that = this;
        if ( $(this).hasClass('confirm') ) {
            if(!confirm('确认要执行该操作吗?')){
                return false;
            }
        }
        if ( (target = $(this).attr('href')) || (target = $(this).attr('url')) ) {
            $.get(target).success(function(data){
                if (data.status==1) {
                    if (data.url) {
                        updateAlert(data.info + ' 页面即将自动跳转~','alert-success');
                    }else{
                        updateAlert(data.info,'alert-success');
                    }
                    setTimeout(function(){
                        if (data.url) {
                            location.href=data.url;
                        }else if( $(that).hasClass('no-refresh')){
                            $('#top-alert').find('button').click();
                        }else{
                            location.reload();
                        }
                    },1500);
                }else{
                    updateAlert(data.info);
                    setTimeout(function(){
                        if (data.url) {
                            location.href=data.url;
                        }else{
                            $('#top-alert').find('button').click();
                        }
                    },1500);
                }
            });

        }
        return false;
    });

    //ajax post submit请求
    $('.ajax-post').click(function(){
        var target,query,form;
        var target_form = $(this).attr('target-form');
        var that = this;
        var nead_confirm=false;
        if( ($(this).attr('type')=='submit') || (target = $(this).attr('href')) || (target = $(this).attr('url')) ){
            form = $('.'+target_form);

            if ($(this).attr('hide-data') === 'true'){//无数据时也可以使用的功能
            	form = $('.hide-data');
            	query = form.serialize();
            }else if (form.get(0)==undefined){
            	return false;
            }else if ( form.get(0).nodeName=='FORM' ){
                if ( $(this).hasClass('confirm') ) {
                    if(!confirm('确认要执行该操作吗?')){
                        return false;
                    }
                }
                if($(this).attr('url') !== undefined){
                	target = $(this).attr('url');
                }else{
                	target = form.get(0).action;
                }
                query = form.serialize();
            }else if( form.get(0).nodeName=='INPUT' || form.get(0).nodeName=='SELECT' || form.get(0).nodeName=='TEXTAREA') {
                form.each(function(k,v){
                    if(v.type=='checkbox' && v.checked==true){
                        nead_confirm = true;
                    }
                })
                if ( nead_confirm && $(this).hasClass('confirm') ) {
                    if(!confirm('确认要执行该操作吗?')){
                        return false;
                    }
                }
                query = form.serialize();
            }else{
                if ( $(this).hasClass('confirm') ) {
                    if(!confirm('确认要执行该操作吗?')){
                        return false;
                    }
                }
                query = form.find('input,select,textarea').serialize();
            }
            $(that).addClass('disabled').attr('autocomplete','off').prop('disabled',true);
            $.post(target,query).success(function(data){
                if (data.status==1) {
                    if (data.url) {
                        updateAlert(data.info + ' 页面即将自动跳转~','alert-success');
                    }else{
                        updateAlert(data.info ,'alert-success');
                    }
                    setTimeout(function(){
                        if (data.url) {
                            location.href=data.url;
                        }else if( $(that).hasClass('no-refresh')){
                            $('#top-alert').find('button').click();
                            $(that).removeClass('disabled').prop('disabled',false);
                        }else{
                            location.reload();
                        }
                    },1500);
                }else{
                    updateAlert(data.info);
                    setTimeout(function(){
                        if (data.url) {
                            location.href=data.url;
                        }else{
                            $('#top-alert').find('button').click();
                            $(that).removeClass('disabled').prop('disabled',false);
                        }
                    },1500);
                }
            });
        }
        return false;
    });

	/**顶部警告栏*/
	var content = $('#main');
	var top_alert = $('#top-alert');
	top_alert.find('.close').on('click', function () {
		top_alert.removeClass('block').slideUp(200);
		// content.animate({paddingTop:'-=55'},200);
	});

    window.updateAlert = function (text,c) {
		text = text||'default';
		c = c||false;
		if ( text!='default' ) {
            top_alert.find('.alert-content').text(text);
			if (top_alert.hasClass('block')) {
			} else {
				top_alert.addClass('block').slideDown(200);
				// content.animate({paddingTop:'+=55'},200);
			}
		} else {
			if (top_alert.hasClass('block')) {
				top_alert.removeClass('block').slideUp(200);
				// content.animate({paddingTop:'-=55'},200);
			}
		}
		if ( c!=false ) {
            top_alert.removeClass('alert-error alert-warn alert-info alert-success').addClass(c);
		}
	};

    //按钮组
    (function(){
        //按钮组(鼠标悬浮显示)
        $(".btn-group").mouseenter(function(){
            var userMenu = $(this).children(".dropdown ");
            var icon = $(this).find(".btn i");
            icon.addClass("btn-arrowup").removeClass("btn-arrowdown");
            userMenu.show();
            clearTimeout(userMenu.data("timeout"));
        }).mouseleave(function(){
            var userMenu = $(this).children(".dropdown");
            var icon = $(this).find(".btn i");
            icon.removeClass("btn-arrowup").addClass("btn-arrowdown");
            userMenu.data("timeout") && clearTimeout(userMenu.data("timeout"));
            userMenu.data("timeout", setTimeout(function(){userMenu.hide()}, 100));
        });

        //按钮组(鼠标点击显示)
        // $(".btn-group-click .btn").click(function(){
        //     var userMenu = $(this).next(".dropdown ");
        //     var icon = $(this).find("i");
        //     icon.toggleClass("btn-arrowup");
        //     userMenu.toggleClass("block");
        // });
        $(".btn-group-click .btn").click(function(e){
            if ($(this).next(".dropdown").is(":hidden")) {
                $(this).next(".dropdown").show();
                $(this).find("i").addClass("btn-arrowup");
                e.stopPropagation();
            }else{
                $(this).find("i").removeClass("btn-arrowup");
            }
        })
        $(".dropdown").click(function(e) {
            e.stopPropagation();
        });
        $(document).click(function() {
            $(".dropdown").hide();
            $(".btn-group-click .btn").find("i").removeClass("btn-arrowup");
        });
    })();

    // 独立域表单获取焦点样式
    $(".text").focus(function(){
        $(this).addClass("focus");
    }).blur(function(){
        $(this).removeClass('focus');
    });
    $("textarea").focus(function(){
        $(this).closest(".textarea").addClass("focus");
    }).blur(function(){
        $(this).closest(".textarea").removeClass("focus");
    });
});

/* 上传图片预览弹出层 */
$(function(){
    $(window).resize(function(){
        var winW = $(window).width();
        var winH = $(window).height();
        $(".upload-img-box").click(function(){
        	//如果没有图片则不显示
        	if($(this).find('img').attr('src') === undefined){
        		return false;
        	}
            // 创建弹出框以及获取弹出图片
            var imgPopup = "<div id=\"uploadPop\" class=\"upload-img-popup\"></div>"
            var imgItem = $(this).find(".upload-pre-item").html();

            //如果弹出层存在，则不能再弹出
            var popupLen = $(".upload-img-popup").length;
            if( popupLen < 1 ) {
                $(imgPopup).appendTo("body");
                $(".upload-img-popup").html(
                    imgItem + "<a class=\"close-pop\" href=\"javascript:;\" title=\"关闭\"></a>"
                );
            }

            // 弹出层定位
            var uploadImg = $("#uploadPop").find("img");
            uploadImg.attr('style', 'max-height:'+(winH-50)+'px;');
            var popW = uploadImg.width();
            var popH = uploadImg.height();
            var left = (winW -popW)/2;
            var top = (winH - popH)/2;
            if(top < 0){
                top = '20px';
            } 
            $(".upload-img-popup").css({
                "max-width" : winW * 0.9,
                "left": left,
                "top": top
            });
        });

        // 关闭弹出层
        $("body").on("click", "#uploadPop .close-pop", function(){
            $(this).parent().remove();
        });
    }).resize();

    // 缩放图片
    function resizeImg(node,isSmall){
        if(!isSmall){
            $(node).height($(node).height()*1.2);
        } else {
            $(node).height($(node).height()*0.8);
        }
    }
	// 鼠标移动至对象后显示tips，用法：class="show_tips" data-tips="文本内容"
	$('.show_tips').mousemove(function(){
		var content = $(this).data('tips');
		if(!content || $(this).next().hasClass('hover-tips')) {
			return;
		}
		var c = '<div class="hover-tips">'+content+'</div>';
		$(this).after(c);
		var $c = $(this).next('.hover-tips');
		$(this).mouseout(function(){
			$c.remove();
		});
		var _box_width = $(this).width();
		var _box_left = $(this).offset().left;
		var _width = $c.width();
		if(_width/2 > _box_width){
			$c.css('left', _box_left-_box_width);
		}
	});
})

//标签页切换(无下一步)
function showTab() {
    $(".tab-nav li").click(function(){
        var self = $(this), target = self.data("tab");
        self.addClass("current").siblings(".current").removeClass("current");
        window.location.hash = "#" + target.substr(3);
        $(".tab-pane.in").removeClass("in");
        $("." + target).addClass("in");
    }).filter("[data-tab=tab" + window.location.hash.substr(1) + "]").click();
}

//标签页切换(有下一步)
function nextTab() {
     $(".tab-nav li").click(function(){
        var self = $(this), target = self.data("tab");
        self.addClass("current").siblings(".current").removeClass("current");
        window.location.hash = "#" + target.substr(3);
        $(".tab-pane.in").removeClass("in");
        $("." + target).addClass("in");
        showBtn();
    }).filter("[data-tab=tab" + window.location.hash.substr(1) + "]").click();

    $("#submit-next").click(function(){
        $(".tab-nav li.current").next().click();
        showBtn();
    });
}

// 下一步按钮切换
function showBtn() {
    var lastTabItem = $(".tab-nav li:last");
    if( lastTabItem.hasClass("current") ) {
        $("#submit").removeClass("hidden");
        $("#submit-next").addClass("hidden");
    } else {
        $("#submit").addClass("hidden");
        $("#submit-next").removeClass("hidden");
    }
}

//导航高亮
function highlight_subnav(url){
    $('.side-sub-menu').find('a[href="'+url+'"]').closest('li').addClass('current');
}

function verifyShow(options){
    layer.msg( options.text, {
        time: 1500
    });
    if( options.default ){
        options.id.focus().end().find('.wrap-data').removeAttr('verify');
        return false;
    }
    options.id.attr('verify',true).focus();
}
function getExplor(){
    var explorer = window.navigator.userAgent;
        if ( explorer.indexOf("QQBrowser") >= 0 || explorer.indexOf("QQ") >= 0){ 
            return myexplorer="腾讯QQ";
        }else if( explorer.indexOf("Safari") >= 0 && explorer.indexOf("MetaSr") >= 0 ){ 
            return myexplorer="搜狗";
        }else if ( !!window.ActiveXObject || "ActiveXObject" in window){
            if (!window.XMLHttpRequest){ 
                return myexplorer="IE6";
            }else if ( window.XMLHttpRequest && !document.documentMode){
                return myexplorer="IE7";
            }else if ( !-[1,] && document.documentMode && !("msDoNotTrack" in window.navigator)){
                return myexplorer="IE8";
            }else{//IE9 10 11
                var hasStrictMode = (function(){
                    "use strict";
                    return this===undefined;
                }()); 
                if (hasStrictMode){
                    if (!!window.attachEvent){
                        return myexplorer="IE10";
                    }else{
                        return myexplorer="IE11";
                    }
                }else{
                    return myexplorer="IE9";
                }
            }
        }else{//非IE
            if ( explorer.indexOf("LBBROWSER") >= 0 ){
                return myexplorer="猎豹";
            }else if( explorer.indexOf("360ee") >= 0 ){
                return myexplorer="360极速浏览器";
            }else if( explorer.indexOf("360se") >=0 ){
                return myexplorer="360安全浏览器";
            }else if( explorer.indexOf("se")>=0 ){
                return myexplorer="搜狗浏览器";
            }else if( explorer.indexOf("aoyou")>=0 ){
                return myexplorer="遨游浏览器";
            }else if( explorer.indexOf("qqbrowser")>=0 ){
                return myexplorer="QQ浏览器";
            }else if( explorer.indexOf("baidu") >= 0 ){
                return myexplorer="百度浏览器";
            }else if( explorer.indexOf("Firefox")>=0 ){
                return myexplorer="火狐";
            }else if( explorer.indexOf("Maxthon")>=0 ){
                return myexplorer="遨游";
            }else if( explorer.indexOf("Chrome")>=0 ){ 
                return myexplorer="谷歌（或360伪装）";
            }else if( explorer.indexOf("Opera") >= 0 ){
                return myexplorer="欧朋";
            }else if ( explorer.indexOf("TheWorld") >= 0 ){
                return myexplorer="世界之窗";
            }else if( explorer.indexOf("Safari")>=0 ){
                return myexplorer = "Safari";
            }else{ 
                return myexplorer = "其他";
            }
        }
}
function productShow(id){
    $('#jq-list').find('tr').each(function () {
        var hId = $.trim($(this).find('td').eq(0).html());
        if (id == hId) {
            layer.msg('商品已存在,但可编辑', {
                time: 1500
            });
            if( $('[name="b_n_num"]').length > 0 ){
                $('[name="b_n_num"]').val( $(this).find('.temp-num').html() ).focus();
                $('[name="b_num"]').val( $(this).find('.temp-box').html() );
                $('[name="b_price"]').val( $(this).find('.temp-price').html() );
                $('[name="g_price"]').val( $(this).find('.temp-cost').html() );
            }else{
                $('[name="g_num"]').focus();
            }
            $('[name="g_num"]').val( $(this).find('.temp-amount').html() );
            $('#jq-form-add').find('[name="remark"],[name="remark_add"]').val( $(this).find('.temp-remark').attr('title') );
        }
    });
}
$.fn.extend({
    verifyForm: function(options){
        return this.find(':input').keyup(function(){
            if( $(this).attr('int') == '' ){
                if ( getExplor() == 'Safari' ) {
                    $(this).val().replace(/\D/gi, '');
                }else{
                    $(this).val( $(this).val().replace(/\D/gi, '') );
                }
            }
            if( $(this).attr('decimal') == '' ){
                if ( getExplor() == 'Safari' ) {
                    $(this).val().replace(/[^\d.]/g, '');
                }else{
                    $(this).val( $(this).val().replace(/[^\d.]/g, '') );
                }
            }
            if( $(this).attr('num') == '' ){
                if ( getExplor() == 'Safari' ) {
                    $(this).val().replace(/\D/gi, '');
                }else{
                    $(this).val( $(this).val().replace(/\D/gi, '') );
                }
            }
            var self = $(this);
            var parents = self.parent().parent();

            var b_n_num = parents.find('[name="b_n_num"]');
            var b_num = parents.find('[name="b_num"]');
            var b_price = parents.find('[name="b_price"]');
            var g_num = parents.find('[name="g_num"]');
            var g_price = parents.find('[name="g_price"]');

            if( self.context.name == 'b_n_num' ){
                var num = self.val() * b_num.val();
                g_num.val( num == 0 ? '' : num );
                var price = b_price.val() / self.val();
                priceCount( price );
            }
            if( self.context.name == 'b_num' ){
                var num = self.val() * b_n_num.val();
                g_num.val( num == 0 ? '' : num );
            }
            if( self.context.name == 'b_price' ){
                var price = self.val() / b_n_num.val();
                priceCount( price );
            }
            function priceCount( price ){
                if( isNaN( price ) || price == Infinity || price == 0 ){
                    g_price.val( '' );
                    return false;
                }
                g_price.val( price );
            }
        }).blur(function(){
            if( $(this).attr('decimal') == '' ){
                var vSelf = $(this).val(),
                    index = vSelf.indexOf('.'),
                      num = vSelf.replace(/\./g,''),
                      arr = num.split('');
                arr.splice(index,0,'.');
                $(this).val( index == -1 ? vSelf : parseFloat( arr.join('') ).toFixed(2) );
            }
            if( $(this).attr('num') == '' ){
                $(this).val( parseInt( $(this).val().replace(/\./g,'') ) );
            }
            if( $(this).attr('decimal') == '' || $(this).attr('num') == '' ){
                if( isNaN( $(this).val() ) ){
                    $(this).val('');
                }
            }
        });
    }
});
$('#jq-verify').verifyForm();