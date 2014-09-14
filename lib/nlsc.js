;(function($,excludePattern,includePattern,mergeIfXhr,resMap2Request) {

var ieVer = navigator.userAgent.match(/MSIE (\d+\.\d+);/);ieVer = ieVer && ieVer[1] ? Number(ieVer) : null;
var cont = (ieVer && ieVer<7.1) ? document.createElement("div") : null;

if (!$.nlsc)
	$.nlsc={resMap:{}};

$.nlsc.normUrl=function(url) {
	if (!url) return null;
	if (cont) {
		cont.innerHTML = "<a href=\""+url+"\"></a>";
		url = cont.firstChild.href;
	}
	if (excludePattern && url.match(excludePattern))
		return null;
	if (includePattern && !url.match(includePattern))
		return null;
	return url.replace(/\?*&*(_=\d+)?&*$/g,"");
};
$.nlsc.h=function(s) {
	var h = 0, i;
	for (i = 0; i < s.length; i++) {
		h = (((h<<5)-h) + s.charCodeAt(i)) & 1073741823;
	}
	return ""+h;
};
$.nlsc.fetchMap=function() {
	//fetching scripts from the DOM
	for(var url,i=0,res=$(document).find("script[src]"); i<res.length; i++) {
		if (url = this.normUrl(res[i].src ? res[i].src : res[i].href))
			this.resMap[url] = {h:$.nlsc.h(url),d:1};//hash,loaded
	}//i
};
$.nlsc.smap=function() {
	var s="[";
	for(var url in this.resMap)
		s += "\""+this.resMap[url].h+"\",";
	return s.replace(/,$/,"")+"]";
};
var c = {
	global:true,
	beforeSend: function(xhr, opt) {

		if (!$.nlsc.fetched) {
			$.nlsc.fetched=1;
			$.nlsc.fetchMap();
		}//if

		if (opt.dataType!="script") {
			//hack: letting the server know what is already in the dom...
			if (mergeIfXhr)
				opt.url = resMap2Request(opt.url);
			return true;
		}
		
		//normalize url + disable no-cache random param
		var url = opt.url = $.nlsc.normUrl(opt.url);
		if (!url) return true;

		if (opt.converters && (opt.converters["text script"])) {
			var saveConv = opt.converters["text script"];
			opt.converters["text script"] = function() {				
				if (!$.nlsc.resMap[url].d) {
					$.nlsc.resMap[url].d = 1;
					saveConv.apply(window, arguments);
				}
			};
		}

		var r = $.nlsc.resMap[url];
		if (r) {
			if (r.d)
				return false;
		} else {
			$.nlsc.resMap[url] = {h:$.nlsc.h(url),d:0};
		}

		return true;
	}//beforeSend
};//c

//removing "defer" attribute from IE scripts anyway

if (ieVer)
	c.dataFilter = function(data,type) {
		if (type && type != "html" && type != "text")
			return data;
		return data.replace(/(<script[^>]+)defer(=[^\s>]*)?/ig, "$1");
	};

$.ajaxSetup(c);

})(jQuery,_excludePattern_,_includePattern_,_mergeIfXhr_,_resMap2Request_);