<?php

function add_google_analytics() {
	?>
	<!-- Google tag (gtag.js) -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-02BCJXDNFN"></script>
	<script>
		window.dataLayer = window.dataLayer || [];

		function gtag() {
			dataLayer.push(arguments);
		}

		gtag('js', new Date());

		gtag('config', 'G-02BCJXDNFN');
	</script>
	<!-- /Google tag (gtag.js) -->
	<?php
}

function add_yandex_metrica() {
	?>
	<!-- Yandex.Metrika counter -->
	<script type="text/javascript">
		(function (m, e, t, r, i, k, a) {
			m[i] = m[i] || function () {
				(m[i].a = m[i].a || []).push(arguments)
			};
			m[i].l = 1 * new Date();
			for (var j = 0; j < document.scripts.length; j++) {
				if (document.scripts[j].src === r) {
					return;
				}
			}
			k = e.createElement(t), a = e.getElementsByTagName(t)[0], k.async = 1, k.src = r, a.parentNode.insertBefore(k, a)
		})(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js?id=109545373', 'ym');

		ym(109545373, 'init', {
			ssr: true,
			webvisor: true,
			clickmap: true,
			ecommerce: "dataLayer",
			referrer: document.referrer,
			url: location.href,
			accurateTrackBounce: true,
			trackLinks: true
		});
	</script>
	<noscript>
		<div><img src="https://mc.yandex.ru/watch/109545373" style="position:absolute; left:-9999px;" alt=""/></div>
	</noscript>
	<!-- /Yandex.Metrika counter -->
	<?php
}

function add_open_ai() {
	?>
	<!-- OpenAI Ads Measurement Pixel -->
	<script>!function (w, d, s, u) {
			if (w.oaiq) return;
			var q = function () {
				q.q.push(arguments)
			};
			q.q = [];
			w.oaiq = q;
			var j = d.createElement(s);
			j.async = 1;
			j.src = u;
			var f = d.getElementsByTagName(s)[0];
			f.parentNode.insertBefore(j, f)
		}(window, document, "script", "https://bzrcdn.openai.com/sdk/oaiq.min.js");
		oaiq("init", {pixelId: "S5kngeRdjHu1xorPnPSoYZ", debug: true});</script>
	<!-- /OpenAI Ads Measurement Pixel -->
	<?php
}

if ( defined( 'WP_ENVIRONMENT_TYPE' ) && 'production' === WP_ENVIRONMENT_TYPE ) {
	add_action( 'wp_head', 'add_google_analytics' );
	add_action( 'wp_head', 'add_yandex_metrica' );
	add_action( 'wp_head', 'add_open_ai' );
}