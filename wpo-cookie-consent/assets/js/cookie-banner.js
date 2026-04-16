/**
 * WPO Cookie Consent – Frontend JS
 * Handles: banner display, user actions, Google Consent Mode v2 updates,
 * GTM dataLayer events, cookie storage, preference modal.
 */
( function () {
	'use strict';

	/* ------------------------------------------------------------------ */
	/*  Config from wp_localize_script                                     */
	/* ------------------------------------------------------------------ */
	var cfg = window.wpoCookieConsent || {};

	/* ------------------------------------------------------------------ */
	/*  Consent Mode v2 helper                                             */
	/* ------------------------------------------------------------------ */
	window.dataLayer = window.dataLayer || [];
	function gtag() { window.dataLayer.push( arguments ); }

	/**
	 * Send a Consent Mode v2 update to GTM/GA4.
	 * @param {Object} choices  { analytics, marketing, preferences }
	 */
	function updateConsentMode( choices ) {
		var analyticsState    = choices.analytics   === 'granted' ? 'granted' : 'denied';
		var marketingState    = choices.marketing   === 'granted' ? 'granted' : 'denied';
		var preferencesState  = choices.preferences === 'granted' ? 'granted' : 'denied';

		gtag( 'consent', 'update', {
			analytics_storage:       analyticsState,
			ad_storage:              marketingState,
			ad_user_data:            marketingState,
			ad_personalization:      marketingState,
			functionality_storage:   preferencesState,
			personalization_storage: preferencesState,
			security_storage:        'granted'
		} );

		// Push a GTM custom event so triggers can react.
		window.dataLayer.push( {
			event:               'wpo_cc_consent_update',
			wpo_cc_analytics:    analyticsState,
			wpo_cc_marketing:    marketingState,
			wpo_cc_preferences:  preferencesState
		} );
	}

	/* ------------------------------------------------------------------ */
	/*  Cookie helpers                                                     */
	/* ------------------------------------------------------------------ */
	function readCookie( name ) {
		var cookies = document.cookie.split( ';' );
		for ( var i = 0; i < cookies.length; i++ ) {
			var parts = cookies[ i ].trim().split( '=' );
			if ( parts[ 0 ] === name ) {
				return decodeURIComponent( parts.slice( 1 ).join( '=' ) );
			}
		}
		return null;
	}

	function writeCookie( name, value, days ) {
		var expires = new Date( Date.now() + days * 864e5 ).toUTCString();
		var secure  = location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = name + '=' + encodeURIComponent( value ) + '; expires=' + expires + '; path=/; SameSite=Lax' + secure;
	}

	/* ------------------------------------------------------------------ */
	/*  DOM references                                                     */
	/* ------------------------------------------------------------------ */
	var overlay       = document.getElementById( 'wpo-cc-overlay' );
	var banner        = document.getElementById( 'wpo-cc-banner' );
	var modal         = document.getElementById( 'wpo-cc-modal' );
	var btnAccept     = document.getElementById( 'wpo-cc-btn-accept' );
	var btnReject     = document.getElementById( 'wpo-cc-btn-reject' );
	var btnCustomize  = document.getElementById( 'wpo-cc-btn-customize' );
	var modalClose    = document.getElementById( 'wpo-cc-modal-close' );
	var modalReject   = document.getElementById( 'wpo-cc-modal-reject' );
	var modalSave     = document.getElementById( 'wpo-cc-modal-save' );
	var modalAccept   = document.getElementById( 'wpo-cc-modal-accept' );
	var chkAnalytics  = document.getElementById( 'wpo-cc-analytics' );
	var chkMarketing  = document.getElementById( 'wpo-cc-marketing' );
	var chkPreferences= document.getElementById( 'wpo-cc-preferences' );

	if ( ! overlay ) { return; } // template not rendered – bail.

	/* ------------------------------------------------------------------ */
	/*  AJAX save to server (also sets httponly=false cookie server-side)  */
	/* ------------------------------------------------------------------ */
	function serverSaveConsent( choices, callback ) {
		if ( ! cfg.ajaxUrl ) {
			if ( callback ) { callback(); }
			return;
		}
		var body = new URLSearchParams();
		body.set( 'action',      'wpo_save_consent' );
		body.set( 'nonce',       cfg.nonce || '' );
		body.set( 'analytics',   choices.analytics );
		body.set( 'marketing',   choices.marketing );
		body.set( 'preferences', choices.preferences );

		fetch( cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' } )
			.then( function () { if ( callback ) { callback(); } } )
			.catch( function () { if ( callback ) { callback(); } } );
	}

	/* ------------------------------------------------------------------ */
	/*  Core save routine                                                  */
	/* ------------------------------------------------------------------ */
	function saveConsent( choices ) {
		// 1. Update Consent Mode immediately (before GTM fires held tags).
		updateConsentMode( choices );

		// 2. Write JS-accessible cookie.
		var payload = JSON.stringify( Object.assign( {}, choices, {
			timestamp: Math.floor( Date.now() / 1000 ),
			version:   cfg.version || '1.0.0'
		} ) );
		writeCookie( cfg.cookieName || 'wpo_cookie_consent', payload, cfg.cookieDays || 365 );

		// 3. Persist via AJAX (server-side cookie).
		serverSaveConsent( choices, null );

		// 4. Hide banner with exit animation.
		hideBanner();
	}

	/* ------------------------------------------------------------------ */
	/*  Banner visibility helpers                                          */
	/* ------------------------------------------------------------------ */
	function showBanner() {
		overlay.removeAttribute( 'hidden' );
		document.body.style.paddingBottom = ( banner && banner.offsetHeight ) ? banner.offsetHeight + 'px' : '';
	}

	function hideBanner() {
		if ( banner ) {
			banner.classList.add( 'wpo-cc-hidden' );
		}
		var delay = prefersReducedMotion() ? 0 : 320;
		setTimeout( function () {
			overlay.setAttribute( 'hidden', '' );
			document.body.style.paddingBottom = '';
		}, delay );
		closeModal();
	}

	function prefersReducedMotion() {
		return window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	}

	/* ------------------------------------------------------------------ */
	/*  Modal helpers                                                      */
	/* ------------------------------------------------------------------ */
	function openModal() {
		modal.removeAttribute( 'hidden' );
		overlay.classList.add( 'wpo-cc-modal-open' );
		modal.focus();
		// Populate checkboxes with any saved values.
		var saved = cfg.savedConsent || {};
		if ( chkAnalytics )   { chkAnalytics.checked   = saved.analytics   === 'granted'; }
		if ( chkMarketing )   { chkMarketing.checked   = saved.marketing   === 'granted'; }
		if ( chkPreferences ) { chkPreferences.checked = saved.preferences === 'granted'; }
		updateSwitchLabels();
	}

	function closeModal() {
		modal.setAttribute( 'hidden', '' );
		overlay.classList.remove( 'wpo-cc-modal-open' );
	}

	/* Update "Activadas" / "Desactivadas" labels reactively */
	function updateSwitchLabels() {
		[ chkAnalytics, chkMarketing, chkPreferences ].forEach( function ( chk ) {
			if ( ! chk ) { return; }
			var label = chk.closest( '.wpo-cc-switch' );
			if ( ! label ) { return; }
			var span = label.querySelector( '.wpo-cc-switch__label--off' );
			if ( ! span ) { return; }
			span.textContent = chk.checked ? 'Activadas' : 'Desactivadas';
		} );
	}

	/* ------------------------------------------------------------------ */
	/*  Button event handlers                                              */
	/* ------------------------------------------------------------------ */
	if ( btnAccept ) {
		btnAccept.addEventListener( 'click', function () {
			saveConsent( { analytics: 'granted', marketing: 'granted', preferences: 'granted' } );
		} );
	}

	if ( btnReject ) {
		btnReject.addEventListener( 'click', function () {
			saveConsent( { analytics: 'denied', marketing: 'denied', preferences: 'denied' } );
		} );
	}

	if ( btnCustomize ) {
		btnCustomize.addEventListener( 'click', openModal );
	}

	if ( modalClose ) {
		modalClose.addEventListener( 'click', closeModal );
	}

	if ( modalReject ) {
		modalReject.addEventListener( 'click', function () {
			saveConsent( { analytics: 'denied', marketing: 'denied', preferences: 'denied' } );
		} );
	}

	if ( modalAccept ) {
		modalAccept.addEventListener( 'click', function () {
			saveConsent( { analytics: 'granted', marketing: 'granted', preferences: 'granted' } );
		} );
	}

	if ( modalSave ) {
		modalSave.addEventListener( 'click', function () {
			saveConsent( {
				analytics:   chkAnalytics   && chkAnalytics.checked   ? 'granted' : 'denied',
				marketing:   chkMarketing   && chkMarketing.checked   ? 'granted' : 'denied',
				preferences: chkPreferences && chkPreferences.checked ? 'granted' : 'denied'
			} );
		} );
	}

	// Toggle labels update on change.
	[ chkAnalytics, chkMarketing, chkPreferences ].forEach( function ( chk ) {
		if ( chk ) {
			chk.addEventListener( 'change', updateSwitchLabels );
		}
	} );

	// Close modal on Escape key.
	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && ! modal.hasAttribute( 'hidden' ) ) {
			closeModal();
		}
	} );

	// Close modal on overlay backdrop click.
	if ( overlay ) {
		overlay.addEventListener( 'click', function ( e ) {
			if ( e.target === overlay && ! modal.hasAttribute( 'hidden' ) ) {
				closeModal();
			}
		} );
	}

	/* ------------------------------------------------------------------ */
	/*  Initialisation                                                     */
	/* ------------------------------------------------------------------ */
	function init() {
		var raw = readCookie( cfg.cookieName || 'wpo_cookie_consent' );

		if ( raw ) {
			// Consent already saved – fire update so GTM gets signals immediately.
			try {
				var saved = JSON.parse( raw );
				updateConsentMode( saved );
			} catch ( e ) { /* invalid cookie – ignore */ }
			// Don't show banner.
			return;
		}

		// No consent yet – show banner.
		showBanner();
	}

	// Run after DOM is ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	/* ------------------------------------------------------------------ */
	/*  Public API (optional re-open from footer link)                    */
	/* ------------------------------------------------------------------ */
	window.wpoCCOpenPreferences = function () {
		if ( overlay.hasAttribute( 'hidden' ) ) {
			overlay.removeAttribute( 'hidden' );
		}
		openModal();
	};

} )();
