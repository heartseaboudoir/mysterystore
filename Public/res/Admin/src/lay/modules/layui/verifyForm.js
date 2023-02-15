layui.define(['jquery','layer','form','element'], function(exports) {
        var $ = layui.jquery,
        layer = layui.layer,
         form = layui.form,
      element = layui.element;

    $.fn.verifyForm = function(options) {
        var oForm = this,
           oInput = oForm.find(':input'),
           tError = '<p class="wrap-verify">{{value}}</p>',
            onOff = true;

        form.on('select(user_id)', function(data) {
            if (data.value > 0) {
                $(this).parents('.layui-form-item').find('.wrap-verify').remove();
            }
        });
        form.on('checkbox(lives)', function(data) {
            if ($(this).prop('checked')) {
                oLives.parents('.layui-form-item').find('.wrap-verify').remove();
            }
            var arr = [];
            $.each(oLives, function(i, data) {
                if ($(this).prop('checked')) {
                    arr.push($(this).prop('checked'));
                }
            })
            if (arr == '') {
                onOff = true;
                $(this).parent('div').append(tError.replace('{{value}}', '请选择'));
            }
        });
        oInput.keyup(function() {
            var oParent = $(this).parent('div');
            oParent.find('.wrap-verify').remove();
            if( $(this).attr('num') == '' ){
                var that = $(this).val().replace(/\D/gi, '');
                $(this).val( that );
            }
            for(var key in options.fields){
                if(options.fields.hasOwnProperty(key)){
                    if ( $(this).is(key) ) {
                        var tVal = $.trim( $(this).val() );

                        if (tVal == '' && options.fields[key].notEmpty != undefined) { 
                            var error = options.fields[key].notEmpty.message;
                            oParent.append(tError.replace('{{value}}', error ));
                        } else if ( options.fields[key].identical != undefined ) {
                            var tField = $(options.fields[key].identical.field).val();
                            if( tVal != tField ){
                                var error = options.fields[key].identical.message;
                                oParent.append(tError.replace('{{value}}', error ));
                            }
                        } else if(options.fields[key].regexp != undefined){
                            var reg = options.fields[key].regexp.regexp;
                            if( tVal != '' && !reg.test(tVal) ){
                                var error = options.fields[key].regexp.message;
                                oParent.append(tError.replace('{{value}}', error ));
                            }
                        } else {
                            oParent.find('.wrap-verify').remove();
                        }
                        $(this).val(tVal);
                    }
                }
            }

        }).blur(function() {
            $(this).triggerHandler('keyup');
        });
        var init = {
            dataUrl: oForm.attr('action'),
               tip : '正在加载中...'
        };
        $.extend(init, options.getPost);
        $('#jq-submit').on({
            click: function() {
                if(!/(iPhone|iPad|iOS)/i.test(navigator.userAgent)){
                    oInput.trigger('blur');
                }
                var oError = oForm.find('.wrap-verify'),
                    aLen = oError.length;
                oError.eq(0).prev().focus();
                if (aLen > 0) {
                    return false;
                }
                /*if( window.localStorage && $('[name="input[remember]"]').length > 0 ){
                    if( $('[name="input[remember]"]').prop('checked') ){
                    localStorage.setItem("user", $('[name="input[login_name]"]').val());
                    localStorage.setItem("pwd", $('[name="input[login_password]"]').val());  
                    }else{
                    localStorage.removeItem("user");
                    localStorage.removeItem("pwd");  
                    }
                }*/
                //console.log( JSON.stringify( oForm.serializeJson() ) );
                $.post( init.dateUrl,oForm.serializeJson(), function(res){
                    if( res.status ){
                        layer.msg( init.tip,{
                            time : 1500
                        },function(){
                            window.location.href = res.url;
                        });
                    }else{   
                        layer.msg( res.info, {
                            time : 1500
                        });
                        $('#jq-captcha').click();
                    }
                });
                return false;
            }
        });
    }
    $.fn.serializeJson = function() {
        var init = {},
            array = this.serializeArray(),
            str = this.serialize();
        $(array).each(function() {
            if (init[this.name]) {
                if ($.isArray(init[this.name])) {
                    init[this.name].push(this.value);
                } else {
                    init[this.name] = [init[this.name], this.value];
                }
            } else {
                init[this.name] = this.value;
            }
        });
        return init;
    };

    exports('verifyForm', {});
});