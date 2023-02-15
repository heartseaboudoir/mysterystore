var Confirm = Vue.extend({
	data: function () {
		return {
			msg: '',
			display: true
		}
	},
	template: '<div v-if="display" class="weui_dialog_confirm">\
                <div class="weui_mask"></div>\
                <div class="weui_dialog">\
                    <div class="weui_dialog_bd weui_dialog_alert">\
                        <span class="icon-info">{{msg}}</span>\
                    </div>\
                    <div class="weui_dialog_ft">\
                        <a href="javascript:;" @click.prevent="_handleClose" class="weui_btn_dialog default">取消</a>\
                        <a href="javascript:;" @click.prevent="_handleConfirm" class="weui_btn_dialog primary">确定</a>\
                    </div>\
                </div>\
            </div>',
	methods: {
		_handleClose: function (evt) {
			this.display = (this.close && !!this.close()) || false
		},
		_handleConfirm: function (evt) {
			this.display = (this.confirm && !!this.confirm()) || false
		},
	}
})

Vue.component('Confirm', Confirm)

// thanks: http://stackoverflow.com/questions/6750880/javascript-how-does-new-work-internally
function NEW(f) {
	var obj, ret, proto;

	// Check if `f.prototype` is an object, not a primitive
	proto = Object(f.prototype) === f.prototype ? f.prototype : Object.prototype;

	// Create an object that inherits from `proto`
	obj = Object.create(proto);

	// Apply the function setting `obj` as the `this` value
	ret = f.apply(obj, Array.prototype.slice.call(arguments, 1));

	if (Object(ret) === ret) { // the result is an object?
		return ret;
	}
	return obj;
}

function ComponentFactory(fn) {
	return function (options, selector) {
		var methods = {}
		var data = {}
		var keys = Object.keys(options)
		keys.forEach(function (key) {
			if (typeof options[key] === 'function') {
				methods[key] = options[key]
			} else {
				data[key] = options[key]
			}
		})

		return NEW(fn, {
			data: data,
			methods: methods
		}).$mount().$appendTo(selector)
	}
}

window.ConfirmFactory = ComponentFactory(Confirm)