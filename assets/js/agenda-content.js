function initModalFunctionality() {
    const modal = document.getElementById('wc-order-modal');
    const closeButton = document.querySelector('.wc-order-modal-close');
    const modalBody = document.getElementById('wc-order-modal-body');
    const wrapper = document.querySelector('.wc-weekagenda-wrapper');
    const agendaVisibility = wrapper ? wrapper.dataset.agendaVisibility : 'alle_werknemers';

    document.querySelectorAll('.wc-weekagenda-item-google').forEach(item => {
        item.removeEventListener('click', handleOrderItemClick);
        item.addEventListener('click', handleOrderItemClick);
    });

    function handleOrderItemClick() {
        if (!modal || !modalBody) {
            return;
        }

        const orderId = this.dataset.orderId;
        const orderType = this.dataset.orderType;

        modal.style.display = 'block';
        modalBody.innerHTML = '<div style="text-align:center;padding:20px;">Laden bestelgegevens...</div>';

        const formData = new FormData();
        formData.append('action', 'wc_get_order_details_ajax');
        formData.append('order_id', orderId);
        formData.append('order_type', orderType);
        formData.append('agenda_visibility_setting', agendaVisibility);

        fetch(HagemanAgendaData.ajaxUrl, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(result => {
                modalBody.innerHTML = result.success
                    ? result.data
                    : '<div style="text-align:center;padding:20px;color:red;">' + result.data + '</div>';
            })
            .catch(() => {
                modalBody.innerHTML = '<div style="text-align:center;padding:20px;color:red;">Fout bij het laden van bestelgegevens.</div>';
            });
    }

    if (closeButton) {
        closeButton.onclick = () => {
            modal.style.display = 'none';
        };
    }

    window.onclick = event => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    };
}

function initFilters() {
    const dateFilter = document.getElementById('wc-date-filter');
    if (!dateFilter) {
        return;
    }

    const handleDateFilter = function () {
        const date = new Date(this.value + 'T00:00:00');
        const dayOffset = (date.getDay() + 6) % 7;
        date.setDate(date.getDate() - dayOffset);
        const monday = date.getFullYear() + '/' + String(date.getMonth() + 1).padStart(2, '0') + '/' + String(date.getDate()).padStart(2, '0');
        const wrapper = document.querySelector('.wc-weekagenda-wrapper');
        const visibility = wrapper ? wrapper.dataset.agendaVisibility : 'alle_werknemers';
        fetchAgenda(monday, visibility);
    };

    dateFilter.removeEventListener('change', handleDateFilter);
    dateFilter.addEventListener('change', handleDateFilter);
}

function initDailySummaryButtons() {
    const modal = document.getElementById('wc-order-modal');
    const modalBody = document.getElementById('wc-order-modal-body');
    if (!modal || !modalBody) {
        return;
    }

    const handleSummary = function () {
        const day = this.dataset.dayDate;
        modal.style.display = 'block';
        modalBody.innerHTML = '<div style="text-align:center;padding:20px;">Laden dagoverzicht...</div>';

        const formData = new FormData();
        formData.append('action', 'wc_get_daily_product_summary_ajax');
        formData.append('day_date', day);

        fetch(HagemanAgendaData.ajaxUrl, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(result => {
                modalBody.innerHTML = result.success
                    ? result.data
                    : '<div style="text-align:center;color:red;">' + result.data + '</div>';
            })
            .catch(() => {
                modalBody.innerHTML = '<div style="text-align:center;color:red;">Fout bij laden dagoverzicht.</div>';
            });
    };

    document.querySelectorAll('.wc-daily-summary-button').forEach(button => {
        button.removeEventListener('click', handleSummary);
        button.addEventListener('click', handleSummary);
    });
}

function initOrderTypeFilters() {
    const filterBanquetingButton = document.getElementById('wc-filter-wc');
    const hideCancelledButton = document.getElementById('wc-hide-cancelled');

    if (filterBanquetingButton) {
        filterBanquetingButton.dataset.active = filterBanquetingButton.dataset.active || '0';
        filterBanquetingButton.textContent = filterBanquetingButton.dataset.active === '1'
            ? 'Toon alle orders'
            : 'Toon alleen banqueting';

        filterBanquetingButton.addEventListener('click', function () {
            const showOnlyBanqueting = this.dataset.active === '0';
            document.querySelectorAll('.wc-weekagenda-item-google').forEach(item => {
                if (showOnlyBanqueting) {
                    item.style.display = item.dataset.orderType === 'maatwerk' ? '' : 'none';
                } else {
                    item.style.display = '';
                }
            });

            this.dataset.active = showOnlyBanqueting ? '1' : '0';
            this.textContent = this.dataset.active === '1' ? 'Toon alle orders' : 'Toon alleen banqueting';
        });
    }

    if (hideCancelledButton) {
        hideCancelledButton.dataset.active = '1';
        hideCancelledButton.textContent = 'Toon geannuleerd';
        hideCancelledButton.setAttribute('aria-pressed', 'true');

        document.querySelectorAll('.wc-weekagenda-item-google').forEach(item => {
            const type = item.dataset.orderType;
            const status = item.dataset.postStatus;
            const isCancelled = (type === 'woocommerce' && status === 'wc-cancelled')
                || (type === 'maatwerk' && (status === 'geannuleerd' || status === 'afgewezen'));
            if (isCancelled) {
                item.style.display = 'none';
            }
        });

        hideCancelledButton.addEventListener('click', function () {
            const hiding = this.dataset.active === '0';
            document.querySelectorAll('.wc-weekagenda-item-google').forEach(item => {
                const type = item.dataset.orderType;
                const status = item.dataset.postStatus;
                const isCancelled = (type === 'woocommerce' && status === 'wc-cancelled')
                    || (type === 'maatwerk' && (status === 'geannuleerd' || status === 'afgewezen'));
                if (isCancelled) {
                    item.style.display = hiding ? 'none' : '';
                }
            });

            this.dataset.active = hiding ? '1' : '0';
            this.textContent = this.dataset.active === '1' ? 'Toon geannuleerd' : 'Verberg geannuleerd';
            this.setAttribute('aria-pressed', this.dataset.active === '1' ? 'true' : 'false');
        });
    }
}
