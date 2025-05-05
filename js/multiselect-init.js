// (function ($, Drupal) {
//     Drupal.behaviors.enhanceMultiSelect = {
//       attach: function (context, settings) {
//         $(once('enhance-select', '.enhanced-multiselect', context)).each(function () {
//           new Choices(this, {
//             removeItemButton: true,
//             placeholder: true,
//             placeholderValue: 'Select colors',
//           });
//         });
//       }
//     };
//   })(jQuery, Drupal);
  

(function ($, Drupal) {
    Drupal.behaviors.enhanceMultiSelect = {
      attach: function (context, settings) {
        $(once('enhance-select', '.enhanced-multiselect', context)).each(function () {
          const selectEl = this;
  
          const choicesInstance = new Choices(selectEl, {
            removeItemButton: true,
            placeholder: true,
            placeholderValue: 'Select colors',
          });
  
          setTimeout(function () {
            const $container = $(selectEl).closest('.choices__inner').parent();
            const $input = $container.find('.choices__input.choices__input--cloned');
            const $list = $container.find('.choices__list--multiple');
  
            function updatePlaceholder() {
              const hasChips = $list.children('.choices__item--selectable').length > 0;
              if (hasChips) {
                $input.attr('placeholder', '');
                $input.css('font-size', '0px');
                $input.removeClass('placeholder-visible');
              } else {
                $input.attr('placeholder', 'Select colors');
                $input[0].style.removeProperty('font-size');
                $input[0].style.removeProperty('width');
                $input[0].style.removeProperty('min-width');
                $input.addClass('placeholder-visible');
              }
            }
  
            // Run once at start in case form is prefilled
            updatePlaceholder();
  
            // Attach click and change listeners on the container
            $container.on('click', updatePlaceholder);
            $(selectEl).on('change', updatePlaceholder);
  
          }, 0);
        });
      }
    };
  })(jQuery, Drupal);
  