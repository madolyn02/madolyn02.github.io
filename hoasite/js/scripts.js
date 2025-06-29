/*!
* Start Bootstrap - Freelancer v7.0.7 (https://startbootstrap.com/theme/freelancer)
* Copyright 2013-2023 Start Bootstrap
* Licensed under MIT (https://github.com/StartBootstrap/startbootstrap-freelancer/blob/master/LICENSE)
*/
//
// Scripts
// 

window.addEventListener('DOMContentLoaded', event => {

    // Navbar shrink function
    var navbarShrink = function () {
        var navbarCollapsible = document.body.querySelector('#mainNav');
        if (!navbarCollapsible) {
            return;
        }
        if (window.scrollY === 0) {
            navbarCollapsible.classList.remove('navbar-shrink')
        } else {
            navbarCollapsible.classList.add('navbar-shrink')
        }
    };

    // Shrink the navbar 
    navbarShrink();

    // Shrink the navbar when page is scrolled
    document.addEventListener('scroll', navbarShrink);

    // Activate Bootstrap scrollspy on the main nav element
    var mainNav = document.body.querySelector('#mainNav');
    if (mainNav) {
        new bootstrap.ScrollSpy(document.body, {
            target: '#mainNav',
            rootMargin: '0px 0px -40%',
        });
    }

    // Collapse responsive navbar when toggler is visible
    var navbarToggler = document.body.querySelector('.navbar-toggler');
    var responsiveNavItems = [].slice.call(
        document.querySelectorAll('#navbarResponsive .nav-link')
    );
    responsiveNavItems.map(function (responsiveNavItem) {
        responsiveNavItem.addEventListener('click', () => {
            if (window.getComputedStyle(navbarToggler).display !== 'none') {
                navbarToggler.click();
            }
        });
    });

    // Mortgage calculator
    var form = document.getElementById('mortgageForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var salary = +document.getElementById('salary').value;
            var percent = +document.getElementById('percent').value / 100;
            var down = +document.getElementById('downPayment').value;
            var rate = (+document.getElementById('rate').value / 100) / 12;
            var term = +document.getElementById('term').value * 12;
            var monthly = (salary * percent) / 12;
            var pv = monthly * (1 - Math.pow(1 + rate, -term)) / rate;
            var max = pv + down;
            document.getElementById('result').textContent = `Maximum Affordable Home Price: $${max.toFixed(2)}`;
        });
    }

});
