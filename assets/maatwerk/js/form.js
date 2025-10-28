document.addEventListener('DOMContentLoaded', () => {
    const wrapper = document.querySelector('.maatwerk-bestelformulier-wrapper');
    if (!wrapper) {
        return;
    }

    const settings = window.HagemanMaatwerkData || {};
    const ajaxUrl = settings.ajaxUrl || '';
    const searchNonce = settings.searchNonce || '';
    const deleteNonce = settings.deleteNonce || '';
    const strings = settings.i18n || {};
    const confirmDelete = strings.confirmDelete || 'Weet je zeker dat je deze bestelling wilt verwijderen?';
    const noResultsText = strings.noResults || 'Geen resultaten gevonden';

    if (wrapper.dataset.autoClose === 'true') {
        setTimeout(() => {
            if (window.opener) {
                window.opener.location.reload();
            }
            window.close();
        }, 100);
    }

    // Optie geldig tot toggle
    const statusSelect = document.getElementById('order_status');
    const optieField = document.getElementById('optie_geldig_tot_field');
    const optieInput = document.getElementById('optie_geldig_tot');
    const optieStar = document.getElementById('optie_req_star');

    const toggleOptieField = () => {
        if (!statusSelect || !optieField || !optieInput) {
            return;
        }

        if (statusSelect.value === 'in-optie') {
            optieField.classList.add('is-visible');
            optieInput.setAttribute('required', 'required');
            if (optieStar) {
                optieStar.classList.remove('is-hidden');
            }
        } else if (statusSelect.value === 'datum-in-optie') {
            optieField.classList.add('is-visible');
            optieInput.removeAttribute('required');
            if (optieStar) {
                optieStar.classList.add('is-hidden');
            }
        } else {
            optieField.classList.remove('is-visible');
            optieInput.removeAttribute('required');
            optieInput.value = '';
            if (optieStar) {
                optieStar.classList.add('is-hidden');
            }
        }
    };

    if (statusSelect) {
        statusSelect.addEventListener('change', toggleOptieField);
        toggleOptieField();
    }

    // User search
    const userSearchInput = document.getElementById('user_search');
    const userSearchResults = document.getElementById('user_search_results');
    const hiddenUserId = document.getElementById('user_id');
    const forenameInput = document.getElementById('maatwerk_voornaam');
    const surnameInput = document.getElementById('maatwerk_achternaam');
    const emailInput = document.getElementById('maatwerk_email');
    const phoneInput = document.getElementById('maatwerk_telefoonnummer');
    const companyInput = document.getElementById('bedrijfsnaam');
    const addressInput = document.getElementById('straat_huisnummer');
    const postcodeInput = document.getElementById('postcode');
    const cityInput = document.getElementById('plaats');

    if (userSearchInput && userSearchResults && ajaxUrl && searchNonce) {
        let searchTimeout;

        const hideResults = () => {
            userSearchResults.classList.add('is-hidden');
            userSearchResults.innerHTML = '';
        };

        userSearchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            const term = userSearchInput.value.trim();
            if (term.length < 2) {
                hideResults();
                return;
            }

            searchTimeout = window.setTimeout(() => {
                const data = new FormData();
                data.append('action', 'maatwerk_search_users');
                data.append('search_term', term);
                data.append('nonce', searchNonce);

                fetch(ajaxUrl, {
                    method: 'POST',
                    body: data,
                })
                    .then((response) => response.json())
                    .then((payload) => {
                        userSearchResults.innerHTML = '';
                        if (payload.success && Array.isArray(payload.data) && payload.data.length) {
                            payload.data.forEach((user) => {
                                const li = document.createElement('li');
                                const labelParts = [user.display_name];
                                if (user.user_email) {
                                    labelParts.push(`(${user.user_email})`);
                                }
                                li.textContent = labelParts.join(' ');
                                li.dataset.userId = user.ID;
                                li.dataset.firstName = user.first_name || '';
                                li.dataset.lastName = user.last_name || '';
                                li.dataset.email = user.user_email || '';
                                li.dataset.phone = user.billing_phone || '';
                                li.dataset.company = user.company || user.billing_company || '';
                                li.dataset.address1 = user.billing_address_1 || '';
                                li.dataset.postcode = user.billing_postcode || '';
                                li.dataset.city = user.billing_city || '';
                                li.addEventListener('click', () => {
                                    if (forenameInput) {
                                        forenameInput.value = li.dataset.firstName || '';
                                    }
                                    if (surnameInput) {
                                        surnameInput.value = li.dataset.lastName || '';
                                    }
                                    if (emailInput) {
                                        emailInput.value = li.dataset.email || '';
                                    }
                                    if (phoneInput) {
                                        phoneInput.value = li.dataset.phone || '';
                                    }
                                    if (companyInput) {
                                        companyInput.value = li.dataset.company || '';
                                    }
                                    if (addressInput) {
                                        addressInput.value = li.dataset.address1 || '';
                                    }
                                    if (postcodeInput) {
                                        postcodeInput.value = li.dataset.postcode || '';
                                    }
                                    if (cityInput) {
                                        cityInput.value = li.dataset.city || '';
                                    }
                                    if (hiddenUserId) {
                                        hiddenUserId.value = li.dataset.userId || '';
                                    }
                                    userSearchInput.value = li.textContent;
                                    hideResults();
                                });
                                userSearchResults.appendChild(li);
                            });
                            userSearchResults.classList.remove('is-hidden');
                        } else {
                            const li = document.createElement('li');
                            li.textContent = noResultsText;
                            li.classList.add('is-empty');
                            userSearchResults.appendChild(li);
                            userSearchResults.classList.remove('is-hidden');
                        }
                    })
                    .catch(() => {
                        hideResults();
                    });
            }, 300);
        });

        document.addEventListener('click', (event) => {
            if (
                !userSearchResults.contains(event.target) &&
                !userSearchInput.contains(event.target)
            ) {
                hideResults();
            }
        });
    }

    // PDF uploads
    const pdfContainer = document.getElementById('pdf-uploads-container');
    const addPdfButton = document.getElementById('add-pdf-button');

    const updateRemoveButtons = () => {
        if (!pdfContainer) {
            return;
        }
        const groups = pdfContainer.querySelectorAll('.pdf-upload-group');
        groups.forEach((group) => {
            const removeButton = group.querySelector('.remove-button');
            if (!removeButton) {
                return;
            }
            if (groups.length > 1) {
                removeButton.classList.remove('is-hidden');
            } else {
                removeButton.classList.add('is-hidden');
            }
        });
    };

    if (pdfContainer && addPdfButton) {
        let pdfCount = pdfContainer.querySelectorAll('.pdf-upload-group').length;

        addPdfButton.addEventListener('click', () => {
            const wrapperDiv = document.createElement('div');
            wrapperDiv.className = 'pdf-upload-group new-pdf-group';
            wrapperDiv.id = `pdf-upload-group-${pdfCount}`;
            wrapperDiv.innerHTML = `
                <div class="form-field">
                    <label for="pdf_upload_${pdfCount}">Upload PDF</label>
                    <input type="file" id="pdf_upload_${pdfCount}" name="pdf_upload[]" accept=".pdf">
                </div>
                <div class="visibility-field">
                    <label for="pdf_visibility_${pdfCount}">Zichtbaarheid</label>
                    <select name="pdf_visibility[]" id="pdf_visibility_${pdfCount}">
                        <option value="public">Alle Werknemers</option>
                        <option value="private">Interne medewerkers</option>
                    </select>
                </div>
                <button type="button" class="pdf-action-button remove-button" data-id="${pdfCount}">
                    <span class="dashicons dashicons-no"></span>
                </button>
            `;
            pdfContainer.insertBefore(wrapperDiv, addPdfButton);
            pdfCount += 1;
            updateRemoveButtons();
        });

        pdfContainer.addEventListener('click', (event) => {
            const button = event.target.closest('.remove-button');
            if (!button) {
                return;
            }
            const group = button.closest('.pdf-upload-group');
            if (group) {
                group.remove();
                updateRemoveButtons();
            }
        });

        updateRemoveButtons();
    }

    // Employee details
    const employeeContainer = document.getElementById('employee-details-container');
    const employeeCountInput = document.getElementById('aantal_medewerkers');
    let existingEmployees = [];
    try {
        existingEmployees = wrapper.dataset.existingEmployees
            ? JSON.parse(wrapper.dataset.existingEmployees)
            : [];
    } catch (error) {
        existingEmployees = [];
    }

    const renderEmployeeBlocks = (count) => {
        if (!employeeContainer) {
            return;
        }
        employeeContainer.innerHTML = '';
        for (let i = 0; i < count; i += 1) {
            const data = existingEmployees[i] || {};
            const block = document.createElement('div');
            block.className = 'employee-group';
            block.dataset.index = String(i);
            block.innerHTML = `
                <div class="field">
                    <label for="employee_name_${i}">Naam medewerker</label>
                    <input type="text" id="employee_name_${i}" name="employee_name[]" placeholder="Naam" value="${data.name ? data.name.replace(/"/g, '&quot;') : ''}">
                </div>
                <div class="field">
                    <label for="employee_start_${i}">Start tijd medewerker</label>
                    <input type="time" id="employee_start_${i}" name="employee_start[]" value="${data.start || ''}">
                </div>
                <div class="field">
                    <label for="employee_end_${i}">Eind tijd medewerker</label>
                    <input type="time" id="employee_end_${i}" name="employee_end[]" value="${data.end || ''}">
                </div>
            `;
            employeeContainer.appendChild(block);
        }
    };

    const syncEmployees = () => {
        if (!employeeCountInput) {
            return;
        }
        const value = parseInt(employeeCountInput.value, 10);
        const total = Number.isNaN(value) || value < 0 ? 0 : value;
        renderEmployeeBlocks(total);
    };

    if (employeeCountInput) {
        employeeCountInput.addEventListener('input', syncEmployees);
        syncEmployees();
    }

    // Delete order
    const deleteButton = document.getElementById('delete-order-button');
    const editId = wrapper.dataset.editId || '';

    if (deleteButton && ajaxUrl && deleteNonce && editId) {
        deleteButton.addEventListener('click', () => {
            if (!window.confirm(confirmDelete)) {
                return;
            }

            const body = new URLSearchParams();
            body.append('action', 'delete_maatwerk_order');
            body.append('order_id', editId);
            body.append('nonce', deleteNonce);

            fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: body.toString(),
            })
                .then((response) => response.json())
                .then((payload) => {
                    if (payload.success) {
                        if (window.opener) {
                            window.opener.location.reload();
                        }
                        window.close();
                    } else {
                        window.alert(payload.data || 'Verwijderen mislukt.');
                    }
                })
                .catch(() => {
                    window.alert('Er is een fout opgetreden tijdens het verwijderen.');
                });
        });
    }
});
