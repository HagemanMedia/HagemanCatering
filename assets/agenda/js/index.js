document.addEventListener('DOMContentLoaded', function () {
    if (typeof initAgendaNavigation === 'function') {
        initAgendaNavigation();
    }

    if (typeof initWeekNavigationButtons === 'function') {
        initWeekNavigationButtons();
    }

    if (typeof initModalFunctionality === 'function') {
        initModalFunctionality();
    }

    if (typeof initNewMaatwerkButton === 'function') {
        initNewMaatwerkButton();
    }

    if (typeof initAddCustomerButton === 'function') {
        initAddCustomerButton();
    }

    if (typeof initTurflijstButton === 'function') {
        initTurflijstButton();
    }

    if (typeof initRefreshButton === 'function') {
        initRefreshButton();
    }

    if (typeof initFilters === 'function') {
        initFilters();
    }

    if (typeof initDailySummaryButtons === 'function') {
        initDailySummaryButtons();
    }

    if (typeof initOrderTypeFilters === 'function') {
        initOrderTypeFilters();
    }
});
