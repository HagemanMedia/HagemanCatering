function isMobileView() {
    return window.matchMedia('(max-width: 768px)').matches;
}

function formatDateYYYYMMDD(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + d;
}

function mobileDefaultToToday() {
    if (!isMobileView()) return;

    const params = new URLSearchParams(window.location.search);
    if (params.get('filter_day')) {
        return;
    }

    const today = new Date();
    params.set('filter_day', formatDateYYYYMMDD(today));

    const monday = new Date(today);
    const dayOffset = (monday.getDay() + 6) % 7;
    monday.setDate(monday.getDate() - dayOffset);
    params.set('filter_week', formatDateYYYYMMDD(monday));

    const newUrl = new URL(window.location.href);
    newUrl.search = params.toString();
    window.location.replace(newUrl.toString());
}

function preventManualDatePickerInput(datePicker) {
    if (!datePicker) {
        return;
    }

    datePicker.addEventListener('keydown', event => {
        event.preventDefault();
    });
}

function initWeekNavigationButtons() {
    mobileDefaultToToday();

    const todayBtn = document.querySelector('.wc-today-button');
    if (todayBtn) {
        todayBtn.addEventListener('click', event => {
            event.preventDefault();

            const url = new URL(window.location.href);
            const today = new Date();

            if (isMobileView()) {
                url.searchParams.set('filter_day', formatDateYYYYMMDD(today));

                const monday = new Date(today);
                const dayOffset = (monday.getDay() + 6) % 7;
                monday.setDate(monday.getDate() - dayOffset);
                url.searchParams.set('filter_week', formatDateYYYYMMDD(monday));
            } else {
                const monday = new Date(today);
                const dayOffset = (monday.getDay() + 6) % 7;
                monday.setDate(monday.getDate() - dayOffset);
                url.searchParams.set('filter_week', formatDateYYYYMMDD(monday));
                url.searchParams.delete('filter_day');
            }

            window.location.href = url.toString();
        });
    }

    document.querySelectorAll('.wc-arrow-button').forEach(button => {
        button.addEventListener('click', event => {
            event.preventDefault();

            const params = new URLSearchParams(window.location.search);

            if (isMobileView()) {
                const filterDay = params.get('filter_day');
                let currentDate = filterDay ? new Date(filterDay) : new Date();
                const direction = button.dataset.direction;

                if (direction === '-1') {
                    currentDate.setDate(currentDate.getDate() - 1);
                } else if (direction === '1') {
                    currentDate.setDate(currentDate.getDate() + 1);
                }

                params.set('filter_day', formatDateYYYYMMDD(currentDate));

                const monday = new Date(currentDate);
                const dayOffset = (monday.getDay() + 6) % 7;
                monday.setDate(monday.getDate() - dayOffset);
                params.set('filter_week', formatDateYYYYMMDD(monday));
            } else {
                const filterWeek = params.get('filter_week');
                let reference = filterWeek ? new Date(filterWeek) : new Date();
                const dayOffset = (reference.getDay() + 6) % 7;
                reference.setDate(reference.getDate() - dayOffset);

                const direction = button.dataset.direction;
                if (direction === '-1') {
                    reference.setDate(reference.getDate() - 7);
                } else if (direction === '1') {
                    reference.setDate(reference.getDate() + 7);
                }

                params.set('filter_week', formatDateYYYYMMDD(reference));
                params.delete('filter_day');
            }

            const newUrl = new URL(window.location.href);
            newUrl.search = params.toString();
            window.location.href = newUrl.toString();
        });
    });

    const datePicker = document.getElementById('wc-week-date-picker');
    preventManualDatePickerInput(datePicker);

    if (datePicker) {
        datePicker.addEventListener('change', function () {
            const selected = new Date(this.value);
            if (isNaN(selected)) {
                return;
            }

            const params = new URLSearchParams(window.location.search);

            if (isMobileView()) {
                params.set('filter_day', formatDateYYYYMMDD(selected));

                const monday = new Date(selected);
                const dayOffset = (monday.getDay() + 6) % 7;
                monday.setDate(monday.getDate() - dayOffset);
                params.set('filter_week', formatDateYYYYMMDD(monday));
            } else {
                const dayOffset = (selected.getDay() + 6) % 7;
                selected.setDate(selected.getDate() - dayOffset);
                params.set('filter_week', formatDateYYYYMMDD(selected));
                params.delete('filter_day');
            }

            const newUrl = new URL(window.location.href);
            newUrl.search = params.toString();
            window.location.href = newUrl.toString();
        });
    }
}

function initAgendaNavigation() {
    const navButtons = document.querySelectorAll('.wc-nav-elements-right .wc-nav-button:not(.wc-date-filter-button):not(.wc-today-button):not(.wc-arrow-button)');
    const dateFilter = document.getElementById('wc-date-filter');
    const todayButton = document.querySelector('.wc-today-button');

    function handleNavClick(event) {
        event.preventDefault();
        const startDate = this.dataset.weekStart;
        if (!startDate) {
            return;
        }

        const newUrl = new URL(window.location.href);
        newUrl.searchParams.set('filter_week', startDate);
        newUrl.searchParams.delete('filter_day');
        window.location.href = newUrl.toString();
    }

    navButtons.forEach(button => {
        button.removeEventListener('click', handleNavClick);
        button.addEventListener('click', handleNavClick);
    });

    if (todayButton) {
        const handleTodayClick = event => {
            event.preventDefault();
            const todayDate = todayButton.dataset.weekStart;
            const newUrl = new URL(window.location.href);
            newUrl.searchParams.set('filter_week', todayDate);
            newUrl.searchParams.delete('filter_day');
            window.location.href = newUrl.toString();
        };

        todayButton.removeEventListener('click', handleTodayClick);
        todayButton.addEventListener('click', handleTodayClick);
    }

    if (dateFilter) {
        const handleDateFilter = function () {
            const date = new Date(this.value + 'T00:00:00');
            const dayOffset = (date.getDay() + 6) % 7;
            date.setDate(date.getDate() - dayOffset);
            const monday = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;

            const newUrl = new URL(window.location.href);
            newUrl.searchParams.set('filter_week', monday);
            newUrl.searchParams.delete('filter_day');
            window.location.href = newUrl.toString();
        };

        dateFilter.removeEventListener('change', handleDateFilter);
        dateFilter.addEventListener('change', handleDateFilter);
    }
}

function initNewMaatwerkButton() {
    const button = document.querySelector('.wc-new-maatwerk-button');
    if (!button) {
        return;
    }

    const openMaatwerk = event => {
        event.preventDefault();
        window.open(
            'https://banquetingportaal.nl/alg/maatwerk',
            '_blank',
            `width=800,height=800,top=${(screen.height / 2) - (800 / 2)},left=${(screen.width / 2) - (800 / 2)}`
        );
    };

    button.removeEventListener('click', openMaatwerk);
    button.addEventListener('click', openMaatwerk);
}

function initAddCustomerButton() {
    const button = document.querySelector('.wc-add-customer-button');
    if (!button) {
        return;
    }

    const openAddCustomer = event => {
        event.preventDefault();
        window.open(
            'https://banquetingportaal.nl/alg/add-klant/',
            '_blank',
            `width=800,height=800,top=${(screen.height / 2) - (800 / 2)},left=${(screen.width / 2) - (800 / 2)}`
        );
    };

    button.removeEventListener('click', openAddCustomer);
    button.addEventListener('click', openAddCustomer);
}

function initTurflijstButton() {
    const button = document.querySelector('.wc-turflijst-button');
    if (!button) {
        return;
    }

    const openTurflijst = event => {
        event.preventDefault();
        window.open(
            'https://banquetingportaal.nl/alg/turf/',
            '_blank',
            `width=800,height=800,top=${(screen.height / 2) - (800 / 2)},left=${(screen.width / 2) - (800 / 2)}`
        );
    };

    button.removeEventListener('click', openTurflijst);
    button.addEventListener('click', openTurflijst);
}

function initRefreshButton() {
    const button = document.querySelector('.wc-refresh-button');
    if (!button) {
        return;
    }

    button.removeEventListener('click', () => {});
    button.addEventListener('click', event => {
        event.preventDefault();
        window.location.reload();
    });
}
