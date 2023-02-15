layui.define(['jquery','layer','form'], function(exports) {
    var $ = layui.jquery,
    layer = layui.layer,
    form = layui.form;

    Date.prototype.format = function(format) {
       var date = {
              "M+": this.getMonth() + 1,
              "d+": this.getDate(),
              "h+": this.getHours(),
              "m+": this.getMinutes(),
              "s+": this.getSeconds(),
              "q+": Math.floor((this.getMonth() + 3) / 3),
              "S+": this.getMilliseconds()
       };
       if (/(y+)/i.test(format)) {
              format = format.replace(RegExp.$1, (this.getFullYear() + '').substr(4 - RegExp.$1.length));
       }
       for (var k in date) {
              if (new RegExp("(" + k + ")").test(format)) {
                     format = format.replace(RegExp.$1, RegExp.$1.length == 1
                            ? date[k] : ("00" + date[k]).substr(("" + date[k]).length));
              }
       }
       return format;
    }
    function loadDate(options){
        if( options != null ){
            $('[name="s_date"]').val(options.s_date);
            $('[name="e_date"]').val(options.e_date);
        }
    }
    function loadStatus(){
        $('#jq-loading').fadeOut();
        $('#jq-content').fadeIn();
    }
    $.extend({
        getData: function(options,callback){
            $.get( options.url, function(res) {
                if (res.code == 200) {
                    if( options.load ){
                        loadDate(res.content);
                    }
                    callback(res);
                    if( options.load ){
                        loadStatus();
                    }
                } else {
                    layer.msg(res.content, {
                        time: 1500
                    });
                }
            }, 'json');
        },
        postData: function(options,callback,errorback){
            $.post( options.url, options.data, function(res) {
                if (res.code == 200) {
                    if( options.load ){
                        loadDate(res.content);
                    }
                    callback(res);
                    if( options.load ){
                        loadStatus();
                    }
                } else {
                    layer.msg(res.content, {
                        time: 1500
                    });
                    if( typeof errorback == 'function' ){
                       errorback(); 
                    }
                }
            }, 'json');
        },
        dataJson:function(options){
            var passData = {
                p: 1, 
                pageSize: 15,
                s_date: '',
                e_date: ''
            }
            passData = $.extend( passData,options );
            var parameter = window.location.pathname.replace(/\//g,'.').split('.');
            $.each( parameter, function(i,items){
                switch(items){
                    case 'list_type':
                    passData.list_type = parameter[i+1];
                    break;
                    case 'p':
                    passData.p = parameter[i+1];
                    break;
                    case 's_date':
                    passData.s_date = parameter[i+1];
                    break;
                    case 'e_date':
                    passData.e_date = parameter[i+1];
                    break;
                    case 'supply_select':
                    passData.supply_select = decodeURIComponent( parameter[i+1] ).split(',').map(function(items){
                        return parseInt( items );
                    });
                    break;
                }
            });
            return passData;
        },
        parseJson: function(param,key){
            var paramStr = '';
            if(param instanceof String || param instanceof Number || param instanceof Boolean){
                paramStr += '&' + key + '=' + encodeURIComponent(param);
            }else{
                $.each( param,function(i){
                    var k = key == null? i : key + (param instanceof Array ? '['+i+']':'.'+i);
                    paramStr += '&' + $.parseJson(this,k);
                });
            }
            return paramStr.substr(1);
        },
        dateRange: function(day){
            var reg = /(\d+)(\/)/g,
                date;
            if( day == undefined ){
                date = new Date();
            }else{
                date = new Date( new Date - 0 + (86400000 * day) );
            }
            return date.toLocaleDateString().replace( reg,function($0,$1){
                if( $1.length == 1 ){
                    $1 = '0' + $1;
                }
                return $1 + '-';
            });
        }
    });
    $.fn.addBack = $.fn.addBack || $.fn.andSelf;
    $.fn.extend({
        fileDown: function(options){
            return $(this).off().one({
                click: function(){
                    var self = $(this);
                    if( options.post ){
                        $.post( options.url, options.data, function(res) {
                            if (res.code == 200) {
                                self.attr('href', res.content.filename.slice(1));
                                window.location.href = res.content.filename.slice(1);
                            } else {
                                layer.msg(res.content, {
                                    time: 1500
                                });
                            }
                        }, 'json');
                    }else{
                        $.get( options.url, function(res) {
                            if (res.code == 200) {
                                self.attr('href', res.content.slice(1));
                                window.location.href = res.content.slice(1);
                            } else {
                                layer.msg(res.content, {
                                    time: 1500
                                });
                            }
                        }, 'json');
                    }
                }
            });
        },
        tempConfirm: function(options,databack,callback){
            return $(this).on({
                click: function(){
                    if( options.verify ){
                        var oTable = $(this).parents('form');
                        if( oTable.find('[name="selectActive"]').filter(':checked').length == 0 ){
                            layer.msg( '请选择要生成的订单', {
                                time: 1500
                            });
                            return false;
                        }
                    }
                    var self = $(this),
                        data = typeof callback == 'undefined' ? {} : databack( self ),
                        test = '是否'+ self.html() +'吗?',
                        oOut = self.parents('tr').find('.temp-outnum'),
                        vOut = parseInt( oOut.html() );
                    if( oOut.length > 0 && vOut > 0 && self.context.className == 'temp-verify' ){
                        test = '存在退货数量 <span class="wrap-empty">'+ vOut +'</span> ' + test;
                    }
                    layer.confirm( test,{
                        btn: ['确定', '取消']
                    }, function(index) {
                        if( options.link ){
                            window.location.href = self.attr('href');
                            return false;
                        }
                        $.post( options.url, data, function(res) {
                            if( res.content.msg != undefined ){
                                layer.msg( res.content.msg, {
                                    time: 1500
                                });
                                return false;
                            };
                            layer.msg( res.content, {
                                time: 1500
                            });
                            if( res.code == 200 ){
                                typeof callback == 'undefined' ? databack(data) : callback(data);
                            }
                        }, 'json');
                        layer.close(index);
                    });
                    return false;
                }
            });
        },
        enterLeave: function(options,callback){
            return this.blur(function(){
                var self = $(this),
                   vSelf = $.trim( self.val() ),
                    name = self.context.name,
                    data = options.data || {};
                    data[name] = vSelf;
                if( vSelf != '' ){
                    $.postData({
                         url: options.url,
                        data: data
                    }, function(res){
                        $('[name="goods_id"]').val( res.content.goods_id );
                        $('[name="goods_name"]').val( res.content.goods_name );
                        $('[name="g_price"]').val( res.content.g_price );
                        productShow( res.content.goods_id );
                        if(data.temp_type != 6 && data.temp_type != 8){
                        	 $('#jq-select-value').parents('label').remove();
                             if( res.content.attr_value_array.length > 0 ){
                               $('#jq-select-options').find('label').eq(2).after( template('temp-select-value', { items: res.content.attr_value_array }) );
                             }
                        }
                       
                    }, function(){
                        self.val('');
                    });
                }
            });
        },
        verifyForm: function(options){
            return this.find(':input').not('[name="endtime"]').keyup(function(){
                if( $(this).attr('decimal') == '' ){
                    if( getExplor() == 'Safari' ) {
                        $(this).val().replace(/[^\d.]/g, '');
                    }else{
                        $(this).val( $(this).val().replace(/[^\d.]/g, '') );
                    }
                }
                if( $(this).attr('num') == '' ){
                    if( getExplor() == 'Safari' ) {
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
                var g_num = parents.find('[name="g_num"],[name="g_num_show"]');
                var g_price = parents.find('[name="g_price"],[name="g_price_show"]');

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
                    g_price.val( price.toFixed(2) );
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
        },
        actual: function ( method, options ){
             // check if the jQuery method exist
             if( !this[ method ]){
               throw '$.actual => The jQuery method "' + method + '" you called does not exist';
             }

             var defaults = {
               absolute      : false,
               clone         : false,
               includeMargin : false
             };

             var configs = $.extend( defaults, options );

             var $target = this.eq( 0 );
             var fix, restore;

             if( configs.clone === true ){
               fix = function (){
                 var style = 'position: absolute !important; top: -1000 !important; ';

                 // this is useful with css3pie
                 $target = $target.
                   clone().
                   attr( 'style', style ).
                   appendTo( 'body' );
               };

               restore = function (){
                 // remove DOM element after getting the width
                 $target.remove();
               };
             }else{
               var tmp   = [];
               var style = '';
               var $hidden;

               fix = function (){
                 // get all hidden parents
                 $hidden = $target.parents().addBack().filter( ':hidden' );
                 style   += 'visibility: hidden !important; display: block !important; ';

                 if( configs.absolute === true ) style += 'position: absolute !important; ';

                 // save the origin style props
                 // set the hidden el css to be got the actual value later
                 $hidden.each( function (){
                   var $this = $( this );

                   // Save original style. If no style was set, attr() returns undefined
                   tmp.push( $this.attr( 'style' ));
                   $this.attr( 'style', style );
                 });
               };

               restore = function (){
                 // restore origin style values
                 $hidden.each( function ( i ){
                   var $this = $( this );
                   var _tmp  = tmp[ i ];

                   if( _tmp === undefined ){
                     $this.removeAttr( 'style' );
                   }else{
                     $this.attr( 'style', _tmp );
                   }
                 });
               };
             }

             fix();
             // get the actual value with user specific methed
             // it can be 'width', 'height', 'outerWidth', 'innerWidth'... etc
             // configs.includeMargin only works for 'outerWidth' and 'outerHeight'
             var actual = /(outer)/.test( method ) ?
               $target[ method ]( configs.includeMargin ) :
               $target[ method ]();

             restore();
             // IMPORTANT, this plugin only return the value of the first element
             return actual;
        }
    });

    if( $('#temp_type').length > 0 ){
        var urlType = window.location.pathname.match(/\/(\w+)\/_controller/)[1],
                url = '';
        switch(urlType){
            case 'StoreModule':
            url = '/Erp/StoreRequest/getgoods';
            break;
            case 'Warehouse':
            url = '/Erp/WarehouseInventory/getgoods';
            break;
            case 'Purchase':
            url = '/Erp/PurchaseReport/getgoods';
            break;
        }
        var data = {
            temp_type: $('#temp_type').val()
        }
        if( $('#cate_id').length > 0 ){
            data['cate_id'] = $('#cate_id').val();
        }
        $('[name="goods_id"],[name="bar_code"]').enterLeave({
            url: url,
            data: data
        });
    }

    exports('main', {});
});
/*return this.find(':input').keyup(function(){
    if( $(this).attr('decimal') == '' ){
        var reg = /^[0-9]+\.{0,1}([0-9]+)?$/g.test( $(this).val() );
        var index = $(this).val().length;
        for(var i=0;i<this.value.length;i++){
            if( !/^[0-9]+\.{0,1}([0-9]+)?$/g.test( this.value[i] ) ){
                index = i;
            }
        }
        if( !reg ){
            this.value = $(this).val().slice( 0,index );
        }
    }
    if( $(this).attr('num') == '' ){
        var reg = /^[0-9]+$/g.test( $(this).val() );
        var index = $(this).val().length-1;
        for(var i=0;i<this.value.length;i++){
            if( !/^[0-9]+$/g.test( this.value[i] ) ){
                index = i;
            }
        }
        if( !reg ){
            this.value = $(this).val().slice( 0,index );
        }
    }

    var self = $(this);
    var parents = self.parent().parent();

    var b_num = parents.find('[name="b_num"]');
    var g_num = parents.find('[name="g_num"]');
    var b_price = parents.find('[name="b_price"]');
    var b_n_num = parents.find('[name="b_n_num"]');
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
        g_price.val( price.toFixed(2) );
    }
}).blur(function(){
    if( $(this).attr('decimal') == '' ){
        $(this).val( parseFloat( $(this).val() ) );
    }
});*/
/*.blur(function(){
    var parents = $(this).parent().parent();
    if( $(this).attr('decimal') == '' ){
        var reg = /^[1-9]+.?([0-9]+)?$|0\.0*d*[1-9]+|0\.d*[1-9]+?$/.test( $(this).val() );
        $(this).val( reg ? $(this).val() : 1 );
        if( !reg ){
            var price = parents.find('[name="b_price"]').val() / parents.find('[name="b_n_num"]').val();
            parents.find('[name="g_price"]').val( price.toFixed(2) );
        }
    }
});*/