(function($) {	
	"use strict";
	
	$.fn.popoutWindow = function(t, n, r) {
		return r = r || {}, r.beforeFilter || (r.beforeFilter = function() {
			return !0;
		}), r.closeCallback || (r.closeCallback = function() {
			window.location.reload();
		}), r.successCallback || (r.successCallback = function() {
			window.location.reload();
		}), r.errorCallback || (r.errorCallback = function() {}), this.each(function() {
			$(this).on("click", r.selector, function(i) {
				var s = $(this);
				i.preventDefault();
				if (!r.beforeFilter() || s.data("popout_debounce")) return;
				s.data("popout_interval") && clearInterval(s.data("popout_interval"));
				var o;
				$.isFunction(t) ? o = t() : o = t, window[n] = window.open(o, n, r.popoutOpts), s.data("popout_debounce", !0), setTimeout(function() {
					s.data("popout_debounce", !1);
				}, 2e3), s.data("popout_interval", setInterval(function() {
					window[n] && window[n].closed && (clearInterval(s.data("popout_interval")), r.closeCallback('closed')), window[n] && window[n].success && (clearInterval(s.data("popout_interval")), r.successCallback()), window[n] && window[n].error && (clearInterval(s.data("popout_interval")), r.errorCallback(window[n].errormsg))
				}, 500));
			});
		});
	};
})(jQuery);

function handleWindow() {
	var body = document.querySelector('body');

	if (window.innerWidth > body.clientWidth + 5) {
		body.classList.add('has-scrollbar');
		body.setAttribute('style', '--scroll-bar: ' + (window.innerWidth - body.clientWidth) + 'px');
	} else {
		body.classList.remove('has-scrollbar');
	}
}

function isFunction(possibleFunction) {
	return typeof(possibleFunction) === typeof(Function);
}

handleWindow();

$(function() {
	"use strict";

	var dualScreenLeft = ((window.screenLeft !== undefined) ? window.screenLeft : screen.left),
		dualScreenTop = ((window.screenTop !== undefined) ? window.screenTop : screen.top),
		left = (($(window).width() / 2) - 300) + dualScreenLeft,
		top = (($(window).height() / 2) - 310) + dualScreenTop,
		tl = null, fbl = null, twl = null, dsl = null;

	if($('#videoModal').length > 0) {
		$('#videoModal').on('shown.bs.modal', function (e) {
			handleWindow();
		});

		$('#videoModal').on('hidden.bs.modal', function (e) {
			handleWindow();
		});
	}

	$('#connectionModal').on('shown.bs.modal', function (e) {
		handleWindow();
	});

	$('#connectionModal').on('hidden.bs.modal', function (e) {
		handleWindow();
	});

	$('[data-toggle="tooltip"]').tooltip();
	$('[data-toggle="popover"]').popover();

	$(window).resize(function() {
		handleWindow();
	});

	if($(window).scrollTop() > 60) {
		$('.page-nav').addClass('page-nav-solid');
		$('.to-top').removeClass('fadeOut').removeClass('d-none').addClass('fadeIn');
	}

	$(window).scroll(function() {
		var n = $(window).scrollTop(),
			i = null;

		if($('.parallax-banner').length > 0) {
			i = n * (2 / 5);
			
			$('.parallax-banner .image').css('top', i+'px');
		}

		if ($(this).scrollTop() <= 60) {
			$('.page-nav').removeClass('page-nav-solid');

			$('.to-top').removeClass('fadeIn').addClass('fadeOut').one('animationend webkitAnimationEnd oAnimationEnd MSAnimationEnd webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend', function() {
				if($('.to-top').hasClass('fadeOut')) {
					$('.to-top').addClass('d-none');
				}
			});
		} else {
			$('.page-nav').addClass('page-nav-solid');

			$('.to-top').removeClass('fadeOut').removeClass('d-none').addClass('fadeIn');
		}
	});

	$('.to-top').click(function(e) {
		e.preventDefault();

		$('html, body').animate({scrollTop : 0},800);
	});

	$('#newsletterForm').submit(function(e) {
		e.preventDefault();

		$.ajax({
			type: "POST",
			url: baseuri + '/api/newsletter/',
			data: $('#newsletterForm').serialize(),
			success: function(data) {
				if(data.result === 'success') {
					swal("Newsletter Signup Completed", "You have been successfully subscribed for our newsletters. Thank you for your interest.", "success");
				} else {
					swal("Newsletter Signup Error", "" + data.message, "error");
				}
			}
		});
	});

	if($('.modal-login').length > 0) {
		$('.modal-navigation > li > a').click(function (e) {
			e.preventDefault();

			var action = $(this).data('action');
			var other = 'login';

			if(action !== undefined) {
				if(action === 'login') {
					other = 'signup';

					$('#'+action+'Form').removeClass('d-none');
					$('#'+other+'Form').addClass('d-none');
				} else {
					other = 'login';

					if(typeof fbq === 'function') {
						fbq('track', 'Lead');
					}

					$('#'+action+'Form').removeClass('d-none');
					$('#'+other+'Form').addClass('d-none');
				}

				$('.modal-login .alert.alert-danger').addClass('d-none').html('');

				$('#'+action+'Action').addClass('active');
				$('#'+other+'Action').removeClass('active');

			}
		});

		$('.modal-login').on('hidden.bs.modal', function () {
			$('#loginForm').removeClass('d-none');
			$('#signupForm').addClass('d-none');

			$('.modal-login #signupForm .is-invalid').each(function() {
				$(this).removeClass('is-invalid');
			});

			$('.modal-login .alert.alert-danger').addClass('d-none').html('');
		});

		$('.modal-login #loginForm').submit(function(e) {
			e.preventDefault();

			$.ajax({
				type: "POST",
				url: baseuri + '/sign-in/',
				data: $('.modal-login #loginForm').serialize(),
				success: function(data) {
					if(data.result === 'success') {
						//gtag('event', 'login', { method : 'Login Modal' });

						if(data.redirect === null || data.redirect === undefined || data.redirect === '') {
							if(window.location.href === baseuri + '/login/') {
								window.location.replace(baseuri + '/dashboard/');
							} else {
								window.location.reload();	
							}
						} else {
							window.location.replace(data.redirect);
						}
					} else {
						$('.modal-login .alert.alert-danger').removeClass('d-none').html('<strong>Error:</strong>');

						if($.isArray(data.message)) {
							$('.modal-login .alert.alert-danger').append('<ul></ul>');

							$.each(data.message, function(index, value) {
								$('.modal-login .alert.alert-danger > ul').append('<li>'+value+'</li>');
							});
						} else {
							$('.modal-login .alert.alert-danger').append(data.message);
						}

						if(data.captcha !== undefined && data.captcha !== null) {
							$('.modal-login #loginForm #loginReCaptchaContainer').removeClass('d-none');

							grecaptcha.render('loginReCaptcha', {
								'sitekey' : data.captcha,
								'theme' : 'dark'
							});
						}
					}
				}
			});
		});

		// Singup Form Submittion
		$('.modal-login #signupForm').submit(function(e) {
			e.preventDefault();

			$.ajax({
				type: "POST",
				url: baseuri + '/sign-up/',
				data: $('.modal-login #signupForm').serialize(),
				success: function(data) {
					if(data.result === 'success') {
						$('.modal-login').modal('hide');

						if(typeof fbq === 'function') {
							fbq('track', 'CompleteRegistration');
						}

						swal("Registration Complete!", "Congratulations you have successfully registered to LVLUP Dojo.", "success");

						if(data.login === true) {
							location.reload();
						}
					} else {
						// Reset reCaptcha
						grecaptcha.reset();

						$('.modal-login .alert.alert-danger').removeClass('d-none').html('<strong>Error:</strong>');

						if($.isArray(data.message)) {
							$('.modal-login .alert.alert-danger').append('<ul></ul>');

							$.each(data.message, function(index, value) {
								$('.modal-login .alert.alert-danger > ul').append('<li>'+value+'</li>');
							});
						} else {
							$('.modal-login .alert.alert-danger').append(data.message);
						}

						if(data.invalid.email !== undefined) {
							$('.modal-login #signupForm input[name="signup-email"]').addClass('is-invalid');

							if($('.modal-login #signupForm #emailGroup > .invalid-tooltip').length > 0) {
								$('.modal-login #signupForm #emailGroup > .invalid-tooltip').html(data.invalid.email);
							} else {
								$('.modal-login #signupForm #emailGroup').append('<div class="invalid-tooltip">' + data.invalid.email + '</div>');
							}
						} else {
							$('.modal-login #signupForm input[name="signup-email"]').removeClass('is-invalid');
						}

						if(data.invalid.username !== undefined) {
							$('.modal-login #signupForm input[name="signup-username"]').addClass('is-invalid');

							if($('.modal-login #signupForm #usernameGroup > .invalid-tooltip').length > 0) {
								$('.modal-login #signupForm #usernameGroup > .invalid-tooltip').html(data.invalid.username);
							} else {
								$('.modal-login #signupForm #usernameGroup').append('<div class="invalid-tooltip">' + data.invalid.username + '</div>');
							}
						} else {
							$('.modal-login #signupForm input[name="signup-username"]').removeClass('is-invalid');
						}

						if(data.invalid.password !== undefined) {
							$('.modal-login #signupForm input[name="signup-password"]').addClass('is-invalid');

							if($('.modal-login #signupForm #passwordGroup > .invalid-tooltip').length > 0) {
								$('.modal-login #signupForm #passwordGroup > .invalid-tooltip').html(data.invalid.password);
							} else {
								$('.modal-login #signupForm #passwordGroup').append('<div class="invalid-tooltip">' + data.invalid.password + '</div>');
							}
						} else {
							$('.modal-login #signupForm input[name="signup-password"]').removeClass('is-invalid');
						}
					}
				}
			});
		});

		tl = function(message) {
			if(window.twitchLogin) {
				window.twitchLogin.close();

				if(message === 'closed') {
					message = 'Connection Canceled';
				}

				if($('.modal-login').hasClass('show')) {
					$('.modal-login .alert.alert-danger').removeClass('d-none').html('<strong>Error:</strong> ' + message);
				} else {
					if($('form[name="signinForm"]').length > 0) {
						if($('form[name="signinForm"] > .row > .col-md-12 > .alert.alert-danger').length > 0) {
							$('form[name="signinForm"] > .row > .col-md-12 > .alert.alert-danger').html('<strong>Error:</strong> ' + message);
						} else {
							$('form[name="signinForm"] > .row').prepend('<div class="col-md-12"><div class="alert alert-danger" role="alert"><strong>Error:</strong> ' + message + '</div></div>');
						}
					}
	
					if($('form[name="signupMainForm"]').length > 0) {
						if($('form[name="signupMainForm"] > .row > .col-md-12 > .alert.alert-danger').length > 0) {
							$('form[name="signupMainForm"] > .row > .col-md-12 > .alert.alert-danger').html('<strong>Error:</strong> ' + message);
						} else {
							$('form[name="signupMainForm"] > .row').prepend('<div class="col-md-12"><div class="alert alert-danger" role="alert"><strong>Error:</strong> ' + message + '</div></div>');
						}
					}
				}
			}
		};

		$('.modal-login .btn-twitch').popoutWindow(baseuri + '/twitch-authentication/', "twitchLogin", {
			popoutOpts: "width=600,height=620,left=" + left + ",top=" + top,
			successCallback: function() {
				if(typeof gtag === 'function') {
					gtag('event', 'login', { method : 'Twitch Login' });
				}

				var redirect = null;

				if (window.twitchLogin) {
					redirect = window.twitchLogin.redirect;

					window.twitchLogin.close();
				}
				
				if(redirect !== null && redirect !== 'null') {
					window.location.href = redirect;
				} else {
					if(window.location.href === baseuri + '/login/') {
						window.location.replace(baseuri + '/dashboard/');
					} else {
						window.location.reload();	
					}
				}
			},
			errorCallback: tl,
			closeCallback: tl
		});

		$('form[name="signinForm"] .btn-twitch').popoutWindow(baseuri + '/twitch-authentication/', "twitchLogin", {
			popoutOpts: "width=600,height=620,left=" + left + ",top=" + top,
			successCallback: function() {
				if(typeof gtag === 'function') {
					gtag('event', 'login', { method : 'Twitch Login' });
				}

				var redirect = null;

				if (window.twitchLogin) {
					redirect = window.twitchLogin.redirect;

					window.twitchLogin.close();
				}
				
				if(redirect !== null && redirect !== 'null') {
					window.location.href = redirect;
				} else {
					if(window.location.href === baseuri + '/login/') {
						window.location.replace(baseuri + '/dashboard/');
					} else {
						window.location.reload();	
					}
				}
			},
			errorCallback: tl,
			closeCallback: tl
		});

		$('form[name="signupMainForm"] .btn-twitch').popoutWindow(baseuri + '/twitch-authentication/', "twitchLogin", {
			popoutOpts: "width=600,height=620,left=" + left + ",top=" + top,
			successCallback: function() {
				if(typeof gtag === 'function') {
					gtag('event', 'login', { method : 'Twitch Login' });
				}

				var redirect = null;

				if (window.twitchLogin) {
					redirect = window.twitchLogin.redirect;

					window.twitchLogin.close();
				}
				
				if(redirect !== null && redirect !== 'null') {
					window.location.href = redirect;
				} else {
					if(window.location.href === baseuri + '/login/') {
						window.location.replace(baseuri + '/dashboard/');
					} else {
						window.location.reload();	
					}
				}
			},
			errorCallback: tl,
			closeCallback: tl
		});

		fbl = function(message) {
			if(window.facebookLogin) {
				window.facebookLogin.close();

				if(message === 'closed') {
					message = 'Connection Canceled';
				}

				if($('.modal-login').hasClass('show')) {
					$('.modal-login .alert.alert-danger').removeClass('d-none').html('<strong>Error:</strong> ' + message);
				} else {
					if($('form[name="signinForm"]').length > 0) {
						if($('form[name="signinForm"] > .row > .col-md-12 > .alert.alert-danger').length > 0) {
							$('form[name="signinForm"] > .row > .col-md-12 > .alert.alert-danger').html('<strong>Error:</strong> ' + message);
						} else {
							$('form[name="signinForm"] > .row').prepend('<div class="col-md-12"><div class="alert alert-danger" role="alert"><strong>Error:</strong> ' + message + '</div></div>');
						}
					}
	
					if($('form[name="signupMainForm"]').length > 0) {
						if($('form[name="signupMainForm"] > .row > .col-md-12 > .alert.alert-danger').length > 0) {
							$('form[name="signupMainForm"] > .row > .col-md-12 > .alert.alert-danger').html('<strong>Error:</strong> ' + message);
						} else {
							$('form[name="signupMainForm"] > .row').prepend('<div class="col-md-12"><div class="alert alert-danger" role="alert"><strong>Error:</strong> ' + message + '</div></div>');
						}
					}
				}
			}
		};

		$('.modal-login .btn-facebook').popoutWindow(baseuri + '/facebook-authentication/', "facebookLogin", {
			popoutOpts: "width=600,height=620,left=" + left + ",top=" + top,
			successCallback: function() {
				if(typeof gtag === 'function') {
					gtag('event', 'login', { method : 'Facebook Login' });
				}

				var redirect = null;

				if (window.facebookLogin) {
					redirect = window.facebookLogin.redirect;

					window.facebookLogin.close();
				}
				
				if(redirect !== null && redirect !== 'null') {
					window.location.href = redirect;
				} else {
					if(window.location.href === baseuri + '/login/') {
						window.location.replace(baseuri + '/dashboard/');
					} else {
						window.location.reload();	
					}
				}
			},
			errorCallback: fbl,
			closeCallback: fbl
		});

		$('form[name="signinForm"] .btn-facebook').popoutWindow(baseuri + '/facebook-authentication/', "facebookLogin", {
			popoutOpts: "width=600,height=620,left=" + left + ",top=" + top,
			successCallback: function() {
				if(typeof gtag === 'function') {
					gtag('event', 'login', { method : 'Facebook Login' });
				}

				var redirect = null;

				if (window.facebookLogin) {
					redirect = window.facebookLogin.redirect;

					window.facebookLogin.close();
				}
				
				if(redirect !== null && redirect !== 'null') {
					window.location.href = redirect;
				} else {
					if(window.location.href === baseuri + '/login/') {
						window.location.replace(baseuri + '/dashboard/');
					} else {
						window.location.reload();	
					}
				}
			},
			errorCallback: fbl,
			closeCallback: fbl
		});

		$('form[name="signupMainForm"] .btn-facebook').popoutWindow(baseuri + '/facebook-authentication/', "facebookLogin", {
			popoutOpts: "width=600,height=620,left=" + left + ",top=" + top,
			successCallback: function() {
				if(typeof gtag === 'function') {
					gtag('event', 'login', { method : 'Facebook Login' });
				}

				var redirect = null;

				if (window.facebookLogin) {
					redirect = window.facebookLogin.redirect;

					window.facebookLogin.close();
				}
				
				if(redirect !== null && redirect !== 'null') {
					window.location.href = redirect;
				} else {
					if(window.location.href === baseuri + '/login/') {
						window.location.replace(baseuri + '/dashboard/');
					} else {
						window.location.reload();	
					}
				}
			},
			errorCallback: fbl,
			closeCallback: fbl
		});

		twl = function(message) {
			if(window.twitterLogin) {
				window.twitterLogin.close();

				if(message === 'closed') {
					message = 'Connection Canceled';
				}

				if($('.modal-login').hasClass('show')) {
					$('.modal-login .alert.alert-danger').removeClass('d-none').html('<strong>Error:</strong> ' + message);
				} else {
					if($('form[name="signinForm"]').length > 0) {
						if($('form[name="signinForm"] > .row > .col-md-12 > .alert.alert-danger').length > 0) {
							$('form[name="signinForm"] > .row > .col-md-12 > .alert.alert-danger').html('<strong>Error:</strong> ' + message);
						} else {
							$('form[name="signinForm"] > .row').prepend('<div class="col-md-12"><div class="alert alert-danger" role="alert"><strong>Error:</strong> ' + message + '</div></div>');
						}
					}
	
					if($('form[name="signupMainForm"]').length > 0) {
						if($('form[name="signupMainForm"] > .row > .col-md-12 > .alert.alert-danger').length > 0) {
							$('form[name="signupMainForm"] > .row > .col-md-12 > .alert.alert-danger').html('<strong>Error:</strong> ' + message);
						} else {
							$('form[name="signupMainForm"] > .row').prepend('<div class="col-md-12"><div class="alert alert-danger" role="alert"><strong>Error:</strong> ' + message + '</div></div>');
						}
					}
				}
			}
		};

		$('.modal-login .btn-twitter').popoutWindow(baseuri + '/twitter-authentication/', "twitterLogin", {
			popoutOpts: "width=600,height=620,left=" + left + ",top=" + top,
			successCallback: function() {
				if(typeof gtag === 'function') {
					gtag('event', 'login', { method : 'Twitter Login' });
				}

				var redirect = null;

				if (window.twitterLogin) {
					redirect = window.twitterLogin.redirect;

					window.twitterLogin.close();
				}
				
				if(redirect !== null && redirect !== 'null') {
					window.location.href = redirect;
				} else {
					if(window.location.href === baseuri + '/login/') {
						window.location.replace(baseuri + '/dashboard/');
					} else {
						window.location.reload();	
					}
				}
			},
			errorCallback: twl,
			closeCallback: twl
		});

		$('form[name="signinForm"] .btn-twitter').popoutWindow(baseuri + '/twitter-authentication/', "twitterLogin", {
			popoutOpts: "width=600,height=620,left=" + left + ",top=" + top,
			successCallback: function() {
				if(typeof gtag === 'function') {
					gtag('event', 'login', { method : 'Twitter Login' });
				}

				var redirect = null;

				if (window.twitterLogin) {
					redirect = window.twitterLogin.redirect;

					window.twitterLogin.close();
				}
				
				if(redirect !== null && redirect !== 'null') {
					window.location.href = redirect;
				} else {
					if(window.location.href === baseuri + '/login/') {
						window.location.replace(baseuri + '/dashboard/');
					} else {
						window.location.reload();	
					}
				}
			},
			errorCallback: twl,
			closeCallback: twl
		});

		$('form[name="signupMainForm"] .btn-twitter').popoutWindow(baseuri + '/twitter-authentication/', "twitterLogin", {
			popoutOpts: "width=600,height=620,left=" + left + ",top=" + top,
			successCallback: function() {
				if(typeof gtag === 'function') {
					gtag('event', 'login', { method : 'Twitter Login' });
				}

				var redirect = null;

				if (window.twitterLogin) {
					redirect = window.twitterLogin.redirect;

					window.twitterLogin.close();
				}
				
				if(redirect !== null && redirect !== 'null') {
					window.location.href = redirect;
				} else {
					if(window.location.href === baseuri + '/login/') {
						window.location.replace(baseuri + '/dashboard/');
					} else {
						window.location.reload();	
					}
				}
			},
			errorCallback: twl,
			closeCallback: twl
		});

		dsl = function(message) {
			if(window.discordLogin) {
				window.discordLogin.close();

				if(message === 'closed') {
					message = 'Connection Canceled';
				}

				if($('.modal-login').hasClass('show')) {
					$('.modal-login .alert.alert-danger').removeClass('d-none').html('<strong>Error:</strong> ' + message);
				} else {
					if($('form[name="signinForm"]').length > 0) {
						if($('form[name="signinForm"] > .row > .col-md-12 > .alert.alert-danger').length > 0) {
							$('form[name="signinForm"] > .row > .col-md-12 > .alert.alert-danger').html('<strong>Error:</strong> ' + message);
						} else {
							$('form[name="signinForm"] > .row').prepend('<div class="col-md-12"><div class="alert alert-danger" role="alert"><strong>Error:</strong> ' + message + '</div></div>');
						}
					}
	
					if($('form[name="signupMainForm"]').length > 0) {
						if($('form[name="signupMainForm"] > .row > .col-md-12 > .alert.alert-danger').length > 0) {
							$('form[name="signupMainForm"] > .row > .col-md-12 > .alert.alert-danger').html('<strong>Error:</strong> ' + message);
						} else {
							$('form[name="signupMainForm"] > .row').prepend('<div class="col-md-12"><div class="alert alert-danger" role="alert"><strong>Error:</strong> ' + message + '</div></div>');
						}
					}
				}
			}
		};

		$('.modal-login .btn-discord').popoutWindow(baseuri + '/discord-authentication/', "discordLogin", {
			popoutOpts: "width=600,height=620,left=" + left + ",top=" + top,
			successCallback: function() {
				if(typeof gtag === 'function') {
					gtag('event', 'login', { method : 'Discord Login' });
				}

				var redirect = null;

				if (window.discordLogin) {
					redirect = window.discordLogin.redirect;

					window.discordLogin.close();
				}
				
				if(redirect !== null && redirect !== 'null') {
					window.location.href = redirect;
				} else {
					if(window.location.href === baseuri + '/login/') {
						window.location.replace(baseuri + '/dashboard/');
					} else {
						window.location.reload();	
					}
				}
			},
			errorCallback: dsl,
			closeCallback: dsl
		});

		$('form[name="signinForm"] .btn-discord').popoutWindow(baseuri + '/discord-authentication/', "discordLogin", {
			popoutOpts: "width=600,height=620,left=" + left + ",top=" + top,
			successCallback: function() {
				if(typeof gtag === 'function') {
					gtag('event', 'login', { method : 'Discord Login' });
				}

				var redirect = null;

				if (window.discordLogin) {
					redirect = window.discordLogin.redirect;

					window.discordLogin.close();
				}
				
				if(redirect !== null && redirect !== 'null') {
					window.location.href = redirect;
				} else {
					if(window.location.href === baseuri + '/login/') {
						window.location.replace(baseuri + '/dashboard/');
					} else {
						window.location.reload();	
					}
				}
			},
			errorCallback: dsl,
			closeCallback: dsl
		});

		$('form[name="signupMainForm"] .btn-discord').popoutWindow(baseuri + '/discord-authentication/', "discordLogin", {
			popoutOpts: "width=600,height=620,left=" + left + ",top=" + top,
			successCallback: function() {
				if(typeof gtag === 'function') {
					gtag('event', 'login', { method : 'Discord Login' });
				}

				var redirect = null;

				if (window.discordLogin) {
					redirect = window.discordLogin.redirect;

					window.discordLogin.close();
				}
				
				if(redirect !== null && redirect !== 'null') {
					window.location.href = redirect;
				} else {
					if(window.location.href === baseuri + '/login/') {
						window.location.replace(baseuri + '/dashboard/');
					} else {
						window.location.reload();	
					}
				}
			},
			errorCallback: dsl,
			closeCallback: dsl
		});
	}

	// Connection Page
	if ($('.card[data-platform^="service-"]').length > 0) {
		var errorConnectionHandle = function (message) {
			if (window.connectionDialog) {
				window.connectionDialog.close();

				if (message === 'closed') {
					message = 'Connection Canceled';
				}

				swal('An error occurred!', message, 'error');
			}
		};
		$('.card[data-platform^="service-"]').each(function () {
			var platform = $(this).data('platform');

			$(this).popoutWindow(baseuri + '/' + platform.replace('service-', '')+'-authentication/', 'connectionDialog', {
				popoutOpts: 'width=600,height=620,left=' + left + ',top=' + top,
				successCallback: function () {
					window.connectionDialog.close();
					window.location.reload();
				},
				errorCallback: errorConnectionHandle,
				closeCallback: errorConnectionHandle
			});
		});
	}

	if ($('.btn[data-disconnect^="service-"]').length > 0) {
		var errorDisconnectHandle = function (message) {
			if (window.connectionDisconnectDialog) {
				window.connectionDisconnectDialog.close();

				if (message === 'closed') {
					message = 'Connection Canceled';
				}

				swal('An error occurred!', message, 'error');
			}
		};
		
		$('.btn[data-disconnect^="service-"]').each(function () {
			var platform = $(this).data('disconnect');

			$(this).popoutWindow(baseuri + '/' + platform.replace('service-', '') + '-authentication/disconnect/', 'connectionDisconnectDialog', {
				popoutOpts: 'width=600,height=620,left=' + left + ',top=' + top,
				successCallback: function () {
					window.connectionDisconnectDialog.close();
					window.location.reload();
				},
				errorCallback: errorDisconnectHandle,
				closeCallback: errorDisconnectHandle
			});
		});
	}

	// Handle Cart
	$('[data-action]').on("click", function(e) {
		e.preventDefault();

		var root    = $(this),
			action  = $(this).attr('data-action'),
			value   = $(this).data('item');

		if(value !== null && value !== undefined) {
			var cartCount = ($('#cartCount').length === 0) ? 0 : parseInt($('#cartCount').html());

			switch(action) {
				default:
					swal("An Error Occured!", "Missing Action Data.", "error");
				break;
				
				case"addItem":
					var formData = new FormData();
						formData.append('itemID', value);

					$.ajax({
						method: "POST",
						processData: false,
						contentType: false,
						url: "/api/cart/add/",
						data: formData,
						dataType: "json",
						cache: false,
						error: function(jqXHR, data, errorThrown) {
							swal("An error occured while adding an item to cart!", data.message, "error");
						},
						success: function(data, textStatus, jqXHR) {
							if(data.result == 'success') {
								if($(root).hasClass('btn-primary')) {
									$(root).html('<i class="fas fa-shopping-cart"></i> Remove From Cart');
									$(root).attr('data-action', 'removeItem');
								}
			
								if(cartCount == 0) {
									$('<span class="badge badge-pill badge-danger ml-1" id="cartCount">1</div>').insertAfter('#cartLink > .fas.fa-shopping-cart');
								} else {
									$('#cartCount').html(cartCount + 1);
								}

								if(typeof fbq === 'function') {
									var courseCost = parseFloat($('#courseCost').html());

									fbq('track', 'AddToCart', {
										value: courseCost,
										currency: 'USD',
										content_name: $('#courseTitle').html(),
										content_type: 'product',
										content_ids: [value],
										contents: [{
											'id': value,
											'item_name': $('#courseTitle').html(),
											'item_price': courseCost
										}]
									});
								}
							} else {
								swal("An error occured while adding an item to cart!", data.message, "error");
							}
						}
					});
				break;

				case"removeItem":
					var formData = new FormData();
						formData.append('itemID', value);
		
					$.ajax({
						method: "POST",
						processData: false,
						contentType: false,
						url: "/api/cart/remove/",
						data: formData,
						dataType: "json",
						cache: false,
						error: function(jqXHR, data, errorThrown) {
							swal("An error occured while removing an item from cart!", data.message, "error");
						},
						success: function(data, textStatus, jqXHR) {
							if(data.result == 'success') {
								cartCount -= 1;
			
								if(cartCount > 0) {
									$('#cartCount').html(cartCount);
								} else {
									$('#cartCount').remove();
								}								

								if($(root).hasClass('btn-primary')) {
									$(root).html('<i class="fas fa-shopping-cart"></i> Add To Cart');
									$(root).attr('data-action', 'addItem');
								}

								if($('#cartTable').length > 0) {
									var cost = parseFloat($('#cartTblItem' + value + ' #cost > #priceContainer').html()),
										total = parseFloat($('#cartTable #cartTotal').html());

									total -= parseFloat(cost);
									total = total.toFixed(2);

									if(cartCount == 0) {
										$('form[name="cartCheckout"]').html('<div class="row"><div class="col-md-12"><div class="alert alert-danger" role="alert">Your cart is empty...</div></div></div>');
									} else {
										$('#cartTblItem' + value).remove();
										$('#cartTable #cartTotal').html(total);
									}
								}
							} else {
								swal("An error occured while removing item from cart!", data.message, "error");
							}
						}
					});
				break;

				case"clearItems":
					if($('#cartTable').length > 0) {
						$.ajax({
							method: "POST",
							processData: false,
							contentType: false,
							url: "/api/cart/clear/",
							dataType: "json",
							cache: false,
							error: function(jqXHR, data, errorThrown) {
								swal("An error occured while removing an item from cart!", data.message, "error");
							},
							success: function(data, textStatus, jqXHR) {
								if(data.result == 'success') {
									$('#cartCount').remove();

									if($('#cartTable').length > 0) {
										$('form[name="cartCheckout"]').html('<div class="row"><div class="col-md-12"><div class="alert alert-danger" role="alert">Your cart is empty...</div></div></div>');
									}
								} else {
									swal("An error occured while removing item from cart!", data.message, "error");
								}
							}
						});
					}
				break;
			}
		}
	});
});