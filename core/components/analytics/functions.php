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

/**
 * LinkedIn Insight Tag with the campaign conversions
 *
 * Printed in the footer so the queueing stub exists before any conversion fires.
 *
 * @return void
 */
function add_linkedin_insight_tag() {
	?>
	<!-- LinkedIn Insight Tag -->
	<script type="text/javascript">
		_linkedin_partner_id = "11010401";
		window._linkedin_data_partner_ids = window._linkedin_data_partner_ids || [];
		window._linkedin_data_partner_ids.push(_linkedin_partner_id);
	</script>
	<script type="text/javascript">
		(function (l) {
			if (!l) {
				window.lintrk = function (a, b) {
					window.lintrk.q.push([a, b])
				};
				window.lintrk.q = []
			}
			var s = document.getElementsByTagName("script")[0];
			var b = document.createElement("script");
			b.type = "text/javascript";
			b.async = true;
			b.src = "https://snap.licdn.com/li.lms-analytics/insight.min.js";
			s.parentNode.insertBefore(b, s);
		})(window.lintrk);
	</script>
	<noscript>
		<img height="1" width="1" style="display:none;" alt=""
			 src="https://px.ads.linkedin.com/collect/?pid=11010401&amp;fmt=gif"/>
	</noscript>
	<script type="text/javascript">
		document.addEventListener('click', function (event) {
			var target = event.target;
			if (!target || !target.closest || !target.closest('a[href*="wa.me"], a[href*="whatsapp.com"]')) {
				return;
			}

			window.lintrk('track', {conversion_id: 31618489});
		});

		document.addEventListener('request_a_callback', function () {
			window.lintrk('track', {conversion_id: 31618481});
		});
	</script>
	<!-- /LinkedIn Insight Tag -->
	<?php
}

if ( 'production' === wp_get_environment_type() ) {
	add_action( 'wp_head', 'add_google_analytics' );
	add_action( 'wp_head', 'add_yandex_metrica' );
	add_action( 'wp_head', 'add_open_ai' );
	add_action( 'wp_footer', 'add_linkedin_insight_tag' );
}
