layui.define(['jquery','layer','form','element','laypage'], function(exports) {
        var $ = layui.jquery,
        layer = layui.layer,
         form = layui.form,
      element = layui.element,
      laypage = layui.laypage;

    $.fn.fixedScroll = function(options){
        var init = {};
        $.extend(init, options);
        var oTable = this,
               sum = 0;
        oTable.find('col').each(function(i,data){
              sum += parseInt( $(this).prop('width') );      
        });
        autoTable();
        function autoTable(){
            if( $(window).width() < 1600 ){
                oTable.css('width',sum);
            }else{
                oTable.css('width','100%');
            }
        }
        $(window).resize(function() {
            autoTable();
        });
    }

    $.fn.eventCheck = function(options){
        var init = {
              all : 'checkbox(allChoose)',
           single : 'checkbox(choose)',
             oBtn : '.wrap-btn-disabled',
             cBtn : 'layui-btn-disabled'
        };
        $.extend(init, options);
        var that = this;
        form.on( init.all, function(data){
            var child = $(data.elem).parents('table').find('tbody input[type="checkbox"]');
            child.each(function(index, item){
                item.checked = data.elem.checked;
            });
            !data.elem.checked ? $(init.oBtn).addClass(init.cBtn) : $(init.oBtn).removeClass(init.cBtn);
            form.render('checkbox');
        });
        form.on( init.single,function(data){
            var oAll = that.find('thead input[type="checkbox"]'),
                cAll = 'layui-form-checked';
            if( !data.elem.checked ){
                oAll.prop('checked',data.elem.checked).next().removeClass(cAll);
            }
            var arr = [],
              child = $(data.elem).parents('table').find('tbody input[type="checkbox"]');
            child.each(function(index, item){
                if( item.checked ){
                    arr.push( item.checked );
                }
            });
            arr == '' ? $(init.oBtn).addClass(init.cBtn) : $(init.oBtn).removeClass(init.cBtn);
            if( arr.length == child.length ){
                oAll.prop('checked',true).next().addClass(cAll);
            }
        })
    }

    $.fn.jumpUrl = function(){
        this.on({
            click:function(){
                var aUrl =  $(this).attr('url');
                window.location.href = aUrl;
            }
        });
    }

    $.fn.eventClick = function(options){
        var init = {
            oBox : 'tbody input[type="checkbox"]',
             oId : '.jq-id'
        };
        $.extend(init,options);
        this.on({
            click:function(){
                var aItem = $(this).attr('item'),
                     aUrl = $(this).attr('url'),
                    tText = $(this).text(),
                      pId = 0;
                if( aItem != undefined ){
                    var arr = [];
                    $(init.oBox).each(function(){
                        var tId = $(this).parents('tr').find(init.oId).text();
                        if( this.checked ){
                            arr.push( tId );
                        };
                    });
                    if( arr == '' ){
                        return false;
                    }
                    pId = arr.join(',');
                }else{
                    pId = $(this).parents('tr').find(init.oId).text();
                }
                var id = '<span class="wrap-error">'+ pId +'</span>';
                layer.confirm('您确定要'+tText+'ID："'+id+'" 列表行内容?',{
                    title:'提示'
                },function(index){
                    layer.close(index);
                    $.post( aUrl,{'id[]':pId},function(res){
                        if( res.status ){
                            layer.msg( '正在'+tText+'中...',{
                                time : 1500
                            },function(){
                                window.location.reload();
                            });
                        }else{
                            layer.msg( res.info, {
                                time : 1500
                            });
                        }
                    });
                });
            }
        })
    }
    
    $.fn.pageClick = function(options){
        var init = {
            cont : 'pageNav',
           pages : 20,
            curr : 1,
          groups : 5,
            skin : '#f86442',
           first : '首页',
            last : '末页',
            skip : true,
            jump : function(obj,first){
                if(!first){
                    window.location.href = init.url+'?page='+obj.curr;
                }
            }
        };
        $.extend(init,options);
        laypage({
            cont : init.cont,
           pages : init.pages,
            curr : init.curr, 
          groups : init.groups,
            skin : init.skin,
           first : init.first,
            last : init.last,
            skip : init.skip,
            jump : init.jump
        });
    }

    exports('layTable', {});
});