(function(){
	'use strict';

	var state = new WeakSet();

	function getConfig(node){
		var mode = node.getAttribute('data-mode') || '';
		var path = node.getAttribute('data-path') || '/tour-search';
		var key = node.getAttribute('data-key') || '';
		return {node: node, mode: mode, path: path, key: key};
	}

	function ensureTravelySearch(){
		return typeof window.TravelySearch !== 'undefined';
	}

	function initWidget(cfg){
		if(state.has(cfg.node)){
			return;
		}
		if(!ensureTravelySearch()){
			return;
		}
		if(cfg.mode === 'results'){
			window.TravelySearch.initIframe(cfg.node.id, cfg.key, cfg.path);
		}else{
			var options = cfg.mode.split(',').filter(Boolean);
			window.TravelySearch.initSearch(cfg.node.id, cfg.path, options);
		}
		state.add(cfg.node);
	}

	function scan(){
		var nodes = document.querySelectorAll('[data-travely-widget="true"]');
		for(var i=0;i<nodes.length;i++){
			initWidget(getConfig(nodes[i]));
		}
	}

	function onReady(){
		scan();
		observeChanges();

		if(!ensureTravelySearch()){
			var retries = 10;
			var interval = setInterval(function(){
				if(ensureTravelySearch()){
					scan();
					clearInterval(interval);
				}else if(--retries <= 0){
					clearInterval(interval);
				}
			}, 300);
		}
	}

	function observeChanges(){
		if(typeof MutationObserver === 'undefined' || !document.body){
			return;
		}

		var observer = new MutationObserver(function(mutations){
			var shouldScan = false;

			for(var i = 0; i < mutations.length; i++){
				if(mutations[i].addedNodes && mutations[i].addedNodes.length){
					shouldScan = true;
					break;
				}
			}

			if(shouldScan){
				scan();
			}
		});

		observer.observe(document.body, {
			childList: true,
			subtree: true
		});
	}

	if(document.readyState === 'loading'){
		document.addEventListener('DOMContentLoaded', onReady);
	}else{
		onReady();
	}
})();

