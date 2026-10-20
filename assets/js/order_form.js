(function () {
    'use strict';

    var form = document.querySelector('[data-order-form]');
    if (!form) {
        return;
    }

    var lines = document.getElementById('order-lines');
    var lineTemplate = document.getElementById('order-line-template');
    var addButton = document.getElementById('add-order-line');
    var totalDisplay = document.getElementById('grand-total');
    var submitted = false;

    function formatMoney(amount) {
        return '₱' + amount.toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function updateLine(line, index) {
        var serviceSelect = line.querySelector('.service-select');
        var quantityInput = line.querySelector('.quantity-input');
        var serviceLabel = line.querySelector('[data-service-label]');
        var quantityLabel = line.querySelector('.quantity-label');
        var lineNumber = line.querySelector('[data-line-number]');
        var selectedService = serviceSelect.options[serviceSelect.selectedIndex];
        var pricingType = selectedService ? selectedService.dataset.pricingType : '';
        var price = selectedService ? Number(selectedService.dataset.price) : 0;
        var quantity = Number(quantityInput.value);
        var subtotal = Number.isFinite(price) && Number.isFinite(quantity) && quantity > 0
            ? Math.round(price * quantity * 100) / 100
            : 0;

        serviceSelect.id = 'service-' + index;
        if (serviceLabel) {
            serviceLabel.htmlFor = serviceSelect.id;
        }
        quantityInput.id = 'quantity-' + index;
        quantityLabel.htmlFor = quantityInput.id;
        lineNumber.textContent = String(index + 1);

        if (pricingType === 'per_kg') {
            quantityLabel.textContent = 'Weight in kg';
            quantityInput.step = '0.01';
            quantityInput.min = '0.01';
            quantityHelp(line, 'Enter weight in kilograms, up to two decimal places.');
        } else if (pricingType === 'per_piece') {
            quantityLabel.textContent = 'Number of pieces';
            quantityInput.step = '1';
            quantityInput.min = '1';
            quantityHelp(line, 'Enter a whole number of pieces.');
        } else {
            quantityLabel.textContent = 'Weight in kg or piece count';
            quantityInput.step = '0.01';
            quantityInput.min = '0.01';
            quantityHelp(line, 'Choose a service, then enter its weight or piece count.');
        }

        line.querySelector('.line-subtotal').textContent = formatMoney(subtotal);
        return subtotal;
    }

    function quantityHelp(line, message) {
        var help = line.querySelector('.quantity-help');
        if (help) {
            help.textContent = message;
        }
    }

    function updateTotals() {
        var total = 0;
        var lineCards = lines.querySelectorAll('[data-order-line]');

        lineCards.forEach(function (line, index) {
            total += updateLine(line, index);
            var removeButton = line.querySelector('.remove-order-line');
            removeButton.disabled = lineCards.length === 1;
            removeButton.setAttribute('aria-label', 'Remove service line ' + (index + 1));
        });

        totalDisplay.textContent = formatMoney(Math.round(total * 100) / 100);
    }

    function addLine() {
        var newLine = lineTemplate.content.firstElementChild.cloneNode(true);
        lines.appendChild(newLine);
        updateTotals();
        newLine.querySelector('.service-select').focus();
    }

    addButton.addEventListener('click', addLine);

    lines.addEventListener('change', function (event) {
        if (event.target.matches('.service-select, .quantity-input')) {
            updateTotals();
        }
    });

    lines.addEventListener('input', function (event) {
        if (event.target.matches('.quantity-input')) {
            updateTotals();
        }
    });

    lines.addEventListener('click', function (event) {
        var removeButton = event.target.closest('.remove-order-line');
        if (removeButton && lines.querySelectorAll('[data-order-line]').length > 1) {
            removeButton.closest('[data-order-line]').remove();
            updateTotals();
        }
    });

    form.addEventListener('submit', function (event) {
        if (submitted) {
            event.preventDefault();
            return;
        }

        submitted = true;
        form.querySelectorAll('button[type="submit"]').forEach(function (button) {
            button.disabled = true;
        });
    });

    updateTotals();
}());
