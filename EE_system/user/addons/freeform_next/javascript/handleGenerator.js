"use strict";$(function(){var e=$("input[data-generator-base]"),t=$("input[data-generator-target]"),a=function(e){return e.replace(/(?:^\w|[A-Z]|\b\w)/g,function(e,t){return e.toLowerCase()}).replace(/\s+/g,"_").replace(/[^a-zA-Z0-9_]/g,"")};e.on({keyup:function(e){var n=a($(e.target).val());t.val(n)},change:function(){$(void 0).trigger("keyup")}}),t.on({keyup:function(e){var t=e.target,n=$(e.target),r=n.val().length,c=t.selectionStart,g=a(n.val());if(n.val(g),t.createTextRange){var o=t.createTextRange();o.move("character",c),o.select(),console.log("range")}else{var l=r-g.length;t.setSelectionRange(c-l,c-l)}}})});