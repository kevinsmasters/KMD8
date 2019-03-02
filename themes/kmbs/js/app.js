(function($, Drupal) {
  // To understand behaviors, see https://drupal.org/node/756722#behaviors
  Drupal.behaviors.my_custom_behavior = {
    attach: function(context, settings) {
      // Place your code here.

      var main = function($) {
        "use strict";

        if ($("body").hasClass("path-frontpage")) {
          var winHeight = $(window).height(),
            pt75Height = winHeight * 0.75,
            lastScrollTop = 0,
            st;

          $("#page-header")
            .height(winHeight)
            .addClass("homeHeader");

          var projectTop = $("#block-views-block-projects-block-1").position()
              .top,
            projectBottom =
              $("#block-views-block-projects-block-1").height() + projectTop;
          //home scroll effects

          $(window).on("scroll", function() {
            st = $(this).scrollTop();
            //console.log(st);
            if (st < lastScrollTop) {
              //console.log('up');
            } else {
              if (st > pt75Height) {
                showMoon();
              }

              if (st > projectTop) {
                fadeThumb();
              }

              if (st > projectBottom) {
                fadeForm();
              }
            }
            lastScrollTop = st;
          });

          var showMoon = once(function() {
            $(".container-fluid > .row").addClass("mooned");
          });

          var fadeThumb = once(function() {
            $(".home-slide li").each(function(index) {
              $(this)
                .delay(400 * index)
                .fadeIn(300);
            });
          });

          var fadeForm = once(function() {
            $("#block-webform .form-item").each(function(index) {
              $(this)
                .delay(400 * index)
                .fadeIn(500);
            });
          });

          function once(fn, context) {
            var result;

            return function() {
              if (fn) {
                result = fn.apply(context || this, arguments);
                fn = null;
              }

              return result;
            };
          }
        } //end if home
      };

      $(document).ready(main);
    }
  };
})(jQuery, Drupal, this, this.document);
