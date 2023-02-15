layui.define(['jquery','element'], function(exports) {
    var $ = layui.jquery,
    element = layui.element; 

    var url = window.location.pathname + window.location.search;
        url = url.replace(/(\/(p)\/\d+)|(&p=\d+)|(&id=\d+)|(\/(id|uid|pos_id|pid|model|aid|group|warehouse_id)\/\d+)|(&group=\d+)/, "");
        url = url.replace(/(\/(id|uid|pos_id|pid|model|aid|group|warehouse_id)\/\d+)|(\/(p_code)\/\w+)/, "");

        url = url.replace(/2\/pid\/0\/model_id\//, "");
        //url = url.replace(/view|save|edit|show|createGroup|save/,'index');
        url = url.replace(/edit|show|createGroup|save/,'index');
        url = url.replace(/\/add/,'\/index');
        url = url.replace(/article/,'Article');
        url = url.replace(/PositionDataAdmin/,'PositionAdmin');
        url = url.replace(/PosterDataAdmin/,'PosterAdmin');
        url = url.replace(/storesale\/type\/\d+/,'allsale');

        /*销售统计*/
        url = url.replace(/(\/(start_date)\/[0-9\-]+)|(\/(s_date)\/[0-9\-]+)/ , "");
        url = url.replace(/(\/(end_date)\/[0-9\-]+)|(\/(e_date)\/[0-9\-]+)/ , "");
        /*采购申请单处理*/
        if(url.indexOf('?') > -1 ){
            url = url.slice('1',url.indexOf('?'))
        }
        /*仓库管理*/
        //url = url.replace(/\/Warehouse\/_action|\/MemberAdmin\/_action/,'\/WarehouseList\/_action');
        
    $('#jq-nav-left').find("a[href$='" + url + "']").parent().addClass("current");   

    $('#jq-nav-top,#jq-nav-left').find('.current').addClass('layui-this');

    var isShow = true;
    autoTable();
    function autoTable(){
        //if( $(window).width() > 1600 ){
            //isShow = true;
            $('#jq-set-icon').find('i').html('&#xe602;').end().prev().show();
        //}else{
            //isShow = false;
            //$('#jq-set-icon').find('i').html('&#xe603;').end().prev().hide();
        //}
    }
    /*if (/(Android|iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)) {
        $('#jq-set-box').addClass('wrap-set-phone').removeClass('wrap-set');
    };*/
    $(window).resize(function() {
        autoTable();
    });
    $('#jq-set-icon').on({
        click:function(){
            if( isShow ){
                $(this).find('i').html('&#xe603;').end().prev().hide();
            }else{
                $(this).find('i').html('&#xe602;').end().prev().show();
            }
            isShow = !isShow;
        }
    }); 

    if( $('#jq-content').length > 0 ){
        $('#jq-loading').show();
    }
    if( $('.jq-confirm,#jq-clear,.jq-submit-auto').length > 0 ){
        layui.use(['main'], function() {
            var $ = layui.jquery,
            layer = layui.layer;

            var Global = {
                   addFlag: true,
                submitFlag: true
            }

            $('#jq-submit-verify').on({
                click: function() {
                    var oForm = $('#jq-form-add');
                    oForm.find('label').each(function(){
                        var self = $(this),
                           vData = self.find('.wrap-data').val(),
                            text = $.trim( self.context.innerText );

                        if( $('[name="b_n_num"]').length > 0 ){
                            var oNum = $('[name="b_n_num"]'),
                             oBoxNum = $('[name="b_num"]'),
                              oPrice = $('[name="b_price"]'),
                           oPriceAnd = $('[name="g_price_show"]'),
                                vNum = parseInt( oNum.val() ),
                             vBoxNum = parseInt( oBoxNum.val() ),
                              vPrice = parseFloat( oPrice.val() ),
                           vPriceAnd = parseFloat( oPriceAnd.val() ),
                                 reg = /^[1-9]+\.{0,1}([0-9]+)?$|0\.0*d*[1-9]+|0\.d*[1-9]+?$/,
                            regPrice = reg.test( vPrice ),
                         regPriceAnd = reg.test( vPriceAnd );

                            if( vNum <= 0 ){
                                verifyShow({
                                     text: '箱规须大于0',
                                       id: oNum,
                                  default: true
                                });
                                return false;
                            }
                            if( vBoxNum <= 0 ){ 
                                verifyShow({
                                    text: '采购箱数须大于0',
                                      id: oBoxNum,
                                 default: true
                                });
                                return false;
                            }
                            if( !regPrice && vPrice == '' ){ 
                                verifyShow({
                                    text: '每箱价格须大于0',
                                      id: oPrice,
                                 default: true
                                });
                                return false;
                            }
                            if( !regPriceAnd && vPriceAnd == '' ){ 
                                verifyShow({
                                    text: '采购价格须大于0',
                                      id: oNum,
                                 default: true
                                });
                                return false;
                            }
                        }else{
                            var oAmount = $('[name="g_num"]'),
                            vAmount = parseInt( oAmount.val() );
                            if( vAmount <= 0 ){
                                verifyShow({
                                     text: '申请数量须大于0',
                                       id: oAmount,
                                  default: true
                                });
                                return false;
                            }
                        }

                        if( vData == '' ){
                            layer.msg( '请输入' + text.slice( 0,text.length-1 ), {
                                time: 1500
                            });
                            self.focus().end().find('.wrap-data').removeAttr('verify');
                            return false;
                        }else{
                            self.find('.wrap-data').attr('verify',true);
                        }
                    });
                    var oData = oForm.find('.wrap-data');
                    if( oForm.find('[verify="true"]').length != oData.length || !Global.addFlag ){
                        return false;
                    }
                    if( oForm.find('[verify="true"]').length == oData.length && Global.addFlag ){

                        var data = $('#jq-form-add').attr('action'),
                             vId = $('#jq-select-options').attr('data-id');
                        if( vId != undefined ){
                            data += '?temp_id='+vId;
                            $('#jq-form-add').attr('action',data);
                        }

                        Global.addFlag = false;
                        layer.msg( '操作成功', {
                            time: 1500
                        });
                    }
                }
            });

            $('.jq-edit').on({
                click:function(){
                    var oTr = $(this).parents('tr');
                    if( $('[name="b_n_num"]').length > 0 ){
                        $('[name="b_n_num"]').val( oTr.find('.temp-num').html() ).focus();
                        $('[name="b_num"]').val( oTr.find('.temp-box').html() );
                        $('[name="b_price"]').val( oTr.find('.temp-price').html() );
                        $('[name="g_price"]').val( oTr.find('.temp-cost').html() );
                    }else{
                        $('[name="g_num"]').focus();
                    }
                    $('[name="goods_id"]').val( oTr.find('.temp-id').html() );
                    $('[name="goods_name"]').val( oTr.find('.temp-name').find('a').html() );
                    $('[name="g_num"]').val( oTr.find('.temp-amount').html() );
                    $('#jq-form-add').find('.remark_add').val( oTr.find('.temp-remark').attr('title') );
                    
                    if($(this).attr('id') == 'purchase'){
                    	 $.getData({
                             url: '/Erp/PurchaseReport/getSingleRequestTempInfo_purchase?id=' + $(this).attr('data-id')
                        }, function(res){
                            $('#jq-select-options').attr('data-id',res.content[0].id);
                            $('#jq-select-value').parents('label').remove();
                            if( res.content[0].attr_value_array.length > 0 ){
                               $('#jq-select-options').find('label').eq(2).after( template('temp-select-value', { items: res.content[0].attr_value_array }) );
                               $('#jq-select-value option[value="'+ res.content[0].value_id +'"]').attr('selected',true);
                            }
                        });
                    }else{
                    	 $.getData({
                             url: '/Erp/WarehouseAssignmentApplication/getSingleRequestTempInfo?id=' + $(this).attr('data-id')
                        }, function(res){
                            $('#jq-select-options').attr('data-id',res.content[0].id);
                            $('#jq-select-value').parents('label').remove();
                            if( res.content[0].attr_value_array.length > 0 ){
                               $('#jq-select-options').find('label').eq(2).after( template('temp-select-value', { items: res.content[0].attr_value_array }) );
                               $('#jq-select-value option[value="'+ res.content[0].value_id +'"]').attr('selected',true);
                            }
                        });
                    }
                   
                }
            });

            var listlen = $('#jq-list').find('tr').length;
            $('.jq-confirm').tempConfirm({
                link: true
            });
            if( !listlen > 0 ){
                $('#jq-clear').css('cursor','default').on({
                    click:function(){
                        return false;
                    }
                });
            }else{
                $('#jq-clear').removeAttr('style').tempConfirm({
                    link: true
                });
            }
            $('#jq-submit-btn').on({
                click:function(){
                    if( !listlen > 0 ){
                        layer.msg( '没有申请商品', {
                            time: 1500
                        });
                        return false;
                    }
                    if( $('[name="supply_id"]').length > 0 ){
                        if( $('[name="warehouse_id"]').val() == 0 && $('[name="store_id"]').val() == 0 ){
                            layer.msg( '请选择仓库或门店', {
                                time: 1500
                            });
                            return false;
                        }
                        if( $('[name="warehouse_id"]').val() != 0 && $('[name="store_id"]').val() != 0 ){
                            layer.msg( '不能同时选择仓库或门店', {
                                time: 1500
                            });
                            return false;
                        }
                        if( $('[name="supply_id"]').val() == 0 ){
                            layer.msg( '请选择供应商', {
                                time: 1500
                            });
                            return false;
                        }
                    }
                    if( $('#jq-select-ship').length > 0 ){
                        if( $('#jq-select-ship').val() == '' ){
                            var tip = $('#jq-select-ship option:first').text();
                            layer.msg( tip, {
                                time: 1500
                            });
                            return false;
                        }else{
                            var onOff = true;
                            $('#jq-list').find('tr').each(function(){
                                var id = $(this).find('.temp-id').html(),
                                 stock = $(this).find('.temp-stock span');
                                if( stock.length > 0 ){
                                    layer.msg( 'ID为'+id+'的商品发货库存不足，无法提交申请', {
                                        time: 1500
                                    });
                                    onOff = false;
                                    return false;
                                }
                            })
                            if( !onOff ){
                                return false;
                            }
                        }
                    }
                    if( !Global.submitFlag ){
                        return false;
                    }
                    if( Global.submitFlag ){
                        Global.submitFlag = false;
                    }
                }
            });

            $('[name="selectallgoods[]"]').on({
                click:function(){
                    $(this).parents('table').find('[name="selectprdid[]"]').prop('checked',this.checked);
                }
            });
            $('[name="selectprdid[]"]').on({
                click:function(){
                    var oForm = $(this).parents('table'),
                         oAll = oForm.find('[name="selectallgoods[]"]'),
                      oActive = oForm.find('[name="selectprdid[]"]');
                    oAll.prop('checked',oActive.length == oActive.filter(':checked').length);
                }
            });
            $('.jq-submit-auto').off().on({
                click:function(){
                    if( !Global.addFlag ){
                        return false;
                    }
                    if( Global.submitFlag ){
                        var self = $(this),
                            test = self.html(),
                          oTable = self.parents('table');
                        if( oTable.find('[name="selectprdid[]"]').filter(':checked').length == 0 ){
                            layer.msg( '请选择要生成的订单', {
                                time: 1500
                            });
                            return false;
                        }
                        if( oTable.find('[name="warehouse_id"]').length > 0 && oTable.find('[name="store_id"]').length > 0  ){
                            if( oTable.find('[name="warehouse_id"]').val() == 0 && oTable.find('[name="store_id"]').val() == 0 ){
                                layer.msg( '请选择仓库或门店', {
                                    time: 1500
                                });
                                return false;
                            }
                            if( oTable.find('[name="warehouse_id"]').val() != 0 && oTable.find('[name="store_id"]').val() != 0 ){
                                layer.msg( '不能同时选择仓库或门店', {
                                    time: 1500
                                });
                                return false;
                            }
                        }
                        if( oTable.find('[name="supply_id"]').length > 0 ){
                            if( oTable.find('[name="supply_id"]').val() == 0 ){
                                layer.msg( '请选择供应商', {
                                    time: 1500
                                });
                                return false;
                            }
                        }
                        layer.confirm( test,{
                            btn: ['确定', '取消']
                        }, function(index) {
                            Global.submitFlag = false;
                            self.click();
                            layer.close(index);
                        });
                        return false;
                    }else{
                        Global.addFlag = false;
                    }
                }
            });
            $('.jq-submit-refuse').off().on({
                click:function(){
                    if( !Global.addFlag ){
                        return false;
                    }
                    if( Global.submitFlag ){
                        var self = $(this),
                            test = self.html(),
                          oTable = self.parents('table');
                        if( oTable.find('[name="selectprdid[]"]').filter(':checked').length == 0 ){
                            layer.msg( '请选择要生成的订单', {
                                time: 1500
                            });
                            return false;
                        }
                        layer.confirm( test,{
                            btn: ['确定', '取消']
                        }, function(index) {
                            self.parents('form').attr("action",$('#jq-form-refuse').val());
                            Global.submitFlag = false;
                            self.click();
                            layer.close(index);
                        });
                        return false;
                    }else{
                        Global.addFlag = false;
                    }
                }
            });

        });
    }
    
    exports('base', {});
});
/*$(function() {
    $.each($('#subnav ul'), function(x, y) {
        if ($(y).find('.current').html() == undefined) {
            $(y).hide();
            $(y).prev('h3').find('i').addClass('icon-fold');
        } else {
            $(y).show();
            $(y).prev('h3').find('i').removeClass('icon-fold');
        }
    });
});
(function() {
    /*var $window = $(window),
        $subnav = $("#subnav"),
        url;
    $window.resize(function() {
        $("#main").css("min-height", $window.height() - 130);
    }).resize();*/

    /* 左边菜单高亮 */
    /*url = window.location.pathname + window.location.search;
    url = url.replace(/(\/(p)\/\d+)|(&p=\d+)|(\/(id)\/\d+)|(&id=\d+)|(\/(group)\/\d+)|(&group=\d+)/, "");
    $subnav.find("a[href$='" + url + "']").parent().addClass("current");*/

    /* 左边菜单显示收起 */
    /*$("#subnav").on("click", "h3", function() {
        var $this = $(this);
        $this.find(".icon").toggleClass("icon-fold");
        $this.next().slideToggle("fast").siblings(".side-sub-menu:visible").
        prev("h3").find("i").addClass("icon-fold").end().end().hide();
    });
    $("#subnav h3 a").click(function(e) { e.stopPropagation() });*/

    /* 头部管理员菜单 */
    /*$(".user-bar").mouseenter(function() {
        var userMenu = $(this).children(".user-menu ");
        userMenu.removeClass("hidden");
        clearTimeout(userMenu.data("timeout"));
    }).mouseleave(function() {
        var userMenu = $(this).children(".user-menu");
        userMenu.data("timeout") && clearTimeout(userMenu.data("timeout"));
        userMenu.data("timeout", setTimeout(function() { userMenu.addClass("hidden") }, 100));
    });*/

    // 导航栏超出窗口高度后的模拟滚动条
    /*var sHeight = $(".sidebar").height();
    var subHeight = $(".subnav").height();
    var diff = subHeight - sHeight; //250
    var sub = $(".subnav");
    if (diff > 0) {
        $(window).mousewheel(function(event, delta) {
            if (delta > 0) {
                if (parseInt(sub.css('marginTop')) > -10) {
                    sub.css('marginTop', '0px');
                } else {
                    sub.css('marginTop', '+=' + 10);
                }
            } else {
                if (parseInt(sub.css('marginTop')) < '-' + (diff - 10)) {
                    sub.css('marginTop', '-' + (diff - 10));
                } else {
                    sub.css('marginTop', '-=' + 10);
                }
            }
        });
    }
}());*/