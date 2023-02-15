function htmlSize(){
	var oHtml = document.documentElement;
	var aWidth = oHtml.getBoundingClientRect().width;
	if(aWidth < 750){
		oHtml.style.fontSize = aWidth / 15 + "px";
	}else{
		oHtml.style.fontSize = 750 / 15 + "px";
	}	
}
(function(){
	htmlSize();
})();
window.onresize = function(){
	htmlSize();
}

function tips(msg){
    var tips = $('<div class="site-tips"><p>' + msg + '</p></div>').appendTo( document.body );
    var timer = setTimeout(function(){
        tips.remove();
    }, 1500);
}
var lazyload = {  
    //初始化函数  
    init : function(){  
        //判断需要根据哪个容器来懒加载图片，如果没有参数，则是根据document  
        var _ele = document.querySelector(arguments[0]) || document;  
        //获取容器里面所有的图片  
        var _img = _ele.querySelectorAll('.wrap-img img'),_this = this;  
        //初始执行一次  \
        this.set(_img);  
        //给window绑定scroll事件  
        this.addEvent(window,'scroll',function(){  
            _this.set(_img)  
        });  
        //给window绑定resize事件  
        this.addEvent(window,'resize',function(){  
            _this.set(_img)  
        });  
    },  
    //设置图片  
    set : function(_img){  
        //获取可视区的高度  
        var _h = document.documentElement.clientHeight;  
        //然后循环img  
        for (var i=0;i<_img.length;i++) {  
            //判断是否出现
            if(!_img[i].off && (_img[i].getBoundingClientRect().top) <  _h ){  
                 //设置src  
                _img[i].src = _img[i].getAttribute('data-src');  
                //删除自定义_src属性  
                _img[i].removeAttribute('data-src');  
                //设置一个开关，防止每次滚动都重新修改src  
                _img[i].off = true;  
            }  
        };  
    },  
    //绑定事件  
    addEvent : function (obj,types,fn){  
        obj.attachEvent ? obj.attachEvent('on'+types,fn) : obj.addEventListener(types,fn,false)  
    }  
}